// Isolated build: all dependencies are compiled into static assets for PHP hosting.
// The HostGator installation never needs npm or an external CDN.
import { build } from 'esbuild';
import fs from 'node:fs/promises';
import path from 'node:path';

const root=process.cwd();
const output=path.join(root,'assets/vendor/email-builder');
await fs.mkdir(output,{recursive:true});
await build({
  entryPoints:[path.join(root,'tools/browser-src/communications-email-lab.mjs')],
  outfile:path.join(output,'lab.js'),
  bundle:true,
  platform:'browser',
  format:'iife',
  target:['es2020'],
  legalComments:'eof',
  minify:false,
  logLevel:'info',
});
await fs.copyFile(path.join(root,'node_modules/grapesjs/dist/css/grapes.min.css'),path.join(output,'grapes.min.css'));
console.log('GrapesJS/MJML experimental assets built in assets/vendor/email-builder/');
