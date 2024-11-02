import { defineStore } from 'pinia'

export const useCounterStore = defineStore('counter', {
  state: () => ({
    counter: 0,
    products: [],
    carrito: []
  }),
  getters: {
    doubleCount: (state) => state.counter * 2
  },
  actions: {
    increment () {
      this.counter++
    },
    addCarrito (producto) {
      this.carrito.push(producto)
    }
  }
})
