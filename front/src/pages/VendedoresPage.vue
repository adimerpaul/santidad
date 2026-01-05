<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card>
      <q-card-section>
        <div class="row items-center">
          <div class="text-h6">Gestión de Vendedores</div>
          <q-space />
          <q-btn
            color="primary"
            icon="add"
            label="Nuevo Vendedor"
            @click="clickCreate"
            unelevated
          />
        </div>
      </q-card-section>

      <q-card-section class="q-pa-none">
        <q-table
          :rows="vendedores"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :filter="filter"
          flat
          bordered
        >
          <template v-slot:top-right>
            <q-input borderless dense debounce="300" v-model="filter" placeholder="Buscar">
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </template>

          <template v-slot:body-cell-proveedor="props">
            <q-td :props="props">
              <q-badge color="blue" v-if="props.row.client">
                {{ props.row.client.nombreRazonSocial }}
              </q-badge>
              <span v-else class="text-grey">Sin asignar</span>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props" auto-width>
              <q-btn flat round dense color="orange" icon="edit" @click="clickEdit(props.row)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat round dense color="negative" icon="delete" @click="clickDelete(props.row)">
                <q-tooltip>Eliminar</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ editMode ? 'Editar Vendedor' : 'Nuevo Vendedor' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit="onSubmit">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-select
                  v-model="vendedor.client_id"
                  :options="proveedores"
                  option-value="id"
                  option-label="nombreRazonSocial"
                  label="Seleccionar Proveedor (Empresa)"
                  outlined
                  dense
                  emit-value
                  map-options
                  :rules="[val => !!val || 'El proveedor es obligatorio']"
                  use-input
                  @filter="filterProveedores"
                >
                  <template v-slot:no-option>
                    <q-item>
                      <q-item-section class="text-grey">Sin resultados</q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>

              <div class="col-12">
                <q-input
                  v-model="vendedor.nombre"
                  label="Nombre Completo del Vendedor"
                  outlined
                  dense
                  :rules="[val => !!val || 'El nombre es obligatorio']"
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="vendedor.celular"
                  label="Celular (WhatsApp)"
                  type="number"
                  outlined
                  dense
                  :rules="[val => !!val || 'El celular es obligatorio']"
                />
              </div>
            </div>

            <div class="row justify-end q-mt-md">
              <q-btn label="Cancelar" color="grey" flat v-close-popup class="q-mr-sm" />
              <q-btn :label="editMode ? 'Actualizar' : 'Guardar'" color="primary" type="submit" :loading="loadingSubmit" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>
<script>
export default {
  name: 'VendedoresPage',
  data () {
    return {
      vendedores: [],
      proveedores: [],
      proveedoresAll: [],
      loading: false,
      loadingSubmit: false,
      dialog: false,
      editMode: false,
      filter: '',
      vendedor: {
        id: null,
        nombre: '',
        celular: '',
        client_id: null
      },
      columns: [
        { name: 'id', label: '#', field: 'id', sortable: true, align: 'left', style: 'width: 50px' },
        { name: 'proveedor', label: 'Empresa / Proveedor', field: row => row.client?.nombreRazonSocial || 'N/A', align: 'left', sortable: true },
        { name: 'nombre', label: 'Nombre Vendedor', field: 'nombre', align: 'left', sortable: true },
        { name: 'celular', label: 'Celular', field: 'celular', align: 'left' },
        { name: 'actions', label: 'Acciones', field: 'actions', align: 'right' }
      ]
    }
  },
  mounted () {
    this.getVendedores()
    this.getProveedores()
  },
  methods: {
    getVendedores () {
      this.loading = true
      this.$axios.get('vendedores').then(res => {
        this.vendedores = res.data
      }).catch(err => {
        // Aquí SÍ usamos err, así que no dará error
        this.$alert.error(err.response?.data?.message || 'Error al cargar vendedores')
      }).finally(() => {
        this.loading = false
      })
    },
    getProveedores () {
      // CAMBIO: Usamos 'providers' que es lo que usas en otras páginas
      this.$axios.get('providers').then(res => {
        this.proveedores = res.data
        this.proveedoresAll = res.data
      }).catch(err => {
        console.error(err) // Usamos err para que no marque error rojo
      })
    },
    filterProveedores (val, update) {
      if (val === '') {
        update(() => { this.proveedores = this.proveedoresAll })
        return
      }
      update(() => {
        const needle = val.toLowerCase()
        this.proveedores = this.proveedoresAll.filter(v => v.nombreRazonSocial.toLowerCase().indexOf(needle) > -1)
      })
    },
    clickCreate () {
      this.editMode = false
      this.vendedor = { nombre: '', celular: '', client_id: null }
      this.dialog = true
    },
    clickEdit (row) {
      this.editMode = true
      this.vendedor = { ...row }
      // Aseguramos que client_id sea el correcto
      if (row.client) {
        this.vendedor.client_id = row.client.id
      }
      this.dialog = true
    },
    onSubmit () {
      this.loadingSubmit = true
      if (this.editMode) {
        this.$axios.put(`vendedores/${this.vendedor.id}`, this.vendedor).then(() => {
          this.$alert.success('Vendedor actualizado correctamente')
          this.getVendedores()
          this.dialog = false
        }).catch(err => {
          console.error(err) // Usamos err para evitar error de linter
          this.$alert.error('Error al actualizar')
        }).finally(() => { this.loadingSubmit = false })
      } else {
        this.$axios.post('vendedores', this.vendedor).then(() => {
          this.$alert.success('Vendedor creado correctamente')
          this.getVendedores()
          this.dialog = false
        }).catch(err => {
          console.error(err) // Usamos err para evitar error de linter
          this.$alert.error('Error al crear')
        }).finally(() => { this.loadingSubmit = false })
      }
    },
    clickDelete (row) {
      this.$q.dialog({
        title: 'Confirmar eliminación',
        message: `¿Seguro que deseas eliminar a <b>${row.nombre}</b>?`,
        html: true,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.delete(`vendedores/${row.id}`).then(() => {
          this.$alert.success('Eliminado correctamente')
          this.getVendedores()
        }).catch(err => {
          console.error(err)
          this.$alert.error('No se pudo eliminar')
        })
      })
    }
  }
}
</script>
