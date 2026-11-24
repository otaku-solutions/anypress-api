// Builds online-store-swipe.json: the Online Store site type's Playground blueprint with the Swipe concept added.
// Run: node demos/swipe/build.mjs
import { readFileSync, writeFileSync } from 'node:fs';
const here = new URL('.', import.meta.url);
const base = JSON.parse(readFileSync(new URL('../../templates/anypress-store/playground/online-store.json', here)));
const bp = structuredClone(base);
bp.meta = { ...bp.meta, title: 'AnyPress: Online Store with Swipe Package Protection (concept)', description: 'An AnyPress online store on FluentCart with a concept of Swipe AI package protection at checkout. Runs in your browser; nothing is saved or sent.' };
bp.landingPage = '/wp-admin/admin.php?page=anypress-swipe';
bp.steps.push(
  { step: 'writeFile', path: '/wordpress/wp-content/mu-plugins/swipe-protection.php', data: readFileSync(new URL('swipe-protection.php', here), 'utf8') },
  { step: 'writeFile', path: '/wordpress/anypress-swipe-sample.php', data: readFileSync(new URL('sample.php', here), 'utf8') },
  { step: 'runPHP', code: "<?php require '/wordpress/anypress-swipe-sample.php';", progress: { caption: 'Protecting past orders with Swipe' } },
);
writeFileSync(new URL('online-store-swipe.json', here), JSON.stringify(bp, null, '\t') + '\n');
console.log('steps', bp.steps.length);
