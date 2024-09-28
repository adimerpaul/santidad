<template>
  <q-page class="q-pa-xs bg-grey-3">
    <q-table dense :filter="filter"
             title="Gestion carosel" :rows="carousels" :columns="columns"  row-key="name"
             :rows-per-page-options="[50,100]"
             @row-click="carouselEdit"
    >
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
            @click="carouselUpdate(props.row,$event)"
            :color="props.row.status === 'active' ? 'positive' : 'negative'"
            :label="props.row.status"
            text-color="white"
          />
        </q-td>
      </template>
    </q-table>
<!--    <pre>{{carousels}}</pre>-->
    <q-dialog v-model="dialog" persistent>
      <q-card>
        <q-card-section>
          <q-card-section class="q-pa-none text-bold row">
            {{carousel.id ? 'Editar' : 'Nuevo'}} carosel
            <q-space />
            <q-btn
              flat
              round
              dense
              icon="close"
              @click="dialog = false"/>
          </q-card-section>
          <q-card-section>
            <input
              placeholder="Imagen"
              type="file"
              accept="image/*"
              @change="fileChange"
            />
<!--            input select-->
            <q-select
                outlined
              v-model="carousel.tipo"
              :options="['Normal','Mini']"
              label="Estado"
              dense
            />
          </q-card-section>
          <q-card-section class="text-right">
            <q-btn
              :label="carousel.id ? 'Actualizar' : 'Guardar'"
              :color="carousel.id ? 'orange' : 'positive'"
              @click="carouselSave"
              :loading="loading"
              :icon="carousel.id ? 'edit' : 'save'"
              class="q-mb-xs"
              no-caps
            />
            <q-btn
              label="Cancelar"
              color="negative"
              @click="dialog = false"
              icon="cancel"
              :loading="loading"
              class="q-mb-xs"
              no-caps
            />
          </q-card-section>
        </q-card-section>
      </q-card>
    </q-dialog>
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
      loading: false,
      columns: [
        { name: 'id', label: 'ID', align: 'center', field: row => row.id },
        { name: 'image', label: 'Imagen', align: 'left', field: row => row.image },
        { name: 'status', label: 'Estado', align: 'center', field: row => row.status },
        { name: 'tipo', label: 'Tipo', align: 'center', field: row => row.tipo }
        // { name: 'opcion', label: 'Opcion', align: 'center', field: row => row.opcion }
      ]
    }
  },
  mounted () {
    this.getCarousels()
  },
  methods: {
    fileChange (event) {
      this.carousel.image = event.target.files[0]
    },
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
      if (!this.carousel.image) {
        this.$alert.error('Debe seleccionar una imagen')
        return false
      }
      if (this.carousel.id) {
        this.loading = true
        const formData = new FormData()
        formData.append('file', this.carousel.image)
        formData.append('tipo', this.carousel.tipo)
        this.$axios.post('carouselsFile/' + this.carousel.id, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
          .then(response => {
            this.getCarousels()
            this.dialog = false
          })
          .catch(error => {
            this.$alert.error(error.response.data.message)
          }).finally(() => {
            this.loading = false
          })
      } else {
        this.loading = true
        const formData = new FormData()
        formData.append('file', this.carousel.image)
        formData.append('tipo', this.carousel.tipo)
        this.$axios.post('carousels', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
          .then(response => {
            this.getCarousels()
            this.dialog = false
          })
          .catch(error => {
            console.log(error)
          }).finally(() => {
            this.loading = false
          })
      }
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
    carouselEdit (event, carousel) {
      // console.log(carousel)
      this.dialog = true
      this.carousel = carousel
    },
    carouselUpdate (carousel, event) {
      // console.log(carousel)
      // no propagar
      event.stopPropagation()
      this.loading = true
      carousel.status = carousel.status === 'active' ? 'inactive' : 'active'
      this.$axios.put('carousels/' + carousel.id, carousel)
        .then(response => {
          // this.getCarousels()
          console.log(response.data)
          this.dialog = false
        })
        .catch(error => {
          console.log(error)
        }).finally(() => {
          this.loading = false
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
