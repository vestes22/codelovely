(window.webpackJsonp_font_awesome_admin=window.webpackJsonp_font_awesome_admin||[]).push([[17],{281:function(t,e,s){"use strict";s.r(e),s.d(e,"scopeCss",(function(){return j}));const o=")(?:\\(((?:\\([^)(]*\\)|[^)(]*)+?)\\))?([^,{]*)",r=new RegExp("(-shadowcsshost"+o,"gim"),c=new RegExp("(-shadowcsscontext"+o,"gim"),n=new RegExp("(-shadowcssslotted"+o,"gim"),l=/-shadowcsshost-no-combinator([^\s]*)/,a=[/::shadow/g,/::content/g],i=/-shadowcsshost/gim,h=/:host/gim,p=/::slotted/gim,d=/:host-context/gim,u=/\/\*\s*[\s\S]*?\*\//g,m=/\/\*\s*#\s*source(Mapping)?URL=[\s\S]+?\*\//g,g=/(\s*)([^;\{\}]+?)(\s*)((?:{%BLOCK%}?\s*;?)|(?:\s*;))/g,w=/([{}])/g,f=/(^.*?[^\\])??((:+)(.*)|$)/,_=(t,e)=>{const s=x(t);let o=0;return s.escapedString.replace(g,(...t)=>{const r=t[2];let c="",n=t[4],l="";n&&n.startsWith("{%BLOCK%")&&(c=s.blocks[o++],n=n.substring("%BLOCK%".length+1),l="{");const a=e({selector:r,content:c});return`${t[1]}${a.selector}${t[3]}${l}${a.content}${n}`})},x=t=>{const e=t.split(w),s=[],o=[];let r=0,c=[];for(let t=0;t<e.length;t++){const n=e[t];"}"===n&&r--,r>0?c.push(n):(c.length>0&&(o.push(c.join("")),s.push("%BLOCK%"),c=[]),s.push(n)),"{"===n&&r++}return c.length>0&&(o.push(c.join("")),s.push("%BLOCK%")),{escapedString:s.join(""),blocks:o}},$=(t,e,s)=>t.replace(e,(...t)=>{if(t[2]){const e=t[2].split(","),o=[];for(let r=0;r<e.length;r++){const c=e[r].trim();if(!c)break;o.push(s("-shadowcsshost-no-combinator",c,t[3]))}return o.join(",")}return"-shadowcsshost-no-combinator"+t[3]}),b=(t,e,s)=>t+e.replace("-shadowcsshost","")+s,O=(t,e,s)=>e.indexOf("-shadowcsshost")>-1?b(t,e,s):t+e+s+", "+e+" "+t+s,S=(t,e)=>t.replace(f,(t,s="",o,r="",c="")=>s+e+r+c),W=(t,e,s,o,r)=>_(t,t=>{let r=t.selector,c=t.content;return"@"!==t.selector[0]?r=((t,e,s,o)=>t.split(",").map(t=>o&&t.indexOf("."+o)>-1?t.trim():((t,e)=>!(t=>(t=t.replace(/\[/g,"\\[").replace(/\]/g,"\\]"),new RegExp("^("+t+")([>\\s~+[.,{:][\\s\\S]*)?$","m")))(e).test(t))(t,e)?((t,e,s)=>{const o="."+(e=e.replace(/\[is=([^\]]*)\]/g,(t,...e)=>e[0])),r=t=>{let r=t.trim();if(!r)return"";if(t.indexOf("-shadowcsshost-no-combinator")>-1)r=((t,e,s)=>{if(i.lastIndex=0,i.test(t)){const e="."+s;return t.replace(l,(t,s)=>S(s,e)).replace(i,e+" ")}return e+" "+t})(t,e,s);else{const e=t.replace(i,"");e.length>0&&(r=S(e,o))}return r},c=(t=>{const e=[];let s,o=0;return s=(t=t.replace(/(\[[^\]]*\])/g,(t,s)=>{const r=`__ph-${o}__`;return e.push(s),o++,r})).replace(/(:nth-[-\w]+)(\([^)]+\))/g,(t,s,r)=>{const c=`__ph-${o}__`;return e.push(r),o++,s+c}),{content:s,placeholders:e}})(t);let n,a="",h=0;const p=/( |>|\+|~(?!=))\s*/g;let d=!((t=c.content).indexOf("-shadowcsshost-no-combinator")>-1);for(;null!==(n=p.exec(t));){const e=n[1],s=t.slice(h,n.index).trim();d=d||s.indexOf("-shadowcsshost-no-combinator")>-1,a+=`${d?r(s):s} ${e} `,h=p.lastIndex}const u=t.substring(h);return d=d||u.indexOf("-shadowcsshost-no-combinator")>-1,a+=d?r(u):u,m=c.placeholders,a.replace(/__ph-(\d+)__/g,(t,e)=>m[+e]);var m})(t,e,s).trim():t.trim()).join(", "))(t.selector,e,s,o):(t.selector.startsWith("@media")||t.selector.startsWith("@supports")||t.selector.startsWith("@page")||t.selector.startsWith("@document"))&&(c=W(t.content,e,s,o)),{selector:r.replace(/\s{2,}/g," ").trim(),content:c}}),j=(t,e,s)=>{const o=e+"-h",l=e+"-s",i=t.match(m)||[];t=(t=>t.replace(u,""))(t);const g=[];if(s){const e=t=>{const e=`/*!@___${g.length}___*/`,s=`/*!@${t.selector}*/`;return g.push({placeholder:e,comment:s}),t.selector=e+t.selector,t};t=_(t,t=>"@"!==t.selector[0]?e(t):t.selector.startsWith("@media")||t.selector.startsWith("@supports")||t.selector.startsWith("@page")||t.selector.startsWith("@document")?(t.content=_(t.content,e),t):t)}const w=((t,e,s,o,l)=>{const i=((t,e)=>{const s="."+e+" > ",o=[];return t=t.replace(n,(...t)=>{if(t[2]){const e=t[2].trim(),r=t[3],c=s+e+r;let n="";for(let e=t[4]-1;e>=0;e--){const s=t[5][e];if("}"===s||","===s)break;n=s+n}const l=n+c,a=`${n.trimRight()}${c.trim()}`;if(l.trim()!==a.trim()){const t=`${a}, ${l}`;o.push({orgSelector:l,updatedSelector:t})}return c}return"-shadowcsshost-no-combinator"+t[3]}),{selectors:o,cssText:t}})(t=(t=>$(t,c,O))(t=(t=>$(t,r,b))(t=t.replace(d,"-shadowcsscontext").replace(h,"-shadowcsshost").replace(p,"-shadowcssslotted"))),o);return t=(t=>a.reduce((t,e)=>t.replace(e," "),t))(t=i.cssText),e&&(t=W(t,e,s,o)),{cssText:(t=(t=t.replace(/-shadowcsshost-no-combinator/g,"."+s)).replace(/>\s*\*\s+([^{, ]+)/gm," $1 ")).trim(),slottedSelectors:i.selectors}})(t,e,o,l);return t=[w.cssText,...i].join("\n"),s&&g.forEach(({placeholder:e,comment:s})=>{t=t.replace(e,s)}),w.slottedSelectors.forEach(e=>{t=t.replace(e.orgSelector,e.updatedSelector)}),t}}}]);