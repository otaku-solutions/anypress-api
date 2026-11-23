import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { createJobHandlers } from '../src/jobs.ts';
import { Status } from '../src/status.ts';
import { setup, waitFor, type TestContext, OWNER } from './helpers.ts';

let t: TestContext;
let tenant: { id: string; externalId: string };

const getTenant = async (externalId: string) => ((await (await t.call('GET', `/v1/tenants?externalId=${externalId}`)).json()) as any[])[0];

/** Records a failed job, as the worker does after its last retry. */
const failure = async (jobType: string, tenantId: string | null, versionId: string | null = null, age = '1 hour') =>
  (
    await t.pool.query(
      `INSERT INTO job_failures (job_id, job_type, job_data, tenant_id, version_id, error_message, attempts, failed_at)
       VALUES (gen_random_uuid(), $1, '{}', $2, $3, 'Coolify: HTTP 401', 4, now() - $4::interval) RETURNING id`,
      [jobType, tenantId, versionId, age],
    )
  ).rows[0].id as string;
const statusOf = async (id: string) => (await t.pool.query('SELECT status FROM job_failures WHERE id = $1', [id])).rows[0].status as string;

beforeAll(async () => {
  t = await setup();
  const res = await t.call('POST', '/v1/versions', { body: { name: 'v1', phpVersion: '8.3', wordPressVersion: '6.8', useCustomUpload: true } });
  const versionId = ((await res.json()) as { id: string }).id;
  await waitFor(async () => ((await (await t.call('GET', `/v1/versions?versionId=${versionId}`)).json()) as any[])[0]?.statusCode === Status.Done);
  await t.call('PUT', '/v1/versions/production', { body: { versionId } });
  tenant = (await (await t.call('POST', '/v1/tenants', { body: { ...OWNER, name: 'Failures' } })).json()) as typeof tenant;
  await waitFor(async () => (await getTenant(tenant.externalId))?.statusCode === Status.Done);
});
afterAll(async () => {
  await t.close();
});

describe('failed tasks close themselves once fixed', () => {
  it('a job that works dismisses earlier failures of the same job on the same store, and only those', async () => {
    const backup = await failure('tenant.backup', tenant.id);
    const domains = await failure('tenant.domains', tenant.id);
    await t.call('POST', `/v1/tenants/backups?externalId=${tenant.externalId}`);
    await waitFor(async () => (await statusOf(backup)) === 'dismissed');
    expect(await statusOf(domains)).toBe('failed');
  });

  it('maintenance dismisses failures whose store or version is gone, or that a newer backup made good', async () => {
    const handlers = createJobHandlers(t.pool, t.provisioner, t.deps.secrets, 90);
    // Wait for the whole job, not just its backup: the job settles its own failures after the backup is Done.
    const finished = async () =>
      (await t.pool.query("SELECT 1 FROM pgboss.job WHERE data->>'type' = 'tenant.backup' AND data->>'tenantId' = $1 AND state = 'completed'", [tenant.id])).rowCount ?? 0;
    const before = await finished();
    await t.call('POST', `/v1/tenants/backups?externalId=${tenant.externalId}`);
    await waitFor(async () => (await finished()) > before);
    const goneStore = await failure('tenant.backup', crypto.randomUUID());
    const goneVersion = await failure('version.delete', null, crypto.randomUUID());
    // Older than the backup just taken, which worked.
    const madeGood = await failure('tenant.backup', tenant.id, null, '2 days');
    const open = await failure('tenant.domains', tenant.id);
    await handlers.maintenance();
    expect(await statusOf(goneStore)).toBe('dismissed');
    expect(await statusOf(goneVersion)).toBe('dismissed');
    expect(await statusOf(madeGood)).toBe('dismissed');
    expect(await statusOf(open)).toBe('failed');
  });
});
