<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row q-col-gutter-md">
      <div class="col-12 col-lg-4">
        <q-card flat bordered>
          <q-card-section class="bg-blue-grey-10 text-white">
            <div class="text-subtitle1 text-weight-bold">Configuración SIAT</div>
            <div class="text-caption text-grey-4">Variables tomadas desde el backend</div>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input :model-value="config.nit" label="NIT" dense outlined readonly />
              </div>
              <div class="col-6">
                <q-input :model-value="config.codigo_sistema" label="Código sistema" dense outlined readonly />
              </div>
              <div class="col-6">
                <q-input :model-value="config.codigo_modalidad" label="Modalidad" dense outlined readonly />
              </div>
              <div class="col-6">
                <q-input :model-value="config.codigo_ambiente" label="Ambiente" dense outlined readonly />
              </div>
              <div class="col-12">
                <q-input :model-value="config.wsdl_codigos" label="WSDL" dense outlined readonly />
              </div>
              <div class="col-12">
                <q-input :model-value="config.token_masked" label="Token" dense outlined readonly />
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card flat bordered class="q-mt-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Generación</div>
            <div class="text-caption text-grey-7">CUIS anual y CUFD diario por sucursal y punto de venta</div>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input v-model.number="form.codigo_sucursal" type="number" label="Código sucursal" dense outlined />
              </div>
              <div class="col-6">
                <q-input v-model.number="form.codigo_punto_venta" type="number" label="Punto de venta" dense outlined />
              </div>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-actions align="between">
            <q-btn color="primary" icon="verified_user" label="Generar CUIS" :loading="loadingCuis" @click="generarCuis" />
            <q-btn color="deep-orange" icon="confirmation_number" label="Generar CUFD" :loading="loadingCufd" @click="generarCufd" />
          </q-card-actions>
        </q-card>
      </div>

      <div class="col-12 col-lg-8">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-subtitle1 text-weight-bold">Último CUIS</div>
              </q-card-section>
              <q-separator />
              <q-card-section v-if="latestCuis">
                <div class="text-body1 text-weight-bold">{{ latestCuis.codigo }}</div>
                <div class="text-caption text-grey-7">Vigencia: {{ formatDate(latestCuis.fechaVigencia) }}</div>
                <div class="text-caption text-grey-7">Creación: {{ formatDate(latestCuis.fechaCreacion) }}</div>
              </q-card-section>
              <q-card-section v-else class="text-grey-7">
                No hay CUIS para ese contexto.
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-subtitle1 text-weight-bold">Último CUFD</div>
              </q-card-section>
              <q-separator />
              <q-card-section v-if="latestCufd">
                <div class="text-body1 text-weight-bold ellipsis">{{ latestCufd.codigo }}</div>
                <div class="text-caption text-grey-7">Control: {{ latestCufd.codigoControl || '-' }}</div>
                <div class="text-caption text-grey-7">Vigencia: {{ formatDate(latestCufd.fechaVigencia) }}</div>
              </q-card-section>
              <q-card-section v-else class="text-grey-7">
                No hay CUFD para ese contexto.
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-card flat bordered class="q-mt-md">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-subtitle1 text-weight-bold">Historial CUIS</div>
              <div class="text-caption text-grey-7">Estructura solicitada en base de datos</div>
            </div>
            <q-badge color="primary">{{ cuis.length }} registros</q-badge>
          </q-card-section>
          <q-separator />
          <q-table flat dense :rows="cuis" :columns="cuisColumns" row-key="id" :rows-per-page-options="[10, 20, 50]">
            <template v-slot:body-cell-fechaVigencia="props">
              <q-td :props="props">{{ formatDate(props.row.fechaVigencia) }}</q-td>
            </template>
            <template v-slot:body-cell-fechaCreacion="props">
              <q-td :props="props">{{ formatDate(props.row.fechaCreacion) }}</q-td>
            </template>
          </q-table>
        </q-card>

        <q-card flat bordered class="q-mt-md">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-subtitle1 text-weight-bold">Historial CUFD</div>
              <div class="text-caption text-grey-7">Código, código de control y dirección</div>
            </div>
            <q-badge color="deep-orange">{{ cufds.length }} registros</q-badge>
          </q-card-section>
          <q-separator />
          <q-table flat dense :rows="cufds" :columns="cufdColumns" row-key="id" :rows-per-page-options="[10, 20, 50]">
            <template v-slot:body-cell-fechaVigencia="props">
              <q-td :props="props">{{ formatDate(props.row.fechaVigencia) }}</q-td>
            </template>
            <template v-slot:body-cell-fechaCreacion="props">
              <q-td :props="props">{{ formatDate(props.row.fechaCreacion) }}</q-td>
            </template>
          </q-table>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import { date } from 'quasar'

export default {
  name: 'SiatPage',
  data () {
    return {
      loadingCuis: false,
      loadingCufd: false,
      config: {},
      cuis: [],
      cufds: [],
      form: {
        codigo_sucursal: 0,
        codigo_punto_venta: 0
      },
      cuisColumns: [
        { name: 'codigo', label: 'Código CUIS', field: 'codigo', align: 'left', sortable: true },
        { name: 'codigoSucursal', label: 'Sucursal', field: 'codigoSucursal', align: 'center' },
        { name: 'codigoPuntoVenta', label: 'Punto venta', field: 'codigoPuntoVenta', align: 'center' },
        { name: 'fechaVigencia', label: 'Vigencia', field: 'fechaVigencia', align: 'left' },
        { name: 'fechaCreacion', label: 'Creación', field: 'fechaCreacion', align: 'left' }
      ],
      cufdColumns: [
        { name: 'codigo', label: 'Código CUFD', field: 'codigo', align: 'left', sortable: true },
        { name: 'codigoControl', label: 'Código control', field: 'codigoControl', align: 'left' },
        { name: 'direccion', label: 'Dirección', field: 'direccion', align: 'left' },
        { name: 'codigoSucursal', label: 'Sucursal', field: 'codigoSucursal', align: 'center' },
        { name: 'codigoPuntoVenta', label: 'Punto venta', field: 'codigoPuntoVenta', align: 'center' },
        { name: 'fechaVigencia', label: 'Vigencia', field: 'fechaVigencia', align: 'left' },
        { name: 'fechaCreacion', label: 'Creación', field: 'fechaCreacion', align: 'left' }
      ]
    }
  },
  computed: {
    latestCuis () {
      return this.cuis.find(item => this.matchesContext(item)) || null
    },
    latestCufd () {
      return this.cufds.find(item => this.matchesContext(item)) || null
    }
  },
  mounted () {
    this.loadDashboard()
  },
  methods: {
    async loadDashboard () {
      try {
        const [dashboardResponse, cuisResponse, cufdResponse] = await Promise.all([
          this.$axios.get('siat/dashboard'),
          this.$axios.get('siat/cuis'),
          this.$axios.get('siat/cufds')
        ])
        this.config = dashboardResponse.data.config || {}
        this.cuis = cuisResponse.data || []
        this.cufds = cufdResponse.data || []
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'No se pudo cargar el panel SIAT')
      }
    },
    formatDate (value) {
      if (!value) return '-'
      return date.formatDate(value, 'DD/MM/YYYY HH:mm')
    },
    matchesContext (item) {
      return Number(item.codigoSucursal) === Number(this.form.codigo_sucursal) &&
        Number(item.codigoPuntoVenta) === Number(this.form.codigo_punto_venta)
    },
    async generarCuis () {
      this.loadingCuis = true
      try {
        await this.$axios.post('siat/cuis/generar', this.form)
        this.$alert.success('CUIS generado y guardado correctamente')
        await this.loadDashboard()
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'No se pudo generar el CUIS')
      } finally {
        this.loadingCuis = false
      }
    },
    async generarCufd () {
      this.loadingCufd = true
      try {
        await this.$axios.post('siat/cufds/generar', this.form)
        this.$alert.success('CUFD generado y guardado correctamente')
        await this.loadDashboard()
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'No se pudo generar el CUFD')
      } finally {
        this.loadingCufd = false
      }
    }
  }
}
</script>
