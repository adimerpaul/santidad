<template>
  <q-page class="q-pa-md bg-grey-3">
    <!-- Encabezado -->
    <div class="row items-center q-col-gutter-sm q-mb-md">
      <div class="col-12 col-md-auto">
        <div class="text-h6 text-weight-bold">
          <q-icon name="view_carousel" class="q-mr-sm" color="primary" size="28px" />
          Gestión de Carrusel
          <q-badge color="primary" class="q-ml-sm">{{ carouselsFiltrados.length }}</q-badge>
        </div>
      </div>
      <q-space />
      <div class="col-12 col-sm">
        <q-input
          outlined
          dense
          debounce="300"
          v-model="filter"
          placeholder="Buscar..."
          bg-color="white"
          clearable
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
      <div class="col-12 col-sm-auto">
        <q-btn
          label="Nueva imagen"
          color="positive"
          @click="carouselCreate"
          icon="add_circle"
          no-caps
          unelevated
          class="full-width"
        />
      </div>
    </div>

    <!-- Filtro por tipo -->
    <div class="row q-gutter-xs q-mb-md">
      <q-chip
        v-for="t in ['Todos', ...tipos]"
        :key="t"
        clickable
        :color="tipoFiltro === t ? tipoColor(t) : 'white'"
        :text-color="tipoFiltro === t ? 'white' : 'grey-8'"
        :icon="tipoIcono(t)"
        @click="tipoFiltro = t"
        dense
        class="q-px-sm"
      >
        {{ t }}
      </q-chip>
    </div>

    <!-- Skeletons mientras carga -->
    <div v-if="cargando" class="row q-col-gutter-md">
      <div v-for="n in 8" :key="n" class="col-12 col-sm-6 col-md-4 col-lg-3">
        <q-card flat bordered>
          <q-skeleton height="160px" square />
          <q-card-section class="row items-center q-py-sm">
            <q-skeleton type="QChip" width="70px" />
            <q-space />
            <q-skeleton type="QBtn" width="60px" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Sin resultados -->
    <div v-else-if="carouselsFiltrados.length === 0" class="column items-center q-pa-xl text-grey-6">
      <q-icon name="image_not_supported" size="64px" />
      <div class="text-subtitle1 q-mt-sm">No hay imágenes{{ tipoFiltro !== 'Todos' ? ` de tipo ${tipoFiltro}` : '' }}</div>
      <q-btn
        label="Agregar la primera"
        color="positive"
        icon="add_circle"
        no-caps
        flat
        class="q-mt-sm"
        @click="carouselCreate"
      />
    </div>

    <!-- Grilla de tarjetas -->
    <div v-else class="row q-col-gutter-md">
      <div
        v-for="c in carouselsFiltrados"
        :key="c.id"
        class="col-12 col-sm-6 col-md-4 col-lg-3"
      >
        <q-card flat bordered class="carousel-card cursor-pointer" @click="carouselEdit(c)">
          <q-img
            :src="`${$url}../images/${c.image}`"
            :ratio="16/9"
            fit="cover"
          >
            <template v-slot:loading>
              <q-skeleton height="100%" square />
            </template>
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey-4 text-grey-7">
                <q-icon name="broken_image" size="40px" />
              </div>
            </template>
            <div class="absolute-top-left q-pa-xs" style="background: transparent">
              <q-badge :color="tipoColor(c.tipo)" class="q-px-sm">
                <q-icon :name="tipoIcono(c.tipo)" size="12px" class="q-mr-xs" />
                {{ c.tipo || 'Normal' }}
              </q-badge>
            </div>
            <div class="absolute-top-right q-pa-xs" style="background: transparent">
              <q-badge :color="c.status === 'active' ? 'positive' : 'negative'" class="q-px-sm">
                {{ c.status === 'active' ? 'Activo' : 'Inactivo' }}
              </q-badge>
            </div>
          </q-img>
          <q-card-actions class="q-py-xs">
            <div class="text-caption text-grey-6">#{{ c.id }}</div>
            <q-space />
            <q-toggle
              :model-value="c.status === 'active'"
              color="positive"
              dense
              size="sm"
              @update:model-value="carouselToggle(c)"
              @click.stop
            >
              <q-tooltip>{{ c.status === 'active' ? 'Desactivar' : 'Activar' }}</q-tooltip>
            </q-toggle>
            <q-btn flat round dense icon="edit" color="orange" size="sm" @click.stop="carouselEdit(c)">
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn flat round dense icon="delete" color="negative" size="sm" @click.stop="carouselDelete(c)">
              <q-tooltip>Eliminar</q-tooltip>
            </q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <!-- Dialogo crear / editar -->
    <q-dialog v-model="dialog" persistent :maximized="$q.screen.lt.sm">
      <q-card style="width: 480px; max-width: 100vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            <q-icon :name="carousel.id ? 'edit' : 'add_photo_alternate'" class="q-mr-sm" />
            {{ carousel.id ? 'Editar imagen' : 'Nueva imagen' }}
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section>
          <!-- Vista previa -->
          <q-img
            v-if="previewSrc"
            :src="previewSrc"
            :ratio="16/9"
            fit="contain"
            class="rounded-borders bg-grey-2 q-mb-md"
          >
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey-4 text-grey-7">
                <q-icon name="broken_image" size="40px" />
              </div>
            </template>
          </q-img>
          <div
            v-else
            class="flex flex-center column bg-grey-2 rounded-borders text-grey-6 q-mb-md"
            style="height: 160px; border: 2px dashed #bdbdbd"
          >
            <q-icon name="cloud_upload" size="40px" />
            <div class="text-caption">Seleccione una imagen</div>
          </div>

          <q-file
            outlined
            dense
            v-model="archivo"
            label="Imagen"
            accept="image/*"
            max-file-size="2097152"
            @rejected="onRejected"
            clearable
            class="q-mb-md"
          >
            <template v-slot:prepend>
              <q-icon name="attach_file" />
            </template>
          </q-file>

          <q-select
            outlined
            dense
            v-model="carousel.tipo"
            :options="tipos"
            label="Tipo"
          >
            <template v-slot:prepend>
              <q-icon :name="tipoIcono(carousel.tipo)" />
            </template>
          </q-select>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md q-pt-none">
          <q-btn
            label="Cancelar"
            color="negative"
            flat
            icon="cancel"
            :disable="loading"
            v-close-popup
            no-caps
          />
          <q-btn
            :label="carousel.id ? 'Actualizar' : 'Guardar'"
            :color="carousel.id ? 'orange' : 'positive'"
            @click="carouselSave"
            :loading="loading"
            :icon="carousel.id ? 'edit' : 'save'"
            unelevated
            no-caps
          />
        </q-card-actions>
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
      archivo: null,
      dialog: false,
      loading: false,
      cargando: true,
      filter: '',
      tipoFiltro: 'Todos',
      tipos: ['Normal', 'Mini', 'Medio', 'Aplicacion']
    }
  },
  computed: {
    carouselsFiltrados () {
      let lista = this.carousels
      if (this.tipoFiltro !== 'Todos') {
        lista = lista.filter(c => (c.tipo || 'Normal') === this.tipoFiltro)
      }
      if (this.filter) {
        const f = this.filter.toLowerCase()
        lista = lista.filter(c =>
          String(c.id).includes(f) ||
          (c.tipo || '').toLowerCase().includes(f) ||
          (c.status || '').toLowerCase().includes(f)
        )
      }
      return lista
    },
    previewSrc () {
      if (this.archivo) {
        return URL.createObjectURL(this.archivo)
      }
      if (this.carousel.image) {
        return `${this.$url}../images/${this.carousel.image}`
      }
      return ''
    }
  },
  mounted () {
    this.getCarousels()
  },
  methods: {
    tipoColor (tipo) {
      const colores = {
        Todos: 'grey-8',
        Normal: 'primary',
        Mini: 'teal',
        Medio: 'deep-orange',
        Aplicacion: 'purple'
      }
      return colores[tipo] || 'primary'
    },
    tipoIcono (tipo) {
      const iconos = {
        Todos: 'apps',
        Normal: 'view_carousel',
        Mini: 'photo_size_select_small',
        Medio: 'crop_landscape',
        Aplicacion: 'smartphone'
      }
      return iconos[tipo] || 'view_carousel'
    },
    onRejected () {
      this.$alert.error('La imagen debe pesar máximo 2MB')
    },
    carouselCreate () {
      this.archivo = null
      this.carousel = {
        image: '',
        status: 'active',
        tipo: 'Normal'
      }
      this.dialog = true
    },
    carouselEdit (carousel) {
      this.archivo = null
      this.carousel = { ...carousel, tipo: carousel.tipo || 'Normal' }
      this.dialog = true
    },
    getCarousels () {
      this.cargando = true
      this.$axios.get('carousels')
        .then(response => {
          this.carousels = response.data
        })
        .catch(error => {
          console.log(error)
        }).finally(() => {
          this.cargando = false
        })
    },
    carouselSave () {
      if (!this.carousel.id && !this.archivo) {
        this.$alert.error('Debe seleccionar una imagen')
        return false
      }
      this.loading = true
      if (this.archivo) {
        const formData = new FormData()
        formData.append('file', this.archivo)
        formData.append('tipo', this.carousel.tipo)
        const peticion = this.carousel.id
          ? this.$axios.post('carouselsFile/' + this.carousel.id, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
          : this.$axios.post('carousels', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        peticion
          .then(() => {
            this.getCarousels()
            this.dialog = false
          })
          .catch(error => {
            this.$alert.error(error.response?.data?.message || 'Error al guardar')
          }).finally(() => {
            this.loading = false
          })
      } else {
        this.$axios.put('carousels/' + this.carousel.id, this.carousel)
          .then(() => {
            this.getCarousels()
            this.dialog = false
          })
          .catch(error => {
            this.$alert.error(error.response?.data?.message || 'Error al actualizar')
          }).finally(() => {
            this.loading = false
          })
      }
    },
    carouselToggle (carousel) {
      const nuevoEstado = carousel.status === 'active' ? 'inactive' : 'active'
      this.$axios.put('carousels/' + carousel.id, { ...carousel, status: nuevoEstado })
        .then(() => {
          carousel.status = nuevoEstado
        })
        .catch(error => {
          console.log(error)
        })
    },
    carouselDelete (carousel) {
      this.$q.dialog({
        title: 'Eliminar imagen',
        message: `¿Está seguro de eliminar la imagen #${carousel.id} (${carousel.tipo || 'Normal'})?`,
        cancel: { label: 'Cancelar', flat: true, color: 'grey-8' },
        ok: { label: 'Eliminar', color: 'negative', unelevated: true },
        persistent: true
      }).onOk(() => {
        this.$axios.delete('carousels/' + carousel.id)
          .then(() => {
            this.getCarousels()
          })
          .catch(error => {
            console.log(error)
          })
      })
    }
  }
}
</script>
<style scoped>
.carousel-card {
  transition: box-shadow 0.2s, transform 0.2s;
}
.carousel-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}
</style>
