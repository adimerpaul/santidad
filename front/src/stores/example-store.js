import { defineStore } from 'pinia'

// Catálogos que sirve /api/catalogos. Cada clave se guarda en el state con el
// mismo nombre y se pide una sola vez por sesión.
const CLAVES_CATALOGO = [
  'categories',
  'subcategories',
  'agencias',
  'documents',
  'unidades',
  'providers',
  'users'
]

export const useCounterStore = defineStore('counter', {
  state: () => ({
    counter: 0,
    user: {},
    env: {},
    isLoggedIn: !!localStorage.getItem('tokenSantidad'),
    agencia_id: parseInt(localStorage.getItem('agencia_id')),
    loading: false,
    productosVenta: [],
    productosCompra: [],
    pedidoId: null,
    // Cache de datos que cambian poco
    categories: [],
    agencias: [],
    providers: [],
    subcategories: [],
    documents: [],
    unidades: [],
    users: [],
    // Claves ya traídas. Se lleva aparte porque una lista vacía es un
    // resultado válido y con .length se volvería a pedir en cada visita.
    catalogosCargados: [],
    // Peticiones de catálogo en vuelo, por clave, para que dos pantallas que
    // montan a la vez compartan la misma llamada.
    catalogosEnVuelo: {}
  }),
  getters: {
    doubleCount: (state) => state.counter * 2
  },
  actions: {
    increment () {
      this.counter++
    },
    /**
     * Trae los catálogos indicados en UNA sola petición y los cachea por
     * sesión. Las claves ya cargadas no se vuelven a pedir; si otra pantalla
     * las está pidiendo en este momento, se espera esa misma llamada.
     */
    async fetchCatalogos (axios, claves = CLAVES_CATALOGO) {
      const desconocidas = claves.filter(c => !CLAVES_CATALOGO.includes(c))
      if (desconocidas.length > 0) {
        console.error('Catálogo no reconocido:', desconocidas.join(', '))
      }

      const validas = claves.filter(c => CLAVES_CATALOGO.includes(c))
      const enVuelo = validas
        .map(c => this.catalogosEnVuelo[c])
        .filter(p => p)
      const faltantes = validas.filter(
        c => !this.catalogosCargados.includes(c) && !this.catalogosEnVuelo[c]
      )

      if (faltantes.length === 0) {
        // Nada nuevo que pedir, pero puede haber una llamada en curso que
        // todavía no dejó los datos en el state.
        if (enVuelo.length > 0) await Promise.all(enVuelo)
        return this
      }

      const peticion = axios
        .get('catalogos', { params: { include: faltantes.join(',') } })
        .then(res => {
          faltantes.forEach(clave => {
            if (res.data[clave] !== undefined) {
              this[clave] = res.data[clave]
              if (!this.catalogosCargados.includes(clave)) {
                this.catalogosCargados.push(clave)
              }
            }
          })
        })
        .catch(error => {
          console.error('Error fetching catalogos:', error)
        })
        .finally(() => {
          faltantes.forEach(clave => {
            delete this.catalogosEnVuelo[clave]
          })
        })

      faltantes.forEach(clave => {
        this.catalogosEnVuelo[clave] = peticion
      })

      await Promise.all([peticion, ...enVuelo])
      return this
    },
    /**
     * Marca catálogos como no cargados para que la próxima llamada a
     * fetchCatalogos los vuelva a pedir. Se usa tras crear o editar un
     * registro de esas listas.
     */
    invalidarCatalogos (claves) {
      const aInvalidar = Array.isArray(claves) ? claves : [claves]
      this.catalogosCargados = this.catalogosCargados.filter(c => !aInvalidar.includes(c))
    },
    async fetchCategories (axios) {
      await this.fetchCatalogos(axios, ['categories'])
      return this.categories
    },
    async fetchAgencias (axios) {
      await this.fetchCatalogos(axios, ['agencias'])
      return this.agencias
    },
    async fetchProviders (axios) {
      await this.fetchCatalogos(axios, ['providers'])
      return this.providers
    }
  }
})
