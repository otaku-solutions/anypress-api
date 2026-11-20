import type { PgBoss } from 'pg-boss';
import { withTransaction, type Client, type Pool } from './db.ts';
import { enqueue, ensureQueue } from './queue.ts';
import { Status } from './status.ts';

/**
 * Makes a built version its product's production version. Unless `moveStores` is false, every store of the product
 * on another version moves to it (a failed move puts the store back, src/jobs.ts). Answers how many move.
 *
 * With `moveStores: 'tonight'` (template releases and storefronts' copies of them) the stores move in the nightly
 * window instead (moveStoresTonight): a move restarts each store and replaces its code, and owners working on their
 * site in the day saw it go briefly unavailable (waas-api-de8i). Answers 0 then.
 *
 * A store that is busy (Pending) moves too: its jobs run one at a time, so the move waits for the job it is on.
 * Leaving it out left it on the old version for good (waas-api-lca1).
 */
export async function promoteVersion(client: Client, boss: PgBoss, productId: string, versionId: string, moveStores: boolean | 'tonight' = true): Promise<number> {
  await client.query('UPDATE versions SET is_production = false, stores_move_tonight = false, date_changed = now() WHERE product_id = $1 AND is_production', [productId]);
  await client.query('UPDATE versions SET is_production = true, promote_when_done = false, stores_move_tonight = $2, date_changed = now() WHERE id = $1', [
    versionId,
    moveStores === 'tonight',
  ]);
  if (moveStores !== true) return 0;
  return moveStoresTo(client, boss, productId, versionId);
}

async function moveStoresTo(client: Client, boss: PgBoss, productId: string, versionId: string): Promise<number> {
  const { rows } = await client.query<{ id: string; version_id: string }>(
    `UPDATE tenants SET status = $3, date_changed = now()
      WHERE product_id = $1 AND status IN ($3, $4) AND version_id IS NOT NULL AND version_id <> $2
      RETURNING id, version_id`,
    [productId, versionId, Status.Pending, Status.Done],
  );
  for (const row of rows) {
    await enqueue(boss, client, { type: 'tenant.move', tenantId: row.id, targetVersionId: versionId, rollbackVersionId: row.version_id });
  }
  return rows.length;
}

/** The nightly window's run: 11:00 UTC is 3 am Pacific in winter and 4 am in summer, after the nightly backups (src/storebackups.ts). */
const NIGHTLY_MOVES_QUEUE = 'store-moves';
const NIGHTLY_MOVES_SCHEDULE = '0 11 * * *';

/** Moves the stores of every production version waiting for the night. Answers how many moved. */
export async function moveStoresTonight(pool: Pool, boss: PgBoss): Promise<number> {
  return withTransaction(pool, async (client) => {
    const { rows } = await client.query<{ id: string; product_id: string }>(
      'UPDATE versions SET stores_move_tonight = false WHERE stores_move_tonight AND is_production RETURNING id, product_id',
    );
    let moved = 0;
    for (const v of rows) moved += await moveStoresTo(client, boss, v.product_id, v.id);
    return moved;
  });
}

/** Schedules the nightly store moves and works them. */
export async function startNightlyMoves(pool: Pool, boss: PgBoss, log: Pick<Console, 'log' | 'error'> = console): Promise<void> {
  await ensureQueue(boss, NIGHTLY_MOVES_QUEUE, { retryLimit: 2, retryDelay: 300 });
  await boss.schedule(NIGHTLY_MOVES_QUEUE, NIGHTLY_MOVES_SCHEDULE);
  await boss.work(NIGHTLY_MOVES_QUEUE, { pollingIntervalSeconds: 60 }, async () => {
    const moved = await moveStoresTonight(pool, boss);
    if (moved) log.log(`nightly store moves: ${moved} moving to their production version`);
  });
}
