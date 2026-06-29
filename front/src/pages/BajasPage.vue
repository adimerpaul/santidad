<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h5 text-bold text-primary flex items-center">
          <q-icon name="description" class="q-mr-sm" />
          Informes de Bajas
        </div>
        <div class="text-caption text-grey-7">Gestión de informes y productos retirados</div>
      </div>
      <div class="col-auto" v-if="tab !== 'productos'">
        <q-btn
          color="primary"
          :icon="tab === 'inventario' ? 'playlist_add' : 'add'"
          :label="tab === 'inventario' ? 'Crear Control Inventario' : 'Nuevo Informe'"
          @click="tab === 'inventario' ? createControlInventario() : openCreateDialog()"
          no-caps
          unelevated
        />
      </div>
    </div>

    <q-tabs
      v-model="tab"
      dense
      class="text-grey"
      active-color="primary"
      indicator-color="primary"
      align="left"
      narrow-indicator
      @update:model-value="fetchData"
    >
      <q-tab name="informes" label="Informes Mensuales" icon="folder" />
      <q-tab name="inventario" label="Inventario" icon="assignment" />
      <q-tab name="motivos_sanitarios" label="Bajas Motivos Sanitarios" icon="health_and_safety" />
      <q-tab name="productos" label="Productos Retirados" icon="inventory" />
    </q-tabs>
    <q-separator />

    <q-card flat bordered class="q-mb-md shadow-1 q-mt-md">
      <q-card-section class="q-py-sm">
        <div class="row q-col-gutter-md items-center">
          <div class="col-12 col-sm-3">
            <q-select
              v-model="filter.agencia_id"
              :options="agencias"
              label="Sucursales"
              outlined
              dense
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              multiple
              use-chips
              clearable
              @update:model-value="fetchData"
              :disable="String($store.user?.id) !== '1'"
            >
              <template v-slot:prepend>
                <q-icon name="apartment" />
              </template>
            </q-select>
          </div>
          <div class="col-6 col-sm-2">
            <q-select
              v-model="filter.mes"
              :options="mesesFiltro"
              label="Mes"
              outlined
              dense
              emit-value
              map-options
              @update:model-value="fetchData"
            >
              <template v-slot:prepend>
                <q-icon name="calendar_month" />
              </template>
            </q-select>
          </div>
          <div class="col-6 col-sm-2">
            <q-input
              v-model.number="filter.anio"
              type="number"
              label="Año"
              outlined
              dense
              @update:model-value="fetchData"
            >
              <template v-slot:prepend>
                <q-icon name="event" />
              </template>
            </q-input>
          </div>

          <div v-if="tab === 'productos'" class="col-12 col-sm-3">
            <q-select
              v-model="filter.tipo"
              :options="tiposBajaFiltro"
              label="Motivo de Baja"
              outlined
              dense
              emit-value
              map-options
              @update:model-value="fetchData"
            >
              <template v-slot:prepend>
                <q-icon name="label" />
              </template>
            </q-select>
          </div>

          <div class="col-auto">
            <q-btn
              flat
              round
              color="grey-7"
              icon="refresh"
              @click="fetchData"
              :loading="loading"
            >
              <q-tooltip>Actualizar lista</q-tooltip>
            </q-btn>
            <q-btn
              v-if="tab === 'productos'"
              flat
              round
              color="green-7"
              icon="file_download"
              @click="exportProductosCSV"
            >
              <q-tooltip>Exportar a Excel (CSV)</q-tooltip>
            </q-btn>
            <q-btn
              v-if="tab === 'productos'"
              flat
              round
              color="red-7"
              icon="picture_as_pdf"
              @click="exportProductosPDF"
            >
              <q-tooltip>Exportar a PDF</q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <div class="q-mt-md">
      <q-table
        v-if="tab !== 'productos'"
        :rows="reports"
        :columns="columns"
        row-key="id"
        flat
        bordered
        :loading="loading"
        :pagination="pagination"
        @request="onRequestReports"
        class="shadow-1"
        :row-class="getReportRowClass"
      >
        <template v-slot:body-cell-tipo="props">
          <q-td :props="props">
            <q-chip
              :color="getTipoReporteColor(getReportDisplayTipo(props.row))"
              :text-color="getTipoReporteTextColor(getReportDisplayTipo(props.row))"
              :icon="getTipoReporteIcon(getReportDisplayTipo(props.row))"
              dense
              size="sm"
              class="text-weight-bold"
            >
              {{ getReportDisplayTipo(props.row) }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-mes="props">
          <q-td :props="props">
            {{ getMesNombre(props.row.mes) }}
          </q-td>
        </template>

        <template v-slot:body-cell-estado="props">
          <q-td :props="props" class="text-center">
            <q-chip
              :color="props.row.estado === 'ABIERTO' ? 'green-1' : (props.row.estado === 'PENDIENTE' ? 'orange-1' : (props.row.estado === 'OBSERVADO' ? 'red-2' : 'grey-2'))"
              :text-color="props.row.estado === 'ABIERTO' ? 'green-9' : (props.row.estado === 'PENDIENTE' ? 'orange-9' : (props.row.estado === 'OBSERVADO' ? 'red-10' : 'grey-8'))"
              :icon="props.row.estado === 'ABIERTO' ? 'lock_open' : (props.row.estado === 'PENDIENTE' ? 'send' : (props.row.estado === 'OBSERVADO' ? 'warning' : 'lock'))"
              size="sm"
              class="text-weight-bold"
            >
              {{ props.row.estado }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-items_count="props">
          <q-td :props="props" class="text-center">
            <q-badge color="blue-grey-6" outline>
              {{ props.row.items ? props.row.items.length : 0 }} productos
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props" class="text-right">
            <q-btn-group flat round>
              <q-btn
                flat
                round
                size="sm"
                color="primary"
                icon="visibility"
                :to="'/informesBajas/' + props.row.id"
              >
                <q-tooltip>Ver detalle / Gestionar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="(props.row.estado === 'ABIERTO' || props.row.estado === 'PENDIENTE') && $store.user?.id === 1"
                flat
                round
                size="sm"
                color="negative"
                icon="delete"
                @click="confirmDelete(props.row)"
              >
                <q-tooltip>Eliminar informe</q-tooltip>
              </q-btn>
            </q-btn-group>
          </q-td>
        </template>
      </q-table>

      <q-table
        v-else
        :rows="productos"
        :columns="columnsProductos"
        row-key="id"
        flat
        bordered
        :loading="loading"
        :pagination="paginationProductos"
        @request="onRequestProductos"
        class="shadow-1"
      >
        <template v-slot:body-cell-cantidad="props">
          <q-td :props="props" class="text-center">
            <span :class="props.row.cantidad > 0 ? 'text-positive text-bold' : 'text-negative text-bold'">
              {{ formatCantidad(props.row) }}
            </span>
          </q-td>
        </template>
        <template v-slot:body-cell-tipo="props">
          <q-td :props="props">
            <q-chip
              :color="getTipoColor(props.row.tipo)"
              :text-color="getTipoTextColor(props.row.tipo)"
              dense
              size="sm"
              class="text-weight-bold"
            >
              {{ props.row.tipo }}
            </q-chip>
          </q-td>
        </template>
        <template v-slot:body-cell-descripcion="props">
          <q-td :props="props">
            <div v-if="props.row.tipo === 'CONTEO FISICO'" class="q-mb-xs">
              <div class="text-caption text-blue-9 text-bold">
                <q-icon name="inventory" /> Sis: {{ props.row.stock_sistema }} | Fís: {{ props.row.conteo_fisico }}
              </div>
            </div>
            <div style="white-space: pre-wrap;">{{ props.row.descripcion }}</div>
          </q-td>
        </template>
        <template v-slot:body-cell-report_id="props">
          <q-td :props="props">
            <router-link :to="'/informesBajas/' + props.row.withdrawal_report_id" class="text-primary text-bold" style="text-decoration: none;">
              #{{ props.row.withdrawal_report_id }}
            </router-link>
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="showCreateDialog" persistent>
      <q-card style="min-width: 380px">
        <q-card-section class="row items-center bg-primary text-white">
          <div class="text-h6">Crear Nuevo Informe</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <q-form @submit="createReport" class="q-gutter-md">
            <q-select
              v-model="newReport.agencia_id"
              :options="agencias"
              label="Sucursal *"
              outlined
              dense
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :rules="[val => !!val || 'Requerido']"
              :disable="String($store.user?.id) !== '1'"
            />

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-select
                  v-model="newReport.mes"
                  :options="meses"
                  label="Mes *"
                  outlined
                  dense
                  emit-value
                  map-options
                  :rules="[val => !!val || 'Requerido']"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model.number="newReport.anio"
                  type="number"
                  label="Año *"
                  outlined
                  dense
                  :rules="[val => !!val || 'Requerido']"
                />
              </div>
            </div>

            <q-input
              v-model="newReport.observaciones"
              label="Observaciones"
              type="textarea"
              outlined
              dense
              rows="2"
            />

            <div class="row justify-end q-mt-md">
              <q-btn label="Cancelar" color="grey" flat v-close-popup />
              <q-btn label="Crear Informe" type="submit" color="primary" unelevated :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { exportFile } from 'quasar'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

export default {
  data () {
    return {
      tab: localStorage.getItem('bajas_active_tab') || 'informes',
      loading: false,
      reports: [],
      productos: [],
      agencias: [],
      showCreateDialog: false,
      filter: {
        agencia_id: [],
        mes: (new Date().getMonth() + 1),
        anio: new Date().getFullYear(),
        tipo: null
      },
      newReport: {
        agencia_id: null,
        mes: (new Date().getMonth() + 1),
        anio: new Date().getFullYear(),
        observaciones: '',
        tipo: 'VENCIMIENTO/DEVOLUCION',
        customTipo: ''
      },
      tiposReporteOptions: [
        { label: 'Informe de VENCIMIENTO/DEVOLUCION', value: 'VENCIMIENTO/DEVOLUCION' },
        { label: 'Informe de CONTEO FISICO', value: 'CONTEO FISICO' },
        { label: 'Informe de PRODUCTOS DAÑADOS', value: 'PRODUCTOS DAÑADOS' },
        { label: 'Informe de MOTIVOS SANITARIOS', value: 'MOTIVOS SANITARIOS' },
        { label: 'Otro (Ingresar manualmente)', value: 'OTROS' }
      ],
      columns: [
        { name: 'id', align: 'left', label: 'ID', field: 'id', sortable: true },
        { name: 'agencia', align: 'left', label: 'Sucursal', field: row => row.agencia ? row.agencia.nombre : 'N/A', sortable: true },
        { name: 'tipo', align: 'left', label: 'Tipo/Título', field: 'tipo', sortable: true },
        { name: 'mes', align: 'left', label: 'Mes', field: 'mes', sortable: true },
        { name: 'anio', align: 'left', label: 'Año', field: 'anio', sortable: true },
        { name: 'fecha_creacion', align: 'left', label: 'Fecha Creación', field: row => this.formatDate(row.created_at), sortable: true },
        { name: 'items_count', align: 'center', label: 'Productos', field: row => row.items ? row.items.length : 0 },
        { name: 'estado', align: 'center', label: 'Estado', field: 'estado', sortable: true },
        { name: 'user', align: 'left', label: 'Creado por', field: row => row.user ? row.user.name : 'N/A' },
        { name: 'actions', align: 'right', label: 'Acciones', field: 'actions' }
      ],
      columnsProductos: [
        { name: 'report_id', align: 'left', label: 'Inf. #', field: 'withdrawal_report_id', sortable: true },
        { name: 'agencia', align: 'left', label: 'Agencia', field: row => row.agencia?.nombre || (row.buy?.agencia?.nombre || 'Almacen') },
        { name: 'producto', align: 'left', label: 'Producto', field: row => row.product?.nombre },
        { name: 'lote', align: 'left', label: 'Lote', field: row => row.buy?.lote || 'N/A' },
        { name: 'cantidad', align: 'center', label: 'Cantidad', field: 'cantidad', sortable: true },
        { name: 'precio_fact', align: 'right', label: 'Precio Fact.', field: row => row.product?.precio ? (row.product.precio / 1.3).toFixed(2) : '0.00', sortable: true },
        { name: 'total_perdida', align: 'right', label: 'Total', field: row => row.product?.precio ? (row.cantidad * (row.product.precio / 1.3)).toFixed(2) : '0.00', sortable: true },
        { name: 'tipo', align: 'center', label: 'Motivo', field: 'tipo', sortable: true },
        { name: 'descripcion', align: 'left', label: 'Descripción', field: 'descripcion' },
        { name: 'fecha', align: 'left', label: 'Fecha/Hora', field: row => this.formatDate(row.created_at), sortable: true }
      ],
      pagination: {
        sortBy: 'id',
        descending: true,
        page: 1,
        rowsPerPage: 20,
        rowsNumber: 0
      },
      paginationProductos: {
        sortBy: 'id',
        descending: true,
        page: 1,
        rowsPerPage: 20,
        rowsNumber: 0
      },
      meses: [
        { label: 'Enero', value: 1 }, { label: 'Febrero', value: 2 }, { label: 'Marzo', value: 3 },
        { label: 'Abril', value: 4 }, { label: 'Mayo', value: 5 }, { label: 'Junio', value: 6 },
        { label: 'Julio', value: 7 }, { label: 'Agosto', value: 8 }, { label: 'Septiembre', value: 9 },
        { label: 'Octubre', value: 10 }, { label: 'Noviembre', value: 11 }, { label: 'Diciembre', value: 12 }
      ],
      tiposBajaFiltro: [
        { label: 'Todos los motivos', value: null },
        { label: 'VENCIMIENTO', value: 'VENCIMIENTO' },
        { label: 'DEVOLUCION A PROVEEDOR', value: 'DEVOLUCION A PROVEEDOR' },
        { label: 'CONTEO FISICO', value: 'CONTEO FISICO' },
        { label: 'PRODUCTO DAÑADO', value: 'PRODUCTO DAÑADO' },
        { label: 'OTRO', value: 'OTRO' }
      ]
    }
  },
  computed: {
    mesesFiltro () {
      return [{ label: 'Todos los meses', value: null }, ...this.meses]
    }
  },
  created () {
    this.getAgencias()
    this.fetchData()
  },
  methods: {
    getAgencias () {
      this.$axios.get('agencias').then(res => {
        this.agencias = [{ id: null, nombre: 'Todas las sucursales' }, ...res.data]
        if (this.$store && this.$store.user && this.$store.user.agencia_id) {
          this.newReport.agencia_id = this.$store.user.agencia_id
          if (String(this.$store.user.id) !== '1') {
            this.filter.agencia_id = [this.$store.user.agencia_id]
            this.fetchData()
          }
        }
      })
    },
    getReportRowClass (row) {
      if (row.estado === 'OBSERVADO') return 'bg-report-observado'
      return ''
    },
    fetchData () {
      localStorage.setItem('bajas_active_tab', this.tab)
      if (this.tab === 'informes' || this.tab === 'inventario' || this.tab === 'motivos_sanitarios') {
        this.getReports()
      } else {
        this.getProductos()
      }
    },
    getReports (props) {
      this.loading = true
      const { page, rowsPerPage, sortBy, descending } = props?.pagination || this.pagination

      let tipoFiltro = null
      if (this.tab === 'informes') {
        tipoFiltro = 'VENCIMIENTO/DEVOLUCION'
      } else if (this.tab === 'inventario') {
        tipoFiltro = 'CONTEO FISICO'
      } else if (this.tab === 'motivos_sanitarios') {
        tipoFiltro = 'MOTIVOS SANITARIOS'
      }

      this.$axios.get('withdrawal-reports', {
        params: {
          page,
          rowsPerPage,
          sortBy,
          descending,
          agencia_id: this.filter.agencia_id,
          mes: this.filter.mes,
          anio: this.filter.anio,
          tipo: tipoFiltro
        }
      }).then(res => {
        this.reports = res.data.data
        this.pagination.page = res.data.current_page
        this.pagination.rowsPerPage = res.data.per_page
        this.pagination.rowsNumber = res.data.total
        this.pagination.sortBy = sortBy
        this.pagination.descending = descending
      }).catch(err => {
        console.error('Error al cargar informes', err)
      }).finally(() => {
        this.loading = false
      })
    },
    getProductos (props) {
      this.loading = true
      const { page, rowsPerPage, sortBy, descending } = props?.pagination || this.paginationProductos

      this.$axios.get('withdrawal-reports/all-items', {
        params: {
          page,
          rowsPerPage,
          sortBy,
          descending,
          agencia_id: this.filter.agencia_id,
          mes: this.filter.mes,
          anio: this.filter.anio,
          tipo: this.filter.tipo
        }
      }).then(res => {
        this.productos = res.data.data
        this.paginationProductos.page = res.data.current_page
        this.paginationProductos.rowsPerPage = res.data.per_page
        this.paginationProductos.rowsNumber = res.data.total
        this.paginationProductos.sortBy = sortBy
        this.paginationProductos.descending = descending
      }).catch(err => {
        console.error('Error al cargar productos', err)
      }).finally(() => {
        this.loading = false
      })
    },
    onRequestReports (props) {
      this.getReports(props)
    },
    onRequestProductos (props) {
      this.getProductos(props)
    },
    getMesNombre (num) {
      if (!num) return 'N/A'
      const m = this.meses.find(m => m.value === num)
      return m ? m.label : num
    },
    formatCantidad (row) {
      if (!row) return ''
      if (row.cantidad > 0) return `+${row.cantidad}`
      if (row.cantidad < 0) return `${row.cantidad}`
      return row.cantidad
    },
    formatDate (dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
    },
    getTipoColor (tipo) {
      switch (tipo) {
        case 'VENCIMIENTO': return 'red-1'
        case 'PRODUCTO DAÑADO': return 'red-2'
        case 'DEVOLUCION A PROVEEDOR': return 'orange-1'
        case 'CONTEO FISICO': return 'blue-1'
        default: return 'grey-2'
      }
    },
    getTipoTextColor (tipo) {
      switch (tipo) {
        case 'VENCIMIENTO': return 'red-9'
        case 'PRODUCTO DAÑADO': return 'red-8'
        case 'DEVOLUCION A PROVEEDOR': return 'orange-9'
        case 'CONTEO FISICO': return 'blue-9'
        default: return 'grey-9'
      }
    },
    createControlInventario () {
      this.loading = true
      const userAgenciaId = this.$store?.user?.agencia_id
      const payload = {
        agencia_id: userAgenciaId || 1,
        mes: (new Date().getMonth() + 1),
        anio: new Date().getFullYear(),
        tipo: 'CONTEO FISICO',
        observaciones: 'Control de Inventario automático'
      }

      this.$axios.post('withdrawal-reports', payload)
        .then(res => {
          this.$q.notify({ color: 'positive', message: 'Control de Inventario creado correctamente', icon: 'check' })
          this.fetchData()
          this.$router.push('/informesBajas/' + res.data.id)
        })
        .catch(err => {
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al crear control de inventario' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    openCreateDialog () {
      let tipoReporte = 'VENCIMIENTO/DEVOLUCION'
      if (this.tab === 'inventario') {
        tipoReporte = 'CONTEO FISICO'
      } else if (this.tab === 'motivos_sanitarios') {
        tipoReporte = 'MOTIVOS SANITARIOS'
      }

      this.newReport = {
        agencia_id: this.$store?.user?.agencia_id || null,
        mes: (new Date().getMonth() + 1),
        anio: new Date().getFullYear(),
        observaciones: '',
        tipo: tipoReporte,
        customTipo: ''
      }
      this.showCreateDialog = true
    },
    createReport () {
      this.loading = true
      const payload = { ...this.newReport }
      delete payload.customTipo

      this.$axios.post('withdrawal-reports', payload)
        .then(res => {
          this.$q.notify({ color: 'positive', message: 'Informe creado correctamente', icon: 'check' })
          this.showCreateDialog = false
          this.fetchData()
          this.$router.push('/informesBajas/' + res.data.id)
        })
        .catch(err => {
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al crear informe' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    getTipoReporteColor (tipo) {
      if (!tipo) return 'indigo-1'
      if (tipo.includes('VENCIMIENTO') || tipo.includes('VENCIDOS') || tipo.includes('DEVOLUCION')) return 'red-1'
      if (tipo.includes('CONTEO')) return 'blue-1'
      if (tipo.includes('DAÑADOS')) return 'orange-1'
      if (tipo.includes('SANITARIOS')) return 'teal-1'
      return 'indigo-1'
    },
    getTipoReporteTextColor (tipo) {
      if (!tipo) return 'indigo-9'
      if (tipo.includes('VENCIMIENTO') || tipo.includes('VENCIDOS') || tipo.includes('DEVOLUCION')) return 'red-9'
      if (tipo.includes('CONTEO')) return 'blue-9'
      if (tipo.includes('DAÑADOS')) return 'orange-9'
      if (tipo.includes('SANITARIOS')) return 'teal-9'
      return 'indigo-9'
    },
    getTipoReporteIcon (tipo) {
      if (!tipo) return 'description'
      if (tipo.includes('VENCIMIENTO') || tipo.includes('VENCIDOS') || tipo.includes('DEVOLUCION')) return 'warning'
      if (tipo.includes('CONTEO')) return 'inventory'
      if (tipo.includes('DAÑADOS')) return 'dangerous'
      if (tipo.includes('SANITARIOS')) return 'health_and_safety'
      return 'description'
    },
    getReportDisplayTipo (row) {
      if (!row) return ''
      const isVencimientoDevolucion =
        row.tipo === 'VENCIMIENTO/DEVOLUCION' ||
        row.tipo === 'VENCIMIENTO' ||
        row.tipo === 'DEVOLUCION' ||
        row.tipo === 'VENCIDOS/DEVOLUCIONES'

      if (!isVencimientoDevolucion) {
        return row.tipo
      }

      if (!row.items || row.items.length === 0) {
        return row.tipo === 'VENCIDOS/DEVOLUCIONES' ? 'VENCIMIENTO/DEVOLUCION' : row.tipo
      }

      const motives = new Set(row.items.map(item => item.tipo))

      const hasVencimiento = motives.has('VENCIMIENTO')
      const hasDevolucion = motives.has('DEVOLUCION A PROVEEDOR') || motives.has('DEVOLUCION')

      if (hasVencimiento && hasDevolucion) {
        return 'VENCIMIENTO/DEVOLUCION'
      } else if (hasDevolucion) {
        return 'DEVOLUCION'
      } else if (hasVencimiento) {
        return 'VENCIMIENTO'
      }

      return row.tipo === 'VENCIDOS/DEVOLUCIONES' ? 'VENCIMIENTO/DEVOLUCION' : row.tipo
    },
    confirmDelete (report) {
      this.$q.dialog({
        title: 'Confirmar Eliminación',
        message: `¿Estás seguro de eliminar el informe #${report.id}? Se restaurará el stock de todos los productos incluidos.`,
        cancel: true,
        persistent: true,
        ok: { color: 'negative', label: 'Eliminar', unelevated: true }
      }).onOk(() => {
        this.deleteReport(report.id)
      })
    },
    deleteReport (id) {
      this.loading = true
      this.$axios.delete(`withdrawal-reports/${id}`)
        .then(() => {
          this.$q.notify({ color: 'positive', message: 'Informe eliminado correctamente' })
          this.getReports()
        })
        .catch(err => {
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al eliminar informe' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    exportProductosCSV () {
      let content = 'Inf. #,Agencia,Producto,Lote,Cantidad,Precio Fact.,Total,Motivo,Fecha/Hora\n'
      this.productos.forEach(row => {
        const reportId = row.withdrawal_report_id || ''
        const agencia = row.agencia?.nombre || (row.buy?.agencia?.nombre || 'Almacen')
        const producto = row.product?.nombre || ''
        const lote = row.buy?.lote || 'N/A'
        const cantidad = row.cantidad
        const precioFact = row.product?.precio ? (row.product.precio / 1.3).toFixed(2) : '0.00'
        const total = row.product?.precio ? (row.cantidad * (row.product.precio / 1.3)).toFixed(2) : '0.00'
        const tipo = row.tipo || ''
        const fecha = this.formatDate(row.created_at)

        const safeStr = (str) => `"${String(str).replace(/"/g, '""')}"`
        content += `${reportId},${safeStr(agencia)},${safeStr(producto)},${safeStr(lote)},${cantidad},${precioFact},${total},${tipo},${fecha}\n`
      })

      const status = exportFile('productos_retirados.csv', content, 'text/csv')
      if (status !== true) {
        this.$q.notify({ message: 'El navegador denegó la descarga', color: 'negative', icon: 'warning' })
      }
    },
    exportProductosPDF () {
      const doc = jsPDF({ orientation: 'landscape' })
      const pW = doc.internal.pageSize.getWidth()
      const pH = doc.internal.pageSize.getHeight()

      doc.setFillColor(41, 128, 185)
      doc.rect(0, 0, pW, 40, 'F')
      doc.setTextColor(255, 255, 255)
      doc.setFontSize(22)
      doc.setFont('helvetica', 'bold')
      doc.text('REPORTE DE PRODUCTOS RETIRADOS', 14, 25)

      const tableData = this.productos.map(row => {
        const precioFact = row.product?.precio ? (row.product.precio / 1.3).toFixed(2) : '0.00'
        const total = row.product?.precio ? (row.cantidad * (row.product.precio / 1.3)).toFixed(2) : '0.00'
        return [
          row.withdrawal_report_id,
          row.agencia?.nombre || (row.buy?.agencia?.nombre || 'Almacen'),
          row.product?.nombre,
          row.buy?.lote || 'N/A',
          { content: this.formatCantidad(row), styles: { fontStyle: 'bold', halign: 'center', textColor: row.cantidad > 0 ? [46, 204, 113] : [231, 76, 60] } },
          precioFact,
          { content: total, styles: { fontStyle: 'bold', halign: 'right', textColor: row.cantidad > 0 ? [46, 204, 113] : [231, 76, 60] } },
          row.tipo,
          this.formatDate(row.created_at)
        ]
      })

      autoTable(doc, {
        startY: 50,
        head: [['INF. #', 'AGENCIA', 'PRODUCTO', 'LOTE', 'CANT.', 'PRECIO FACT.', 'TOTAL', 'MOTIVO', 'FECHA/HORA']],
        body: tableData,
        theme: 'grid',
        headStyles: { fillColor: [41, 128, 185], fontSize: 8, fontStyle: 'bold', halign: 'center', lineColor: [255, 255, 255], lineWidth: 0.1 },
        bodyStyles: { fontSize: 7.5, lineColor: [220, 220, 220], lineWidth: 0.1 },
        columnStyles: { 4: { cellWidth: 12 }, 5: { cellWidth: 18 }, 6: { cellWidth: 16 }, 7: { cellWidth: 26 }, 8: { cellWidth: 25 } },
        didDrawPage: (data) => {
          doc.setFontSize(8)
          doc.setTextColor(150)
          doc.text(`Página ${data.pageNumber}`, pW - 20, pH - 10, { align: 'right' })
        }
      })

      doc.save('productos_retirados.pdf')
    }
  }
}
</script>
