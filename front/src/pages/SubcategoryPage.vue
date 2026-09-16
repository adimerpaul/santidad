<template>
  <div class="q-pa-md">
    <q-table dense :filter="filter" title="Control subcategorias" :rows="data" :columns="columns" row-key="name" :rows-per-page-options="[0]">
      <template v-slot:top-right>
        <q-btn
          label="Actualizar"
          color="grey"
          @click="misdatos"
          size="sm"
          icon="refresh"
          no-caps
          />
        <q-btn
          label="Nuevo Unidad"
          color="positive"
          @click="regDialog"
          icon="add_circle_outline"
          no-caps
        />
        <q-input outlined dense debounce="300" v-model="filter" placeholder="Buscar...">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>
      <template v-slot:body-cell-category_id="props">
        <q-td key="categoria_id" :props="props">
          <q-chip v-if="props.row.category_id" :label="props.row.category.name" color="primary" text-color="white" />
        </q-td>
      </template>
      <template v-slot:body-cell-opcion="props">
        <q-td key="opcion" :props="props" auto-width>
          <q-btn
            dense
            round
            flat
            color="yellow"
            @click="editRow(props)"
            icon="edit"
          />
          <q-btn
            dense
            round
            flat
            color="red"
            @click="deleteRow(props)"
            icon="delete"
          ></q-btn>
        </q-td>
      </template>
    </q-table>
      <q-dialog v-model="dialogSubcategoria">
        <q-card>
          <q-card-section>
            <q-card-title>
              <div class="text-h6">{{ option === 'create' ? 'Nueva Subcategoria' : 'Editar Subcategoria' }}</div>
            </q-card-title>
            <q-card-section>
              <q-form @submit="formSubcategoria">
              <q-input v-model="dato.name" outlined label="Nombre" />
              <q-select
                v-model="dato.category_id"
                :options="categorias"
                outlined
                label="Categoria"
                emit-value
                map-options
                option-label="name"
                option-value="id"
              />
                <q-card-actions align="right">
                  <q-btn label="Cancelar" color="negative" @click="dialogSubcategoria = false" />
                  <q-btn label="Registrar" color="positive" type="submit" />
                </q-card-actions>
              </q-form>
<!--              <pre>{{dato}}</pre>-->
            </q-card-section>

          </q-card-section>
        </q-card>
      </q-dialog>
  </div>
</template>

<script>
import { date } from 'quasar'
// import moment from 'moment'

export default {
  name: 'UserPage',
  data () {
    return {
      dialogSubcategoria: false,
      categorias: [],
      alert: false,
      dialog_mod: false,
      dialog_del: false,
      fecha: date.formatDate(new Date(), 'YYYY-MM-DD'),
      filter: '',
      dato: { },
      model: '',
      dato2: {},
      options: [],
      option: '',
      props: [],
      unidades: [],
      permisos: [],
      permisos2: [],
      modelpermiso: false,
      uni: {},
      columns: [
        { name: 'opcion', label: 'Opciones', field: 'opcion', sortable: false },
        { name: 'id', align: 'left', label: 'Id', field: 'id', sortable: true },
        { name: 'name', align: 'left', label: 'Nombre', field: 'name', sortable: true },
        { name: 'category_id', align: 'left', label: 'Categoria', field: 'category_id', sortable: true }
      ],
      data: []
    }
  },
  created () {
    this.misdatos()
    this.$axios.get('categories').then((res) => {
      this.categorias = res.data
    })
  },
  methods: {
    formSubcategoria () {
      if (this.option === 'create') {
        this.$axios.post('subcategories', this.dato).then(() => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Registrado correctamente'
          })
          this.dialogSubcategoria = false
          this.misdatos()
        }).catch(err => {
          this.$q.notify({
            message: err.response.data.message,
            icon: 'error',
            color: 'red'
          })
        })
      } else {
        this.$axios.put('subcategories/' + this.dato.id, this.dato).then(() => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Editado correctamente'
          })
          this.dialogSubcategoria = false
          this.misdatos()
        }).catch(err => {
          this.$q.notify({
            message: err.response.data.message,
            icon: 'error',
            color: 'red'
          })
        })
      }
    },
    regDialog () {
      this.dialogSubcategoria = true
      this.option = 'create'
      this.dato = {}
    },
    misdatos () {
      this.$axios.get('subcategories').then((res) => {
        this.data = res.data
      })
    },
    editRow (item) {
      this.option = 'edit'
      this.dato = {
        id: item.row.id,
        name: item.row.name,
        category_id: item.row.category_id
      }
      this.dialogSubcategoria = true
    },
    deleteRow (item) {
      this.$q.dialog({
        title: 'ELIMINAR UNIDAD',
        message: '¿Esta seguro de eliminar la unidad?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.delete('subcategories/' + item.row.id).then(() => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Eliminado correctamente'
          })
          this.misdatos()
        }).catch(err => {
          this.$q.notify({
            message: err.response.data.message,
            icon: 'error',
            color: 'red'
          })
        })
      }).onCancel(() => {
      }).onDismiss(() => {
      })
    }
  }
}
</script>
