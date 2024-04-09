<template>
  <div class="q-pa-md">
    <q-dialog v-model="alert">
      <q-card style="width: 300px;max-width: 90vh;">
        <q-card-section class="bg-green-14 text-white">
          <div class="text-h7"><q-icon name="add_circle" /> REGISTRO DE NUEVO USUARIO</div>
        </q-card-section>
        <q-card-section class="q-pt-xs">
          <q-form @submit="onSubmit" @reset="onReset" class="q-gutter-md">
            <div class="row">
              <div class="col-12">
                <q-input
                    filled
                    v-model="dato.name"
                    type="text"
                    label="Nombre "
                    hint="Ingresar Nombre"
                    lazy-rules
                    :rules="[(val) => val.length > 0 || 'Por favor ingresa datos']"
                    ref="nameInput"
                />

                <q-input
                    filled
                    v-model="dato.email"
                    type="email"
                    label="Email"
                    hint="Correo electronico"
                    lazy-rules
                    :rules="[(val) => val.length > 0 || 'Por favor ingresa datos']"
                    ref="emailInput"
                />

                <q-input
                    filled
                    v-model="dato.password"
                    type="password"
                    label="Contraseña"
                    hint="Contraseña"
                    lazy-rules
                    :rules="[(val) => val.length > 0 || 'Por favor ingresa datos']"
                    ref="passwordInput"
                />

              </div>
              <div class="col-12">
                <q-select
                  filled
                  v-model="dato.agencia_id"
                  :options="agencias"
                  label="Agencia"
                  hint="Seleccionar Agencia"
                  lazy-rules
                  :rules="[(val) => val !== null && val !== undefined || 'Por favor selecciona una agencia']"
                  map-options
                  emit-value
                  option-value="id"
                  option-label="nombre"
                />
<!--                <pre>{{dato.agencia_id}}</pre>-->
              </div>
            </div>

            <div>
              <q-btn label="Crear" type="submit" color="positive" icon="add_circle" />
              <q-btn label="Cancelar" icon="delete" color="negative" v-close-popup />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-table dense :filter="filter" title="Gestion de usuarios" :rows="data" :columns="columns" row-key="name" :rows-per-page-options="[50,100]">
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
              size="sm"
              color="primary"
              @click="editRow(props)"
              icon="edit"
          >
            <q-tooltip>Editar</q-tooltip>
          </q-btn>
          <q-btn
              dense
              round
              flat
              @click="cambiopass(props)"
              size="sm"
              color="primary"
              icon="vpn_key"
          >
            <q-tooltip>Cambiar contraseña</q-tooltip>
          </q-btn>
<!--          <q-btn-->
<!--              dense-->
<!--              round-->
<!--              flat-->
<!--              color="green-10"-->
<!--              @click="mispermisos(props)"-->
<!--              icon="post_add"-->
<!--          />-->
          <q-btn
              dense
              round
              flat
              color="primary"
              @click="deleteRow(props)"
              size="sm"
              icon="delete"
          >
            <q-tooltip>Eliminar</q-tooltip>
          </q-btn>
        </q-td>

      </template>
    </q-table>
<!--    <pre>{{data}}</pre>-->

    <q-dialog v-model="dialog_mod">
      <q-card style="max-width: 80%; width: 50%">
        <q-card-section class="bg-warning text-white">
          <div class="text-h7"> <q-icon name="edit"/> MODIFICAR DATOS DE USUARIO</div>
        </q-card-section>
        <q-card-section class="q-pt-xs">
          <q-form @submit="onMod" class="q-gutter-md">
            <q-input
                filled
                v-model="dato2.name"
                type="text"
                label="Nombre "
                hint="Ingresar Nombre"
                lazy-rules
                :rules="[(val) => val.length > 0 || 'Por favor ingresa datos']"
            />

            <q-input
                filled
                v-model="dato2.email"
                type="email"
                label="Email"
                hint="Correo electronico"
                lazy-rules
                :rules="[(val) => val.length > 0 || 'Por favor ingresa datos']"
            />
            <q-select
              filled
              v-model="dato2.agencia_id"
              :options="agencias"
              label="Agencia"
              hint="Seleccionar Agencia"
              lazy-rules
              :rules="[(val) => val !== null && val !== undefined || 'Por favor selecciona una agencia']"
              map-options
              emit-value
              option-value="id"
              option-label="nombre"
            />
<!--            <pre>{{dato2}}</pre>-->
            <div>
              <q-btn label="Modificar" type="submit" color="positive" icon="add_circle" />
              <q-btn label="Cancelar" icon="delete" color="negative" v-close-popup />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialog_del">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="clear" color="red" text-color="white" />
          <span class="q-ml-sm">Seguro de eliminar Registro.</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Eliminar" color="deep-orange" @click="onDel" />
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="modelpermiso">
      <q-card style="width: 700px;max-width: 80vw">
        <q-card-section class="bg-info">
          <div class="text-h7 text-white"><q-icon name="folder"/> PERMISOS DE ACCESO</div>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="updatepermisos">
            <!--          v-on:click.native="updatepermiso(perfmiso)"-->
            <q-checkbox style="width: 100%"  v-for="(permiso,index) in permisos2" :key="index" :label="permiso.nombre" v-model="permiso.estado" />
            <!--          <q-form>-->
            <!--&lt;!&ndash;            <q-checkbox v-model="permisos" />&ndash;&gt;-->
            <!--          </q-form>-->
            <q-btn  type="submit" color="info" icon="send" label="Actualizar"></q-btn>
          </q-form>
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
        { name: 'opcion', label: 'OPCIÓN', field: 'action', sortable: false },
        { name: 'name', align: 'left', label: 'NOMBRE ', field: 'name', sortable: true },
        { name: 'email', align: 'left', label: 'E-MAIL', field: 'email', sortable: true },
        { name: 'agencia', align: 'left', label: 'AGENCIA', field: (row) => row.agencia.nombre, sortable: true }
        // { name: 'permisos', align: 'left', label: 'PERMISOS', field: 'permisos', sortable: true },
      ],
      data: [],
      agencias: []
    }
  },
  created () {
    /* if (!this.$store.state.login.boolusuario){
       this.$router.replace({ path: '/' })
    } */
    this.agenciaGet()
    this.misdatos()
    // this.$axios.get('permiso').then(res => {
    //   res.data.forEach(r => {
    //     this.permisos.push({ id: r.id, nombre: r.nombre, estado: false })
    //     this.permisos2.push({ id: r.id, nombre: r.nombre, estado: false })
    //   })
    // })
  },
  methods: {
    agenciaGet () {
      this.$axios.get('agencias').then(res => {
        this.agencias = res.data
      })
    },
    regDialog () {
      this.dato = {
        name: '',
        email: '',
        password: ''
      }
      this.alert = true
    },
    updatepermisos () {
      this.$axios.put('updatepermisos/' + this.dato2.id, { permisos: this.permisos2 }).then(() => {
        // console.log(res.data)
        this.modelpermiso = false
        this.misdatos()
      }).catch(err => {
        this.loading = false
        this.$q.alert(err.response.data.message)
      })
    },
    mispermisos (i) {
      // console.log(i.row)
      this.modelpermiso = true
      this.dato2 = i.row
      let p
      this.permisos2.forEach(pe => {
        // console.log(pe);
        p = this.dato2.permisos.find(r => r.pivot.permiso_id === pe.id)
        // console.log(p)
        if (p !== undefined) { pe.estado = true } else { pe.estado = false }
        // console.log(p)
      })
    },
    misdatos () {
      this.$q.loading.show()
      this.$axios.get('user').then((res) => {
        console.log(res.data)
        this.data = res.data
        this.$q.loading.hide()
      })
    },
    editRow (item) {
      this.dato2 = item.row
      this.dato2.unid_id = item.row.unid
      // console.log(this.dato2)
      this.dialog_mod = true
    },
    deleteRow (item) {
      this.dato2 = item.row
      this.dialog_del = true
    },
    onSubmit () {
      this.$q.loading.show()
      this.$axios.post('user', {
        name: this.dato.name,
        password: this.dato.password,
        state: 'active',
        email: this.dato.email,
        permisos: this.permisos,
        agencia_id: this.dato.agencia_id
      }).then(() => {
        // console.log(res.data)
        this.$alert.success('Registrado correctamente')
        // this.dato = { fechaLimite: (moment(this.fecha).add(36, 'months').format('YYYY-MM-DD')) }
        this.alert = false
        this.misdatos()
      }).catch(err => {
        this.$alert.error(err.response.data.message)
        this.$q.loading.hide()
      })
    },
    onMod () {
      this.$q.loading.show()
      this.$axios.put('user/' + this.dato2.id, {
        name: this.dato2.name,
        email: this.dato2.email,
        agencia_id: this.dato2.agencia_id
      }).then(() => {
        this.$alert.success('Modificado correctamente')
        this.dialog_mod = false
        this.misdatos()
      }).catch(err => {
        this.$alert.error(err.response.data.message)
        this.$q.loading.hide()
      })
    },
    onDel () {
      this.$q.loading.show()
      this.$axios.delete('user/' + this.dato2.id)
        .then(() => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Eliminado correctamente'
          })
          this.dialog_del = false
          this.misdatos()
        }).catch(err => {
          this.$q.loading.hide()
          this.$q.notify({
            message: err.response.data.message,
            icon: 'error',
            color: 'red'
          })
        })
    },
    onReset () {
      this.dato.nombre = null
      this.dato.inicio = 0
      this.dato.fin = 0
    },
    cambiopass (i) {
      // console.log(i.row);
      this.$q.dialog({
        title: 'CAMBIAR PASSWORD',
        message: 'Ingresar nueva contraseña',
        prompt: {
          model: '',
          type: 'password' // optional
        },
        cancel: true,
        persistent: true
      }).onOk(data => {
        this.$q.loading.show()
        this.$axios.put('updatePassword/' + i.row.id, { password: data }).then(() => {
          this.$q.loading.hide()
        })
      }).onCancel(() => {
      }).onDismiss(() => {
      })
    }
  }
}
</script>
