<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row q-col-gutter-md">

      <!-- Izquierda: config + generación -->
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
            <div class="text-subtitle1 text-weight-bold">Generación CUIS / CUFD</div>
            <div class="text-caption text-grey-7">Selecciona la sucursal y genera los códigos</div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <q-select
                  v-model="form.codigo_sucursal"
                  :options="sucursalOptions"
                  label="Sucursal"
                  dense outlined
                  emit-value map-options
                />
              </div>
              <div class="col-12">
                <q-input v-model.number="form.codigo_punto_venta" type="number" label="Punto de venta" dense outlined />
              </div>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-actions align="between">
            <q-btn color="primary" icon="verified_user" label="Generar CUIS" :loading="loadingCuis" @click="generarCuis" no-caps />
            <q-btn color="deep-orange" icon="confirmation_number" label="Generar CUFD" :loading="loadingCufd" @click="generarCufd" no-caps />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Derecha: estado por sucursal + tablas -->
      <div class="col-12 col-lg-8">

        <!-- Estado de cada sucursal -->
        <div v-for="suc in sucursales" :key="suc.value" class="q-mb-md">
          <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-sm">
            <q-icon name="store" class="q-mr-xs" />
            {{ suc.label }}
          </div>
          <div class="row q-col-gutter-sm">
            <!-- CUIS de esta sucursal -->
            <div class="col-12 col-sm-6">
              <q-card flat bordered>
                <q-card-section class="q-pb-xs">
                  <div class="row items-center justify-between">
                    <div class="text-subtitle2 text-weight-bold">CUIS</div>
                    <q-badge :color="cuisBadgeColor(suc.value)" :label="cuisBadgeLabel(suc.value)" />
                  </div>
                </q-card-section>
                <q-separator />
                <q-card-section v-if="latestCuisPorSucursal(suc.value)" class="q-pt-sm">
                  <div class="text-body2 text-weight-bold text-mono ellipsis">
                    {{ latestCuisPorSucursal(suc.value).codigo }}
                  </div>
                  <div class="text-caption text-grey-7 q-mt-xs">
                    Vigencia: {{ formatDate(latestCuisPorSucursal(suc.value).fechaVigencia) }}
                  </div>
                  <div class="text-caption text-grey-7">
                    Creación: {{ formatDate(latestCuisPorSucursal(suc.value).fechaCreacion) }}
                  </div>
                </q-card-section>
                <q-card-section v-else class="text-grey-6 text-caption">
                  <q-icon name="warning" color="orange" /> Sin CUIS — genera uno primero
                </q-card-section>
              </q-card>
            </div>

            <!-- CUFD de esta sucursal -->
            <div class="col-12 col-sm-6">
              <q-card flat bordered>
                <q-card-section class="q-pb-xs">
                  <div class="row items-center justify-between">
                    <div class="text-subtitle2 text-weight-bold">CUFD</div>
                    <q-badge :color="cufdBadgeColor(suc.value)" :label="cufdBadgeLabel(suc.value)" />
                  </div>
                </q-card-section>
                <q-separator />
                <q-card-section v-if="latestCufdPorSucursal(suc.value)" class="q-pt-sm">
                  <div class="text-body2 text-weight-bold text-mono ellipsis" style="font-size:11px">
                    {{ latestCufdPorSucursal(suc.value).codigo }}
                  </div>
                  <div class="text-caption text-grey-7 q-mt-xs">
                    Control: {{ latestCufdPorSucursal(suc.value).codigoControl || '-' }}
                  </div>
                  <div class="text-caption text-grey-7">
                    Vigencia: {{ formatDate(latestCufdPorSucursal(suc.value).fechaVigencia) }}
                  </div>
                </q-card-section>
                <q-card-section v-else class="text-grey-6 text-caption">
                  <q-icon name="warning" color="orange" /> Sin CUFD — genera uno primero
                </q-card-section>
              </q-card>
            </div>
          </div>
        </div>

        <!-- Historial CUIS -->
        <q-card flat bordered class="q-mt-md">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-subtitle1 text-weight-bold">Historial CUIS</div>
              <div class="text-caption text-grey-7">Todos los registros en base de datos</div>
            </div>
            <q-badge color="primary">{{ cuis.length }} registros</q-badge>
          </q-card-section>
          <q-separator />
          <q-table flat dense :rows="cuis" :columns="cuisColumns" row-key="id" :rows-per-page-options="[10, 20, 50]">
            <template v-slot:body-cell-codigoSucursal="props">
              <q-td :props="props">
                <q-badge :color="props.row.codigoSucursal === 0 ? 'purple' : 'blue'">
                  {{ props.row.codigoSucursal === 0 ? 'Casa Matriz' : 'Suc. ' + props.row.codigoSucursal }}
                </q-badge>
              </q-td>
            </template>
            <template v-slot:body-cell-fechaVigencia="props">
              <q-td :props="props">{{ formatDate(props.row.fechaVigencia) }}</q-td>
            </template>
            <template v-slot:body-cell-fechaCreacion="props">
              <q-td :props="props">{{ formatDate(props.row.fechaCreacion) }}</q-td>
            </template>
          </q-table>
        </q-card>

        <!-- Historial CUFD -->
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
            <template v-slot:body-cell-codigoSucursal="props">
              <q-td :props="props">
                <q-badge :color="props.row.codigoSucursal === 0 ? 'purple' : 'blue'">
                  {{ props.row.codigoSucursal === 0 ? 'Casa Matriz' : 'Suc. ' + props.row.codigoSucursal }}
                </q-badge>
              </q-td>
            </template>
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
      sucursalOptions: [
        { label: 'Sucursal 0 — Casa Matriz', value: 0 },
        { label: 'Sucursal 1', value: 1 }
      ],
      sucursales: [
        { label: 'Sucursal 0 — Casa Matriz', value: 0 },
        { label: 'Sucursal 1', value: 1 }
      ],
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
    latestCuisPorSucursal (codigoSucursal) {
      return this.cuis.find(item =>
        Number(item.codigoSucursal) === codigoSucursal &&
        Number(item.codigoPuntoVenta) === 0
      ) || null
    },
    latestCufdPorSucursal (codigoSucursal) {
      return this.cufds.find(item =>
        Number(item.codigoSucursal) === codigoSucursal &&
        Number(item.codigoPuntoVenta) === 0
      ) || null
    },
    cuisBadgeColor (codigoSucursal) {
      const item = this.latestCuisPorSucursal(codigoSucursal)
      if (!item) return 'grey'
      return new Date(item.fechaVigencia) > new Date() ? 'positive' : 'negative'
    },
    cuisBadgeLabel (codigoSucursal) {
      const item = this.latestCuisPorSucursal(codigoSucursal)
      if (!item) return 'Sin CUIS'
      return new Date(item.fechaVigencia) > new Date() ? 'Vigente' : 'Vencido'
    },
    cufdBadgeColor (codigoSucursal) {
      const item = this.latestCufdPorSucursal(codigoSucursal)
      if (!item) return 'grey'
      return new Date(item.fechaVigencia) > new Date() ? 'positive' : 'negative'
    },
    cufdBadgeLabel (codigoSucursal) {
      const item = this.latestCufdPorSucursal(codigoSucursal)
      if (!item) return 'Sin CUFD'
      return new Date(item.fechaVigencia) > new Date() ? 'Vigente' : 'Vencido'
    },
    async generarCuis () {
      this.loadingCuis = true
      try {
        await this.$axios.post('siat/cuis/generar', this.form)
        this.$alert.success('CUIS generado correctamente para ' + this.sucursalLabel)
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
        this.$alert.success('CUFD generado correctamente para ' + this.sucursalLabel)
        await this.loadDashboard()
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'No se pudo generar el CUFD')
      } finally {
        this.loadingCufd = false
      }
    }
  },
  computed: {
    sucursalLabel () {
      return this.sucursalOptions.find(o => o.value === this.form.codigo_sucursal)?.label || ''
    }
  }
}
</script>
