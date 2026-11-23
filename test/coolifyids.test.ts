// Coolify Service uuids kept in Postgres (src/coolifyids.ts) for the Coolify provisioner's lookups.
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { serviceIdStore, versionInfoStore } from '../src/coolifyids.ts';
import { setup, type TestContext } from './helpers.ts';

let t: TestContext;
beforeAll(async () => {
  t = await setup();
});
afterAll(async () => t?.close());

describe('serviceIdStore', () => {
  it('saves, reads, seeds and forgets uuids', async () => {
    const ids = serviceIdStore(t.pool);
    await t.pool.query('DELETE FROM coolify_service_ids');
    expect(await ids.get('3/store-a')).toBeUndefined();
    expect(await ids.isSeeded()).toBe(false);
    await ids.set('3/store-a', null);
    expect(await ids.get('3/store-a')).toBeNull();
    await ids.set('3/store-a', 'uuid-a');
    expect(await ids.get('3/store-a')).toBe('uuid-a');
    await ids.seed([['3/store-a', 'uuid-a2'], ['4/version-b', 'uuid-b']]);
    expect(await ids.isSeeded()).toBe(true);
    expect(await ids.get('3/store-a')).toBe('uuid-a2');
    expect(await ids.get('4/version-b')).toBe('uuid-b');
    await ids.delete('4/version-b');
    expect(await ids.get('4/version-b')).toBeUndefined();
    await ids.seed([]);
    expect(await ids.isSeeded()).toBe(true);
  });
});

describe('versionInfoStore', () => {
  it("reads a version's WordPress and PHP versions from the versions table", async () => {
    const info = versionInfoStore(t.pool);
    expect(await info('00000000-0000-0000-0000-000000000000')).toBeUndefined();
    const { rows } = await t.pool.query<{ id: string }>(
      "INSERT INTO versions (product_id, name, domain, wordpress_version, php_version, status) VALUES ($1, 'v1', 'v1.versions.waas.example', '6.9', '8.3', 1) RETURNING id",
      [t.productId],
    );
    expect(await info(rows[0]!.id)).toEqual({ wordPressVersion: '6.9', phpVersion: '8.3' });
  });
});
