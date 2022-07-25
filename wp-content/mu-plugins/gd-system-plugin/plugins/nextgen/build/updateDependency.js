!function(){"use strict";var e,n={571:function(e,n,t){t.r(n),t.d(n,{UpdateDependencyModal:function(){return y}});var o=window.wp.element,r=window.wp.components,a=window.wp.plugins,i=window.wp.i18n,d=window.wp.data,l=window.wp.apiFetch,u=t.n(l);const s="nextgen/update-dependency",c="undefined"!=typeof nextgenForceUpdates&&"true"===(null===(p=nextgenForceUpdates)||void 0===p?void 0:p.updatesAvailable);var p;const f="undefined"!=typeof nextgenForceUpdates&&"true"===(null===(g=nextgenForceUpdates)||void 0===g?void 0:g.updatesWereForced);var g;var v=window.wp.url;const w={updateDependencyModal:c};(0,d.registerStore)(s,{reducer:(e=w,n)=>"TRIGGER_LOCATION_RELOAD"===n.type?(location.href=(0,v.addQueryArgs)(location.href,{"nextgen-update-forced":!0}),e):e,actions:{triggerReload:()=>({type:"TRIGGER_LOCATION_RELOAD"})},selectors:{isUpdateDependencyModalVisible:e=>e.updateDependencyModal||!1}});const y=()=>{const{isVisible:e}=function(){const{isVisible:e}=(0,d.useSelect)((e=>({isVisible:e(s).isUpdateDependencyModalVisible()})),[]),{triggerReload:n}=(0,d.useDispatch)(s),{createNotice:t}=(0,d.useDispatch)("core/notices"),[r,a]=(0,o.useState)(c),l=()=>{u()({path:"nextgen/get/update",method:"GET"}).then((e=>a(!(null==e||!e[0]))))};return(0,o.useEffect)((()=>{r&&(l(),(async()=>{await u()({path:"nextgen/do/update",method:"GET"}).then((()=>l())),await n()})())}),[r]),(0,o.useEffect)((()=>{f&&t("success",(0,i.__)("Update completed.","nextgen"),{type:"snackbar"})}),[f]),{isVisible:e}}();return e&&(0,o.createElement)(r.Modal,{isDismissible:!1,shouldCloseOnEsc:!1,shouldCloseOnClickOutside:!1,className:"nextgen-update-dependency-modal",overlayClassName:"nextgen-update-dependency-modal-overlay"},(0,o.createElement)("h1",{className:"nextgen-modal__title"},(0,i.__)("Updating to the latest and greatest","nextgen")),(0,o.createElement)(r.Spinner,null),(0,o.createElement)("div",null,(0,i.__)("Please wait while we apply the latest update to your editor.","nextgen")),(0,o.createElement)("div",null,(0,i.__)("This shouldn't take long.","nextgen")))};(0,a.registerPlugin)("nextgen-update-dependency",{render:()=>(0,o.createElement)(y,null)})}},t={};function o(e){var r=t[e];if(void 0!==r)return r.exports;var a=t[e]={exports:{}};return n[e](a,a.exports,o),a.exports}o.m=n,e=[],o.O=function(n,t,r,a){if(!t){var i=1/0;for(s=0;s<e.length;s++){t=e[s][0],r=e[s][1],a=e[s][2];for(var d=!0,l=0;l<t.length;l++)(!1&a||i>=a)&&Object.keys(o.O).every((function(e){return o.O[e](t[l])}))?t.splice(l--,1):(d=!1,a<i&&(i=a));if(d){e.splice(s--,1);var u=r();void 0!==u&&(n=u)}}return n}a=a||0;for(var s=e.length;s>0&&e[s-1][2]>a;s--)e[s]=e[s-1];e[s]=[t,r,a]},o.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return o.d(n,{a:n}),n},o.d=function(e,n){for(var t in n)o.o(n,t)&&!o.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},o.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},o.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},function(){var e={919:0,995:0};o.O.j=function(n){return 0===e[n]};var n=function(n,t){var r,a,i=t[0],d=t[1],l=t[2],u=0;if(i.some((function(n){return 0!==e[n]}))){for(r in d)o.o(d,r)&&(o.m[r]=d[r]);if(l)var s=l(o)}for(n&&n(t);u<i.length;u++)a=i[u],o.o(e,a)&&e[a]&&e[a][0](),e[i[u]]=0;return o.O(s)},t=self.webpackChunknextgen_name_=self.webpackChunknextgen_name_||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))}();var r=o.O(void 0,[995],(function(){return o(571)}));r=o.O(r),(window.nextgen=window.nextgen||{}).updateDependency=r}();