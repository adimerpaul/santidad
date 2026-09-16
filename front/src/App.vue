<template>
  <router-view />
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'App',
  mounted () {
    window.addEventListener('storage', (event) => {
      if (event.key === 'tokenSantidad') {
        window.location.reload()
      }
    })
  }
})
</script>
