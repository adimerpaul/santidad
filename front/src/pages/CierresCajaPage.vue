<template>
  <q-page class="q-pa-md bg-grey-2">
    <!-- Encabezado de la Página -->
    <div class="row items-center justify-between q-mb-md">
      <div class="row items-center">
        <q-icon name="lock" size="32px" color="primary" class="q-mr-sm" />
        <div>
          <div class="text-h6 text-bold text-grey-9">Cierres de Caja</div>
          <div class="text-caption text-grey-6">Historial diario y control de turnos de caja</div>
        </div>
      </div>

      <!-- Selector de Agencia para Administrador -->
      <div v-if="isAdmin" style="min-width: 200px;">
        <q-select
          v-model="selectedAgencia"
          outlined
          dense
          label="Filtrar por Sucursal"
          :options="agenciasOptions"
          option-label="nombre"
          option-value="id"
          emit-value
          map-options
          @update:model-value="onAgenciaChange"
        />
      </div>
    </div>

    <!-- Alertas y Seguimiento de Días sin Cierre (Intuitivo) -->
    <q-card flat bordered class="q-mb-md" style="border-radius: 12px;">
      <q-card-section class="q-pb-none">
        <div class="text-bold text-subtitle2 text-grey-8">
          <q-icon name="calendar_today" class="q-mr-xs" />
          Seguimiento de los Últimos 15 Días
        </div>
        <div class="text-caption text-grey-6">Verifica si se realizó el cierre de caja de cada día con ventas activas.</div>
      </q-card-section>

      <!-- Banner de Alerta General -->
      <q-card-section class="q-pt-sm">
        <div v-if="loadingGaps" class="row justify-center q-py-md">
          <q-spinner color="primary" size="30px" />
        </div>
        <div v-else>
          <div v-if="tieneDiasSinCierre" class="q-pa-md bg-red-1 text-red-9 rounded-borders q-mb-sm row items-center no-wrap" style="border: 1px solid #ffcdd2;">
            <q-icon name="warning" size="24px" class="q-mr-md" />
            <div>
              <div class="text-bold">Existen días sin cierre de caja</div>
              <div class="text-caption">Se detectaron ventas realizadas en días que aún no registran ningún cierre de turno. Revisa la lista de abajo.</div>
            </div>
          </div>
          <div v-else class="q-pa-md bg-green-1 text-green-9 rounded-borders q-mb-sm row items-center no-wrap" style="border: 1px solid #c8e6c9;">
            <q-icon name="check_circle" size="24px" class="q-mr-md" />
            <div>
              <div class="text-bold">Todo al día</div>
              <div class="text-caption">Todos los días con movimientos de venta cuentan con al menos un cierre de caja registrado.</div>
            </div>
          </div>

          <!-- Cuadrícula / Checklist de los 15 días -->
          <div class="row q-col-gutter-xs q-mt-sm">
            <div v-for="day in gapsList" :key="day.date" class="col-6 col-sm-4 col-md-2 q-pa-xs">
              <div
                class="gap-card text-center rounded-borders q-pa-sm"
                :class="{
                  'gap-ok': day.has_closure && day.has_sales,
                  'gap-error': day.sin_cierre,
                  'gap-empty': !day.has_sales
                }"
              >
                <div class="text-bold text-caption">{{ formatDayLabel(day.date) }}</div>
                <div style="font-size: 10px;" class="text-grey-7 q-mb-xs">{{ formatDateShort(day.date) }}</div>
                <q-chip
                  dense
                  square
                  size="10px"
                  :color="day.sin_cierre ? 'red' : (day.has_closure ? 'green' : 'grey-5')"
                  text-color="white"
                  class="q-px-xs q-mb-xs"
                >
                  {{ day.sin_cierre ? 'Sin Cierre' : (day.has_closure ? 'Cerrado' : 'Sin Ventas') }}
                </q-chip>

                <!-- Resumen de Cuentas y Montos -->
                <div v-if="day.closures && day.closures.length > 0" class="q-mt-xs text-left full-width" style="font-size: 9px; line-height: 1.2;">
                   <q-separator class="q-my-xs" />
                   <div v-for="(c, idx) in day.closures" :key="idx" class="text-grey-9 text-weight-medium">
                     <div class="row items-center no-wrap">
                       <q-icon name="account_balance_wallet" size="9px" class="q-mr-xs text-primary" />
                       <span class="text-grey-7 ellipsis" style="font-size: 8px; max-width: 50px;">{{ c.closed_by }}:</span>
                       <q-space />
                       <span class="text-weight-bold">{{ parseFloat(c.monto_fisico || 0).toFixed(2) }} Bs</span>
                     </div>
                   </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Tabla Histórica de Cierres -->
    <q-card flat bordered style="border-radius: 12px;">
      <q-card-section class="q-pa-none">
        <q-table
          :rows="closuresList"
          :columns="computedColumns"
          row-key="id"
          :loading="loadingClosures"
          dense
          :rows-per-page-options="[15, 30, 50, 0]"
          no-data-label="No hay registros de cierre de caja"
          wrap-cells
        >
          <!-- Responsables Custom Slot -->
          <template v-slot:body-cell-user="props">
            <q-td :props="props">
              <div class="text-caption" style="font-size: 11px; line-height: 1.2;">
                <div><b>Ape:</b> {{ props.row.user?.name || '---' }}</div>
                <div v-if="props.row.closed_by_user" class="text-grey-7"><b>Cie:</b> {{ props.row.closed_by_user?.name }}</div>
              </div>
            </q-td>
          </template>

          <!-- Rango del Turno -->
          <template v-slot:body-cell-turno="props">
            <q-td :props="props">
              <div class="text-caption" style="font-size: 11px; line-height: 1.2;">
                <div><b>Ape:</b> {{ formatDate(props.row.fecha_apertura) }}</div>
                <div><b>Cie:</b> {{ props.row.fecha_cierre ? formatDate(props.row.fecha_cierre) : '---' }}</div>
              </div>
            </q-td>
          </template>

          <!-- Monto Físico -->
          <template v-slot:body-cell-monto_fisico="props">
            <q-td :props="props" class="text-bold">
              <span v-if="props.row.estado === 'ABIERTO'" class="text-grey-5 text-weight-normal text-italic">Turno activo</span>
              <span v-else>{{ parseFloat(props.row.monto_fisico || 0).toFixed(2) }} Bs</span>
            </q-td>
          </template>

          <!-- Monto Sistema Efectivo -->
          <template v-slot:body-cell-monto_sistema_efectivo="props">
            <q-td :props="props">
              <span v-if="props.row.estado === 'ABIERTO'" class="text-grey-5">---</span>
              <span v-else>{{ parseFloat(props.row.monto_sistema_efectivo || 0).toFixed(2) }} Bs</span>
            </q-td>
          </template>

          <!-- Monto Sistema Digital -->
          <template v-slot:body-cell-monto_sistema_digital="props">
            <q-td :props="props">
              <span v-if="props.row.estado === 'ABIERTO'" class="text-grey-5">---</span>
              <span v-else>{{ parseFloat(props.row.monto_sistema_digital || 0).toFixed(2) }} Bs</span>
            </q-td>
          </template>

          <!-- Total Sistema -->
          <template v-slot:body-cell-monto_sistema_total="props">
            <q-td :props="props" class="text-bold">
              <span v-if="props.row.estado === 'ABIERTO'" class="text-grey-5">---</span>
              <span v-else>{{ parseFloat(props.row.monto_sistema_total || 0).toFixed(2) }} Bs</span>
            </q-td>
          </template>

          <!-- Diferencia de Caja -->
          <template v-slot:body-cell-diferencia="props">
            <q-td :props="props" class="text-bold">
              <span v-if="props.row.estado === 'ABIERTO'" class="text-grey-5">---</span>
              <q-badge
                v-else
                :color="props.row.diferencia < 0 ? 'red' : (props.row.diferencia > 0 ? 'blue' : 'green')"
                text-color="white"
                style="padding: 4px 6px;"
              >
                {{ props.row.diferencia < 0 ? '' : '+' }}{{ parseFloat(props.row.diferencia || 0).toFixed(2) }} Bs
              </q-badge>
            </q-td>
          </template>

          <!-- Observaciones Custom Slot -->
          <template v-slot:body-cell-observaciones="props">
            <q-td :props="props">
              <div class="text-caption" style="font-size: 11px; line-height: 1.2;">
                <div v-if="props.row.observaciones_apertura" class="text-grey-7"><b>Ape:</b> {{ props.row.observaciones_apertura }}</div>
                <div v-if="props.row.observaciones"><b>Cie:</b> {{ props.row.observaciones }}</div>
                <div v-if="!props.row.observaciones_apertura && !props.row.observaciones" class="text-grey-4">Sin observaciones</div>
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'CierresCajaPage',
  data () {
    return {
      closuresList: [],
      gapsList: [],
      agenciasOptions: [],
      selectedAgencia: '',
      loadingClosures: false,
      loadingGaps: false
    }
  },
  computed: {
    isAdmin () {
      return String(this.$store.user?.id) === '1'
    },
    tieneDiasSinCierre () {
      return this.gapsList.some(day => day.sin_cierre)
    },
    computedColumns () {
      const baseColumns = [
        { name: 'fecha_cierre', label: 'Fecha Cierre', align: 'left', field: 'fecha_cierre', format: val => val ? this.formatDate(val) : '🟢 ABIERTO', sortable: true },
        { name: 'user', label: 'Responsable', align: 'left', field: row => row.user?.name, sortable: true },
        { name: 'agencia', label: 'Sucursal', align: 'left', field: row => row.agencia?.nombre, sortable: true },
        { name: 'turno', label: 'Rango de Turno', align: 'left', field: 'turno' },
        { name: 'monto_fisico', label: 'Monto Físico', align: 'right', field: 'monto_fisico', sortable: true },
        { name: 'observaciones', label: 'Observaciones', align: 'left', field: 'observaciones' }
      ]

      if (this.isAdmin) {
        return [
          ...baseColumns.slice(0, 5),
          { name: 'monto_sistema_efectivo', label: 'Sistema Efec.', align: 'right', field: 'monto_sistema_efectivo', sortable: true },
          { name: 'monto_sistema_digital', label: 'Sistema Dig.', align: 'right', field: 'monto_sistema_digital', sortable: true },
          { name: 'monto_sistema_total', label: 'Sistema Total', align: 'right', field: 'monto_sistema_total', sortable: true },
          { name: 'diferencia', label: 'Diferencia', align: 'center', field: 'diferencia', sortable: true },
          baseColumns[5]
        ]
      }
      return baseColumns
    }
  },
  mounted () {
    this.selectedAgencia = this.$store.user?.agencia_id || ''
    if (this.isAdmin) {
      this.fetchAgencias()
    }
    this.fetchData()
  },
  methods: {
    fetchAgencias () {
      this.agenciasOptions = [{ id: '', nombre: 'TODAS' }]
      this.$axios.get('agencias').then(res => {
        this.agenciasOptions = [...this.agenciasOptions, ...res.data]
      }).catch(err => {
        console.error('Error al cargar agencias:', err)
      })
    },
    fetchData () {
      this.fetchClosures()
      this.fetchGaps()
    },
    fetchClosures () {
      this.loadingClosures = true
      const params = {}
      if (this.isAdmin && this.selectedAgencia) {
        params.agencia_id = this.selectedAgencia
      }
      this.$axios.get('cash-closures', { params }).then(res => {
        this.closuresList = res.data
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al cargar historial de cierres')
      }).finally(() => {
        this.loadingClosures = false
      })
    },
    fetchGaps () {
      this.loadingGaps = true
      const params = {}
      if (this.selectedAgencia) {
        params.agencia_id = this.selectedAgencia
      }
      this.$axios.get('cash-closures/gaps', { params }).then(res => {
        this.gapsList = res.data
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al verificar vacíos de cierre')
      }).finally(() => {
        this.loadingGaps = false
      })
    },
    onAgenciaChange () {
      this.fetchData()
    },
    formatDate (iso) {
      if (!iso) return ''
      const d = new Date(iso)
      const day = d.getDate()
      const month = d.toLocaleDateString('es-ES', { month: 'short' })
      const year = d.getFullYear()
      const hours = String(d.getHours()).padStart(2, '0')
      const mins = String(d.getMinutes()).padStart(2, '0')
      return `${day} ${month} ${year}, ${hours}:${mins}`
    },
    formatDateShort (dateStr) {
      if (!dateStr) return ''
      const parts = dateStr.split('-')
      if (parts.length < 3) return dateStr
      return `${parts[2]}/${parts[1]}`
    },
    formatDayLabel (dateStr) {
      if (!dateStr) return ''
      const weekdays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
      const d = new Date(dateStr + 'T00:00:00')
      return weekdays[d.getDay()]
    }
  }
}
</script>

<style scoped>
.gap-card {
  border: 1px solid #e0e0e0;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  min-height: 95px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  background-color: #fafafa;
  border-radius: 8px;
}

.gap-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

.gap-ok {
  border-left: 4px solid #4caf50;
  background-color: #f1f8e9;
}

.gap-error {
  border-left: 4px solid #f44336;
  background-color: #ffebee;
  animation: pulse-border 2s infinite;
}

.gap-empty {
  border-left: 4px solid #9e9e9e;
  background-color: #f5f5f5;
  opacity: 0.85;
}

@keyframes pulse-border {
  0% {
    box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.4);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(244, 67, 54, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(244, 67, 54, 0);
  }
}
</style>
