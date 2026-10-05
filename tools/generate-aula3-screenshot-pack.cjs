const fs=require('node:fs');
const path=require('node:path');
const zlib=require('node:zlib');
const sharp=require('sharp');

const sourceDir=process.argv[2]||path.join('student-visual-audit','complete','desktop');
const output=process.argv[3]||path.join('migrations','assets','aula3-hires.pack.b64');

const screens=[
  {source:'notebook.png',file:'notebook.webp',top:70,bottom:1080},
  {source:'exposure.png',file:'exposure.webp',top:70,bottom:1220},
  {source:'process-library.png',file:'process-library.webp',top:70,bottom:1640},
  {source:'recording-partial.png',file:'recording-partial.webp',top:70,bottom:1120},
  {source:'result-reviewed.png',file:'result-reviewed.webp',top:70,bottom:2070},
  {source:'compare-records.png',file:'compare-records.webp',top:70,bottom:1650},
  {source:'research-derived.png',file:'research-derived.webp',top:70,bottom:680},
  {source:'tools.png',file:'tools.webp',top:70,bottom:1710},
];

(async()=>{
  const parts=[];
  for(const screen of screens){
    const input=path.join(sourceDir,screen.source);
    if(!fs.existsSync(input))throw new Error(`Missing canonical screenshot: ${input}`);
    const image=sharp(input,{failOn:'error'});
    const meta=await image.metadata();
    if(!meta.width||!meta.height)throw new Error(`Could not inspect ${input}`);
    if(meta.width<1200)throw new Error(`${input} is too narrow (${meta.width}px); refusing to seed course material from a downscaled source.`);
    const width=Math.min(1080,meta.width);
    const left=Math.max(0,Math.floor((meta.width-width)/2));
    const top=Math.min(screen.top,Math.max(0,meta.height-1));
    const bottom=Math.min(screen.bottom,meta.height);
    const height=bottom-top;
    if(height<300)throw new Error(`Invalid crop for ${input}: ${height}px high`);
    const bytes=await sharp(input)
      .extract({left,top,width,height})
      .webp({quality:82,effort:6,smartSubsample:true})
      .toBuffer();
    const outMeta=await sharp(bytes).metadata();
    if((outMeta.width||0)<1000)throw new Error(`${screen.file} ended below the 1000px visual-material floor.`);
    parts.push(Buffer.from(`${screen.file}\t${bytes.length}\n`,'ascii'),bytes);
    process.stdout.write(`${screen.file}: ${outMeta.width}x${outMeta.height}, ${bytes.length} bytes\n`);
  }
  parts.push(Buffer.from('END\t0\n','ascii'));
  const packed=zlib.gzipSync(Buffer.concat(parts),{level:9,mtime:0});
  fs.mkdirSync(path.dirname(output),{recursive:true});
  fs.writeFileSync(output,packed.toString('base64')+'\n','utf8');
  process.stdout.write(`Aula 3 screenshot pack: ${output} (${packed.length} compressed bytes)\n`);
})().catch(error=>{console.error(error);process.exit(1);});
