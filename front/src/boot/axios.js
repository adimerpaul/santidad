import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { useCounterStore } from 'stores/example-store'
import { Alert } from 'src/addons/Alert'
import { Excel } from 'src/addons/Excel'
import moment from 'moment'
import { Imprimir } from 'src/addons/Imprimir'
// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
const api = axios.create({ baseURL: 'https://api.example.com' })

export default boot(({ app, router }) => {
  // for use inside Vue files (Options API) through this.$axios and this.$api
  app.config.globalProperties.$axios = axios.create({ baseURL: import.meta.env.VITE_API_BACK })
  app.config.globalProperties.$url = import.meta.env.VITE_API_BACK
  app.config.globalProperties.$alert = Alert
  app.config.globalProperties.$store = useCounterStore()
  app.config.globalProperties.$imprimir = Imprimir
  app.config.globalProperties.$excel = Excel
  app.config.globalProperties.$metodoPago = ['Efectivo', 'Tarjeta', 'Transferencia', 'Otro']
  app.config.globalProperties.$filters = {
    dateDmYHis (value) {
      const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Nov', 'Dic']
      const mes = meses[moment(String(value)).format('MM') - 1]
      if (!value) return ''
      const date = moment(String(value)).format('DD') + ' ' + mes + ' ' + moment(String(value)).format('YYYY') + ' ' + moment(String(value)).format('hh:mm A')
      return date
    },
    capitalize (value) {
      if (!value) return ''
      const lower = value.toLowerCase()
      return value.charAt(0).toUpperCase() + lower.slice(1)
    },
    precioRebajaVenta (monto, porcentaje) {
      const precio = monto - (monto * porcentaje / 100)
      return precio.toFixed(2)
    }
  }
  // app.config.globalProperties.$store = useCounterStore()
  const token = localStorage.getItem('tokenSantidad')
  if (token) {
    useCounterStore().loading = true
    useCounterStore().isLoggedIn = true
    app.config.globalProperties.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
    app.config.globalProperties.$axios.post('me').then((res) => {
      // console.log(res.data)
      useCounterStore().user = res.data.user
      useCounterStore().env = res.data.env
      // useCounterStore().agencia_id = res.data.agencia_id
    }).catch(() => {
      app.config.globalProperties.$axios.defaults.headers.common.Authorization = ''
      useCounterStore().user = {}
      localStorage.removeItem('tokenSantidad')
      localStorage.removeItem('agencia_id')
      useCounterStore().isLoggedIn = false
      router.push('/login')
    }).finally(() => {
      useCounterStore().loading = false
    })
  }
  // ^ ^ ^ this will allow you to use this.$axios (for Vue Options API form)
  //       so you won't necessarily have to import axios in each vue file

  app.config.globalProperties.$api = api
  // ^ ^ ^ this will allow you to use this.$api (for Vue Options API form)
  //       so you can easily perform requests against your app's API
})

export { api }
