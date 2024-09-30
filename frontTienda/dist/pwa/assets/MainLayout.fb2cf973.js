import{c as $,i as U,e as L,l as M,p as G,a as d,h as f,f as J,g as T,j as ee,r as w,k as N,o as F,m as k,n as X,q as I,s as E,w as x,t as Y,u as te,v as ne,x as oe,y as O,z as H,A as ie,_ as ae,B as le,C as re,D as se,E as ue,F as P,G as y,H as W,Q as V}from"./index.616c6016.js";var ce=$({name:"QPageContainer",setup(e,{slots:m}){const{proxy:{$q:i}}=T(),n=U(M,L);if(n===L)return console.error("QPageContainer needs to be child of QLayout"),L;G(ee,!0);const o=d(()=>{const r={};return n.header.space===!0&&(r.paddingTop=`${n.header.size}px`),n.right.space===!0&&(r[`padding${i.lang.rtl===!0?"Left":"Right"}`]=`${n.right.size}px`),n.footer.space===!0&&(r.paddingBottom=`${n.footer.size}px`),n.left.space===!0&&(r[`padding${i.lang.rtl===!0?"Right":"Left"}`]=`${n.left.size}px`),r});return()=>f("div",{class:"q-page-container",style:o.value},J(m.default))}});const de=f("div",{class:"q-space"});var fe=$({name:"QSpace",setup(){return()=>de}}),ve=$({name:"QToolbar",props:{inset:Boolean},setup(e,{slots:m}){const i=d(()=>"q-toolbar row no-wrap items-center"+(e.inset===!0?" q-toolbar--inset":""));return()=>f("div",{class:i.value,role:"toolbar"},J(m.default))}});function he(){const e=w(!N.value);return e.value===!1&&F(()=>{e.value=!0}),e}const Z=typeof ResizeObserver!="undefined",A=Z===!0?{}:{style:"display:block;position:absolute;top:0;left:0;right:0;bottom:0;height:100%;width:100%;overflow:hidden;pointer-events:none;z-index:-1;",url:"about:blank"};var D=$({name:"QResizeObserver",props:{debounce:{type:[String,Number],default:100}},emits:["resize"],setup(e,{emit:m}){let i=null,n,o={width:-1,height:-1};function r(s){s===!0||e.debounce===0||e.debounce==="0"?u():i===null&&(i=setTimeout(u,e.debounce))}function u(){if(i!==null&&(clearTimeout(i),i=null),n){const{offsetWidth:s,offsetHeight:l}=n;(s!==o.width||l!==o.height)&&(o={width:s,height:l},m("resize",o))}}const{proxy:g}=T();if(Z===!0){let s;const l=v=>{n=g.$el.parentNode,n?(s=new ResizeObserver(r),s.observe(n),u()):v!==!0&&I(()=>{l(!0)})};return F(()=>{l()}),k(()=>{i!==null&&clearTimeout(i),s!==void 0&&(s.disconnect!==void 0?s.disconnect():n&&s.unobserve(n))}),X}else{let v=function(){i!==null&&(clearTimeout(i),i=null),l!==void 0&&(l.removeEventListener!==void 0&&l.removeEventListener("resize",r,E.passive),l=void 0)},b=function(){v(),n&&n.contentDocument&&(l=n.contentDocument.defaultView,l.addEventListener("resize",r,E.passive),u())};const s=he();let l;return F(()=>{I(()=>{n=g.$el,n&&b()})}),k(v),g.trigger=r,()=>{if(s.value===!0)return f("object",{style:A.style,tabindex:-1,type:"text/html",data:A.url,"aria-hidden":"true",onLoad:b})}}}}),me=$({name:"QFooter",props:{modelValue:{type:Boolean,default:!0},reveal:Boolean,bordered:Boolean,elevated:Boolean,heightHint:{type:[String,Number],default:50}},emits:["reveal","focusin"],setup(e,{slots:m,emit:i}){const{proxy:{$q:n}}=T(),o=U(M,L);if(o===L)return console.error("QFooter needs to be child of QLayout"),L;const r=w(parseInt(e.heightHint,10)),u=w(!0),g=w(N.value===!0||o.isContainer.value===!0?0:window.innerHeight),s=d(()=>e.reveal===!0||o.view.value.indexOf("F")>-1||n.platform.is.ios&&o.isContainer.value===!0),l=d(()=>o.isContainer.value===!0?o.containerHeight.value:g.value),v=d(()=>{if(e.modelValue!==!0)return 0;if(s.value===!0)return u.value===!0?r.value:0;const t=o.scroll.value.position+l.value+r.value-o.height.value;return t>0?t:0}),b=d(()=>e.modelValue!==!0||s.value===!0&&u.value!==!0),z=d(()=>e.modelValue===!0&&b.value===!0&&e.reveal===!0),h=d(()=>"q-footer q-layout__section--marginal "+(s.value===!0?"fixed":"absolute")+"-bottom"+(e.bordered===!0?" q-footer--bordered":"")+(b.value===!0?" q-footer--hidden":"")+(e.modelValue!==!0?" q-layout--prevent-focus"+(s.value!==!0?" hidden":""):"")),q=d(()=>{const t=o.rows.value.bottom,c={};return t[0]==="l"&&o.left.space===!0&&(c[n.lang.rtl===!0?"right":"left"]=`${o.left.size}px`),t[2]==="r"&&o.right.space===!0&&(c[n.lang.rtl===!0?"left":"right"]=`${o.right.size}px`),c});function p(t,c){o.update("footer",t,c)}function S(t,c){t.value!==c&&(t.value=c)}function Q({height:t}){S(r,t),p("size",t)}function C(){if(e.reveal!==!0)return;const{direction:t,position:c,inflectionPoint:_}=o.scroll.value;S(u,t==="up"||c-_<100||o.height.value-l.value-c-r.value<300)}function R(t){z.value===!0&&S(u,!0),i("focusin",t)}x(()=>e.modelValue,t=>{p("space",t),S(u,!0),o.animate()}),x(v,t=>{p("offset",t)}),x(()=>e.reveal,t=>{t===!1&&S(u,e.modelValue)}),x(u,t=>{o.animate(),i("reveal",t)}),x([r,o.scroll,o.height],C),x(()=>n.screen.height,t=>{o.isContainer.value!==!0&&S(g,t)});const a={};return o.instances.footer=a,e.modelValue===!0&&p("size",r.value),p("space",e.modelValue),p("offset",v.value),k(()=>{o.instances.footer===a&&(o.instances.footer=void 0,p("size",0),p("offset",0),p("space",!1))}),()=>{const t=Y(m.default,[f(D,{debounce:0,onResize:Q})]);return e.elevated===!0&&t.push(f("div",{class:"q-layout__shadow absolute-full overflow-hidden no-pointer-events"})),f("footer",{class:h.value,style:q.value,onFocusin:R},t)}}});const{passive:K}=E,ge=["both","horizontal","vertical"];var be=$({name:"QScrollObserver",props:{axis:{type:String,validator:e=>ge.includes(e),default:"vertical"},debounce:[String,Number],scrollTarget:{default:void 0}},emits:["scroll"],setup(e,{emit:m}){const i={position:{top:0,left:0},direction:"down",directionChanged:!1,delta:{top:0,left:0},inflectionPoint:{top:0,left:0}};let n=null,o,r;x(()=>e.scrollTarget,()=>{s(),g()});function u(){n!==null&&n();const b=Math.max(0,ne(o)),z=oe(o),h={top:b-i.position.top,left:z-i.position.left};if(e.axis==="vertical"&&h.top===0||e.axis==="horizontal"&&h.left===0)return;const q=Math.abs(h.top)>=Math.abs(h.left)?h.top<0?"up":"down":h.left<0?"left":"right";i.position={top:b,left:z},i.directionChanged=i.direction!==q,i.delta=h,i.directionChanged===!0&&(i.direction=q,i.inflectionPoint=i.position),m("scroll",{...i})}function g(){o=te(r,e.scrollTarget),o.addEventListener("scroll",l,K),l(!0)}function s(){o!==void 0&&(o.removeEventListener("scroll",l,K),o=void 0)}function l(b){if(b===!0||e.debounce===0||e.debounce==="0")u();else if(n===null){const[z,h]=e.debounce?[setTimeout(u,e.debounce),clearTimeout]:[requestAnimationFrame(u),cancelAnimationFrame];n=()=>{h(z),n=null}}}const{proxy:v}=T();return x(()=>v.$q.lang.rtl,u),F(()=>{r=v.$el.parentNode,g()}),k(()=>{n!==null&&n(),s()}),Object.assign(v,{trigger:l,getPosition:()=>i}),X}}),pe=$({name:"QLayout",props:{container:Boolean,view:{type:String,default:"hhh lpr fff",validator:e=>/^(h|l)h(h|r) lpr (f|l)f(f|r)$/.test(e.toLowerCase())},onScroll:Function,onScrollHeight:Function,onResize:Function},setup(e,{slots:m,emit:i}){const{proxy:{$q:n}}=T(),o=w(null),r=w(n.screen.height),u=w(e.container===!0?0:n.screen.width),g=w({position:0,direction:"down",inflectionPoint:0}),s=w(0),l=w(N.value===!0?0:O()),v=d(()=>"q-layout q-layout--"+(e.container===!0?"containerized":"standard")),b=d(()=>e.container===!1?{minHeight:n.screen.height+"px"}:null),z=d(()=>l.value!==0?{[n.lang.rtl===!0?"left":"right"]:`${l.value}px`}:null),h=d(()=>l.value!==0?{[n.lang.rtl===!0?"right":"left"]:0,[n.lang.rtl===!0?"left":"right"]:`-${l.value}px`,width:`calc(100% + ${l.value}px)`}:null);function q(a){if(e.container===!0||document.qScrollPrevented!==!0){const t={position:a.position.top,direction:a.direction,directionChanged:a.directionChanged,inflectionPoint:a.inflectionPoint.top,delta:a.delta.top};g.value=t,e.onScroll!==void 0&&i("scroll",t)}}function p(a){const{height:t,width:c}=a;let _=!1;r.value!==t&&(_=!0,r.value=t,e.onScrollHeight!==void 0&&i("scrollHeight",t),Q()),u.value!==c&&(_=!0,u.value=c),_===!0&&e.onResize!==void 0&&i("resize",a)}function S({height:a}){s.value!==a&&(s.value=a,Q())}function Q(){if(e.container===!0){const a=r.value>s.value?O():0;l.value!==a&&(l.value=a)}}let C=null;const R={instances:{},view:d(()=>e.view),isContainer:d(()=>e.container),rootRef:o,height:r,containerHeight:s,scrollbarWidth:l,totalWidth:d(()=>u.value+l.value),rows:d(()=>{const a=e.view.toLowerCase().split(" ");return{top:a[0].split(""),middle:a[1].split(""),bottom:a[2].split("")}}),header:H({size:0,offset:0,space:!1}),right:H({size:300,offset:0,space:!1}),footer:H({size:0,offset:0,space:!1}),left:H({size:300,offset:0,space:!1}),scroll:g,animate(){C!==null?clearTimeout(C):document.body.classList.add("q-body--layout-animate"),C=setTimeout(()=>{C=null,document.body.classList.remove("q-body--layout-animate")},155)},update(a,t,c){R[a][t]=c}};if(G(M,R),O()>0){let c=function(){a=null,t.classList.remove("hide-scrollbar")},_=function(){if(a===null){if(t.scrollHeight>n.screen.height)return;t.classList.add("hide-scrollbar")}else clearTimeout(a);a=setTimeout(c,300)},B=function(j){a!==null&&j==="remove"&&(clearTimeout(a),c()),window[`${j}EventListener`]("resize",_)},a=null;const t=document.body;x(()=>e.container!==!0?"add":"remove",B),e.container!==!0&&B("add"),ie(()=>{B("remove")})}return()=>{const a=Y(m.default,[f(be,{onScroll:q}),f(D,{onResize:p})]),t=f("div",{class:v.value,style:b.value,ref:e.container===!0?void 0:o,tabindex:-1},a);return e.container===!0?f("div",{class:"q-layout-container overflow-hidden",ref:o},[f(D,{onResize:S}),f("div",{class:"absolute-full",style:z.value},[f("div",{class:"scroll",style:h.value},[t])])]):t}}});const we=le({name:"MainLayout",data(){return{tab:"Inicio",tabs:[{name:"Inicio",label:"Inicio",to:"/"},{name:"Sucursales",label:"Sucursales",to:"/sucursales"}],leftDrawerOpen:!1,search:""}}}),ye={class:"text-center bg-white"},xe=W("div",{class:"text-h5"},"Farmacia Santidad Divina",-1),ze=W("div",{class:"text-caption"},"\xA9 2024",-1);function Se(e,m,i,n,o,r){const u=re("router-view");return se(),ue(pe,{view:"lHh Lpr lff"},{default:P(()=>[y(ce,null,{default:P(()=>[y(u)]),_:1}),y(me,null,{default:P(()=>[W("div",ye,[y(V,{class:"q-ma-xs",dense:"",color:"primary",icon:"fa-brands fa-facebook",href:"https://www.facebook.com/"}),y(V,{class:"q-ma-xs",dense:"",color:"red",icon:"fa-brands fa-instagram",href:"https://www.instagram.com/"}),y(V,{class:"q-ma-xs",dense:"",color:"green",icon:"fa-brands fa-whatsapp",href:"https://wa.me/"}),y(V,{class:"q-ma-xs",dense:"",color:"black",icon:"fa-brands fa-tiktok",href:"https://www.tiktok.com/"})]),y(ve,null,{default:P(()=>[xe,y(fe),ze]),_:1})]),_:1})]),_:1})}var $e=ae(we,[["render",Se]]);export{$e as default};