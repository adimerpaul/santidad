<template>
  <q-page class="bg-grey-4 q-pa-xs">
    <q-card>
      <q-card-section class="q-pa-none">
        <div class="row">
          <div class="col-12">
            <div class="text-white bg-orange text-center text-h3 text-bold q-pa-xs">Control de provedores</div>
          </div>
          <div class="col-6 q-pa-xs">
            <q-input v-model="search" label="Buscar cliente por nombre o razón social"
                     dense outlined debounce="300" @update:modelValue="userGet"
                     :loading="loading" clearable counter
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-6 q-pa-xs">
            <q-btn
                color="green"
                label="Nuevo"
                @click="clickNew"
                :loading="loading"
                no-caps
                icon="add_circle_outline"
                text-color="white"
                class="q-ma-xs"
            />
          </div>
          <div class="col-12 flex flex-center">
            <q-pagination
                v-model="page"
                :max-pages="6"
                boundary-numbers
                @update:modelValue="userGet"
                :loading="loading"
                :max="max"
            />
          </div>
          <div class="col-12">
            <q-markup-table dense>
              <thead>
              <tr>
                <th class="text-left">Opciones</th>
                <th class="text-left">Nombre/Razón Social</th>
                <th class="text-left">Codigo Tipo Documento Identidad</th>
                <th class="text-left">Numero Documento</th>
                <th class="text-left">Complemento</th>
                <th class="text-left">Email</th>
                <th class="text-left">id</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="client in clients" :key="client.id">
                <td>
                  <q-btn
                      color="primary"
                      icon="edit"
                      dense
                      size="sm"
                      :loading="loading"
                      @click="clientEdit(client)"
                  />
                  <q-btn
                      color="negative"
                      icon="delete"
                      dense
                      size="sm"
                      :loading="loading"
                      @click="clientDelete(client)"
                  />
                </td>
                <td>{{client.nombreRazonSocial}}</td>
                <td>{{client.codigoTipoDocumentoIdentidad}}</td>
                <td>{{client.numeroDocumento}}</td>
                <td>{{client.complemento}}</td>
                <td>{{client.email}}</td>
                <td>{{client.id}}</td>
              </tr>
              </tbody>
            </q-markup-table>
<!--            <pre>{{clients}}</pre>-->
          </div>
        </div>
      </q-card-section>
    </q-card>
    <q-dialog v-model="clientDialog">
      <q-card>
        <q-card-section>
          <q-form @submit="clientSave">
            <q-input v-model="client.nombreRazonSocial" label="Nombre/Razón Social" dense outlined />
            <q-input v-model="client.codigoTipoDocumentoIdentidad" label="Codigo Tipo Documento Identidad" dense outlined />
            <q-input v-model="client.numeroDocumento" label="Numero Documento" dense outlined />
            <q-input v-model="client.complemento" label="Complemento" dense outlined />
            <q-input v-model="client.email" label="Email" dense outlined />
            <q-input v-model="client.telefono" label="Telefono" dense outlined />
            <q-card-actions align="right">
              <q-btn color="primary" label="Guardar" type="submit" :loading="loading" />
              <q-btn color="negative" label="Cancelar" @click="clientDialog = false" :loading="loading" />
            </q-card-actions>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>
<script>
export default {
  name: 'ProvedoresPage',
  data () {
    return {
      loading: false,
      clients: [],
      client: {},
      clientDialog: false,
      optionClient: '',
      search: '',
      page: 1,
      max: 5
    }
  },
  mounted () {
    this.userGet()
  },
  methods: {
    clientEdit (client) {
      this.clientDialog = true
      this.client = client
      this.optionClient = 'edit'
    },
    clickNew () {
      this.clientDialog = true
      this.client = {}
      this.optionClient = 'new'
    },
    clientSave () {
      this.loading = true
      if (this.optionClient === 'new') {
        this.client.clienteProveedor = 'Proveedor'
        this.$axios.post('clients', this.client)
          .then(response => {
            this.$alert.success('Cliente guardado correctamente')
            this.clientDialog = false
            this.userGet()
          })
          .catch(error => {
            console.log(error)
            this.$alert.error(error.response.data.message)
          }).finally(() => {
            this.loading = false
          })
      } else {
        this.$axios.put(`clients/${this.client.id}`, this.client)
          .then(response => {
            this.$alert.success('Cliente guardado correctamente')
            this.clientDialog = false
            this.userGet()
          })
          .catch(error => {
            console.log(error)
            this.$alert.error(error.response.data.message)
          }).finally(() => {
            this.loading = false
          })
      }
    },
    userGet () {
      this.loading = true
      this.$axios.get(`clientsProvider?page=${this.page}&search=${this.search}`)
        .then(response => {
          this.clients = response.data.data
          this.max = response.data.last_page
        })
        .catch(error => {
          console.log(error)
        }).finally(() => {
          this.loading = false
        })
    },
    clientDelete (client) {
      this.$q.dialog({
        title: 'Eliminar',
        message: `¿Desea eliminar el cliente ${client.nombreRazonSocial}?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.loading = true
        this.$axios.delete(`clients/${client.id}`)
          .then(response => {
            this.$alert.success('Cliente eliminado correctamente')
            this.userGet()
          })
          .catch(error => {
            console.log(error)
            this.$alert.error(error.response.data.message)
          }).finally(() => {
            // this.loading = false
          })
      })
    }
  }
}
</script>
