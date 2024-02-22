<template>
  <div class="q-pa-md">
    <q-table dense :filter="filter" title="Agencias" :rows="data" :columns="columns" row-key="name" :rows-per-page-options="[0]">
      <template v-slot:top-right>
<!--        <q-btn-->
<!--          label="Nuevo Unidad"-->
<!--          color="positive"-->
<!--          @click="regDialog"-->
<!--          icon="add_circle_outline"-->
<!--          no-caps-->
<!--        />-->
        <q-input outlined dense debounce="300" v-model="filter" placeholder="Buscar...">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>
      <template v-slot:body-cell-permisos="props">
        <q-td key="permisos" :props="props">
          <ul>
            <li v-for="(p,i) in props.row.permisos" :key="i">{{p.nombre}}</li>
          </ul>
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
<!--          <q-btn-->
<!--            dense-->
<!--            round-->
<!--            flat-->
<!--            color="red"-->
<!--            @click="deleteRow(props)"-->
<!--            icon="delete"-->
<!--          ></q-btn>-->
        </q-td>

      </template>
    </q-table>

  </div>
</template>

<script>
import { date } from 'quasar'
// import moment from 'moment'

export default {
  name: 'AgenciaPage',
  data () {
    return {
      alert: false,
      dialog_mod: false,
      dialog_del: false,
      fecha: date.formatDate(new Date(), 'YYYY-MM-DD'),
      filter: '',
      dato: { },
      model: '',
      dato2: {},
      options: [],
      props: [],
      unidades: [],
      permisos: [],
      permisos2: [],
      modelpermiso: false,
      uni: {},
      columns: [
        { name: 'opcion', label: 'Opciones', field: 'opcion', sortable: false },
        { name: 'id', align: 'left', label: 'Id', field: 'id', sortable: true },
        { name: 'name', align: 'left', label: 'Nombre', field: 'nombre', sortable: true }
      ],
      data: []
    }
  },
  created () {
    this.misdatos()
  },
  methods: {
    regDialog () {
      this.$q.dialog({
        title: 'REGISTRAR UNIDAD',
        message: 'Ingresar nombre de la unidad',
        prompt: {
          model: '',
          type: 'text' // optional
        }
      }).onOk(data => {
        this.$axios.post('agencias', { nombre: data }).then(() => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Registrado correctamente'
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
    },
    misdatos () {
      this.$axios.get('agencias').then((res) => {
        this.data = res.data
      })
    },
    editRow (item) {
      this.$q.dialog({
        title: 'Editar Agencia',
        message: 'Ingresar nombre de la agencia',
        prompt: {
          model: item.row.nombre,
          type: 'text' // optional
        }
      }).onOk(data => {
        this.$axios.put('agencias/' + item.row.id, { nombre: data }).then(() => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Editado correctamente'
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
    },
    deleteRow (item) {
      this.$q.dialog({
        title: 'ELIMINAR UNIDAD',
        message: '¿Esta seguro de eliminar la unidad?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.delete('agencias/' + item.row.id).then(() => {
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
