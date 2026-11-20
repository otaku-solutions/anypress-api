-- Up Migration
-- A production version made by a template release (or a storefront's copy of one) moves its stores at night
-- (src/promote.ts), not right away: a move restarts each store and replaces its code, which owners saw in the day.
ALTER TABLE versions ADD COLUMN stores_move_tonight boolean NOT NULL DEFAULT false;

-- Down Migration
ALTER TABLE versions DROP COLUMN stores_move_tonight;
