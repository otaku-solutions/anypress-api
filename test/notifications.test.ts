import { describe, expect, it } from 'vitest';
import type { Pool } from '../src/db.ts';
import { notifyEvent } from '../src/notifications.ts';

/** A pool that records the queries notifyEvent makes, answering an insert with a row id. */
function recordingPool() {
  const queries: string[] = [];
  const pool = { query: async (text: string) => { queries.push(text); return { rows: [{ id: 'n1' }], rowCount: 1 }; } } as unknown as Pool;
  return { pool, queries };
}

describe('notifyEvent', () => {
  it('leaves failed backups out of the bell: backups run by themselves and the team is alerted', async () => {
    const { pool, queries } = recordingPool();
    await notifyEvent(pool, 'p1', 'tenant.backup_failed', { tenant: { id: 't1', name: 'Acme' } });
    expect(queries.filter((q) => q.includes('INSERT INTO notifications'))).toHaveLength(0);
  });

  it('still records a failed job', async () => {
    const { pool, queries } = recordingPool();
    await notifyEvent(pool, 'p1', 'tenant.failed', { tenant: { id: 't1', name: 'Acme' } });
    expect(queries.filter((q) => q.includes('INSERT INTO notifications'))).toHaveLength(1);
  });
});
