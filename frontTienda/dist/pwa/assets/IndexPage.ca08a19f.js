import{a3 as xe,a4 as Pe,a5 as j,x as _e,a6 as Ce,a7 as W,a8 as G,a9 as le,s as H,aa as Z,r as X,a as u,q as D,l as Y,y as Se,h as f,a1 as ke,ab as qe,d as ee,ac as we,c as te,ad as Be,ae as Ve,f as ge,o as he,af as ie,ag as pe,ah as ye,ai as $e,aj as Te,Z as Q,k as Ie,ak as Ne,al as oe,am as Ae,g as Fe,an as be,_ as Ee,S as $,T as J,U as R,V as T,W as z,X as re,Y as ue,$ as E,Q as se,ao as ce,ap as De,aq as de,ar as Me}from"./index.7b78a5ca.js";import{Q as Le}from"./QImg.cf0de9a2.js";import{Q as ze}from"./QPage.92833ee2.js";const ne={left:!0,right:!0,up:!0,down:!0,horizontal:!0,vertical:!0},Qe=Object.keys(ne);ne.all=!0;function ve(e){const n={};for(const r of Qe)e[r]===!0&&(n[r]=!0);return Object.keys(n).length===0?ne:(n.horizontal===!0?n.left=n.right=!0:n.left===!0&&n.right===!0&&(n.horizontal=!0),n.vertical===!0?n.up=n.down=!0:n.up===!0&&n.down===!0&&(n.vertical=!0),n.horizontal===!0&&n.vertical===!0&&(n.all=!0),n)}const Oe=["INPUT","TEXTAREA"];function fe(e,n){return n.event===void 0&&e.target!==void 0&&e.target.draggable!==!0&&typeof n.handler=="function"&&Oe.includes(e.target.nodeName.toUpperCase())===!1&&(e.qClonedBy===void 0||e.qClonedBy.indexOf(n.uid)===-1)}function je(){if(window.getSelection!==void 0){const e=window.getSelection();e.empty!==void 0?e.empty():e.removeAllRanges!==void 0&&(e.removeAllRanges(),xe.is.mobile!==!0&&e.addRange(document.createRange()))}else document.selection!==void 0&&document.selection.empty()}function Re(e){const n=[.06,6,50];return typeof e=="string"&&e.length&&e.split(":").forEach((r,c)=>{const o=parseFloat(r);o&&(n[c]=o)}),n}var Ue=Pe({name:"touch-swipe",beforeMount(e,{value:n,arg:r,modifiers:c}){if(c.mouse!==!0&&j.has.touch!==!0)return;const o=c.mouseCapture===!0?"Capture":"",t={handler:n,sensitivity:Re(r),direction:ve(c),noop:_e,mouseStart(l){fe(l,t)&&Ce(l)&&(W(t,"temp",[[document,"mousemove","move",`notPassive${o}`],[document,"mouseup","end","notPassiveCapture"]]),t.start(l,!0))},touchStart(l){if(fe(l,t)){const s=l.target;W(t,"temp",[[s,"touchmove","move","notPassiveCapture"],[s,"touchcancel","end","notPassiveCapture"],[s,"touchend","end","notPassiveCapture"]]),t.start(l)}},start(l,s){j.is.firefox===!0&&G(e,!0);const p=le(l);t.event={x:p.left,y:p.top,time:Date.now(),mouse:s===!0,dir:!1}},move(l){if(t.event===void 0)return;if(t.event.dir!==!1){H(l);return}const s=Date.now()-t.event.time;if(s===0)return;const p=le(l),x=p.left-t.event.x,d=Math.abs(x),y=p.top-t.event.y,v=Math.abs(y);if(t.event.mouse!==!0){if(d<t.sensitivity[1]&&v<t.sensitivity[1]){t.end(l);return}}else if(window.getSelection().toString()!==""){t.end(l);return}else if(d<t.sensitivity[2]&&v<t.sensitivity[2])return;const g=d/s,b=v/s;t.direction.vertical===!0&&d<v&&d<100&&b>t.sensitivity[0]&&(t.event.dir=y<0?"up":"down"),t.direction.horizontal===!0&&d>v&&v<100&&g>t.sensitivity[0]&&(t.event.dir=x<0?"left":"right"),t.direction.up===!0&&d<v&&y<0&&d<100&&b>t.sensitivity[0]&&(t.event.dir="up"),t.direction.down===!0&&d<v&&y>0&&d<100&&b>t.sensitivity[0]&&(t.event.dir="down"),t.direction.left===!0&&d>v&&x<0&&v<100&&g>t.sensitivity[0]&&(t.event.dir="left"),t.direction.right===!0&&d>v&&x>0&&v<100&&g>t.sensitivity[0]&&(t.event.dir="right"),t.event.dir!==!1?(H(l),t.event.mouse===!0&&(document.body.classList.add("no-pointer-events--children"),document.body.classList.add("non-selectable"),je(),t.styleCleanup=I=>{t.styleCleanup=void 0,document.body.classList.remove("non-selectable");const C=()=>{document.body.classList.remove("no-pointer-events--children")};I===!0?setTimeout(C,50):C()}),t.handler({evt:l,touch:t.event.mouse!==!0,mouse:t.event.mouse,direction:t.event.dir,duration:s,distance:{x:d,y:v}})):t.end(l)},end(l){t.event!==void 0&&(Z(t,"temp"),j.is.firefox===!0&&G(e,!1),t.styleCleanup!==void 0&&t.styleCleanup(!0),l!==void 0&&t.event.dir!==!1&&H(l),t.event=void 0)}};if(e.__qtouchswipe=t,c.mouse===!0){const l=c.mouseCapture===!0||c.mousecapture===!0?"Capture":"";W(t,"main",[[e,"mousedown","mouseStart",`passive${l}`]])}j.has.touch===!0&&W(t,"main",[[e,"touchstart","touchStart",`passive${c.capture===!0?"Capture":""}`],[e,"touchmove","noop","notPassiveCapture"]])},updated(e,n){const r=e.__qtouchswipe;r!==void 0&&(n.oldValue!==n.value&&(typeof n.value!="function"&&r.end(),r.handler=n.value),r.direction=ve(n.modifiers))},beforeUnmount(e){const n=e.__qtouchswipe;n!==void 0&&(Z(n,"main"),Z(n,"temp"),j.is.firefox===!0&&G(e,!1),n.styleCleanup!==void 0&&n.styleCleanup(),delete e.__qtouchswipe)}});function We(){const e=new Map;return{getCache:function(n,r){return e[n]===void 0?e[n]=r:e[n]},getCacheWithFn:function(n,r){return e[n]===void 0?e[n]=r():e[n]}}}const Ke={name:{required:!0},disable:Boolean},me={setup(e,{slots:n}){return()=>f("div",{class:"q-panel scroll",role:"tabpanel"},ee(n.default))}},Xe={modelValue:{required:!0},animated:Boolean,infinite:Boolean,swipeable:Boolean,vertical:Boolean,transitionPrev:String,transitionNext:String,transitionDuration:{type:[String,Number],default:300},keepAlive:Boolean,keepAliveInclude:[String,Array,RegExp],keepAliveExclude:[String,Array,RegExp],keepAliveMax:Number},Ye=["update:modelValue","beforeTransition","transition"];function Ge(){const{props:e,emit:n,proxy:r}=Y(),{getCacheWithFn:c}=We();let o,t;const l=X(null),s=X(null);function p(a){const i=e.vertical===!0?"up":"left";S((r.$q.lang.rtl===!0?-1:1)*(a.direction===i?1:-1))}const x=u(()=>[[Ue,p,void 0,{horizontal:e.vertical!==!0,vertical:e.vertical,mouse:!0}]]),d=u(()=>e.transitionPrev||`slide-${e.vertical===!0?"down":"right"}`),y=u(()=>e.transitionNext||`slide-${e.vertical===!0?"up":"left"}`),v=u(()=>`--q-transition-duration: ${e.transitionDuration}ms`),g=u(()=>typeof e.modelValue=="string"||typeof e.modelValue=="number"?e.modelValue:String(e.modelValue)),b=u(()=>({include:e.keepAliveInclude,exclude:e.keepAliveExclude,max:e.keepAliveMax})),I=u(()=>e.keepAliveInclude!==void 0||e.keepAliveExclude!==void 0);D(()=>e.modelValue,(a,i)=>{const h=B(a)===!0?N(a):-1;t!==!0&&A(h===-1?0:h<N(i)?-1:1),l.value!==h&&(l.value=h,n("beforeTransition",a,i),Se(()=>{n("transition",a,i)}))});function C(){S(1)}function M(){S(-1)}function q(a){n("update:modelValue",a)}function B(a){return a!=null&&a!==""}function N(a){return o.findIndex(i=>i.props.name===a&&i.props.disable!==""&&i.props.disable!==!0)}function L(){return o.filter(a=>a.props.disable!==""&&a.props.disable!==!0)}function A(a){const i=a!==0&&e.animated===!0&&l.value!==-1?"q-transition--"+(a===-1?d.value:y.value):null;s.value!==i&&(s.value=i)}function S(a,i=l.value){let h=i+a;for(;h>-1&&h<o.length;){const _=o[h];if(_!==void 0&&_.props.disable!==""&&_.props.disable!==!0){A(a),t=!0,n("update:modelValue",_.props.name),setTimeout(()=>{t=!1});return}h+=a}e.infinite===!0&&o.length!==0&&i!==-1&&i!==o.length&&S(a,a===-1?o.length:-1)}function k(){const a=N(e.modelValue);return l.value!==a&&(l.value=a),!0}function F(){const a=B(e.modelValue)===!0&&k()&&o[l.value];return e.keepAlive===!0?[f(we,b.value,[f(I.value===!0?c(g.value,()=>({...me,name:g.value})):me,{key:g.value,style:v.value},()=>a)])]:[f("div",{class:"q-panel scroll",style:v.value,key:g.value,role:"tabpanel"},[a])]}function m(){if(o.length!==0)return e.animated===!0?[f(ke,{name:s.value},F)]:F()}function V(a){return o=qe(ee(a.default,[])).filter(i=>i.props!==null&&i.props.slot===void 0&&B(i.props.name)===!0),o.length}function P(){return o}return Object.assign(r,{next:C,previous:M,goTo:q}),{panelIndex:l,panelDirectives:x,updatePanelsList:V,updatePanelIndex:k,getPanelContent:m,getEnabledPanels:L,getPanels:P,isValidPanelName:B,keepAliveProps:b,needsUniqueKeepAliveWrapper:I,goToPanelByOffset:S,goToPanel:q,nextPanel:C,previousPanel:M}}var He=te({name:"QCarouselSlide",props:{...Ke,imgSrc:String},setup(e,{slots:n}){const r=u(()=>e.imgSrc?{backgroundImage:`url("${e.imgSrc}")`}:{});return()=>f("div",{class:"q-carousel__slide",style:r.value},ee(n.default))}});let U=0;const Ze={fullscreen:Boolean,noRouteFullscreenExit:Boolean},Je=["update:fullscreen","fullscreen"];function et(){const e=Y(),{props:n,emit:r,proxy:c}=e;let o,t,l;const s=X(!1);Be(e)===!0&&D(()=>c.$route.fullPath,()=>{n.noRouteFullscreenExit!==!0&&d()}),D(()=>n.fullscreen,y=>{s.value!==y&&p()}),D(s,y=>{r("update:fullscreen",y),r("fullscreen",y)});function p(){s.value===!0?d():x()}function x(){s.value!==!0&&(s.value=!0,l=c.$el.parentNode,l.replaceChild(t,c.$el),document.body.appendChild(c.$el),U++,U===1&&document.body.classList.add("q-body--fullscreen-mixin"),o={handler:d},ie.add(o))}function d(){s.value===!0&&(o!==void 0&&(ie.remove(o),o=void 0),l.replaceChild(c.$el,t),s.value=!1,U=Math.max(0,U-1),U===0&&(document.body.classList.remove("q-body--fullscreen-mixin"),c.$el.scrollIntoView!==void 0&&setTimeout(()=>{c.$el.scrollIntoView()})))}return Ve(()=>{t=document.createElement("span")}),ge(()=>{n.fullscreen===!0&&x()}),he(d),Object.assign(c,{toggleFullscreen:p,setFullscreen:x,exitFullscreen:d}),{inFullscreen:s,toggleFullscreen:p}}const tt=["top","right","bottom","left"],nt=["regular","flat","outline","push","unelevated"];var at=te({name:"QCarousel",props:{...pe,...Xe,...Ze,transitionPrev:{type:String,default:"fade"},transitionNext:{type:String,default:"fade"},height:String,padding:Boolean,controlColor:String,controlTextColor:String,controlType:{type:String,validator:e=>nt.includes(e),default:"flat"},autoplay:[Number,Boolean],arrows:Boolean,prevIcon:String,nextIcon:String,navigation:Boolean,navigationPosition:{type:String,validator:e=>tt.includes(e)},navigationIcon:String,navigationActiveIcon:String,thumbnails:Boolean},emits:[...Je,...Ye],setup(e,{slots:n}){const{proxy:{$q:r}}=Y(),c=ye(e,r);let o=null,t;const{updatePanelsList:l,getPanelContent:s,panelDirectives:p,goToPanel:x,previousPanel:d,nextPanel:y,getEnabledPanels:v,panelIndex:g}=Ge(),{inFullscreen:b}=et(),I=u(()=>b.value!==!0&&e.height!==void 0?{height:e.height}:{}),C=u(()=>e.vertical===!0?"vertical":"horizontal"),M=u(()=>`q-carousel q-panel-parent q-carousel--with${e.padding===!0?"":"out"}-padding`+(b.value===!0?" fullscreen":"")+(c.value===!0?" q-carousel--dark q-dark":"")+(e.arrows===!0?` q-carousel--arrows-${C.value}`:"")+(e.navigation===!0?` q-carousel--navigation-${L.value}`:"")),q=u(()=>{const m=[e.prevIcon||r.iconSet.carousel[e.vertical===!0?"up":"left"],e.nextIcon||r.iconSet.carousel[e.vertical===!0?"down":"right"]];return e.vertical===!1&&r.lang.rtl===!0?m.reverse():m}),B=u(()=>e.navigationIcon||r.iconSet.carousel.navigationIcon),N=u(()=>e.navigationActiveIcon||B.value),L=u(()=>e.navigationPosition||(e.vertical===!0?"right":"bottom")),A=u(()=>({color:e.controlColor,textColor:e.controlTextColor,round:!0,[e.controlType]:!0,dense:!0}));D(()=>e.modelValue,()=>{e.autoplay&&S()}),D(()=>e.autoplay,m=>{m?S():o!==null&&(clearTimeout(o),o=null)});function S(){const m=Te(e.autoplay)===!0?Math.abs(e.autoplay):5e3;o!==null&&clearTimeout(o),o=setTimeout(()=>{o=null,m>=0?y():d()},m)}ge(()=>{e.autoplay&&S()}),he(()=>{o!==null&&clearTimeout(o)});function k(m,V){return f("div",{class:`q-carousel__control q-carousel__navigation no-wrap absolute flex q-carousel__navigation--${m} q-carousel__navigation--${L.value}`+(e.controlColor!==void 0?` text-${e.controlColor}`:"")},[f("div",{class:"q-carousel__navigation-inner flex flex-center no-wrap"},v().map(V))])}function F(){const m=[];if(e.navigation===!0){const V=n["navigation-icon"]!==void 0?n["navigation-icon"]:a=>f(Q,{key:"nav"+a.name,class:`q-carousel__navigation-icon q-carousel__navigation-icon--${a.active===!0?"":"in"}active`,...a.btnProps,onClick:a.onClick}),P=t-1;m.push(k("buttons",(a,i)=>{const h=a.props.name,_=g.value===i;return V({index:i,maxIndex:P,name:h,active:_,btnProps:{icon:_===!0?N.value:B.value,size:"sm",...A.value},onClick:()=>{x(h)}})}))}else if(e.thumbnails===!0){const V=e.controlColor!==void 0?` text-${e.controlColor}`:"";m.push(k("thumbnails",P=>{const a=P.props;return f("img",{key:"tmb#"+a.name,class:`q-carousel__thumbnail q-carousel__thumbnail--${a.name===e.modelValue?"":"in"}active`+V,src:a.imgSrc||a["img-src"],onClick:()=>{x(a.name)}})}))}return e.arrows===!0&&g.value>=0&&((e.infinite===!0||g.value>0)&&m.push(f("div",{key:"prev",class:`q-carousel__control q-carousel__arrow q-carousel__prev-arrow q-carousel__prev-arrow--${C.value} absolute flex flex-center`},[f(Q,{icon:q.value[0],...A.value,onClick:d})])),(e.infinite===!0||g.value<t-1)&&m.push(f("div",{key:"next",class:`q-carousel__control q-carousel__arrow q-carousel__next-arrow q-carousel__next-arrow--${C.value} absolute flex flex-center`},[f(Q,{icon:q.value[1],...A.value,onClick:y})]))),Ie(n.control,m)}return()=>(t=l(n),f("div",{class:M.value,style:I.value},[$e("div",{class:"q-carousel__slides-container"},s(),"sl-cont",e.swipeable,()=>p.value)].concat(F())))}});function lt(e,n,r){return r<=n?n:Math.min(r,Math.max(n,e))}function K(e,n){return[!0,!1].includes(e)?e:n}var it=te({name:"QPagination",props:{...pe,modelValue:{type:Number,required:!0},min:{type:[Number,String],default:1},max:{type:[Number,String],required:!0},maxPages:{type:[Number,String],default:0,validator:e=>(typeof e=="string"?parseInt(e,10):e)>=0},inputStyle:[Array,String,Object],inputClass:[Array,String,Object],size:String,disable:Boolean,input:Boolean,iconPrev:String,iconNext:String,iconFirst:String,iconLast:String,toFn:Function,boundaryLinks:{type:Boolean,default:null},boundaryNumbers:{type:Boolean,default:null},directionLinks:{type:Boolean,default:null},ellipses:{type:Boolean,default:null},ripple:{type:[Boolean,Object],default:null},round:Boolean,rounded:Boolean,flat:Boolean,outline:Boolean,unelevated:Boolean,push:Boolean,glossy:Boolean,color:{type:String,default:"primary"},textColor:String,activeDesign:{type:String,default:"",values:e=>e===""||Ne.includes(e)},activeColor:String,activeTextColor:String,gutter:String,padding:{type:String,default:"3px 2px"}},emits:["update:modelValue"],setup(e,{emit:n}){const{proxy:r}=Y(),{$q:c}=r,o=ye(e,c),t=u(()=>parseInt(e.min,10)),l=u(()=>parseInt(e.max,10)),s=u(()=>parseInt(e.maxPages,10)),p=u(()=>b.value+" / "+l.value),x=u(()=>K(e.boundaryLinks,e.input)),d=u(()=>K(e.boundaryNumbers,!e.input)),y=u(()=>K(e.directionLinks,e.input)),v=u(()=>K(e.ellipses,!e.input)),g=X(null),b=u({get:()=>e.modelValue,set:a=>{if(a=parseInt(a,10),e.disable||isNaN(a))return;const i=lt(a,t.value,l.value);e.modelValue!==i&&n("update:modelValue",i)}});D(()=>`${t.value}|${l.value}`,()=>{b.value=e.modelValue});const I=u(()=>"q-pagination row no-wrap items-center"+(e.disable===!0?" disabled":"")),C=u(()=>e.gutter in oe?`${oe[e.gutter]}px`:e.gutter||null),M=u(()=>C.value!==null?`--q-pagination-gutter-parent:-${C.value};--q-pagination-gutter-child:${C.value}`:null),q=u(()=>{const a=[e.iconFirst||c.iconSet.pagination.first,e.iconPrev||c.iconSet.pagination.prev,e.iconNext||c.iconSet.pagination.next,e.iconLast||c.iconSet.pagination.last];return c.lang.rtl===!0?a.reverse():a}),B=u(()=>({"aria-disabled":e.disable===!0?"true":"false",role:"navigation"})),N=u(()=>Ae(e,"flat")),L=u(()=>({[N.value]:!0,round:e.round,rounded:e.rounded,padding:e.padding,color:e.color,textColor:e.textColor,size:e.size,ripple:e.ripple!==null?e.ripple:!0})),A=u(()=>{const a={[N.value]:!1};return e.activeDesign!==""&&(a[e.activeDesign]=!0),a}),S=u(()=>({...A.value,color:e.activeColor||e.color,textColor:e.activeTextColor||e.textColor})),k=u(()=>{let a=Math.max(s.value,1+(v.value?2:0)+(d.value?2:0));const i={pgFrom:t.value,pgTo:l.value,ellipsesStart:!1,ellipsesEnd:!1,boundaryStart:!1,boundaryEnd:!1,marginalStyle:{minWidth:`${Math.max(2,String(l.value).length)}em`}};return s.value&&a<l.value-t.value+1&&(a=1+Math.floor(a/2)*2,i.pgFrom=Math.max(t.value,Math.min(l.value-a+1,e.modelValue-Math.floor(a/2))),i.pgTo=Math.min(l.value,i.pgFrom+a-1),d.value&&(i.boundaryStart=!0,i.pgFrom++),v.value&&i.pgFrom>t.value+(d.value?1:0)&&(i.ellipsesStart=!0,i.pgFrom++),d.value&&(i.boundaryEnd=!0,i.pgTo--),v.value&&i.pgTo<l.value-(d.value?1:0)&&(i.ellipsesEnd=!0,i.pgTo--)),i});function F(a){b.value=a}function m(a){b.value=b.value+a}const V=u(()=>{function a(){b.value=g.value,g.value=null}return{"onUpdate:modelValue":i=>{g.value=i},onKeyup:i=>{Fe(i,13)===!0&&a()},onBlur:a}});function P(a,i,h){const _={"aria-label":i,"aria-current":"false",...L.value,...a};return h===!0&&Object.assign(_,{"aria-current":"true",...S.value}),i!==void 0&&(e.toFn!==void 0?_.to=e.toFn(i):_.onClick=()=>{F(i)}),f(Q,_)}return Object.assign(r,{set:F,setByOffset:m}),()=>{const a=[],i=[];let h;if(x.value===!0&&(a.push(P({key:"bls",disable:e.disable||e.modelValue<=t.value,icon:q.value[0]},t.value)),i.unshift(P({key:"ble",disable:e.disable||e.modelValue>=l.value,icon:q.value[3]},l.value))),y.value===!0&&(a.push(P({key:"bdp",disable:e.disable||e.modelValue<=t.value,icon:q.value[1]},e.modelValue-1)),i.unshift(P({key:"bdn",disable:e.disable||e.modelValue>=l.value,icon:q.value[2]},e.modelValue+1))),e.input!==!0){h=[];const{pgFrom:_,pgTo:ae,marginalStyle:O}=k.value;if(k.value.boundaryStart===!0){const w=t.value===e.modelValue;a.push(P({key:"bns",style:O,disable:e.disable,label:t.value},t.value,w))}if(k.value.boundaryEnd===!0){const w=l.value===e.modelValue;i.unshift(P({key:"bne",style:O,disable:e.disable,label:l.value},l.value,w))}k.value.ellipsesStart===!0&&a.push(P({key:"bes",style:O,disable:e.disable,label:"\u2026",ripple:!1},_-1)),k.value.ellipsesEnd===!0&&i.unshift(P({key:"bee",style:O,disable:e.disable,label:"\u2026",ripple:!1},ae+1));for(let w=_;w<=ae;w++)h.push(P({key:`bpg${w}`,style:O,disable:e.disable,label:w},w,w===e.modelValue))}return f("div",{class:I.value,...B.value},[f("div",{class:"q-pagination__content row no-wrap items-center",style:M.value},[...a,e.input===!0?f(be,{class:"inline",style:{width:`${p.value.length/1.5}em`},type:"number",dense:!0,value:g.value,disable:e.disable,dark:o.value,borderless:!0,inputClass:e.inputClass,inputStyle:e.inputStyle,placeholder:p.value,min:t.value,max:l.value,...V.value}):f("div",{class:"q-pagination__middle row justify-center"},h),...i])])}}});const ot={name:"IndexPage",data(){return{slide:1,carousels:[],products:[],search:"",loading:!1,currentPage:1,totalPages:1}},created(){this.carouselsGet(),this.buscar()},methods:{clickDetalleProducto(e){this.$router.push("/detalle-producto/"+e.id)},buscar(){this.loading=!0,this.$axios.get("productos",{params:{search:this.search,page:this.currentPage}}).then(e=>{this.products=e.data.data,this.totalPages=e.data.last_page}).finally(()=>{this.loading=!1})},carouselsGet(){this.$axios.get("carousels").then(e=>{this.carousels=e.data}).finally(()=>{})}}},rt={class:"row q-pa-xs"},ut={class:"col-12"},st={class:"text-h6 text-center text-bold no-select flex flex-center"},ct={key:0,class:"col-12 flex flex-center"},dt={class:"col-12"},vt={key:0,class:"row cursor-pointer"},ft={class:"text-center text-bold",style:{"line-height":"1","font-size":"14px",height:"40px"}},mt={class:"text-left bg-white",style:{"font-size":"13px"}},gt={key:1,class:"text-center"},ht=E("div",{class:"text-h6"},"No hay productos",-1);function pt(e,n,r,c,o,t){return $(),J(ze,null,{default:R(()=>[T(at,{animated:"",modelValue:o.slide,"onUpdate:modelValue":n[0]||(n[0]=l=>o.slide=l),arrows:"",navigation:"","navigation-icon":"radio_button_unchecked","control-text-color":"primary",autoplay:"",infinite:""},{default:R(()=>[($(!0),z(ue,null,re(o.carousels,(l,s)=>($(),J(He,{name:s++,key:s++,class:"cursor-pointer q-pa-xs","img-src":e.$q.screen.lt.md?`${e.$url}../images/${l.imageResponsive}`:`${e.$url}../images/${l.image}`},null,8,["name","img-src"]))),128))]),_:1},8,["modelValue"]),E("div",rt,[E("div",ut,[E("div",st,[T(be,{modelValue:o.search,"onUpdate:modelValue":n[1]||(n[1]=l=>o.search=l),dense:"",style:{width:"300px","min-width":"300px"},outlined:"","bg-color":"grey-4",rounded:"",placeholder:"Buscar producto",class:"q-ml-sm"},{prepend:R(()=>[T(se,{name:"search"})]),_:1},8,["modelValue"]),T(Q,{rounded:"",onClick:t.buscar,color:"primary",label:"Buscar",loading:o.loading,"no-caps":""},null,8,["onClick","loading"])])]),o.totalPages>1?($(),z("div",ct,[o.totalPages>1?($(),J(it,{key:0,rounded:"",modelValue:o.currentPage,"onUpdate:modelValue":[n[2]||(n[2]=l=>o.currentPage=l),t.buscar],max:o.totalPages,"max-pages":6,"boundary-numbers":"",ellipses:""},null,8,["modelValue","max","onUpdate:modelValue"])):ce("",!0)])):ce("",!0),E("div",dt,[o.products.length>0?($(),z("div",vt,[($(!0),z(ue,null,re(o.products,l=>($(),z("div",{class:"col-4 col-md-2 q-px-xs q-py-xs",key:l.id},[T(De,{onClick:s=>t.clickDetalleProducto(l),flat:"",bordered:"",style:{border:"2px solid #00BD73"}},{default:R(()=>[T(Le,{src:l.imagen.includes("http")?l.imagen:`${e.$url}../images/${l.imagen}`,width:"100%",height:"100px"},null,8,["src"]),E("div",ft,de(l.nombre),1),T(Me,{class:"q-pa-none q-ma-none"},{default:R(()=>[E("div",mt,"Bs. "+de(l.precio),1),T(Q,{onClick:s=>t.clickDetalleProducto(l),label:"A\xF1adir al carrito",icon:"add_shopping_cart",class:"full-width",color:"green",size:"10px","no-caps":"",dense:""},null,8,["onClick"])]),_:2},1024)]),_:2},1032,["onClick"])]))),128))])):($(),z("div",gt,[T(se,{name:"sentiment_very_dissatisfied",size:"100px",color:"grey-5"}),ht]))])])]),_:1})}var Pt=Ee(ot,[["render",pt]]);export{Pt as default};
