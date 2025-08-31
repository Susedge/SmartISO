// tools/downloader_npm.js
// Copy UMD/global bundles from node_modules to public/assets/vendor/fullcalendar
const fs = require('fs');
const path = require('path');

const baseDir = path.resolve(__dirname, '..', 'public', 'assets', 'vendor', 'fullcalendar');
if (!fs.existsSync(baseDir)) fs.mkdirSync(baseDir, { recursive: true });

const version = '6'; // major version placeholder
const candidates = [
  { src: path.join('node_modules', '@fullcalendar', 'core', 'index.global.min.js'), dst: path.join(baseDir, 'main.min.js') },
  { src: path.join('node_modules', '@fullcalendar', 'core', 'main.min.css'), dst: path.join(baseDir, 'main.min.css') },
];

let ok = true;
for (const c of candidates) {
  if (!fs.existsSync(c.src)) {
    console.error('Missing', c.src, '- run `npm install @fullcalendar/core` first');
    ok = false;
    continue;
  }
  fs.copyFileSync(c.src, c.dst);
  console.log('Copied', c.src, '->', c.dst);
}

process.exit(ok ? 0 : 2);
