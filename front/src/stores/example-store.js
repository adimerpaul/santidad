import { defineStore } from 'pinia'

export const useCounterStore = defineStore('counter', {
  state: () => ({
    counter: 0,
    user: {},
    env: {},
    isLoggedIn: !!localStorage.getItem('tokenSantidad'),
    agencia_id: parseInt(localStorage.getItem('agencia_id')),
    loading: false,
    productosVenta: []
  }),
  getters: {
    doubleCount: (state) => state.counter * 2
  },
  actions: {
    increment () {
      this.counter++
    }
  }
})
