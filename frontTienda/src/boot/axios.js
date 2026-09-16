import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { useCounterStore } from 'stores/example-store'
import Alert from 'src/addons/Alert'

const api = axios.create({ baseURL: 'https://api.example.com' })

export default boot(({ app, router }) => {
  const token = localStorage.getItem('tokenSantidad')

  // Crear instancia de axios con baseURL
  const securedAxios = axios.create({
    baseURL: import.meta.env.VITE_API_BACK
  })

  // Si hay token, lo añadimos al header Authorization
  if (token) {
    securedAxios.defaults.headers.common.Authorization = `Bearer ${token}`
  }

  // Asignamos como instancia global
  app.config.globalProperties.$axios = securedAxios
  app.config.globalProperties.$api = api
  app.config.globalProperties.$alert = Alert
  app.config.globalProperties.$url = import.meta.env.VITE_API_BACK
  app.config.globalProperties.$store = useCounterStore()

  // Si quieres validar el token automáticamente (opcional)
  if (token) {
    securedAxios.post('/me')
      .then((res) => {
        useCounterStore().user = res.data.user
        useCounterStore().env = res.data.env
        useCounterStore().isLoggedIn = true
      })
      .catch(() => {
        securedAxios.defaults.headers.common.Authorization = ''
        localStorage.removeItem('tokenSantidad')
        localStorage.removeItem('agencia_id')
        useCounterStore().user = {}
        useCounterStore().isLoggedIn = false
        router.push('/login') // si tienes login
      })
  }
})
