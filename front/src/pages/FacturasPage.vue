<template>
  <q-page padding>
    <q-card>
      <q-card-section class="row items-center justify-between">
        <div>
          <div class="text-h6">Control de Facturas</div>
          <div class="text-subtitle2 text-grey">Facturas al contado o a crédito con pagos parciales</div>
        </div>
        <q-btn color="primary" icon="add" label="Registrar Factura" @click="abrirFormulario" />
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-card bordered flat>
              <q-card-section>
                <div class="text-subtitle2">Total de facturas</div>
                <div class="text-h5">{{ resumenFacturas.totalFacturas }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat>
              <q-card-section>
                <div class="text-subtitle2">Monto total facturado</div>
                <div class="text-h5">{{ formatCurrency(resumenFacturas.montoTotal) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat>
              <q-card-section>
                <div class="text-subtitle2">Pagadas</div>
                <div class="text-h5">{{ resumenFacturas.porcentajePagado }}%</div>
                <q-linear-progress
                  size="10px"
                  :value="resumenFacturas.porcentajePagado / 100"
                  color="positive"
                  class="q-mt-sm"
                />
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat>
              <q-card-section>
                <div class="text-subtitle2">Total pendiente</div>
                <div class="text-h5">{{ formatCurrency(resumenFacturas.totalPendiente) }}</div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="q-mb-md">
          <q-select
            v-model="agenciaSeleccionada"
            :options="agencias"
            label="Elegir sucursal"
            outlined
            dense
            emit-value
            map-options
            style="min-width: 250px"
          />
        </div>

        <div class="q-mb-sm text-primary text-subtitle1">
          Mostrando facturas de: {{ agencias.find(a => a.value === agenciaSeleccionada)?.label }}
        </div>

        <div class="q-gutter-md row items-center">
          <q-input v-model="filtroProveedor" label="Proveedor" outlined dense />
          <q-input v-model="filtroNumero" label="N° Factura" outlined dense />
          <q-select
            v-model="filtroTipo"
            :options="['Contado', 'Crédito']"
            label="Tipo de Pago"
            outlined
            dense
            clearable
          />
          <q-btn color="primary" label="Buscar" icon="search" @click="filtrarFacturas" />
          <q-btn flat label="Actualizar" icon="refresh" @click="cargarFacturas" />
        </div>

        <q-table
          class="q-mt-md"
          :rows="facturas"
          :columns="columnas"
          row-key="id"
          flat
          bordered
          dense
        >
          <template v-slot:body-cell-total="props">
            <q-td class="text-right">
              {{ formatCurrency(props.row.total) }}
            </q-td>
          </template>

          <template v-slot:body-cell-saldo="props">
            <q-td class="text-right">
              <span v-if="props.row.tipo_pago === 'Crédito'">
                {{ formatCurrency(props.row.total - props.row.pagado) }}
              </span>
              <span v-else>-</span>
            </q-td>
          </template>

          <template v-slot:body-cell-estado="props">
            <q-td>
              <q-badge
                :color="props.row.estado === 'Pagado' ? 'green' : 'red'"
                :label="props.row.estado"
                class="text-white"
                style="padding: 6px 12px; border-radius: 6px;"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-sucursal="props">
            <q-td>
              {{ agencias.find(a => a.value === props.row.agencia_id)?.label || 'Desconocida' }}
            </q-td>
          </template>

          <template v-slot:body-cell-acciones="props">
            <q-td align="center">
              <q-btn dense flat icon="visibility" color="primary" @click="verDetalle(props.row)" />
              <q-btn dense flat icon="payments" color="orange" @click="abrirPago(props.row)" v-if="props.row.estado !== 'Pagado'" />
              <q-btn dense flat icon="edit" color="blue" @click="editarFactura(props.row)" />
              <q-btn dense flat icon="delete" color="red" @click="eliminarFactura(props.row)" />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Registro de Factura -->
    <q-dialog v-model="dialogoAbierto">
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">{{ facturaSeleccionada ? 'Editar' : 'Registrar' }} Factura</div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <q-input v-model="form.numero" label="N° Factura" outlined dense />
          <q-input v-model="form.proveedor" label="Proveedor" outlined dense />
          <q-input v-model="form.fecha" type="date" label="Fecha de compra" outlined dense />
          <q-input v-model="form.total" label="Monto total" type="number" outlined dense />
          <q-select v-model="form.tipo_pago" :options="['Contado', 'Crédito']" label="Tipo de pago" outlined dense />
          <q-select v-model="form.metodo_pago" :options="['Efectivo', 'Transferencia']" label="Método de pago" outlined dense /> <!-- Método de pago -->
          <q-input v-if="form.tipo_pago === 'Crédito'" v-model="form.fecha_vencimiento" type="date" label="Fecha de vencimiento" outlined dense />
          <q-select v-model="form.estado" :options="['Pagado', 'Pendiente']" label="Estado" outlined dense />
          <q-input v-model="form.vendedor" label="Vendedor" outlined dense /> <!-- Campo para el nombre del vendedor -->
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn color="primary" label="Guardar" @click="guardarFactura" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Registrar Pago -->
    <q-dialog v-model="dialogoPago">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Registrar Pago</div>
          <div class="text-subtitle2 text-grey">
            Factura: {{ facturaEnPago?.numero }} - Total: {{ formatCurrency(facturaEnPago?.total) }}
          </div>
        </q-card-section>

        <q-card-section>
          <q-input v-model.number="montoPago" label="Monto a pagar" type="number" outlined dense />
          <q-input v-model="pagoVendedor" label="Vendedor del pago" outlined dense /> <!-- Campo para vendedor en el pago -->
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn color="primary" label="Confirmar Pago" @click="registrarPago" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Detalle de Factura -->
    <q-dialog v-model="verDialogoDetalle">
      <q-card style="min-width: 400px; max-width: 90vw;">
        <q-card-section>
          <div class="text-h6">Detalle de Factura {{ detalleFactura?.numero }}</div>
          <div class="text-subtitle2">Proveedor: {{ detalleFactura?.proveedor }}</div>
        </q-card-section>

        <q-card-section>
          <div class="text-subtitle2">Vendedor: {{ detalleFactura?.vendedor || 'No especificado' }}</div> <!-- Mostrar el nombre del vendedor -->
        </q-card-section>

        <q-card-section>
          <q-markup-table dense bordered>
            <thead>
              <tr>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Vendedor</th> <!-- Columna para mostrar el vendedor -->
              </tr>
            </thead>
            <tbody>
              <tr v-if="!detalleFactura?.pagos?.length">
                <td colspan="3">Sin pagos aún</td>
              </tr>
              <tr v-for="(p, i) in detalleFactura.pagos" :key="i">
                <td>{{ formatCurrency(p.monto) }}</td>
                <td>{{ p.fecha }}</td>
                <td>{{ p.vendedor || 'No especificado' }}</td> <!-- Mostrar vendedor en el pago -->
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-section>
          <div><strong>Total:</strong> {{ formatCurrency(detalleFactura?.total) }}</div>
          <div><strong>Pagado:</strong> {{ formatCurrency(detalleFactura?.pagado) }}</div>
          <div><strong>Saldo:</strong> {{ formatCurrency(detalleFactura?.total - detalleFactura?.pagado) }}</div>
          <div><strong>Estado:</strong> {{ detalleFactura?.estado }}</div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'FacturasPage',
  data () {
    return {
      agencias: [
        { label: 'Casa Matriz - Velasco', value: 1 },
        { label: 'Sucursal Central', value: 2 },
        { label: 'Sucursal Norte', value: 3 }
      ],
      agenciaSeleccionada: 1,
      facturas: [],
      columnas: [
        { name: 'numero', label: 'N° Factura', field: 'numero', align: 'left' },
        { name: 'proveedor', label: 'Proveedor', field: 'proveedor', align: 'left' },
        { name: 'fecha', label: 'Fecha de Compra', field: 'fecha', align: 'left' },
        { name: 'total', label: 'Monto', field: 'total', align: 'right' },
        { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'center' },
        { name: 'tipo_pago', label: 'Tipo de Pago', field: 'tipo_pago', align: 'left' },
        { name: 'metodo_pago', label: 'Método de Pago', field: 'metodo_pago', align: 'left' },
        { name: 'fecha_vencimiento', label: 'Vence', field: 'fecha_vencimiento', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
        { name: 'vendedor', label: 'Vendedor', field: 'vendedor', align: 'left' }, // Columna para vendedor
        { name: 'sucursal', label: 'Sucursal', field: 'sucursal', align: 'left' },
        { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' }
      ],
      filtroProveedor: '',
      filtroNumero: '',
      filtroTipo: '',
      filtroMetodoPago: '',
      dialogoAbierto: false,
      dialogoPago: false,
      verDialogoDetalle: false,
      facturaSeleccionada: null,
      facturaEnPago: null,
      detalleFactura: null,
      montoPago: 0,
      pagoVendedor: '', // Campo para el vendedor en el pago
      form: {
        numero: '',
        proveedor: '',
        fecha: '',
        total: '',
        tipo_pago: '',
        metodo_pago: '', // Campo para el método de pago
        vendedor: '', // Campo para el nombre del vendedor
        fecha_vencimiento: '',
        estado: 'Pendiente',
        pagos: [],
        pagado: 0
      }
    }
  },
  computed: {
    resumenFacturas () {
      const totalFacturas = this.facturas.length
      const montoTotal = this.facturas.reduce((sum, factura) => sum + factura.total, 0)
      const totalPagado = this.facturas.reduce((sum, factura) => sum + factura.pagado, 0)
      const totalPendiente = montoTotal - totalPagado
      const porcentajePagado = montoTotal > 0 ? Math.round((totalPagado / montoTotal) * 100) : 0

      return {
        totalFacturas,
        montoTotal,
        totalPagado,
        totalPendiente,
        porcentajePagado
      }
    }
  },
  methods: {
    formatCurrency (value) {
      return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2
      }).format(value)
    },
    cargarFacturas () {
      const todasLasFacturas = [
        {
          id: 1,
          numero: 'F001',
          proveedor: 'Proveedor A',
          vendedor: 'Juan Pérez', // Aquí agregas el nombre del vendedor
          fecha: '2024-03-10',
          total: 1000,
          tipo_pago: 'Crédito',
          metodo_pago: 'Transferencia',
          fecha_vencimiento: '2024-04-10',
          estado: 'Pendiente',
          pagos: [],
          pagado: 0,
          agencia_id: 1
        }
        // Otras facturas...
      ]

      this.facturas = todasLasFacturas.filter(f => f.agencia_id === this.agenciaSeleccionada)
    },
    abrirFormulario () {
      this.facturaSeleccionada = null
      this.resetForm()
      this.dialogoAbierto = true
    },
    editarFactura (factura) {
      this.facturaSeleccionada = factura
      this.form = { ...factura }
      this.dialogoAbierto = true
    },
    guardarFactura () {
      if (this.facturaSeleccionada) {
        Object.assign(this.facturaSeleccionada, this.form)
        this.$q.notify({ type: 'positive', message: 'Factura editada correctamente' })
      } else {
        const nueva = {
          ...this.form,
          id: Date.now(),
          pagos: [],
          pagado: 0,
          agencia_id: this.agenciaSeleccionada
        }
        this.facturas.push(nueva)
        this.$q.notify({ type: 'positive', message: 'Factura registrada exitosamente' })
      }
      this.dialogoAbierto = false
    },
    eliminarFactura (factura) {
      this.facturas = this.facturas.filter(f => f.id !== factura.id)
      this.$q.notify({ type: 'warning', message: 'Factura eliminada' })
    },
    abrirPago (factura) {
      this.facturaEnPago = factura
      this.montoPago = 0
      this.dialogoPago = true
    },
    registrarPago () {
      const factura = this.facturaEnPago
      const saldoRestante = factura.total - factura.pagado
      const EPSILON = 0.05

      if (this.montoPago <= 0) {
        this.$q.notify({ type: 'negative', message: 'El monto debe ser mayor a 0' })
        return
      }

      if (factura.tipo_pago === 'Contado' && Math.abs(this.montoPago - saldoRestante) > EPSILON) {
        this.$q.notify({
          type: 'negative',
          message: `Para facturas al contado debes pagar ${this.formatCurrency(saldoRestante)}`
        })
        return
      }

      if (factura.tipo_pago === 'Crédito' && this.montoPago > saldoRestante + EPSILON) {
        this.$q.notify({
          type: 'negative',
          message: `El monto ingresado supera el saldo pendiente (${this.formatCurrency(saldoRestante)})`
        })
        return
      }

      factura.pagos.push({
        monto: this.montoPago,
        fecha: new Date().toISOString().split('T')[0],
        vendedor: this.pagoVendedor // Aquí asociamos el vendedor al pago
      })

      factura.pagado += this.montoPago

      if (Math.abs(factura.pagado - factura.total) <= EPSILON || factura.pagado > factura.total) {
        factura.estado = 'Pagado'
        factura.pagado = factura.total
      } else {
        factura.estado = 'Pendiente'
      }

      this.dialogoPago = false
      this.montoPago = 0
      this.pagoVendedor = '' // Limpiar el vendedor del pago

      this.$q.notify({ type: 'positive', message: 'Pago registrado exitosamente' })
    },
    verDetalle (factura) {
      this.detalleFactura = factura
      this.verDialogoDetalle = true
    },
    resetForm () {
      this.form = {
        numero: '',
        proveedor: '',
        fecha: '',
        total: '',
        tipo_pago: '',
        metodo_pago: '', // Campo para el método de pago
        vendedor: '', // Campo para el nombre del vendedor
        fecha_vencimiento: '',
        estado: 'Pendiente',
        pagos: [],
        pagado: 0
      }
    },
    filtrarFacturas () {
      this.cargarFacturas()
    }
  },
  watch: {
    agenciaSeleccionada () {
      this.cargarFacturas()
    }
  },
  mounted () {
    this.cargarFacturas()
  }
}
</script>
