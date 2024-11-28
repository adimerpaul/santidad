import{a as ue,Q as _e}from"./QImg.6ff68ae2.js";import{a2 as Pe,a3 as Ce,a4 as U,n as Se,a5 as ke,a6 as G,a7 as Z,a8 as ce,a0 as ee,a9 as te,r as H,a as s,w as L,g as J,q as we,h as g,U as qe,aa as Be,f as oe,ab as $e,c as ie,ac as Ie,ad as Ve,o as he,m as pe,ae as de,W as be,Y as ye,af as Te,ag as Ne,Q as j,t as Ae,ah as Ee,ai as ve,aj as Fe,$ as Me,ak as xe,_ as De,D as k,E as W,F,I as f,L as M,M as ne,O as ae,G as T,al as Le,am as Oe,H as le,J as Qe,S as je,T as X,K as ze,an as Ue,ao as Re}from"./index.8f4629fe.js";import{Q as Ke}from"./QPage.7545bbbf.js";const re={left:!0,right:!0,up:!0,down:!0,horizontal:!0,vertical:!0},Ge=Object.keys(re);re.all=!0;function fe(e){const t={};for(const r of Ge)e[r]===!0&&(t[r]=!0);return Object.keys(t).length===0?re:(t.horizontal===!0?t.left=t.right=!0:t.left===!0&&t.right===!0&&(t.horizontal=!0),t.vertical===!0?t.up=t.down=!0:t.up===!0&&t.down===!0&&(t.vertical=!0),t.horizontal===!0&&t.vertical===!0&&(t.all=!0),t)}const We=["INPUT","TEXTAREA"];function ge(e,t){return t.event===void 0&&e.target!==void 0&&e.target.draggable!==!0&&typeof t.handler=="function"&&We.includes(e.target.nodeName.toUpperCase())===!1&&(e.qClonedBy===void 0||e.qClonedBy.indexOf(t.uid)===-1)}function Xe(){if(window.getSelection!==void 0){const e=window.getSelection();e.empty!==void 0?e.empty():e.removeAllRanges!==void 0&&(e.removeAllRanges(),Pe.is.mobile!==!0&&e.addRange(document.createRange()))}else document.selection!==void 0&&document.selection.empty()}function Ye(e){const t=[.06,6,50];return typeof e=="string"&&e.length&&e.split(":").forEach((r,c)=>{const i=parseFloat(r);i&&(t[c]=i)}),t}var He=Ce({name:"touch-swipe",beforeMount(e,{value:t,arg:r,modifiers:c}){if(c.mouse!==!0&&U.has.touch!==!0)return;const i=c.mouseCapture===!0?"Capture":"",n={handler:t,sensitivity:Ye(r),direction:fe(c),noop:Se,mouseStart(a){ge(a,n)&&ke(a)&&(G(n,"temp",[[document,"mousemove","move",`notPassive${i}`],[document,"mouseup","end","notPassiveCapture"]]),n.start(a,!0))},touchStart(a){if(ge(a,n)){const u=a.target;G(n,"temp",[[u,"touchmove","move","notPassiveCapture"],[u,"touchcancel","end","notPassiveCapture"],[u,"touchend","end","notPassiveCapture"]]),n.start(a)}},start(a,u){U.is.firefox===!0&&Z(e,!0);const b=ce(a);n.event={x:b.left,y:b.top,time:Date.now(),mouse:u===!0,dir:!1}},move(a){if(n.event===void 0)return;if(n.event.dir!==!1){ee(a);return}const u=Date.now()-n.event.time;if(u===0)return;const b=ce(a),_=b.left-n.event.x,d=Math.abs(_),y=b.top-n.event.y,v=Math.abs(y);if(n.event.mouse!==!0){if(d<n.sensitivity[1]&&v<n.sensitivity[1]){n.end(a);return}}else if(window.getSelection().toString()!==""){n.end(a);return}else if(d<n.sensitivity[2]&&v<n.sensitivity[2])return;const h=d/u,x=v/u;n.direction.vertical===!0&&d<v&&d<100&&x>n.sensitivity[0]&&(n.event.dir=y<0?"up":"down"),n.direction.horizontal===!0&&d>v&&v<100&&h>n.sensitivity[0]&&(n.event.dir=_<0?"left":"right"),n.direction.up===!0&&d<v&&y<0&&d<100&&x>n.sensitivity[0]&&(n.event.dir="up"),n.direction.down===!0&&d<v&&y>0&&d<100&&x>n.sensitivity[0]&&(n.event.dir="down"),n.direction.left===!0&&d>v&&_<0&&v<100&&h>n.sensitivity[0]&&(n.event.dir="left"),n.direction.right===!0&&d>v&&_>0&&v<100&&h>n.sensitivity[0]&&(n.event.dir="right"),n.event.dir!==!1?(ee(a),n.event.mouse===!0&&(document.body.classList.add("no-pointer-events--children"),document.body.classList.add("non-selectable"),Xe(),n.styleCleanup=N=>{n.styleCleanup=void 0,document.body.classList.remove("non-selectable");const S=()=>{document.body.classList.remove("no-pointer-events--children")};N===!0?setTimeout(S,50):S()}),n.handler({evt:a,touch:n.event.mouse!==!0,mouse:n.event.mouse,direction:n.event.dir,duration:u,distance:{x:d,y:v}})):n.end(a)},end(a){n.event!==void 0&&(te(n,"temp"),U.is.firefox===!0&&Z(e,!1),n.styleCleanup!==void 0&&n.styleCleanup(!0),a!==void 0&&n.event.dir!==!1&&ee(a),n.event=void 0)}};if(e.__qtouchswipe=n,c.mouse===!0){const a=c.mouseCapture===!0||c.mousecapture===!0?"Capture":"";G(n,"main",[[e,"mousedown","mouseStart",`passive${a}`]])}U.has.touch===!0&&G(n,"main",[[e,"touchstart","touchStart",`passive${c.capture===!0?"Capture":""}`],[e,"touchmove","noop","notPassiveCapture"]])},updated(e,t){const r=e.__qtouchswipe;r!==void 0&&(t.oldValue!==t.value&&(typeof t.value!="function"&&r.end(),r.handler=t.value),r.direction=fe(t.modifiers))},beforeUnmount(e){const t=e.__qtouchswipe;t!==void 0&&(te(t,"main"),te(t,"temp"),U.is.firefox===!0&&Z(e,!1),t.styleCleanup!==void 0&&t.styleCleanup(),delete e.__qtouchswipe)}});function Je(){const e=new Map;return{getCache:function(t,r){return e[t]===void 0?e[t]=r:e[t]},getCacheWithFn:function(t,r){return e[t]===void 0?e[t]=r():e[t]}}}const Ze={name:{required:!0},disable:Boolean},me={setup(e,{slots:t}){return()=>g("div",{class:"q-panel scroll",role:"tabpanel"},oe(t.default))}},et={modelValue:{required:!0},animated:Boolean,infinite:Boolean,swipeable:Boolean,vertical:Boolean,transitionPrev:String,transitionNext:String,transitionDuration:{type:[String,Number],default:300},keepAlive:Boolean,keepAliveInclude:[String,Array,RegExp],keepAliveExclude:[String,Array,RegExp],keepAliveMax:Number},tt=["update:modelValue","beforeTransition","transition"];function nt(){const{props:e,emit:t,proxy:r}=J(),{getCacheWithFn:c}=Je();let i,n;const a=H(null),u=H(null);function b(l){const o=e.vertical===!0?"up":"left";w((r.$q.lang.rtl===!0?-1:1)*(l.direction===o?1:-1))}const _=s(()=>[[He,b,void 0,{horizontal:e.vertical!==!0,vertical:e.vertical,mouse:!0}]]),d=s(()=>e.transitionPrev||`slide-${e.vertical===!0?"down":"right"}`),y=s(()=>e.transitionNext||`slide-${e.vertical===!0?"up":"left"}`),v=s(()=>`--q-transition-duration: ${e.transitionDuration}ms`),h=s(()=>typeof e.modelValue=="string"||typeof e.modelValue=="number"?e.modelValue:String(e.modelValue)),x=s(()=>({include:e.keepAliveInclude,exclude:e.keepAliveExclude,max:e.keepAliveMax})),N=s(()=>e.keepAliveInclude!==void 0||e.keepAliveExclude!==void 0);L(()=>e.modelValue,(l,o)=>{const p=I(l)===!0?A(l):-1;n!==!0&&E(p===-1?0:p<A(o)?-1:1),a.value!==p&&(a.value=p,t("beforeTransition",l,o),we(()=>{t("transition",l,o)}))});function S(){w(1)}function O(){w(-1)}function B(l){t("update:modelValue",l)}function I(l){return l!=null&&l!==""}function A(l){return i.findIndex(o=>o.props.name===l&&o.props.disable!==""&&o.props.disable!==!0)}function Q(){return i.filter(l=>l.props.disable!==""&&l.props.disable!==!0)}function E(l){const o=l!==0&&e.animated===!0&&a.value!==-1?"q-transition--"+(l===-1?d.value:y.value):null;u.value!==o&&(u.value=o)}function w(l,o=a.value){let p=o+l;for(;p>-1&&p<i.length;){const C=i[p];if(C!==void 0&&C.props.disable!==""&&C.props.disable!==!0){E(l),n=!0,t("update:modelValue",C.props.name),setTimeout(()=>{n=!1});return}p+=l}e.infinite===!0&&i.length!==0&&o!==-1&&o!==i.length&&w(l,l===-1?i.length:-1)}function q(){const l=A(e.modelValue);return a.value!==l&&(a.value=l),!0}function D(){const l=I(e.modelValue)===!0&&q()&&i[a.value];return e.keepAlive===!0?[g($e,x.value,[g(N.value===!0?c(h.value,()=>({...me,name:h.value})):me,{key:h.value,style:v.value},()=>l)])]:[g("div",{class:"q-panel scroll",style:v.value,key:h.value,role:"tabpanel"},[l])]}function m(){if(i.length!==0)return e.animated===!0?[g(qe,{name:u.value},D)]:D()}function V(l){return i=Be(oe(l.default,[])).filter(o=>o.props!==null&&o.props.slot===void 0&&I(o.props.name)===!0),i.length}function P(){return i}return Object.assign(r,{next:S,previous:O,goTo:B}),{panelIndex:a,panelDirectives:_,updatePanelsList:V,updatePanelIndex:q,getPanelContent:m,getEnabledPanels:Q,getPanels:P,isValidPanelName:I,keepAliveProps:x,needsUniqueKeepAliveWrapper:N,goToPanelByOffset:w,goToPanel:B,nextPanel:S,previousPanel:O}}var at=ie({name:"QCarouselSlide",props:{...Ze,imgSrc:String},setup(e,{slots:t}){const r=s(()=>e.imgSrc?{backgroundImage:`url("${e.imgSrc}")`}:{});return()=>g("div",{class:"q-carousel__slide",style:r.value},oe(t.default))}});let R=0;const lt={fullscreen:Boolean,noRouteFullscreenExit:Boolean},ot=["update:fullscreen","fullscreen"];function it(){const e=J(),{props:t,emit:r,proxy:c}=e;let i,n,a;const u=H(!1);Ie(e)===!0&&L(()=>c.$route.fullPath,()=>{t.noRouteFullscreenExit!==!0&&d()}),L(()=>t.fullscreen,y=>{u.value!==y&&b()}),L(u,y=>{r("update:fullscreen",y),r("fullscreen",y)});function b(){u.value===!0?d():_()}function _(){u.value!==!0&&(u.value=!0,a=c.$el.parentNode,a.replaceChild(n,c.$el),document.body.appendChild(c.$el),R++,R===1&&document.body.classList.add("q-body--fullscreen-mixin"),i={handler:d},de.add(i))}function d(){u.value===!0&&(i!==void 0&&(de.remove(i),i=void 0),a.replaceChild(c.$el,n),u.value=!1,R=Math.max(0,R-1),R===0&&(document.body.classList.remove("q-body--fullscreen-mixin"),c.$el.scrollIntoView!==void 0&&setTimeout(()=>{c.$el.scrollIntoView()})))}return Ve(()=>{n=document.createElement("span")}),he(()=>{t.fullscreen===!0&&_()}),pe(d),Object.assign(c,{toggleFullscreen:b,setFullscreen:_,exitFullscreen:d}),{inFullscreen:u,toggleFullscreen:b}}const rt=["top","right","bottom","left"],st=["regular","flat","outline","push","unelevated"];var ut=ie({name:"QCarousel",props:{...be,...et,...lt,transitionPrev:{type:String,default:"fade"},transitionNext:{type:String,default:"fade"},height:String,padding:Boolean,controlColor:String,controlTextColor:String,controlType:{type:String,validator:e=>st.includes(e),default:"flat"},autoplay:[Number,Boolean],arrows:Boolean,prevIcon:String,nextIcon:String,navigation:Boolean,navigationPosition:{type:String,validator:e=>rt.includes(e)},navigationIcon:String,navigationActiveIcon:String,thumbnails:Boolean},emits:[...ot,...tt],setup(e,{slots:t}){const{proxy:{$q:r}}=J(),c=ye(e,r);let i=null,n;const{updatePanelsList:a,getPanelContent:u,panelDirectives:b,goToPanel:_,previousPanel:d,nextPanel:y,getEnabledPanels:v,panelIndex:h}=nt(),{inFullscreen:x}=it(),N=s(()=>x.value!==!0&&e.height!==void 0?{height:e.height}:{}),S=s(()=>e.vertical===!0?"vertical":"horizontal"),O=s(()=>`q-carousel q-panel-parent q-carousel--with${e.padding===!0?"":"out"}-padding`+(x.value===!0?" fullscreen":"")+(c.value===!0?" q-carousel--dark q-dark":"")+(e.arrows===!0?` q-carousel--arrows-${S.value}`:"")+(e.navigation===!0?` q-carousel--navigation-${Q.value}`:"")),B=s(()=>{const m=[e.prevIcon||r.iconSet.carousel[e.vertical===!0?"up":"left"],e.nextIcon||r.iconSet.carousel[e.vertical===!0?"down":"right"]];return e.vertical===!1&&r.lang.rtl===!0?m.reverse():m}),I=s(()=>e.navigationIcon||r.iconSet.carousel.navigationIcon),A=s(()=>e.navigationActiveIcon||I.value),Q=s(()=>e.navigationPosition||(e.vertical===!0?"right":"bottom")),E=s(()=>({color:e.controlColor,textColor:e.controlTextColor,round:!0,[e.controlType]:!0,dense:!0}));L(()=>e.modelValue,()=>{e.autoplay&&w()}),L(()=>e.autoplay,m=>{m?w():i!==null&&(clearTimeout(i),i=null)});function w(){const m=Ne(e.autoplay)===!0?Math.abs(e.autoplay):5e3;i!==null&&clearTimeout(i),i=setTimeout(()=>{i=null,m>=0?y():d()},m)}he(()=>{e.autoplay&&w()}),pe(()=>{i!==null&&clearTimeout(i)});function q(m,V){return g("div",{class:`q-carousel__control q-carousel__navigation no-wrap absolute flex q-carousel__navigation--${m} q-carousel__navigation--${Q.value}`+(e.controlColor!==void 0?` text-${e.controlColor}`:"")},[g("div",{class:"q-carousel__navigation-inner flex flex-center no-wrap"},v().map(V))])}function D(){const m=[];if(e.navigation===!0){const V=t["navigation-icon"]!==void 0?t["navigation-icon"]:l=>g(j,{key:"nav"+l.name,class:`q-carousel__navigation-icon q-carousel__navigation-icon--${l.active===!0?"":"in"}active`,...l.btnProps,onClick:l.onClick}),P=n-1;m.push(q("buttons",(l,o)=>{const p=l.props.name,C=h.value===o;return V({index:o,maxIndex:P,name:p,active:C,btnProps:{icon:C===!0?A.value:I.value,size:"sm",...E.value},onClick:()=>{_(p)}})}))}else if(e.thumbnails===!0){const V=e.controlColor!==void 0?` text-${e.controlColor}`:"";m.push(q("thumbnails",P=>{const l=P.props;return g("img",{key:"tmb#"+l.name,class:`q-carousel__thumbnail q-carousel__thumbnail--${l.name===e.modelValue?"":"in"}active`+V,src:l.imgSrc||l["img-src"],onClick:()=>{_(l.name)}})}))}return e.arrows===!0&&h.value>=0&&((e.infinite===!0||h.value>0)&&m.push(g("div",{key:"prev",class:`q-carousel__control q-carousel__arrow q-carousel__prev-arrow q-carousel__prev-arrow--${S.value} absolute flex flex-center`},[g(j,{icon:B.value[0],...E.value,onClick:d})])),(e.infinite===!0||h.value<n-1)&&m.push(g("div",{key:"next",class:`q-carousel__control q-carousel__arrow q-carousel__next-arrow q-carousel__next-arrow--${S.value} absolute flex flex-center`},[g(j,{icon:B.value[1],...E.value,onClick:y})]))),Ae(t.control,m)}return()=>(n=a(t),g("div",{class:O.value,style:N.value},[Te("div",{class:"q-carousel__slides-container"},u(),"sl-cont",e.swipeable,()=>b.value)].concat(D())))}});function ct(e,t,r){return r<=t?t:Math.min(r,Math.max(t,e))}function Y(e,t){return[!0,!1].includes(e)?e:t}var dt=ie({name:"QPagination",props:{...be,modelValue:{type:Number,required:!0},min:{type:[Number,String],default:1},max:{type:[Number,String],required:!0},maxPages:{type:[Number,String],default:0,validator:e=>(typeof e=="string"?parseInt(e,10):e)>=0},inputStyle:[Array,String,Object],inputClass:[Array,String,Object],size:String,disable:Boolean,input:Boolean,iconPrev:String,iconNext:String,iconFirst:String,iconLast:String,toFn:Function,boundaryLinks:{type:Boolean,default:null},boundaryNumbers:{type:Boolean,default:null},directionLinks:{type:Boolean,default:null},ellipses:{type:Boolean,default:null},ripple:{type:[Boolean,Object],default:null},round:Boolean,rounded:Boolean,flat:Boolean,outline:Boolean,unelevated:Boolean,push:Boolean,glossy:Boolean,color:{type:String,default:"primary"},textColor:String,activeDesign:{type:String,default:"",values:e=>e===""||Ee.includes(e)},activeColor:String,activeTextColor:String,gutter:String,padding:{type:String,default:"3px 2px"}},emits:["update:modelValue"],setup(e,{emit:t}){const{proxy:r}=J(),{$q:c}=r,i=ye(e,c),n=s(()=>parseInt(e.min,10)),a=s(()=>parseInt(e.max,10)),u=s(()=>parseInt(e.maxPages,10)),b=s(()=>x.value+" / "+a.value),_=s(()=>Y(e.boundaryLinks,e.input)),d=s(()=>Y(e.boundaryNumbers,!e.input)),y=s(()=>Y(e.directionLinks,e.input)),v=s(()=>Y(e.ellipses,!e.input)),h=H(null),x=s({get:()=>e.modelValue,set:l=>{if(l=parseInt(l,10),e.disable||isNaN(l))return;const o=ct(l,n.value,a.value);e.modelValue!==o&&t("update:modelValue",o)}});L(()=>`${n.value}|${a.value}`,()=>{x.value=e.modelValue});const N=s(()=>"q-pagination row no-wrap items-center"+(e.disable===!0?" disabled":"")),S=s(()=>e.gutter in ve?`${ve[e.gutter]}px`:e.gutter||null),O=s(()=>S.value!==null?`--q-pagination-gutter-parent:-${S.value};--q-pagination-gutter-child:${S.value}`:null),B=s(()=>{const l=[e.iconFirst||c.iconSet.pagination.first,e.iconPrev||c.iconSet.pagination.prev,e.iconNext||c.iconSet.pagination.next,e.iconLast||c.iconSet.pagination.last];return c.lang.rtl===!0?l.reverse():l}),I=s(()=>({"aria-disabled":e.disable===!0?"true":"false",role:"navigation"})),A=s(()=>Fe(e,"flat")),Q=s(()=>({[A.value]:!0,round:e.round,rounded:e.rounded,padding:e.padding,color:e.color,textColor:e.textColor,size:e.size,ripple:e.ripple!==null?e.ripple:!0})),E=s(()=>{const l={[A.value]:!1};return e.activeDesign!==""&&(l[e.activeDesign]=!0),l}),w=s(()=>({...E.value,color:e.activeColor||e.color,textColor:e.activeTextColor||e.textColor})),q=s(()=>{let l=Math.max(u.value,1+(v.value?2:0)+(d.value?2:0));const o={pgFrom:n.value,pgTo:a.value,ellipsesStart:!1,ellipsesEnd:!1,boundaryStart:!1,boundaryEnd:!1,marginalStyle:{minWidth:`${Math.max(2,String(a.value).length)}em`}};return u.value&&l<a.value-n.value+1&&(l=1+Math.floor(l/2)*2,o.pgFrom=Math.max(n.value,Math.min(a.value-l+1,e.modelValue-Math.floor(l/2))),o.pgTo=Math.min(a.value,o.pgFrom+l-1),d.value&&(o.boundaryStart=!0,o.pgFrom++),v.value&&o.pgFrom>n.value+(d.value?1:0)&&(o.ellipsesStart=!0,o.pgFrom++),d.value&&(o.boundaryEnd=!0,o.pgTo--),v.value&&o.pgTo<a.value-(d.value?1:0)&&(o.ellipsesEnd=!0,o.pgTo--)),o});function D(l){x.value=l}function m(l){x.value=x.value+l}const V=s(()=>{function l(){x.value=h.value,h.value=null}return{"onUpdate:modelValue":o=>{h.value=o},onKeyup:o=>{Me(o,13)===!0&&l()},onBlur:l}});function P(l,o,p){const C={"aria-label":o,"aria-current":"false",...Q.value,...l};return p===!0&&Object.assign(C,{"aria-current":"true",...w.value}),o!==void 0&&(e.toFn!==void 0?C.to=e.toFn(o):C.onClick=()=>{D(o)}),g(j,C)}return Object.assign(r,{set:D,setByOffset:m}),()=>{const l=[],o=[];let p;if(_.value===!0&&(l.push(P({key:"bls",disable:e.disable||e.modelValue<=n.value,icon:B.value[0]},n.value)),o.unshift(P({key:"ble",disable:e.disable||e.modelValue>=a.value,icon:B.value[3]},a.value))),y.value===!0&&(l.push(P({key:"bdp",disable:e.disable||e.modelValue<=n.value,icon:B.value[1]},e.modelValue-1)),o.unshift(P({key:"bdn",disable:e.disable||e.modelValue>=a.value,icon:B.value[2]},e.modelValue+1))),e.input!==!0){p=[];const{pgFrom:C,pgTo:se,marginalStyle:z}=q.value;if(q.value.boundaryStart===!0){const $=n.value===e.modelValue;l.push(P({key:"bns",style:z,disable:e.disable,label:n.value},n.value,$))}if(q.value.boundaryEnd===!0){const $=a.value===e.modelValue;o.unshift(P({key:"bne",style:z,disable:e.disable,label:a.value},a.value,$))}q.value.ellipsesStart===!0&&l.push(P({key:"bes",style:z,disable:e.disable,label:"\u2026",ripple:!1},C-1)),q.value.ellipsesEnd===!0&&o.unshift(P({key:"bee",style:z,disable:e.disable,label:"\u2026",ripple:!1},se+1));for(let $=C;$<=se;$++)p.push(P({key:`bpg${$}`,style:z,disable:e.disable,label:$},$,$===e.modelValue))}return g("div",{class:N.value,...I.value},[g("div",{class:"q-pagination__content row no-wrap items-center",style:O.value},[...l,e.input===!0?g(xe,{class:"inline",style:{width:`${b.value.length/1.5}em`},type:"number",dense:!0,value:h.value,disable:e.disable,dark:i.value,borderless:!0,inputClass:e.inputClass,inputStyle:e.inputStyle,placeholder:b.value,min:n.value,max:a.value,...V.value}):g("div",{class:"q-pagination__middle row justify-center"},p),...o])])}}});const vt={name:"IndexPage",data(){return{slide:1,carousels:[],carouselsMini:[],search:"",loading:!1,currentPage:1,totalPages:1}},created(){this.carouselsGet(),this.buscar(),this.carouselsMiniGet()},methods:{carouselsMiniGet(){this.$axios.get("carouselsMini").then(e=>{this.carouselsMini=e.data})},clickDetalleProducto(e){this.$router.push("/detalle-producto/"+e.id+"/"+this.espacioCambioGuion(e.nombre))},espacioCambioGuion(e){return e.replace(/ |\/|\./g,"-").replace(/,/g,"")},buscar(){this.loading=!0,this.$axios.get("productos",{params:{search:this.search,page:this.currentPage}}).then(e=>{this.$store.products=[],this.totalPages=e.data.last_page,e.data.data.forEach(t=>{if(t.porcentaje>0){this.es_porcentaje=!0,t.precioNormal=t.precio;const r=t.precio-t.precio*t.porcentaje/100;t.precio=r.toFixed(2)}this.$store.products.push(t)})}).finally(()=>{this.loading=!1})},carouselsGet(){this.$axios.get("carouselsPage").then(e=>{this.carousels=e.data}).finally(()=>{})}}},K=e=>(Ue("data-v-144776ba"),e=e(),Re(),e),ft={class:"page-wrapper"},gt={class:"carousel-container"},mt={class:"image-track"},ht=["src"],pt={class:"blue-bar"},bt=K(()=>f("img",{src:"images/logo.png",alt:"Logo",class:"logo"},null,-1)),yt={class:"nav-buttons"},xt=K(()=>f("i",{class:"fas fa-shopping-cart nav-icon"},null,-1)),_t=K(()=>f("i",{class:"fas fa-search nav-icon"},null,-1)),Pt={class:"carousel-container-large"},Ct=K(()=>f("div",{class:"row bg-grey-3"},null,-1)),St={class:"row q-pa-xs"},kt={class:"col-12"},wt={class:"text-h6 text-center text-bold no-select flex flex-center"},qt={key:0,class:"col-12 flex flex-center"},Bt={class:"col-12"},$t={key:0,class:"row cursor-pointer q-pa-md"},It={class:"text-center text-bold",style:{"line-height":"1","font-size":"14px",height:"30px"}},Vt={class:"text-left bg-white",style:{"font-size":"13px"}},Tt={class:"text-grey text-bold tachar"},Nt={class:"text-bold text-blue"},At={key:1,class:"text-center"},Et=K(()=>f("div",{class:"text-h6"},"No hay productos",-1)),Ft=[Et];function Mt(e,t,r,c,i,n){return k(),W(Ke,{class:"q-pa-none"},{default:F(()=>[f("div",ft,[f("div",gt,[f("div",mt,[(k(!0),M(ae,null,ne(i.carouselsMini,(a,u)=>(k(),M("img",{key:u,src:`${e.$url}../images/${a.image}`,alt:"Imagen 1"},null,8,ht))),128))])]),f("div",pt,[bt,f("div",yt,[xt,_t,f("button",{class:"nav-button",onClick:t[0]||(t[0]=a=>e.$router.push("/"))}," INICIO "),f("button",{class:"nav-button",onClick:t[1]||(t[1]=a=>e.$router.push("/sucursales"))}," SUCURSALES ")])])]),f("div",Pt,[T(ut,{animated:"",modelValue:i.slide,"onUpdate:modelValue":t[2]||(t[2]=a=>i.slide=a),arrows:"",navigation:"",infinite:"","transition-prev":"slide-right","transition-next":"slide-left",height:"auto",autoplay:"","autoplay-interval":"5000"},{default:F(()=>[(k(!0),M(ae,null,ne(i.carousels,(a,u)=>(k(),W(at,{key:u,name:u+1},{default:F(()=>[T(ue,{src:`${e.$url}../images/${a.image}`},null,8,["src"])]),_:2},1032,["name"]))),128))]),_:1},8,["modelValue"])]),Ct,f("div",St,[f("div",kt,[f("div",wt,[T(xe,{modelValue:i.search,"onUpdate:modelValue":t[3]||(t[3]=a=>i.search=a),dense:"",style:{width:"70%","margin-top":"5px","margin-bottom":"5px"},outlined:"","bg-color":"grey-4",rounded:"",onKeyup:Oe(n.buscar,["enter"]),placeholder:"Buscar Producto / Palabra Clave",class:"search-input q-ml-sm"},{prepend:F(()=>[T(Le,{name:"search"})]),_:1},8,["modelValue","onKeyup"]),T(j,{rounded:"",style:{width:"90px"},onClick:n.buscar,color:"primary",label:"Buscar",loading:i.loading,"no-caps":""},null,8,["onClick","loading"])])]),i.totalPages>1?(k(),M("div",qt,[i.totalPages>1?(k(),W(dt,{key:0,rounded:"",modelValue:i.currentPage,"onUpdate:modelValue":[t[4]||(t[4]=a=>i.currentPage=a),n.buscar],max:i.totalPages,"max-pages":6,"boundary-numbers":"",ellipses:""},null,8,["modelValue","max","onUpdate:modelValue"])):le("",!0)])):le("",!0),f("div",Bt,[e.$store.products.length>0?(k(),M("div",$t,[(k(!0),M(ae,null,ne(e.$store.products,a=>(k(),M("div",{class:"col-6 col-md-3 q-pa-md",key:a.id},[T(Qe,{onClick:u=>n.clickDetalleProducto(a),flat:"",bordered:"",class:"q-pa-md",style:{border:"2px solid #00BD73",padding:"5px",margin:"10px"}},{default:F(()=>[T(ue,{src:a.imagen.includes("http")?a.imagen:`${e.$url}../images/${a.imagen}`,width:"100%",height:"130px"},{default:F(()=>[a.porcentaje?(k(),W(_e,{key:0,color:"red",floating:"",style:{padding:"10px 10px 5px 5px",margin:"0px"}},{default:F(()=>[je(X(a.porcentaje)+"% ",1)]),_:2},1024)):le("",!0)]),_:2},1032,["src"]),f("div",It,X(a.nombre),1),T(ze,{class:"q-pa-none q-ma-none"},{default:F(()=>[f("div",Vt,[f("div",Tt," Antes Bs. "+X(a==null?void 0:a.precioNormal)+" ",1),f("div",Nt," Ahora Bs. "+X(a==null?void 0:a.precio),1)]),T(j,{onClick:u=>n.clickDetalleProducto(a),label:"A\xF1adir al carrito",icon:"add_shopping_cart",class:"full-width",color:"green",size:"10px","no-caps":"",dense:""},null,8,["onClick"])]),_:2},1024)]),_:2},1032,["onClick"])]))),128))])):(k(),M("div",At,Ft))])])]),_:1})}var Qt=De(vt,[["render",Mt],["__scopeId","data-v-144776ba"]]);export{Qt as default};