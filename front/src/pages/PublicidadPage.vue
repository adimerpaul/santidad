<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="text-h6">Gestión de Publicidad TV</div>
      <q-space />
      <q-btn label="Subir Publicidad" icon="upload" color="primary" @click="showUploadDialog = true" />
    </div>

    <div class="row q-col-gutter-md">
      <!-- Tabla de administración -->
      <div class="col-12">
        <q-card flat bordered class="q-pa-sm">
          <q-table
            :rows="publicidades"
            :columns="columns"
            row-key="id"
            :loading="loading"
            flat
          >
            <template v-slot:body-cell-preview="props">
              <q-td :props="props">
                <q-btn
                  flat
                  round
                  color="primary"
                  icon="visibility"
                  @click="openDriveLink(props.row.file_id)"
                >
                  <q-tooltip>Ver en Google Drive</q-tooltip>
                </q-btn>
              </q-td>
            </template>
            <template v-slot:body-cell-active="props">
              <q-td :props="props">
                <q-toggle
                  v-model="props.row.active"
                  :true-value="1"
                  :false-value="0"
                  @update:model-value="toggleActive(props.row)"
                />
              </q-td>
            </template>
            <template v-slot:body-cell-type="props">
              <q-td :props="props">
                <q-chip :color="props.row.type === 'video' ? 'blue' : 'green'" text-color="white" size="sm">
                  {{ props.row.type }}
                </q-chip>
              </q-td>
            </template>
            <template v-slot:body-cell-actions="props">
              <q-td :props="props" class="q-gutter-xs">
                <q-btn icon="delete" color="negative" flat round size="sm" @click="deletePublicidad(props.row)" />
              </q-td>
            </template>
          </q-table>
        </q-card>
      </div>
    </div>

    <!-- Diálogo de subida -->
    <q-dialog v-model="showUploadDialog" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Subir Nueva Publicidad</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="form.name" label="Nombre de la publicidad" :rules="[val => !!val || 'El nombre es requerido']" />
          <q-select
            v-model="form.agencia_id"
            :options="agenciasOptions"
            label="Sucursal"
            emit-value
            map-options
            class="q-mb-md"
          />
          <q-file
            v-model="form.file"
            label="Seleccionar Archivo (Video o Imagen)"
            accept="video/*,image/*"
            hint="Maximo 200MB"
            :rules="[val => !!val || 'El archivo es requerido']"
            @update:model-value="onFileSelected"
          >
            <template v-slot:prepend>
              <q-icon name="attach_file" />
            </template>
          </q-file>

          <div v-if="uploading" class="q-mt-md">
            <div class="text-caption text-grey-8">Subiendo: {{ (uploadProgress * 100).toFixed(0) }}%</div>
            <q-linear-progress :value="uploadProgress" color="primary" class="q-mt-sm" />
          </div>

          <div v-if="previewUrl && !uploading" class="q-mt-md flex justify-center border-grey-4 rounded-borders q-pa-sm" style="border: 1px solid #ddd">
            <q-img
              v-if="isImage"
              :src="previewUrl"
              style="max-height: 200px; max-width: 100%"
              contain
            />
            <video
              v-else-if="isVideo"
              :src="previewUrl"
              style="max-height: 200px; max-width: 100%"
              controls
            ></video>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup @click="clearForm" />
          <q-btn flat label="Subir" :loading="uploading" @click="uploadFile" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showVideoDialog">
      <q-card style="width: 800px; max-width: 90vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Vista Previa de Video</div>
          <q-space />
          <q-btn icon="close" flat round v-close-popup />
        </q-card-section>

        <q-card-section>
          <video :src="currentVideoUrl" style="width: 100%" controls autoplay></video>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  data () {
    return {
      publicidades: [],
      loading: false,
      showUploadDialog: false,
      uploading: false,
      uploadProgress: 0,
      previewUrl: null,
      isImage: false,
      isVideo: false,
      showVideoDialog: false,
      currentVideoUrl: '',
      agencias: [],
      form: {
        name: '',
        file: null,
        agencia_id: null
      },
      columns: [
        { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
        { name: 'preview', label: 'Vista Previa', field: 'url', align: 'center' },
        { name: 'agencia', label: 'Sucursal', field: row => row.agencia ? row.agencia.nombre : 'TODAS', align: 'left', sortable: true },
        { name: 'name', label: 'Nombre', field: 'name', align: 'left', sortable: true },
        { name: 'type', label: 'Tipo', field: 'type', align: 'center' },
        { name: 'active', label: 'Activo', field: 'active', align: 'center' },
        { name: 'created_at', label: 'Fecha', field: row => row.created_at.substring(0, 10), align: 'left' },
        { name: 'actions', label: 'Acciones', field: 'actions', align: 'center' }
      ]
    }
  },
  computed: {
    agenciasOptions () {
      const options = [{ label: 'TODAS LAS SUCURSALES (GLOBAL)', value: null }]
      this.agencias.forEach(a => {
        options.push({ label: a.nombre, value: a.id })
      })
      return options
    }
  },
  mounted () {
    this.getPublicidades()
    this.getAgencias()
  },
  methods: {
    getAgencias () {
      this.$axios.get('agencias')
        .then(res => {
          this.agencias = res.data
        })
    },
    onFileSelected (file) {
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl)
      }

      if (!file) {
        this.previewUrl = null
        this.isImage = false
        this.isVideo = false
        return
      }

      this.previewUrl = URL.createObjectURL(file)
      this.isImage = file.type.startsWith('image/')
      this.isVideo = file.type.startsWith('video/')
    },
    clearForm () {
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl)
      }
      this.form = { name: '', file: null, agencia_id: null }
      this.previewUrl = null
      this.isImage = false
      this.isVideo = false
      this.uploadProgress = 0
    },
    openDriveLink (fileId) {
      if (!fileId) return
      window.open(`https://drive.google.com/file/d/${fileId}/view`, '_blank')
    },
    getPublicidades () {
      this.loading = true
      this.$axios.get('publicidad')
        .then(res => {
          this.publicidades = res.data
        })
        .finally(() => {
          this.loading = false
        })
    },
    uploadFile () {
      if (!this.form.name || !this.form.file) {
        this.$q.notify({ message: 'Todos los campos son requeridos', color: 'negative' })
        return
      }

      this.uploading = true
      this.uploadProgress = 0
      const formData = new FormData()
      formData.append('name', this.form.name)
      formData.append('file', this.form.file)
      if (this.form.agencia_id) {
        formData.append('agencia_id', this.form.agencia_id)
      }

      this.$axios.post('publicidad', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
          this.uploadProgress = progressEvent.loaded / progressEvent.total
        }
      })
        .then(res => {
          this.$q.notify({ message: 'Publicidad subida correctamente', color: 'positive' })
          this.showUploadDialog = false
          this.clearForm()
          this.getPublicidades()
        })
        .catch(err => {
          this.$q.notify({ message: 'Error al subir: ' + (err.response?.data?.error || err.message), color: 'negative' })
        })
        .finally(() => {
          this.uploading = false
        })
    },
    toggleActive (row) {
      this.$axios.post(`publicidad/${row.id}/toggle`)
        .then(() => {
          this.$q.notify({ message: 'Estado actualizado', color: 'positive', timeout: 500 })
        })
        .catch(() => {
          row.active = row.active === 1 ? 0 : 1
          this.$q.notify({ message: 'Error al actualizar estado', color: 'negative' })
        })
    },
    deletePublicidad (row) {
      this.$q.dialog({
        title: 'Confirmar eliminacion',
        message: '¿Estas seguro de eliminar esta publicidad de Drive y la base de datos?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.delete(`publicidad/${row.id}`)
          .then(() => {
            this.$q.notify({ message: 'Eliminado correctamente', color: 'positive' })
            this.getPublicidades()
          })
          .catch(err => {
            this.$q.notify({ message: 'Error al eliminar: ' + err.message, color: 'negative' })
          })
      })
    }

  }
}
</script>

<style scoped>
</style>
