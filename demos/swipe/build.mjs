// Builds online-store-swipe.json: the AnyPress theme's Aisle store demo (Thread & Co. on FluentCart, pinned to a theme
// commit) with the Swipe package protection concept and six months of orders added.
// Run: node demos/swipe/build.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import { execSync } from 'node:child_process';
const here = new URL('.', import.meta.url);
const THEME = '432e81b0f1c556322bd6270585cf5fdce7db426b';
const base = await (await fetch(`https://raw.githubusercontent.com/otaku-solutions/anypress-theme/${THEME}/demo/aisle/blueprint.json`)).json();
const bp = JSON.parse(JSON.stringify(base).replaceAll('/anypress-theme/main/', `/anypress-theme/${THEME}/`));
bp.meta = { ...(bp.meta || {}), title: 'Thread & Co. with Swipe Package Protection (concept)', description: 'An AnyPress online store (the Aisle kit on FluentCart) with a concept of Swipe AI package protection at checkout. Runs in your browser; nothing is saved or sent.' };
bp.landingPage = '/';
// The kit's hero photo crops the couple at the shoulders; this one shows them whole (Pexels 6328569, Pexels licence).
const HERO = execSync('git log -1 --format=%H -- hero.jpg', { cwd: new URL('.', import.meta.url) }).toString().trim();
for (const st of bp.steps) if (st.step === 'writeFile' && st.path?.endsWith('/demo/aisle/photos/hero.jpg')) st.data.url = `https://raw.githubusercontent.com/otaku-solutions/anypress-api/${HERO}/demos/swipe/hero.jpg`;
// FluentHub (FluentToolkit), on every AnyPress store: the Fluent plugins in one workspace with one menu, as stores have it.
const hubAt = bp.steps.findIndex((st) => st.step === 'installPlugin' && /anypress-client/.test(st.pluginData?.url || ''));
bp.steps.splice(hubAt, 0, { step: 'installPlugin', pluginData: { resource: 'url', url: 'https://raw.githubusercontent.com/otaku-solutions/anypress-api/96ba753d76e907b3b3349766312bb4c7c1384cbf/templates/anypress-store/plugins/fluent-toolkit-2.1.4.zip' }, options: { activate: true, targetFolderName: 'fluent-toolkit' }, progress: { caption: 'Adding FluentHub' } });
const logo = readFileSync(new URL('swipe-wordmark.b64', here), 'utf8').trim();
bp.steps.push(
  { step: 'writeFile', path: '/wordpress/wp-content/mu-plugins/swipe-protection.php', data: readFileSync(new URL('swipe-protection.php', here), 'utf8').replace('__SWIPE_WORDMARK__', logo) },
  { step: 'writeFile', path: '/wordpress/anypress-swipe-sample.php', data: readFileSync(new URL('sample.php', here), 'utf8') },
  { step: 'runPHP', code: "<?php require '/wordpress/anypress-swipe-sample.php';", progress: { caption: 'Taking six months of orders' } },
);
writeFileSync(new URL('online-store-swipe.json', here), JSON.stringify(bp, null, '\t') + '\n');
console.log('steps', bp.steps.length);
