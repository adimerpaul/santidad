// Harness local sin boot de autenticación, API, sockets ni datos de producción.
import { createApp, h } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHashHistory, RouterView } from 'vue-router'
import { Quasar, Dialog, QLayout, QPageContainer } from 'quasar'
import axios from 'axios'
import 'quasar/dist/quasar.css'
import '@quasar/extras/material-icons/material-icons.css'
import SupportPage from '../../src/pages/AtencionClientePage.vue'
import { useCounterStore } from '../../src/stores/example-store'

const app = createApp({ render: () => h(QLayout, null, { default: () => h(QPageContainer, null, { default: () => h(RouterView) }) }) })
app.config.globalProperties.$axios = axios.create({ baseURL: '/api/' })
app.use(createPinia())
app.use(Quasar, { plugins: { Dialog } })
const store = useCounterStore()
store.user = { id: 2, agencia_id: 1, agencia: { nombre: 'Sucursal de prueba' } }
const router = createRouter({ history: createWebHashHistory(), routes: [{ path: '/', component: SupportPage }] })
app.use(router)
router.isReady().then(() => app.mount('#app'))
