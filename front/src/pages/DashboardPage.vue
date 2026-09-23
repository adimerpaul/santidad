<template>
  <q-page class="dashboard q-pa-md">
    <!-- ───────── Barra de filtros ───────── -->
    <q-card flat bordered class="dash-card q-mb-md">
      <q-card-section class="q-py-sm">
        <div class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-3">
            <div class="text-h6 text-weight-bold text-grey-9 line-height-1">Dashboard</div>
            <div class="text-caption text-grey-6">{{ subtitulo }}</div>
          </div>

          <div class="col-12 col-md-4">
            <q-btn-toggle
              v-model="periodo" @update:model-value="cargar()" spread unelevated no-caps dense
              toggle-color="primary" color="grey-2" text-color="grey-8" class="dash-toggle"
              :options="[
                { label: 'Hoy', value: 'hoy' },
                { label: 'Ayer', value: 'ayer' },
                { label: 'Semana', value: 'semana' },
                { label: 'Mes', value: 'mes' },
                { label: 'Rango', value: 'rango' }
              ]"
            />
          </div>

          <div class="col-6 col-md-2" v-if="periodo === 'rango'">
            <q-input dense outlined type="date" v-model="desde" label="Desde" @update:model-value="cargar()"/>
          </div>
          <div class="col-6 col-md-2" v-if="periodo === 'rango'">
            <q-input dense outlined type="date" v-model="hasta" label="Hasta" @update:model-value="cargar()"/>
          </div>

          <div class="col-6 col-md-2">
            <q-select dense outlined v-model="agenciaId" :options="agencias" label="Agencia"
                      emit-value map-options option-value="id" option-label="nombre"
                      :disable="!esAdmin" @update:model-value="cargar()"/>
          </div>
          <div class="col-6 col-md-2">
            <q-select dense outlined v-model="userId" :options="usuarios" label="Usuario"
                      emit-value map-options option-value="id" option-label="name"
                      @update:model-value="cargar()"/>
          </div>

          <div class="col-12 col-md-1 text-right">
            <q-btn dense unelevated round color="primary" icon="refresh" :loading="cargando"
                   @click="cargar(true)">
              <q-tooltip>Actualizar datos</q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- ───────── Indicadores ───────── -->
    <div class="row q-col-gutter-md">
      <div class="col-6 col-md-4 col-lg" v-for="kpi in kpis" :key="kpi.titulo">
        <q-card flat bordered class="dash-card kpi" :style="{ borderTop: `3px solid ${kpi.color}` }">
          <q-tooltip v-if="kpi.pista" anchor="bottom middle" self="top middle" class="text-body2">
            {{ kpi.pista }}
          </q-tooltip>
          <q-card-section class="q-pa-md">
            <div class="row items-center no-wrap q-mb-xs">
              <q-icon :name="kpi.icono" size="18px" :style="{ color: kpi.color }" class="q-mr-xs"/>
              <div class="text-caption text-grey-7 ellipsis">{{ kpi.titulo }}</div>
            </div>
            <div class="text-h6 text-weight-bold text-grey-9">
              <q-skeleton v-if="cargando" type="text" width="80px"/>
              <span v-else>{{ kpi.valor }}</span>
            </div>
            <div class="text-caption q-mt-xs" :class="claseVariacion(kpi.variacion)">
              <template v-if="kpi.variacion !== null && kpi.variacion !== undefined">
                <q-icon :name="kpi.variacion >= 0 ? 'trending_up' : 'trending_down'" size="14px"/>
                {{ kpi.variacion > 0 ? '+' : '' }}{{ kpi.variacion }}% vs periodo anterior
              </template>
              <span v-else class="text-grey-5">{{ kpi.pie || '—' }}</span>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- ───────── Evolución + métodos de pago ───────── -->
    <div class="row q-col-gutter-md q-mt-none">
      <div class="col-12 col-lg-8">
        <q-card flat bordered class="dash-card">
          <q-card-section class="q-pb-none">
            <div class="row items-center q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <div class="text-subtitle1 text-weight-bold text-grey-9">Evolución de ventas</div>
                <div class="text-caption text-grey-6">{{ etiquetaGranularidad }}</div>
              </div>
              <div class="col-7 col-md-5">
                <q-btn-toggle v-model="metrica" spread unelevated no-caps dense
                              toggle-color="primary" color="grey-2" text-color="grey-8" class="dash-toggle"
                              :options="[
                                { label: 'Ventas', value: 'ingresos' },
                                { label: 'Ganancia', value: 'ganancia' },
                                { label: 'N° ventas', value: 'ventas' }
                              ]"/>
              </div>
              <div class="col-5 col-md-3">
                <q-btn-toggle v-model="tipoGrafico" spread unelevated no-caps dense
                              toggle-color="teal-7" color="grey-2" text-color="grey-8" class="dash-toggle"
                              :options="[
                                { label: 'Montañas', value: 'area', icon: 'landscape' },
                                { label: 'Barras', value: 'barras', icon: 'bar_chart' }
                              ]"/>
              </div>
            </div>
          </q-card-section>
          <q-card-section>
            <ChartTendencia :datos="serieGrafico" :tipo="tipoGrafico" :color="colorMetrica"
                            :moneda="metrica !== 'ventas'" :alto="320"/>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-4">
        <q-card flat bordered class="dash-card full-height">
          <q-card-section class="q-pb-xs">
            <div class="text-subtitle1 text-weight-bold text-grey-9">Métodos de pago</div>
            <div class="text-caption text-grey-6">Cómo cobraron las ventas del periodo</div>
          </q-card-section>
          <q-card-section class="q-pt-sm">
            <div v-if="!metodosPago.length" class="text-caption text-grey-5 text-center q-py-lg">Sin datos</div>
            <div v-for="(metodo, i) in metodosPago" :key="metodo.metodo" class="q-mb-md">
              <div class="row items-center justify-between text-caption q-mb-xs">
                <div class="text-weight-medium text-grey-8">
                  <q-icon :name="iconoMetodo(metodo.metodo)" size="16px" class="q-mr-xs"/>{{ metodo.metodo }}
                </div>
                <div class="text-grey-7">{{ bs(metodo.total) }} · {{ porcentaje(metodo.total, totalMetodos) }}%</div>
              </div>
              <q-linear-progress :value="metodo.total / (totalMetodos || 1)" size="10px" rounded
                                 :color="paleta[i % paleta.length]" track-color="grey-3"/>
            </div>

            <q-separator class="q-my-md"/>
            <div class="row text-center">
              <div class="col-6">
                <div class="text-caption text-grey-6">Facturas emitidas</div>
                <div class="text-subtitle1 text-weight-bold text-grey-9">{{ num(resumen.facturas) }}</div>
              </div>
              <div class="col-6">
                <div class="text-caption text-grey-6">Pendientes SIAT</div>
                <div class="text-subtitle1 text-weight-bold"
                     :class="resumen.pendientesSiat > 0 ? 'text-orange-8' : 'text-grey-9'">
                  {{ num(resumen.pendientesSiat) }}
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- ───────── Rankings ───────── -->
    <div class="row q-col-gutter-md q-mt-none">
      <div class="col-12 col-md-4">
        <q-card flat bordered class="dash-card full-height">
          <q-card-section class="q-pb-xs">
            <div class="text-subtitle1 text-weight-bold text-grey-9">Productos más vendidos</div>
            <div class="text-caption text-grey-6">Top 10 por unidades</div>
          </q-card-section>
          <q-card-section class="q-pt-sm">
            <div v-if="!topProductos.length" class="text-caption text-grey-5 text-center q-py-lg">Sin datos</div>
            <div v-for="(producto, i) in topProductos" :key="producto.id" class="ranking-item">
              <div class="row items-center no-wrap">
                <div class="ranking-puesto" :class="`bg-${paleta[i % paleta.length]}-1 text-${paleta[i % paleta.length]}-9`">
                  {{ i + 1 }}
                </div>
                <div class="col ellipsis text-caption text-weight-medium text-grey-9">
                  {{ producto.nombre }}
                  <q-tooltip>{{ producto.nombre }}</q-tooltip>
                </div>
                <div class="text-caption text-weight-bold text-grey-8 q-ml-sm">{{ num(producto.cantidad) }}</div>
              </div>
              <q-linear-progress :value="producto.cantidad / (maxProducto || 1)" size="6px" rounded
                                 color="primary" track-color="grey-2" class="q-mt-xs"/>
              <div class="text-caption text-grey-6 q-mt-xs">{{ bs(producto.total) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="dash-card full-height">
          <q-card-section class="q-pb-xs">
            <div class="text-subtitle1 text-weight-bold text-grey-9">Quién más vendió</div>
            <div class="text-caption text-grey-6">Top 10 por monto vendido</div>
          </q-card-section>
          <q-card-section class="q-pt-sm">
            <div v-if="!topUsuarios.length" class="text-caption text-grey-5 text-center q-py-lg">Sin datos</div>
            <q-item v-for="(usuario, i) in topUsuarios" :key="usuario.id" dense class="q-px-none">
              <q-item-section avatar class="q-pr-sm" style="min-width: 34px">
                <q-avatar size="30px" :class="`bg-${paleta[i % paleta.length]}-1 text-${paleta[i % paleta.length]}-9`"
                          class="text-weight-bold" style="font-size: 12px">
                  {{ iniciales(usuario.nombre) }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-caption text-weight-medium ellipsis">{{ usuario.nombre }}</q-item-label>
                <q-item-label caption>{{ num(usuario.ventas) }} ventas</q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="text-caption text-weight-bold text-grey-9">{{ bs(usuario.total) }}</div>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="dash-card full-height">
          <q-card-section class="q-pb-xs">
            <div class="text-subtitle1 text-weight-bold text-grey-9">Ventas por agencia</div>
            <div class="text-caption text-grey-6">Comparativa del periodo</div>
          </q-card-section>
          <q-card-section class="q-pt-sm">
            <div v-if="!porAgencia.length" class="text-caption text-grey-5 text-center q-py-lg">Sin datos</div>
            <div v-for="(agencia, i) in porAgencia" :key="agencia.id" class="ranking-item">
              <div class="row items-center no-wrap justify-between">
                <div class="col ellipsis text-caption text-weight-medium text-grey-9">
                  {{ agencia.nombre }}
                  <q-tooltip>{{ agencia.nombre }}</q-tooltip>
                </div>
                <div class="text-caption text-weight-bold text-grey-8 q-ml-sm">{{ bs(agencia.total) }}</div>
              </div>
              <q-linear-progress :value="agencia.total / (maxAgencia || 1)" size="8px" rounded
                                 :color="paleta[i % paleta.length]" track-color="grey-2" class="q-mt-xs"/>
              <div class="text-caption text-grey-6 q-mt-xs">{{ num(agencia.ventas) }} ventas</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="text-caption text-grey-5 text-right q-mt-sm" v-if="generado">
      Datos al {{ generado }} · el resumen se guarda unos minutos, usa Actualizar para recalcularlo
    </div>
  </q-page>
</template>

<script>
import moment from 'moment'
import ChartTendencia from 'components/ChartTendencia.vue'

export default {
  name: 'DashboardPage',
  components: { ChartTendencia },
  data () {
    return {
      cargando: false,
      periodo: 'hoy',
      desde: moment().startOf('month').format('YYYY-MM-DD'),
      hasta: moment().format('YYYY-MM-DD'),
      agenciaId: '',
      userId: '',
      agencias: [{ id: '', nombre: 'Todas las agencias' }],
      usuarios: [{ id: '', name: 'Todos los usuarios' }],
      metrica: 'ingresos',
      tipoGrafico: 'area',
      // Colores con tonos (bg-teal-1, text-teal-9...): 'primary' no los tiene.
      paleta: ['blue', 'teal', 'deep-purple', 'orange', 'pink', 'light-blue', 'green', 'indigo', 'red', 'brown'],
      rango: {},
      resumen: {},
      serie: [],
      topProductos: [],
      topUsuarios: [],
      porAgencia: [],
      metodosPago: [],
      generado: ''
    }
  },
  mounted () {
    this.catalogosGet()
    this.cargar()
  },
  computed: {
    esAdmin () {
      return this.$store.user && String(this.$store.user.id) === '1'
    },
    subtitulo () {
      if (!this.rango.desde) return 'Resumen de ventas'
      const desde = moment(this.rango.desde)
      const hasta = moment(this.rango.hasta)
      return desde.isSame(hasta, 'day')
        ? `Ventas del ${desde.format('DD/MM/YYYY')}`
        : `Del ${desde.format('DD/MM/YYYY')} al ${hasta.format('DD/MM/YYYY')} · ${this.rango.dias} días`
    },
    etiquetaGranularidad () {
      return { hora: 'Por hora', dia: 'Por día', mes: 'Por mes' }[this.rango.granularidad] || ''
    },
    kpis () {
      const r = this.resumen
      return [
        {
          titulo: 'Ventas netas',
          pista: 'Suma de las ventas del periodo ya con los descuentos aplicados. No incluye ventas anuladas.',
          valor: this.bs(r.ingresos),
          variacion: r.variacionIngresos,
          icono: 'payments',
          color: '#1976d2'
        },
        {
          titulo: 'N° de ventas',
          pista: 'Cantidad de ventas de ingreso registradas en el periodo.',
          valor: this.num(r.ventas),
          variacion: r.variacionVentas,
          icono: 'receipt_long',
          color: '#6a1b9a'
        },
        {
          titulo: 'Ticket promedio',
          pista: 'Ventas netas dividido entre el número de ventas.',
          valor: this.bs(r.ticketPromedio),
          variacion: null,
          pie: 'Por venta',
          icono: 'shopping_cart_checkout',
          color: '#00838f'
        },
        {
          titulo: 'Margen estimado',
          pista: 'Venta menos el costo actual del producto. Solo entran los productos que tienen costo cargado, así que es una estimación.',
          valor: this.bs(r.margenEstimado),
          variacion: null,
          pie: `${r.margenPorcentaje || 0}% sobre el costo cargado`,
          icono: 'trending_up',
          color: '#ef6c00'
        },
        {
          titulo: 'Unidades vendidas',
          pista: 'Unidades despachadas en el periodo y cuántos productos distintos se movieron.',
          valor: this.num(r.unidades),
          variacion: null,
          pie: `${this.num(r.productosDistintos)} productos distintos`,
          icono: 'inventory_2',
          color: '#ad1457'
        }
      ]
    },
    serieGrafico () {
      return this.serie.map(punto => ({
        etiqueta: punto.etiqueta,
        valor: punto[this.metrica],
        detalle: this.metrica === 'ventas'
          ? this.bs(punto.ingresos)
          : `${this.num(punto.ventas)} ventas`
      }))
    },
    colorMetrica () {
      return { ingresos: '#1976d2', ganancia: '#2e7d32', ventas: '#6a1b9a' }[this.metrica]
    },
    totalMetodos () {
      return this.metodosPago.reduce((suma, metodo) => suma + Number(metodo.total || 0), 0)
    },
    maxProducto () {
      return Math.max(...this.topProductos.map(p => p.cantidad), 0)
    },
    maxAgencia () {
      return Math.max(...this.porAgencia.map(a => a.total), 0)
    }
  },
  methods: {
    async catalogosGet () {
      await this.$store.fetchCatalogos(this.$axios, ['agencias', 'users'])
      this.agencias = [{ id: '', nombre: 'Todas las agencias' }, ...this.$store.agencias]
      this.usuarios = [{ id: '', name: 'Todos los usuarios' }, ...this.$store.users]
    },

    cargar (refrescar = false) {
      const params = { agencia_id: this.agenciaId, user_id: this.userId }

      if (this.periodo === 'rango') {
        if (!this.desde || !this.hasta) return
        params.desde = this.desde
        params.hasta = this.hasta
      } else {
        params.periodo = this.periodo
      }

      if (refrescar) params.refrescar = 1

      this.cargando = true
      this.$axios.get('dashboard/resumen', { params }).then(res => {
        this.cargando = false
        this.rango = res.data.rango || {}
        this.resumen = res.data.resumen || {}
        this.serie = res.data.serie || []
        this.topProductos = res.data.topProductos || []
        this.topUsuarios = res.data.topUsuarios || []
        this.porAgencia = res.data.porAgencia || []
        this.metodosPago = res.data.metodosPago || []
        this.generado = res.data.generado ? moment(res.data.generado).format('DD/MM/YYYY HH:mm') : ''
      }).catch(err => {
        this.cargando = false
        this.$alert.error(err.response?.data?.message || 'No se pudo cargar el dashboard')
      })
    },
    bs (valor) {
      const n = Number(valor) || 0
      return `${n.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Bs`
    },
    num (valor) {
      const n = Number(valor) || 0
      return n.toLocaleString('es-BO', { maximumFractionDigits: 0 })
    },
    porcentaje (parte, total) {
      if (!total) return 0
      return Math.round((Number(parte) / Number(total)) * 100)
    },
    claseVariacion (variacion) {
      if (variacion === null || variacion === undefined) return ''
      return variacion >= 0 ? 'text-green-7 text-weight-medium' : 'text-red-6 text-weight-medium'
    },
    iconoMetodo (metodo) {
      return {
        Efectivo: 'payments',
        QR: 'qr_code_2',
        Tarjeta: 'credit_card',
        Transferencia: 'account_balance'
      }[metodo] || 'point_of_sale'
    },
    iniciales (nombre) {
      return (nombre || '?')
        .split(' ')
        .filter(parte => parte.length > 2 && !parte.endsWith('.'))
        .slice(0, 2)
        .map(parte => parte[0])
        .join('')
        .toUpperCase() || '?'
    }
  }
}
</script>

<style scoped>
.dashboard {
  background: #f4f6fa;
}
.dash-card {
  border-radius: 14px;
  background: #fff;
}
.dash-card.full-height {
  height: 100%;
}
.kpi {
  border-radius: 14px;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.kpi:hover {
  box-shadow: 0 6px 18px rgba(38, 50, 56, 0.1);
  transform: translateY(-2px);
}
.line-height-1 {
  line-height: 1.2;
}
.dash-toggle {
  border-radius: 8px;
  overflow: hidden;
}
.ranking-item {
  padding: 6px 0;
}
.ranking-item + .ranking-item {
  border-top: 1px solid #f1f3f7;
}
.ranking-puesto {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 8px;
  flex: 0 0 20px;
}
</style>
