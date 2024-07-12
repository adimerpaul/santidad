<template>
<q-page class="bg-grey-3">
  {{id}}
</q-page>
</template>
<script>
export default {
  name: 'DetalleProducto',
  data () {
    return {
      id: this.$route.params.id
    }
  },
  mounted () {
    console.log(this.id)
  }
}
</script>
