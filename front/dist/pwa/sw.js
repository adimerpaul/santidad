if(!self.define){let e,a={};const c=(c,s)=>(c=new URL(c+".js",s).href,a[c]||new Promise((a=>{if("document"in self){const e=document.createElement("script");e.src=c,e.onload=a,document.head.appendChild(e)}else e=c,importScripts(c),a()})).then((()=>{let e=a[c];if(!e)throw new Error(`Module ${c} didn’t register its module`);return e})));self.define=(s,i)=>{const f=e||("document"in self?document.currentScript.src:"")||location.href;if(a[f])return;let n={};const o=e=>c(e,f),r={module:{uri:f},exports:n,require:o};a[f]=Promise.all(s.map((e=>r[e]||o(e)))).then((e=>(i(...e),n)))}}define(["./workbox-57ffaad5"],(function(e){"use strict";e.setCacheNameDetails({prefix:"santidad"}),self.skipWaiting(),e.clientsClaim(),e.precacheAndRoute([{url:"assets/axios.a6ef5510.js",revision:"e858ad795f0edf1518f07d1b0b1e1567"},{url:"assets/CarouselPage.5b1c5dd1.js",revision:"14015eb539ee31fbe9a356a18e03a5a6"},{url:"assets/ErrorNotFound.4fe21bb4.js",revision:"a16da930a79553e72bbe79cf180e022c"},{url:"assets/fa-brands-400.2ef6fdde.ttf",revision:"94227daa6303fd42918ce3af431ef7e8"},{url:"assets/fa-brands-400.f4617423.woff2",revision:"83a42241ada98e0f959353ca0c3ee027"},{url:"assets/fa-regular-400.12dea17b.ttf",revision:"47fa5e12e9fc4e8325a6e1a0735b38f6"},{url:"assets/fa-regular-400.7ba24c41.woff2",revision:"3f505ed834ca9466cf7efc2806b86604"},{url:"assets/fa-solid-900.67a880b4.ttf",revision:"e8132d053030afa547e23b5163ef8301"},{url:"assets/fa-solid-900.e2c5cf54.woff2",revision:"44f8148f80e6c3d60fa6425b0cbb862a"},{url:"assets/fa-v4compatibility.7c377405.woff2",revision:"60cd2d66cb03cd2521862a12f970dbe9"},{url:"assets/fa-v4compatibility.8d9500e8.ttf",revision:"91fc9b0078b939bddcde338550684848"},{url:"assets/flUhRq6tzZclQEJ-Vdg-IuiaDsNa.fd84f88b.woff",revision:"3e1afe59fa075c9e04c436606b77f640"},{url:"assets/flUhRq6tzZclQEJ-Vdg-IuiaDsNcIhQ8tQ.4a4dbc62.woff2",revision:"a4160421d2605545f69a4cd6cd642902"},{url:"assets/gok-H7zzDkdnRel8-DQ6KAXJ69wP1tGnf4ZGhUcel5euIg.35dca8a7.woff2",revision:"0ba49c096a77b67734434cebcaf2e14d"},{url:"assets/gok-H7zzDkdnRel8-DQ6KAXJ69wP1tGnf4ZGhUcY.8e94758c.woff",revision:"0e4321a7c0dda51d72a669ac5892fc39"},{url:"assets/index.01debd3f.js",revision:"7b25b5b4b22320116bfad454232fdc1a"},{url:"assets/index.f04258f2.css",revision:"fff449a074fa2ddb20b0fd6ea3040f1d"},{url:"assets/KFOkCnqEu92Fr1MmgVxIIzQ.34e9582c.woff",revision:"4aa2e69855e3b83110a251c47fdd05fc"},{url:"assets/KFOlCnqEu92Fr1MmEU9fBBc-.9ce7f3ac.woff",revision:"40bcb2b8cc5ed94c4c21d06128e0e532"},{url:"assets/KFOlCnqEu92Fr1MmSU5fBBc-.bf14c7d7.woff",revision:"ea60988be8d6faebb4bc2a55b1f76e22"},{url:"assets/KFOlCnqEu92Fr1MmWUlfBBc-.e0fd57c0.woff",revision:"0774a8b7ca338dc1aba5a0ec8f2b9454"},{url:"assets/KFOlCnqEu92Fr1MmYUtfBBc-.f6537e32.woff",revision:"bcb7c7e2499a055f0e2f93203bdb282b"},{url:"assets/KFOmCnqEu92Fr1Mu4mxM.f2abf7fb.woff",revision:"d3907d0ccd03b1134c24d3bcaf05b698"},{url:"assets/Roboto-Regular.7277cfb8.ttf",revision:"afe8eacfc0903cc0612dc696881f0480"},{url:"assets/SubcategoryPage.c3e0fb3c.js",revision:"997c2e3ac2f472cb679a07d4276982d7"},{url:"favicon.ico",revision:"d498e815cd165b7670066b13763813ba"},{url:"icons/apple-icon-120x120.png",revision:"ed73606cf59948bc2f723c6c5518f5b7"},{url:"icons/apple-icon-152x152.png",revision:"7b79afe837bf10df8d4bbc4736d783b2"},{url:"icons/apple-icon-167x167.png",revision:"34a05651cf850005cee8705a15e24ce2"},{url:"icons/apple-icon-180x180.png",revision:"3a103d92e2cc745faacb56958a2c729b"},{url:"icons/apple-launch-1125x2436.png",revision:"ef8e429f5f82896880607ea4a51cda84"},{url:"icons/apple-launch-1170x2532.png",revision:"61d2b223817dca192bf929a05f192cee"},{url:"icons/apple-launch-1242x2208.png",revision:"960883028ad7059cd41da9c36a9f3f9d"},{url:"icons/apple-launch-1242x2688.png",revision:"ce80b5035c792918b6425dae9030e8de"},{url:"icons/apple-launch-1284x2778.png",revision:"684974e2c00d5c4dfda6c1b7a543edda"},{url:"icons/apple-launch-1536x2048.png",revision:"30f308e04c7eabcce32a640c28836b2e"},{url:"icons/apple-launch-1620x2160.png",revision:"188ba9094e40ecd0d46f682ebac3df09"},{url:"icons/apple-launch-1668x2224.png",revision:"857df7992b5b16eb31f9dfc6fae6d685"},{url:"icons/apple-launch-1668x2388.png",revision:"f728244a04c128a49a1b7ea8bc4b0ed9"},{url:"icons/apple-launch-2048x2732.png",revision:"c3eed2f32968408cf9a5e34592423c24"},{url:"icons/apple-launch-750x1334.png",revision:"4019e5a964215414c8e43dd86bb2c140"},{url:"icons/apple-launch-828x1792.png",revision:"aac7a09a53dcb8411186f52c5a4880c6"},{url:"icons/favicon-128x128.png",revision:"473fe3482434b8dbb5ebef11a1491eac"},{url:"icons/favicon-16x16.png",revision:"bb814c3bf2c6f4c63e48da1e420d329d"},{url:"icons/favicon-32x32.png",revision:"36b1e8ded5b635eeafb61ee4f11b47c7"},{url:"icons/favicon-96x96.png",revision:"0acbf51887e5cb52bde7ad06733a7c07"},{url:"icons/icon-128x128.png",revision:"473fe3482434b8dbb5ebef11a1491eac"},{url:"icons/icon-192x192.png",revision:"1e7a20230f57791ef44920bc129d48e4"},{url:"icons/icon-256x256.png",revision:"b61b8d392d13fa7214a0e368a20f5364"},{url:"icons/icon-384x384.png",revision:"a246d796a9a3851b2703f8c3954de99f"},{url:"icons/icon-512x512.png",revision:"a3339894ad74bb17b6632ab5de2bad69"},{url:"icons/ms-icon-144x144.png",revision:"d4a6303eb589b6e44324b74d812ce164"},{url:"icons/safari-pinned-tab.svg",revision:"56908fc2ffb301503347c2ff1400069d"},{url:"index.html",revision:"b14b9a911326d7ae20bf013cfebe762c"},{url:"logo.jpg",revision:"70196050ec3155274c019c56408fed4f"},{url:"logo.png",revision:"2bd1f8389e627bf528d9ac4a5cb74e95"},{url:"logo1.png",revision:"27729a9ba1ebd461c850acd9dd38aba4"},{url:"manifest.json",revision:"315e344224f8eaf77caf443f9681b28d"}],{}),e.cleanupOutdatedCaches(),e.registerRoute(new e.NavigationRoute(e.createHandlerBoundToURL("index.html"),{denylist:[/sw\.js$/,/workbox-(.)*\.js$/]}))}));
