<template>
  <q-page class="q-pa-xs bg-grey-3">
    <q-table dense :filter="filter" title="Gestion carosel" :rows="carousels"  row-key="name" :rows-per-page-options="[50,100]">
      <template v-slot:top-right>
        <q-btn
          label="Nuevo usuario"
          color="positive"
          @click="regDialog"
          icon="add_circle"
          class="q-mb-xs"
          no-caps
        />
        <q-input outlined dense debounce="300" v-model="filter" placeholder="Search">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>
      <template v-slot:body-cell-opcion="props">
        <q-td key="opcion" :props="props" auto-width>
        </q-td>
      </template>
    </q-table>
  </q-page>
</template>
<script>
export default {
  name: 'CarouselPage',
  data () {
    return {
      carousels: [],
      carousel: {}
    }
  },
  mounted () {
    this.getCarousels()
  },
  methods: {
    getCarousels () {
      this.$axios.get('carousels')
        .then(response => {
          this.carousels = response.data
        })
        .catch(error => {
          console.log(error)
        })
    }
  }
}
</script>
