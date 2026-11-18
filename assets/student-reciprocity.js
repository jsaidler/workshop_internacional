(()=>{
  'use strict';

  const RECIPROCITY_EXPONENT=1.38542662;
  const MAX_DECIMALS=3;

  const decimalValue=value=>Number(String(value).replace(',','.'));
  const finitePositive=value=>Number.isFinite(value)&&value>=0;

  function decimalString(value,separator='.'){
    const rounded=Math.round((value+Number.EPSILON)*10**MAX_DECIMALS)/10**MAX_DECIMALS;
    let text=rounded.toFixed(MAX_DECIMALS).replace(/0+$/,'').replace(/\.$/,'');
    if(separator===',')text=text.replace('.',',');
    return text;
  }

  function parseDuration(rawValue){
    const raw=String(rawValue??'').trim();
    if(raw==='')return null;

    const fraction=raw.match(/^(\d+(?:[.,]\d+)?)\s*\/\s*(\d+(?:[.,]\d+)?)(\s*(?:s|sec|secs|seg|segs|segundo|segundos))?$/i);
    if(fraction){
      const numerator=decimalValue(fraction[1]);
      const denominator=decimalValue(fraction[2]);
      if(!finitePositive(numerator)||!Number.isFinite(denominator)||denominator<=0)return null;
      return {
        raw,
        seconds:numerator/denominator,
        format:{kind:'fraction',suffix:fraction[3]??'',separator:fraction[1].includes(',')||fraction[2].includes(',')?',':'.'}
      };
    }

    if(raw.includes(':')){
      const parts=raw.split(':');
      if(parts.length!==2&&parts.length!==3)return null;
      if(!parts.every((part,index)=>index===parts.length-1?/^\d+(?:[.,]\d+)?$/.test(part):/^\d+$/.test(part)))return null;
      const values=parts.map(decimalValue);
      if(values.some(value=>!finitePositive(value)))return null;
      if(values[values.length-1]>=60)return null;
      if(parts.length===3&&values[1]>=60)return null;
      const seconds=parts.length===3?(values[0]*3600)+(values[1]*60)+values[2]:(values[0]*60)+values[1];
      const secondPart=parts[parts.length-1];
      const integerPart=secondPart.split(/[.,]/)[0];
      return {
        raw,
        seconds,
        format:{
          kind:'clock',
          fields:parts.length,
          widths:parts.map(part=>part.split(/[.,]/)[0].length),
          separator:secondPart.includes(',')?',':'.',
          secondWidth:integerPart.length
        }
      };
    }

    const unit=raw.match(/^(\d+(?:[.,]\d+)?)(\s*)(s|sec|secs|seg|segs|segundo|segundos|min|mins|minuto|minutos|m|h|hora|horas)$/i);
    if(unit){
      const value=decimalValue(unit[1]);
      if(!finitePositive(value))return null;
      const token=unit[3].toLowerCase();
      const factor=(token==='h'||token==='hora'||token==='horas')?3600:(token==='m'||token==='min'||token==='mins'||token==='minuto'||token==='minutos')?60:1;
      return {
        raw,
        seconds:value*factor,
        format:{kind:'unit',factor,space:unit[2],unit:unit[3],separator:unit[1].includes(',')?',':'.'}
      };
    }

    if(/^\d+(?:[.,]\d+)?$/.test(raw)){
      const seconds=decimalValue(raw);
      if(!finitePositive(seconds))return null;
      return {raw,seconds,format:{kind:'plain',separator:raw.includes(',')?',':'.'}};
    }

    return null;
  }

  function adjustedSeconds(seconds){
    if(!finitePositive(seconds))return null;
    return seconds<=1?seconds:Math.pow(seconds,RECIPROCITY_EXPONENT);
  }

  function formatClock(seconds,format){
    const totalMilliseconds=Math.round(seconds*1000);
    const totalSeconds=Math.floor(totalMilliseconds/1000);
    const milliseconds=totalMilliseconds%1000;
    const fields=format.fields;
    let first;
    let middle=null;
    let last;

    if(fields===3){
      first=Math.floor(totalSeconds/3600);
      middle=Math.floor((totalSeconds%3600)/60);
      last=totalSeconds%60;
    }else{
      first=Math.floor(totalSeconds/60);
      last=totalSeconds%60;
    }

    const pad=(value,width)=>String(value).padStart(width,'0');
    const firstText=pad(first,format.widths[0]);
    const lastWidth=Math.max(2,format.secondWidth);
    let lastText=pad(last,lastWidth);
    if(milliseconds>0){
      const fraction=String(milliseconds).padStart(3,'0').replace(/0+$/,'');
      lastText+=format.separator+fraction;
    }

    if(fields===3){
      const middleText=pad(middle,Math.max(2,format.widths[1]));
      return `${firstText}:${middleText}:${lastText}`;
    }
    return `${firstText}:${lastText}`;
  }

  function formatAdjusted(parsed,adjusted){
    const format=parsed.format;
    if(parsed.seconds<=1)return parsed.raw;
    if(format.kind==='clock')return formatClock(adjusted,format);
    if(format.kind==='unit')return `${decimalString(adjusted/format.factor,format.separator)}${format.space}${format.unit}`;
    if(format.kind==='fraction')return `${decimalString(adjusted,format.separator)}${format.suffix}`;
    return decimalString(adjusted,format.separator);
  }

  function calculate(rawValue){
    const parsed=parseDuration(rawValue);
    if(!parsed)return null;
    const adjusted=adjustedSeconds(parsed.seconds);
    if(adjusted===null)return null;
    return formatAdjusted(parsed,adjusted);
  }

  function bindReciprocityCalculator(root=document){
    const source=root.querySelector('input[name="calculated_time"]');
    const output=root.querySelector('input[name="reciprocity_time"]');
    if(!source||!output)return;

    output.readOnly=true;
    output.setAttribute('aria-readonly','true');
    output.dataset.reciprocityCalculated='true';
    output.title='Calculado automaticamente a partir do tempo informado.';
    if(!source.placeholder)source.placeholder='Ex.: 4 s, 02:30 ou 00:00:04';

    const update=()=>{
      const raw=source.value.trim();
      if(raw===''){
        output.value='';
        output.placeholder='Calculado automaticamente';
        output.removeAttribute('data-reciprocity-invalid');
        return;
      }
      const value=calculate(raw);
      if(value===null){
        output.value='';
        output.placeholder='Formato de tempo não reconhecido';
        output.dataset.reciprocityInvalid='true';
        return;
      }
      output.value=value;
      output.placeholder='Calculado automaticamente';
      output.removeAttribute('data-reciprocity-invalid');
      output.dispatchEvent(new Event('input',{bubbles:true}));
    };

    source.addEventListener('input',update);
    source.addEventListener('change',update);
    update();
  }

  if(typeof window!=='undefined'){
    window.StudentReciprocity={RECIPROCITY_EXPONENT,parseDuration,adjustedSeconds,calculate,bindReciprocityCalculator};
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>bindReciprocityCalculator());
    else bindReciprocityCalculator();
  }
})();
