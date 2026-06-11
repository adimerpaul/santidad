import { defineStore } from 'pinia'

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
    providers: []
  }),
  getters: {
    doubleCount: (state) => state.counter * 2
  },
  actions: {
    increment () {
      this.counter++
    },
    async fetchCategories (axios) {
      if (this.categories.length > 0) return this.categories
      try {
        const res = await axios.get('categories')
        this.categories = res.data
        return this.categories
      } catch (error) {
        console.error('Error fetching categories:', error)
        return []
      }
    },
    async fetchAgencias (axios) {
      if (this.agencias.length > 0) return this.agencias
      try {
        const res = await axios.get('agencias')
        this.agencias = res.data
        return this.agencias
      } catch (error) {
        console.error('Error fetching agencias:', error)
        return []
      }
    },
    async fetchProviders (axios) {
      if (this.providers.length > 0) return this.providers
      try {
        const res = await axios.get('providers')
        this.providers = res.data
        return this.providers
      } catch (error) {
        console.error('Error fetching providers:', error)
        return []
      }
    }
  }
})
