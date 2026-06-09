<template>
  <div class="q-pa-md">
    <q-table dense :filter="filter" title="Agencias" :rows="data" :columns="columns" row-key="id" :rows-per-page-options="[0]">
      <template v-slot:top-right>
        <q-btn
          label="Nueva Agencia"
          color="positive"
          @click="abrirCrear"
          icon="add_circle_outline"
          no-caps
          class="q-mr-sm"
        />
        <q-input outlined dense debounce="300" v-model="filter" placeholder="Buscar...">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>

      <template v-slot:body-cell-sucursal="props">
        <q-td key="sucursal" :props="props">
          <q-badge :color="props.row.sucursal === 0 ? 'purple' : 'blue'">
            {{ props.row.sucursal === 0 ? 'Casa Matriz' : 'Sucursal ' + props.row.sucursal }}
          </q-badge>
        </q-td>
      </template>

      <template v-slot:body-cell-opcion="props">
        <q-td key="opcion" :props="props" auto-width>
          <q-btn-dropdown dense flat color="primary" icon="more_vert" no-caps dropdown-icon="none">
            <q-list style="min-width: 140px">
              <q-item clickable v-close-popup @click="abrirEditar(props.row)">
                <q-item-section avatar>
                  <q-icon color="amber-7" name="edit" />
                </q-item-section>
                <q-item-section class="text-amber-7 text-weight-medium">Editar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <!-- Dialog crear / editar -->
    <q-dialog v-model="dialogForm" persistent>
      <q-card style="min-width: 380px">
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">{{ modoEditar ? 'Editar Agencia' : 'Nueva Agencia' }}</div>
        </q-card-section>

        <q-card-section class="q-gutter-sm q-pt-md">
          <q-input
            v-model="form.nombre"
            label="Nombre"
            outlined
            dense
            autofocus
          />
          <q-select
            v-model="form.sucursal"
            :options="sucursalOptions"
            label="Sucursal"
            outlined
            dense
            emit-value
            map-options
          />
        </q-card-section>

        <q-card-actions align="right" class="q-pb-md q-pr-md">
          <q-btn flat label="Cancelar" color="grey" v-close-popup no-caps />
          <q-btn color="primary" label="Guardar" @click="guardar" no-caps :loading="cargando" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
export default {
  name: 'AgenciaPage',
  data () {
    return {
      filter: '',
      dialogForm: false,
      modoEditar: false,
      cargando: false,
      form: { nombre: '', sucursal: 0 },
      editId: null,
      sucursalOptions: [
        { label: 'Casa Matriz', value: 0 },
        { label: 'Sucursal 1', value: 1 }
      ],
      columns: [
        { name: 'opcion', label: 'Opciones', field: 'opcion', sortable: false },
        { name: 'id', align: 'left', label: 'Id', field: 'id', sortable: true },
        { name: 'name', align: 'left', label: 'Nombre', field: 'nombre', sortable: true },
        { name: 'sucursal', align: 'center', label: 'Sucursal', field: 'sucursal', sortable: true }
      ],
      data: []
    }
  },
  created () {
    this.misdatos()
  },
  methods: {
    misdatos () {
      this.$axios.get('agencias').then((res) => {
        this.data = res.data
      })
    },
    abrirCrear () {
      this.modoEditar = false
      this.editId = null
      this.form = { nombre: '', sucursal: 0 }
      this.dialogForm = true
    },
    abrirEditar (row) {
      this.modoEditar = true
      this.editId = row.id
      this.form = { nombre: row.nombre, sucursal: row.sucursal ?? 0 }
      this.dialogForm = true
    },
    guardar () {
      if (!this.form.nombre.trim()) {
        this.$q.notify({ message: 'El nombre es requerido', color: 'red', icon: 'error' })
        return
      }
      this.cargando = true
      const req = this.modoEditar
        ? this.$axios.put('agencias/' + this.editId, this.form)
        : this.$axios.post('agencias', this.form)

      req.then(() => {
        this.$q.notify({ color: 'green-4', textColor: 'white', icon: 'cloud_done', message: this.modoEditar ? 'Editado correctamente' : 'Registrado correctamente' })
        this.dialogForm = false
        this.misdatos()
      }).catch(err => {
        this.$q.notify({ message: err.response?.data?.message ?? 'Error al guardar', icon: 'error', color: 'red' })
      }).finally(() => {
        this.cargando = false
      })
    }
  }
}
</script>
