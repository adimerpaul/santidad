if(!self.define){let e,a={};const c=(c,s)=>(c=new URL(c+".js",s).href,a[c]||new Promise((a=>{if("document"in self){const e=document.createElement("script");e.src=c,e.onload=a,document.head.appendChild(e)}else e=c,importScripts(c),a()})).then((()=>{let e=a[c];if(!e)throw new Error(`Module ${c} didn’t register its module`);return e})));self.define=(s,i)=>{const f=e||("document"in self?document.currentScript.src:"")||location.href;if(a[f])return;let n={};const r=e=>c(e,f),b={module:{uri:f},exports:n,require:r};a[f]=Promise.all(s.map((e=>b[e]||r(e)))).then((e=>(i(...e),n)))}}define(["./workbox-57ffaad5"],(function(e){"use strict";e.setCacheNameDetails({prefix:"fronttienda"}),self.skipWaiting(),e.clientsClaim(),e.precacheAndRoute([{url:"assets/axios.29f7a2c6.js",revision:"587a6be0ac0be2520173030954982b79"},{url:"assets/Cabin-VariableFont_wdth.c191c4a2.ttf",revision:"355e1554bea0f09447c091a6bb6e2748"},{url:"assets/DetalleProducto.fb105272.js",revision:"93f426f3a9f486cabd60356ba20e7644"},{url:"assets/ErrorNotFound.318844c8.js",revision:"b45c342ae9f75e4229933a1133bfddad"},{url:"assets/fa-brands-400.20c4a58b.ttf",revision:"0ab3921d9b80975c5597432ab59f5d0a"},{url:"assets/fa-brands-400.74833209.woff2",revision:"8b0ddedbb27cbc9971c8667caa8a0cc1"},{url:"assets/fa-regular-400.528d022d.ttf",revision:"20206738b2bffb741b00200d5d3d6d20"},{url:"assets/fa-regular-400.8e7e5ea1.woff2",revision:"61f30b79daf5b31f0d254a31fba66158"},{url:"assets/fa-solid-900.67a65763.ttf",revision:"e2ceb83946c9e5fc7eab24453a03bffb"},{url:"assets/fa-solid-900.7152a693.woff2",revision:"c64278386c2bbb5e293e11b94ca2f6d1"},{url:"assets/fa-v4compatibility.0515a423.ttf",revision:"d20cedd7e254d4b58b721b6995ca52b4"},{url:"assets/fa-v4compatibility.694a17c3.woff2",revision:"4bc58bc16bb05a05d3a47a4f7e143b75"},{url:"assets/flUhRq6tzZclQEJ-Vdg-IuiaDsNa.fd84f88b.woff",revision:"3e1afe59fa075c9e04c436606b77f640"},{url:"assets/flUhRq6tzZclQEJ-Vdg-IuiaDsNcIhQ8tQ.4a4dbc62.woff2",revision:"a4160421d2605545f69a4cd6cd642902"},{url:"assets/index.1da94828.js",revision:"8621e07e6448633921ba2f0237b04970"},{url:"assets/index.3c534acb.css",revision:"2d970a3f607e789ea8a8e66bbd07c9d6"},{url:"assets/IndexPage.2153ec02.js",revision:"f15f72f30e5d143fa093e8b0ff0bdd99"},{url:"assets/KFOkCnqEu92Fr1MmgVxIIzQ.34e9582c.woff",revision:"4aa2e69855e3b83110a251c47fdd05fc"},{url:"assets/KFOlCnqEu92Fr1MmEU9fBBc-.9ce7f3ac.woff",revision:"40bcb2b8cc5ed94c4c21d06128e0e532"},{url:"assets/KFOlCnqEu92Fr1MmSU5fBBc-.bf14c7d7.woff",revision:"ea60988be8d6faebb4bc2a55b1f76e22"},{url:"assets/KFOlCnqEu92Fr1MmWUlfBBc-.e0fd57c0.woff",revision:"0774a8b7ca338dc1aba5a0ec8f2b9454"},{url:"assets/KFOlCnqEu92Fr1MmYUtfBBc-.f6537e32.woff",revision:"bcb7c7e2499a055f0e2f93203bdb282b"},{url:"assets/KFOmCnqEu92Fr1Mu4mxM.f2abf7fb.woff",revision:"d3907d0ccd03b1134c24d3bcaf05b698"},{url:"assets/leaflet-src.esm.5b9ad573.js",revision:"4d9f8bd4b5cc6aef9c72b11321e9e688"},{url:"assets/MainLayout.33ccb4d6.js",revision:"e0a4a3aa25f5c8892a660f15fe5fef25"},{url:"assets/marker-icon-2x.17982e67.js",revision:"0790d7e83f4077151c98c6c58631a589"},{url:"assets/marker-icon.c1a4cbf5.js",revision:"ae9594e56f7ef338b86ab7ce21ffde43"},{url:"assets/marker-shadow.91c47cf3.js",revision:"d860c6368d72161eaf8264b28956b42b"},{url:"assets/QBadge.254b1db9.js",revision:"002016c9da64e893855c34bcc5ecc105"},{url:"assets/QImg.f6426920.js",revision:"328429500df9b505fec8e3611b67d21d"},{url:"assets/QPage.2a4df6f6.js",revision:"cb4ff74d55ada6dfd14b1c784b0bab9f"},{url:"assets/Sucursales.8214bf57.js",revision:"947f2b429ddcd35a909b8efca587c586"},{url:"assets/Sucursales.cb5574c5.css",revision:"963c23cf1e6e2d9f6fe7a99f66714280"},{url:"favicon.ico",revision:"fcec59f344106373122ba1e105586f3c"},{url:"icons/apple-icon-120x120.png",revision:"9e400eb62e48b94d98b1705050397cf6"},{url:"icons/apple-icon-152x152.png",revision:"f92c80b1b5d650416d567a12089253ee"},{url:"icons/apple-icon-167x167.png",revision:"10cc720cc8b1f145064307fcf4f02434"},{url:"icons/apple-icon-180x180.png",revision:"39b7b8f2fb4aca4d3ac778de8863175f"},{url:"icons/apple-launch-1080x2340.png",revision:"f4fdc48513068961867b4219f14abc77"},{url:"icons/apple-launch-1125x2436.png",revision:"4a6ebeb91b41aa74469f0c6453dd09ca"},{url:"icons/apple-launch-1170x2532.png",revision:"d1da969f3464aaa7dba063921881f569"},{url:"icons/apple-launch-1179x2556.png",revision:"761d717369d7fab6c76406d1b899a5cc"},{url:"icons/apple-launch-1242x2208.png",revision:"32a1815d24a3a141c50d4138c60cebb8"},{url:"icons/apple-launch-1242x2688.png",revision:"437c0bcabb862594cf7dd88f7f930647"},{url:"icons/apple-launch-1284x2778.png",revision:"a314b6b31e566cd55cbe4860cf93a007"},{url:"icons/apple-launch-1290x2796.png",revision:"6ab7520c8f4f606dfbfb268b5637052b"},{url:"icons/apple-launch-1536x2048.png",revision:"8e9deb37cfca41b6cc81b2cab70e7e9a"},{url:"icons/apple-launch-1620x2160.png",revision:"fee5249e23fe9f29d93fa8265f6d94fe"},{url:"icons/apple-launch-1668x2224.png",revision:"df62f2ab0e169e8b2078530848a0c734"},{url:"icons/apple-launch-1668x2388.png",revision:"fc0b63928ef57286299a873b4b386ab0"},{url:"icons/apple-launch-2048x2732.png",revision:"87e4fd9f0ee4bd9abf41907723321e50"},{url:"icons/apple-launch-750x1334.png",revision:"c6cea11244b7a2e77480b6f9f2401959"},{url:"icons/apple-launch-828x1792.png",revision:"b36497fe39dd1989cc2a1d718230d656"},{url:"icons/favicon-128x128.png",revision:"369c3f6e4f5ec1367b5b4f9aa244608c"},{url:"icons/favicon-16x16.png",revision:"91fd7cb3d449ae26d9d6801b0fcaf69a"},{url:"icons/favicon-32x32.png",revision:"77d832f7cfccb2c69cba120c86e8deae"},{url:"icons/favicon-96x96.png",revision:"9cd6d563c485c2b2604065409b6762e7"},{url:"icons/icon-128x128.png",revision:"369c3f6e4f5ec1367b5b4f9aa244608c"},{url:"icons/icon-192x192.png",revision:"0a6a1c1e572298cccd4412e93ab5709f"},{url:"icons/icon-256x256.png",revision:"3e303a95ea4a2c4695012254e64b3003"},{url:"icons/icon-384x384.png",revision:"bddff1750e3aec1edccd450e0137945f"},{url:"icons/icon-512x512.png",revision:"d765afc9eb493b0ce2a015a38a206bbc"},{url:"icons/ms-icon-144x144.png",revision:"0752fd8dfc223aa76b74c65039687e30"},{url:"icons/safari-pinned-tab.svg",revision:"fb3fe8034354caa7ec361f85b14213bc"},{url:"index.html",revision:"332ee3c04e51ec7fb2edab6601385249"},{url:"logo.png",revision:"3e490226573c0ec3fde5380683da0ebc"},{url:"logo1.png",revision:"27729a9ba1ebd461c850acd9dd38aba4"},{url:"manifest.json",revision:"5ecb7154a7f914fb07673c91a2319eb5"}],{}),e.cleanupOutdatedCaches(),e.registerRoute(new e.NavigationRoute(e.createHandlerBoundToURL("index.html"),{denylist:[/sw\.js$/,/workbox-(.)*\.js$/]}))}));
