<template>
  <q-page class="q-pa-xs bg-grey-3">
    <q-table dense :filter="filter" title="Gestion carosel" :rows="carousels" :columns="columns"  row-key="name" :rows-per-page-options="[50,100]">
      <template v-slot:top-right>
        <q-btn
          label="Nuevo carucel"
          color="positive"
          @click="carouselCreate"
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
      <template v-slot:body-cell-id="props">
        <q-td key="id" :props="props" auto-width>
          {{props.row.id}}
        </q-td>
      </template>
      <template v-slot:body-cell-image="props">
        <q-td key="image" :props="props" auto-width class="text-center">
          <q-img :src="`${$url}../images/${props.row.image}`" width="100px" />
        </q-td>
      </template>
      <template v-slot:body-cell-status="props">
        <q-td key="status" :props="props" auto-width>
          <q-btn
            rounded
            size="10px"
            no-caps
            @click="carouselUpdate(props.row)"
            :color="props.row.status === 'active' ? 'positive' : 'negative'"
            :label="props.row.status"
            text-color="white"
          />
        </q-td>
      </template>
    </q-table>
    <pre>{{carousels}}</pre>
  </q-page>
</template>
<script>
export default {
  name: 'CarouselPage',
  data () {
    return {
      carousels: [],
      carousel: {},
      dialog: false,
      columns: [
        { name: 'id', label: 'ID', align: 'center', field: row => row.id },
        { name: 'image', label: 'Imagen', align: 'left', field: row => row.image },
        { name: 'status', label: 'Estado', align: 'center', field: row => row.status }
        // { name: 'opcion', label: 'Opción', align: 'center', field: row => row.opcion }
      ]
    }
  },
  mounted () {
    this.getCarousels()
  },
  methods: {
    carouselCreate () {
      this.dialog = true
      this.carousel = {
        image: '',
        status: 'active'
      }
    },
    getCarousels () {
      this.$axios.get('carousels')
        .then(response => {
          this.carousels = response.data
        })
        .catch(error => {
          console.log(error)
        })
    },
    carouselSave () {
      this.$axios.post('carousels', this.carousel)
        .then(response => {
          this.getCarousels()
          this.dialog = false
        })
        .catch(error => {
          console.log(error)
        })
    },
    carouselDelete (carousel) {
      this.$axios.delete('carousels/' + carousel.id)
        .then(response => {
          this.getCarousels()
        })
        .catch(error => {
          console.log(error)
        })
    },
    carouselEdit (carousel) {
      this.dialog = true
      this.carousel = carousel
    },
    carouselUpdate (carousel) {
      // console.log(carousel)
      carousel.status = carousel.status === 'active' ? 'inactive' : 'active'
      this.$axios.put('carousels/' + carousel.id, carousel)
        .then(response => {
          // this.getCarousels()
          console.log(response.data)
          this.dialog = false
        })
        .catch(error => {
          console.log(error)
        })
    },
    carouselSubmit () {
      if (this.carousel.id) {
        this.carouselUpdate()
      } else {
        this.carouselSave()
      }
    }
  }
}
</script>
