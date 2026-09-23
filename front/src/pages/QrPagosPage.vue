<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row items-center justify-between q-mb-md q-col-gutter-sm">
      <div class="row items-center">
        <q-icon name="qr_code_2" size="32px" color="primary" class="q-mr-sm" />
        <div>
          <div class="text-h6 text-bold text-grey-9">Pagos QR</div>
          <div class="text-caption text-grey-6">Pagos recibidos en Banco Económico y la venta asociada</div>
        </div>
      </div>

      <q-form class="row items-center q-gutter-sm" @submit.prevent="buscar">
        <q-input v-model="fechaInicio" type="date" outlined dense label="Desde" style="min-width: 150px" />
        <q-input v-model="fechaFin" type="date" outlined dense label="Hasta" style="min-width: 150px" />
        <q-select
          v-model="agenciaFiltro"
          :options="agenciaOptions"
          emit-value
          map-options
          outlined
          dense
          label="Agencia"
          style="min-width: 180px"
        />
        <q-btn type="submit" color="primary" icon="search" label="Buscar" no-caps :loading="loading" />
      </q-form>
    </div>

    <q-banner v-if="errores.length" dense class="bg-red-1 text-red-9 q-mb-md rounded-borders">
      <template #avatar><q-icon name="error_outline" /></template>
      No se pudieron consultar algunos días:
      <div v-for="e in errores" :key="e" class="text-caption">{{ e }}</div>
    </q-banner>

    <div class="row q-col-gutter-sm q-mb-md">
      <div class="col-6 col-md-3">
        <q-card flat bordered class="q-pa-sm">
          <div class="text-caption text-grey-7">Pagos recibidos</div>
          <div class="text-h6 text-bold">{{ pagosFiltrados.length }}</div>
        </q-card>
      </div>
      <div class="col-6 col-md-3">
        <q-card flat bordered class="q-pa-sm">
          <div class="text-caption text-grey-7">Total cobrado</div>
          <div class="text-h6 text-bold text-green-8">Bs {{ totalPagos.toFixed(2) }}</div>
        </q-card>
      </div>
      <div class="col-6 col-md-3">
        <q-card flat bordered class="q-pa-sm">
          <div class="text-caption text-grey-7">Pagos sin venta</div>
          <div class="text-h6 text-bold" :class="pagosSinVenta ? 'text-orange-8' : ''">{{ pagosSinVenta }}</div>
        </q-card>
      </div>
      <div class="col-6 col-md-3">
        <q-card flat bordered class="q-pa-sm">
          <div class="text-caption text-grey-7">Ventas QR sin pago</div>
          <div class="text-h6 text-bold" :class="ventasSinPagoFiltradas.length ? 'text-red-8' : ''">{{ ventasSinPagoFiltradas.length }}</div>
        </q-card>
      </div>
    </div>

    <q-table
      flat
      bordered
      dense
      title="Pagos recibidos"
      :rows="pagosFiltrados"
      :columns="columns"
      row-key="qrId"
      :loading="loading"
      :rows-per-page-options="[0]"
      no-data-label="No hay pagos QR en estas fechas"
    >
      <template #body-cell-venta="props">
        <q-td :props="props">
          <template v-if="props.row.venta">
            <q-chip dense square color="green" text-color="white" size="11px" clickable icon="visibility" @click="verVenta(props.row)">
              #{{ props.row.venta.id }}
            </q-chip>
            <span class="text-caption">
              {{ props.row.venta.client?.nombreRazonSocial || 'S/N' }} · {{ props.row.venta.user?.name }}
              <span v-if="props.row.venta.agencia"> · {{ props.row.venta.agencia.nombre }}</span>
              · Bs {{ Number(props.row.venta.montoTotal).toFixed(2) }}
            </span>
            <q-badge v-if="props.row.venta.estado === 'ANULADO'" color="red" class="q-ml-xs">Anulada</q-badge>
            <q-badge
              v-else-if="Math.abs(Number(props.row.venta.montoQr || props.row.venta.montoTotal) - Number(props.row.amount)) > 0.009"
              color="orange"
              class="q-ml-xs"
            >Monto distinto</q-badge>
          </template>
          <q-chip v-else dense square color="orange" text-color="white" size="11px">Sin venta</q-chip>
        </q-td>
      </template>
      <template #body-cell-acciones="props">
        <q-td :props="props" auto-width>
          <q-btn
            flat
            dense
            round
            size="sm"
            color="primary"
            :icon="props.row.venta ? 'swap_horiz' : 'link'"
            @click="abrirVincular(props.row)"
          >
            <q-tooltip>{{ props.row.venta ? 'Cambiar venta vinculada' : 'Vincular a una venta' }}</q-tooltip>
          </q-btn>
          <q-btn
            v-if="props.row.venta"
            flat
            dense
            round
            size="sm"
            color="red"
            icon="link_off"
            @click="desvincular(props.row)"
          >
            <q-tooltip>Quitar vínculo</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <q-table
      v-if="ventasSinPagoFiltradas.length"
      class="q-mt-md"
      flat
      bordered
      dense
      title="Ventas con QR sin pago confirmado por el banco"
      :rows="ventasSinPagoFiltradas"
      :columns="columnsVentas"
      row-key="id"
      :rows-per-page-options="[0]"
    />

    <q-dialog v-model="dialogVincular">
      <q-card style="width: 760px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-sm">
          <div>
            <div class="text-subtitle1 text-bold">Vincular pago QR a una venta</div>
            <div v-if="pagoSel" class="text-caption text-grey-7">
              {{ formatFecha(pagoSel.paymentDate) }} {{ pagoSel.paymentTime }} ·
              <b>{{ pagoSel.currency }} {{ Number(pagoSel.amount).toFixed(2) }}</b> ·
              {{ pagoSel.senderName }} · QR {{ pagoSel.qrId }}
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="searchVenta"
            outlined
            dense
            clearable
            debounce="400"
            placeholder="Buscar por N° de venta, factura, cliente o CI (vacío = ventas del día del pago)"
            @update:model-value="cargarCandidatas"
          >
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </q-card-section>

        <q-table
          flat
          dense
          :rows="candidatas"
          :columns="columnsCandidatas"
          row-key="id"
          :loading="loadingCandidatas"
          :rows-per-page-options="[10]"
          no-data-label="No se encontraron ventas"
        >
          <template #body-cell-montoTotal="props">
            <q-td :props="props">
              <span :class="Math.abs(Number(props.row.montoTotal) - Number(pagoSel?.amount)) < 0.01 ? 'text-green-8 text-bold' : ''">
                {{ Number(props.row.montoTotal).toFixed(2) }}
              </span>
            </q-td>
          </template>
          <template #body-cell-qrId="props">
            <q-td :props="props">
              <q-badge v-if="props.row.qrId && props.row.qrId === pagoSel?.qrId" color="green">Actual</q-badge>
              <span v-else class="text-caption text-grey-7">{{ props.row.qrId || '—' }}</span>
            </q-td>
          </template>
          <template #body-cell-acciones="props">
            <q-td :props="props" auto-width>
              <q-btn
                dense
                no-caps
                size="sm"
                color="primary"
                label="Vincular"
                :disable="props.row.qrId === pagoSel?.qrId"
                :loading="vinculando === props.row.id"
                @click="vincular(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogVenta">
      <q-card v-if="pagoVer && pagoVer.venta" style="width: 640px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-sm">
          <div>
            <div class="text-subtitle1 text-bold">
              Venta #{{ pagoVer.venta.id }}
              <q-badge v-if="pagoVer.venta.estado === 'ANULADO'" color="red" class="q-ml-xs">Anulada</q-badge>
            </div>
            <div class="text-caption text-grey-7">
              {{ pagoVer.venta.fechaEmision ? formatFechaHora(pagoVer.venta.fechaEmision) : '' }}
              <span v-if="pagoVer.venta.numeroFactura"> · Factura {{ pagoVer.venta.numeroFactura }}</span>
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-sm text-body2">
            <div class="col-12 col-sm-6"><b>Cliente:</b> {{ pagoVer.venta.client?.nombreRazonSocial || 'S/N' }}</div>
            <div class="col-12 col-sm-6"><b>CI/NIT:</b> {{ pagoVer.venta.client?.numeroDocumento || '—' }}</div>
            <div class="col-12 col-sm-6"><b>Vendedor:</b> {{ pagoVer.venta.user?.name || '—' }}</div>
            <div class="col-12 col-sm-6"><b>Agencia:</b> {{ pagoVer.venta.agencia?.nombre || '—' }}</div>
            <div class="col-12 col-sm-6"><b>Método de pago:</b> {{ pagoVer.venta.metodoPago || '—' }}</div>
            <div class="col-12 col-sm-6"><b>QR:</b> {{ pagoVer.qrId }}</div>
          </div>
        </q-card-section>

        <q-markup-table flat bordered dense separator="cell" class="q-mx-md">
          <thead>
            <tr>
              <th class="text-left">Producto</th>
              <th class="text-right">Cant.</th>
              <th class="text-right">P. unit.</th>
              <th class="text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in pagoVer.venta.details || []" :key="d.id">
              <td>{{ d.descripcion }}</td>
              <td class="text-right">{{ d.cantidad }}</td>
              <td class="text-right">{{ Number(d.precioUnitario).toFixed(2) }}</td>
              <td class="text-right">{{ Number(d.subTotal).toFixed(2) }}</td>
            </tr>
            <tr v-if="!(pagoVer.venta.details || []).length">
              <td colspan="4" class="text-center text-grey-6">Sin detalle de productos</td>
            </tr>
          </tbody>
        </q-markup-table>

        <q-card-section class="text-right text-body2">
          <div>Total venta: <b>Bs {{ Number(pagoVer.venta.montoTotal).toFixed(2) }}</b></div>
          <div>
            Pagado por QR: <b class="text-green-8">{{ pagoVer.currency }} {{ Number(pagoVer.amount).toFixed(2) }}</b>
            <span class="text-grey-7"> · {{ pagoVer.senderName }}</span>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'QrPagosPage',
  data () {
    return {
      fechaInicio: moment().format('YYYY-MM-DD'),
      fechaFin: moment().format('YYYY-MM-DD'),
      pagos: [],
      ventasSinPago: [],
      errores: [],
      loading: false,
      dialogVincular: false,
      pagoSel: null,
      searchVenta: '',
      candidatas: [],
      loadingCandidatas: false,
      vinculando: null,
      agenciaFiltro: 0,
      dialogVenta: false,
      pagoVer: null,
      columns: [
        { name: 'fecha', label: 'Fecha', align: 'left', field: 'paymentDate', format: val => this.formatFecha(val), sortable: true },
        { name: 'hora', label: 'Hora', align: 'left', field: 'paymentTime', sortable: true },
        { name: 'monto', label: 'Monto', align: 'right', field: row => `${row.currency} ${Number(row.amount).toFixed(2)}`, sortable: true, sort: (a, b, ra, rb) => ra.amount - rb.amount },
        { name: 'pagador', label: 'Pagador', align: 'left', field: 'senderName', sortable: true },
        { name: 'cuenta', label: 'Cuenta', align: 'left', field: 'senderAccount' },
        { name: 'banco', label: 'Banco', align: 'left', field: 'senderBankCode' },
        { name: 'transaccion', label: 'Transacción', align: 'left', field: 'transactionId' },
        { name: 'qrId', label: 'QR', align: 'left', field: 'qrId' },
        { name: 'venta', label: 'Venta', align: 'left', field: 'venta' },
        { name: 'acciones', label: '', align: 'center', field: 'acciones' }
      ],
      columnsVentas: [
        { name: 'id', label: 'Venta', align: 'left', field: 'id' },
        { name: 'fecha', label: 'Fecha', align: 'left', field: 'fechaEmision' },
        { name: 'cliente', label: 'Cliente', align: 'left', field: row => row.client?.nombreRazonSocial },
        { name: 'usuario', label: 'Usuario', align: 'left', field: row => row.user?.name },
        { name: 'montoQr', label: 'Monto QR', align: 'right', field: row => Number(row.montoQr || 0).toFixed(2) },
        { name: 'qrId', label: 'QR', align: 'left', field: 'qrId' }
      ],
      columnsCandidatas: [
        { name: 'id', label: 'Venta', align: 'left', field: 'id' },
        { name: 'fecha', label: 'Fecha', align: 'left', field: 'fechaEmision', format: val => val ? moment(val).format('DD/MM HH:mm') : '' },
        { name: 'cliente', label: 'Cliente', align: 'left', field: row => row.client?.nombreRazonSocial || 'S/N' },
        { name: 'usuario', label: 'Usuario', align: 'left', field: row => row.user?.name },
        { name: 'metodoPago', label: 'Pago', align: 'left', field: 'metodoPago' },
        { name: 'montoTotal', label: 'Total', align: 'right', field: 'montoTotal' },
        { name: 'qrId', label: 'QR actual', align: 'left', field: 'qrId' },
        { name: 'acciones', label: '', align: 'center', field: 'acciones' }
      ]
    }
  },
  computed: {
    agenciaOptions () {
      return [
        { label: 'Todas las agencias', value: 0 },
        ...(this.$store.agencias || []).map(a => ({ label: a.nombre, value: a.id })),
        { label: 'Pagos sin venta', value: -1 }
      ]
    },
    // El banco no informa la agencia: se toma la de la venta vinculada
    pagosFiltrados () {
      if (this.agenciaFiltro === 0) return this.pagos
      if (this.agenciaFiltro === -1) return this.pagos.filter(p => !p.venta)
      return this.pagos.filter(p => p.venta && Number(p.venta.agencia_id) === this.agenciaFiltro)
    },
    ventasSinPagoFiltradas () {
      if (this.agenciaFiltro === 0) return this.ventasSinPago
      if (this.agenciaFiltro === -1) return []
      return this.ventasSinPago.filter(v => Number(v.agencia_id) === this.agenciaFiltro)
    },
    totalPagos () {
      return this.pagosFiltrados.reduce((sum, p) => sum + Number(p.amount || 0), 0)
    },
    pagosSinVenta () {
      return this.pagosFiltrados.filter(p => !p.venta).length
    }
  },
  mounted () {
    this.$store.fetchCatalogos(this.$axios, ['agencias'])
    this.buscar()
  },
  methods: {
    formatFecha (val) {
      return val ? moment(val).format('DD/MM/YYYY') : ''
    },
    formatFechaHora (val) {
      return val ? moment(val).format('DD/MM/YYYY HH:mm') : ''
    },
    verVenta (pago) {
      this.pagoVer = pago
      this.dialogVenta = true
    },
    buscar () {
      if (!this.fechaInicio || !this.fechaFin) return
      if (this.fechaFin < this.fechaInicio) {
        this.$alert.error('La fecha "Hasta" no puede ser menor que "Desde"')
        return
      }
      this.loading = true
      this.$axios.get('qr/pagados', {
        params: { fecha_inicio: this.fechaInicio, fecha_fin: this.fechaFin }
      }).then(res => {
        this.pagos = res.data.pagos || []
        this.ventasSinPago = res.data.ventasSinPago || []
        this.errores = res.data.errores || []
      }).catch(err => {
        this.pagos = []
        this.ventasSinPago = []
        this.errores = []
        this.$alert.error(err.response?.data?.message || 'Error al consultar pagos QR')
      }).finally(() => {
        this.loading = false
      })
    },
    abrirVincular (pago) {
      this.pagoSel = pago
      this.searchVenta = ''
      this.candidatas = []
      this.dialogVincular = true
      this.cargarCandidatas()
    },
    cargarCandidatas () {
      if (!this.pagoSel) return
      this.loadingCandidatas = true
      this.$axios.get('qr/ventas-candidatas', {
        params: {
          fecha: moment(this.pagoSel.paymentDate).format('YYYY-MM-DD'),
          monto: this.pagoSel.amount,
          search: this.searchVenta || undefined
        }
      }).then(res => {
        this.candidatas = res.data
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al buscar ventas')
      }).finally(() => {
        this.loadingCandidatas = false
      })
    },
    vincular (venta) {
      const pago = this.pagoSel
      const aviso = venta.qrId
        ? `<br><b class="text-orange-9">La venta #${venta.id} ya tiene el QR ${venta.qrId}; será reemplazado.</b>`
        : ''
      this.$q.dialog({
        title: 'Confirmar vínculo',
        message: `¿Vincular el pago de Bs ${Number(pago.amount).toFixed(2)} (${pago.senderName}) a la venta #${venta.id} por Bs ${Number(venta.montoTotal).toFixed(2)}?${aviso}`,
        html: true,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.vinculando = venta.id
        this.$axios.post('qr/vincular', { qrId: pago.qrId, sale_id: venta.id }).then(res => {
          this.$alert.success('Pago vinculado a la venta #' + venta.id)
          // Solo un pago puede apuntar a esta venta: se limpia de otras filas
          this.pagos.forEach(p => {
            if (p.venta && p.venta.id === venta.id) p.venta = null
          })
          pago.venta = res.data
          this.ventasSinPago = this.ventasSinPago.filter(v => v.id !== venta.id)
          this.dialogVincular = false
        }).catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al vincular')
        }).finally(() => {
          this.vinculando = null
        })
      })
    },
    desvincular (pago) {
      this.$q.dialog({
        title: 'Quitar vínculo',
        message: `¿Quitar el vínculo del pago QR ${pago.qrId} con la venta #${pago.venta.id}?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.post('qr/desvincular', { qrId: pago.qrId }).then(() => {
          this.$alert.success('Vínculo eliminado')
          pago.venta = null
        }).catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al desvincular')
        })
      })
    }
  }
}
</script>
