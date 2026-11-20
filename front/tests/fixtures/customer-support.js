// Harness local sin boot de autenticación, API, sockets ni datos de producción.
import { createApp, h, shallowRef } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHashHistory, RouterView } from 'vue-router'
import { Quasar, Dialog, QItem, QLayout, QPageContainer } from 'quasar'
import axios from 'axios'
import 'quasar/dist/quasar.css'
import '@quasar/extras/material-icons/material-icons.css'
import SupportPage from '../../src/pages/AtencionClientePage.vue'
import { useCounterStore } from '../../src/stores/example-store'

const app = createApp({
  render: () => h(QLayout, null, {
    default: () => h(QPageContainer, null, {
      default: () => [
        h(QItem, { to: '/', clickable: true }, () => 'Abrir Atención al cliente'),
        h(QItem, { to: '/otra-pagina', clickable: true }, () => 'Ir a otra página'),
        h(RouterView)
      ]
    })
  })
})
app.config.globalProperties.$axios = axios.create({ baseURL: '/api/' })
const listeners = new Map()
const fixtureSocket = {
  on (name, listener) { listeners.set(name, listener) },
  off (name) { listeners.delete(name) },
  emit (name, data) { listeners.get(name)?.(data) },
  connect () { this.auth(() => this.emit('connect')) },
  disconnect () { this.emit('disconnect') }
}
window.fixtureSocket = fixtureSocket
app.provide('mainSocketManager', shallowRef({ socket: () => fixtureSocket }))
app.use(createPinia())
app.use(Quasar, { plugins: { Dialog } })
const store = useCounterStore()
store.user = { id: 2, agencia_id: 1, agencia: { nombre: 'Sucursal de prueba' } }
window.fixtureSignInAsAdmin = () => { store.user = { ...store.user, id: 1 } }
const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: SupportPage },
    { path: '/otra-pagina', component: { render: () => h('h1', 'Otra página') } }
  ]
})
app.use(router)
router.isReady().then(() => app.mount('#app'))
