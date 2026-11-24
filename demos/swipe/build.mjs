// Builds online-store-swipe.json: the AnyPress theme's Aisle store demo (Thread & Co. on FluentCart, pinned to a theme
// commit) with the Swipe package protection concept and six months of orders added.
// Run: node demos/swipe/build.mjs
import { readFileSync, writeFileSync } from 'node:fs';
const here = new URL('.', import.meta.url);
const THEME = '432e81b0f1c556322bd6270585cf5fdce7db426b';
const base = await (await fetch(`https://raw.githubusercontent.com/otaku-solutions/anypress-theme/${THEME}/demo/aisle/blueprint.json`)).json();
const bp = JSON.parse(JSON.stringify(base).replaceAll('/anypress-theme/main/', `/anypress-theme/${THEME}/`));
bp.meta = { ...(bp.meta || {}), title: 'Thread & Co. with Swipe Package Protection (concept)', description: 'An AnyPress online store (the Aisle kit on FluentCart) with a concept of Swipe AI package protection at checkout. Runs in your browser; nothing is saved or sent.' };
bp.landingPage = '/';
bp.features = { ...(bp.features || {}), networking: true }; // FluentCart's Stripe test mode talks to api.stripe.com
const logo = readFileSync(new URL('swipe-wordmark.b64', here), 'utf8').trim();
bp.steps.push(
  { step: 'writeFile', path: '/wordpress/wp-content/mu-plugins/swipe-protection.php', data: readFileSync(new URL('swipe-protection.php', here), 'utf8').replace('__SWIPE_WORDMARK__', logo) },
  { step: 'writeFile', path: '/wordpress/anypress-swipe-sample.php', data: readFileSync(new URL('sample.php', here), 'utf8') },
  { step: 'runPHP', code: "<?php require '/wordpress/anypress-swipe-sample.php';", progress: { caption: 'Taking six months of orders' } },
);
writeFileSync(new URL('online-store-swipe.json', here), JSON.stringify(bp, null, '\t') + '\n');
console.log('steps', bp.steps.length);
