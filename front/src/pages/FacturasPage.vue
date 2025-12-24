<template>
  <q-page padding>
    <q-card>
      <q-card-section class="row items-center justify-between">
        <div>
          <div class="text-h6">Control de Facturas de Compra</div>
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
                <div class="text-h5">{{ resumenFacturas.total_facturas }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat>
              <q-card-section>
                <div class="text-subtitle2">Monto total facturado</div>
                <div class="text-h5">{{ formatCurrency(resumenFacturas.monto_total) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat>
              <q-card-section>
                <div class="text-subtitle2">Pagadas</div>
                <div class="text-h5">{{ resumenFacturas.porcentaje_pagado }}%</div>
                <q-linear-progress
                  size="10px"
                  :value="resumenFacturas.porcentaje_pagado / 100"
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
                <div class="text-h5">{{ formatCurrency(resumenFacturas.pendiente_total) }}</div>
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
            @update:model-value="cargarFacturas"
          />
<!--          <pre>{{ agencias }}</pre>-->
        </div>

        <div class="q-mb-sm text-primary text-subtitle1">
          Mostrando facturas de: {{ agencias.find(a => a.value === agenciaSeleccionada)?.label || 'Todas' }}
        </div>

        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-md-3">
<!--            <q-input v-model="filtroProveedor" label="Proveedor" outlined dense />-->
            <q-select
              v-model="filtroProveedor"
              :options="proveedores"
              label="Proveedor"
              outlined
              dense
              clearable
              option-value="nombreRazonSocial"
              option-label="nombreRazonSocial"
              emit-value
              map-options
            />
<!--            <pre>{{proveedores}}</pre>-->
          </div>
          <div class="col-12 col-md-3">
            <q-input v-model="filtroNumero" label="N° Factura" outlined dense clearable />
          </div>
          <div class="col-12 col-md-2">
            <q-select
              v-model="filtroTipo"
              :options="['Contado', 'Crédito']"
              label="Tipo de Pago"
              outlined
              dense
              clearable
            />
          </div>
          <div class="col-12 col-md-2">
            <q-select
              v-model="filtroEstado"
              :options="['Pagado', 'Pendiente', 'Parcial']"
              label="Estado"
              outlined
              dense
              clearable
            />
          </div>
          <div class="col-12 col-md-2">
            <div class="row q-col-gutter-xs">
              <div class="col-6">
                <q-btn color="primary" label="Buscar" icon="search" @click="filtrarFacturas" class="full-width" />
              </div>
              <div class="col-6">
                <q-btn flat label="Limpiar" icon="refresh" @click="limpiarFiltros" class="full-width" />
              </div>
            </div>
          </div>
        </div>

        <q-table
          class="q-mt-md"
          :rows="facturas"
          :columns="columnas"
          row-key="id"
          flat
          bordered
          dense
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
        >
          <template v-slot:body-cell-numero_factura="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.numero_factura }}</div>
              <div class="text-caption text-grey">{{ props.row.proveedor }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-fecha_compra="props">
            <q-td :props="props">
              {{ formatDate(props.row.fecha_compra) }}
            </q-td>
          </template>

          <template v-slot:body-cell-fecha_vencimiento="props">
            <q-td :props="props">
              <span v-if="props.row.fecha_vencimiento">
                {{ formatDate(props.row.fecha_vencimiento) }}
                <q-badge v-if="estaPorVencer(props.row.fecha_vencimiento)" color="warning" class="q-ml-xs">
                  Por vencer
                </q-badge>
                <q-badge v-if="estaVencida(props.row.fecha_vencimiento)" color="negative" class="q-ml-xs">
                  Vencida
                </q-badge>
              </span>
              <span v-else>-</span>
            </q-td>
          </template>

          <template v-slot:body-cell-monto_total="props">
            <q-td class="text-right" :props="props">
              {{ formatCurrency(props.row.monto_total) }}
            </q-td>
          </template>

          <template v-slot:body-cell-saldo="props">
            <q-td class="text-right" :props="props">
              <span v-if="props.row.tipo_pago === 'Crédito'">
                {{ formatCurrency(props.row.monto_total - props.row.pagado) }}
              </span>
              <span v-else>-</span>
            </q-td>
          </template>

          <template v-slot:body-cell-estado="props">
            <q-td :props="props">
              <q-badge
                :color="getEstadoColor(props.row.estado)"
                :label="props.row.estado"
                class="text-white"
                style="padding: 6px 12px; border-radius: 6px;"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-sucursal="props">
            <q-td :props="props">
              {{ agencias.find(a => a.value === props.row.agencia_id)?.label || 'Desconocida' }}
            </q-td>
          </template>

          <template v-slot:body-cell-acciones="props">
            <q-td align="center">
              <q-btn dense flat icon="visibility" color="primary" @click="verDetalle(props.row)" />
              <q-btn dense flat icon="payments" color="orange" @click="abrirPago(props.row)"
                     v-if="props.row.estado !== 'Pagado' && props.row.tipo_pago === 'Crédito'" />
              <q-btn dense flat icon="edit" color="blue" @click="editarFactura(props.row)" />
              <q-btn dense flat icon="delete" color="red" @click="eliminarFactura(props.row)" />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Registro/Edición de Factura -->
    <q-dialog v-model="dialogoAbierto" persistent>
      <q-card style="min-width: 500px; max-width: 700px;">
        <q-card-section>
          <div class="text-h6">{{ facturaSeleccionada ? 'Editar' : 'Registrar' }} Factura</div>
        </q-card-section>

        <q-card-section class="">
          <div class="row ">
            <div class="col-6">
              <q-input v-model="form.numero_factura" label="N° Factura *" outlined dense
                       :rules="[val => !!val || 'Campo requerido']" />
            </div>
            <div class="col-6">
              <q-input v-model="form.vendedor" label="Vendedor" outlined dense />
            </div>
          </div>

          <q-input v-model="form.proveedor" label="Proveedor *" outlined dense
                   :rules="[val => !!val || 'Campo requerido']" />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input v-model="form.fecha_compra" type="date" label="Fecha de compra *" outlined dense
                       :rules="[val => !!val || 'Campo requerido']" />
            </div>
            <div class="col-6">
              <q-input v-model="form.monto_total" label="Monto total *" type="number" outlined dense
                       :rules="[val => !!val || 'Campo requerido', val => val > 0 || 'Monto debe ser mayor a 0']" />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select v-model="form.tipo_pago" :options="['Contado', 'Crédito']" label="Tipo de pago *"
                        outlined dense :rules="[val => !!val || 'Campo requerido']" />
            </div>
            <div class="col-6">
              <q-select v-model="form.metodo_pago" :options="['Efectivo', 'Transferencia', 'Cheque', 'Tarjeta']"
                        label="Método de pago" outlined dense />
            </div>
          </div>

          <q-input v-if="form.tipo_pago === 'Crédito'" v-model="form.fecha_vencimiento" type="date"
                   label="Fecha de vencimiento" outlined dense />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select v-model="form.estado" :options="['Pagado', 'Pendiente', 'Parcial']" label="Estado" outlined dense />
            </div>
            <div class="col-6">
              <q-select v-model="form.agencia_id" :options="agencias" label="Sucursal *" outlined dense
                        emit-value map-option :rules="[val => !!val || 'Campo requerido']" />
            </div>
          </div>

          <q-input v-model="form.observaciones" label="Observaciones" type="textarea" outlined dense rows="2" />

          <div class="text-caption text-grey">
            * Campos obligatorios
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" v-close-popup />
          <q-btn color="primary" label="Guardar" @click="guardarFactura" :loading="loadingGuardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Registrar Pago -->
    <q-dialog v-model="dialogoPago" persistent>
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">Registrar Pago</div>
          <div class="text-subtitle2 text-grey">
            Factura: {{ facturaEnPago?.numero_factura }} - Proveedor: {{ facturaEnPago?.proveedor }}
          </div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <div class="text-subtitle2">Total Factura:</div>
              <div class="text-h6">{{ formatCurrency(facturaEnPago?.monto_total) }}</div>
            </div>
            <div class="col-6">
              <div class="text-subtitle2">Pagado:</div>
              <div class="text-h6">{{ formatCurrency(facturaEnPago?.pagado) }}</div>
            </div>
          </div>

          <div class="text-subtitle2 text-center q-mt-md">
            Saldo Pendiente: <span class="text-h6">{{ formatCurrency(facturaEnPago?.monto_total - facturaEnPago?.pagado) }}</span>
          </div>

          <q-input v-model.number="montoPago" label="Monto a pagar *" type="number" outlined dense
                   :rules="[val => !!val || 'Campo requerido', val => val > 0 || 'Monto debe ser mayor a 0']"
                   :max="facturaEnPago?.monto_total - facturaEnPago?.pagado" />

          <q-select v-model="metodoPagoSeleccionado" :options="['Efectivo', 'Transferencia', 'Cheque', 'Tarjeta']"
                    label="Método de pago" outlined dense />

          <q-input v-model="referenciaPago" label="Referencia/Número" outlined dense />

          <q-input v-model="vendedorPago" label="Vendedor que recibe el pago" outlined dense />

          <q-input v-model="observacionesPago" label="Observaciones" type="textarea" outlined dense rows="2" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" v-close-popup />
          <q-btn color="primary" label="Confirmar Pago" @click="registrarPago" :loading="loadingPago" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Detalle de Factura -->
    <q-dialog v-model="verDialogoDetalle" persistent>
      <q-card style="min-width: 600px; max-width: 90vw;">
        <q-card-section>
          <div class="text-h6">Detalle de Factura {{ detalleFactura?.numero_factura }}</div>
          <div class="text-subtitle2">Proveedor: {{ detalleFactura?.proveedor }}</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <div><strong>Fecha de Compra:</strong> {{ formatDate(detalleFactura?.fecha_compra) }}</div>
              <div><strong>Vendedor:</strong> {{ detalleFactura?.vendedor || 'No especificado' }}</div>
              <div><strong>Tipo de Pago:</strong> {{ detalleFactura?.tipo_pago }}</div>
            </div>
            <div class="col-6">
              <div><strong>Método de Pago:</strong> {{ detalleFactura?.metodo_pago || 'No especificado' }}</div>
              <div><strong>Vencimiento:</strong> {{ detalleFactura?.fecha_vencimiento ? formatDate(detalleFactura.fecha_vencimiento) : 'No aplica' }}</div>
              <div><strong>Sucursal:</strong> {{ agencias.find(a => a.value === detalleFactura?.agencia_id)?.label }}</div>
            </div>
          </div>

          <div class="row q-col-gutter-md q-mt-md">
            <div class="col-4">
              <q-card bordered flat>
                <q-card-section>
                  <div class="text-subtitle2">Total Factura</div>
                  <div class="text-h6">{{ formatCurrency(detalleFactura?.monto_total) }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-4">
              <q-card bordered flat>
                <q-card-section>
                  <div class="text-subtitle2">Pagado</div>
                  <div class="text-h6">{{ formatCurrency(detalleFactura?.pagado) }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-4">
              <q-card bordered flat>
                <q-card-section>
                  <div class="text-subtitle2">Saldo</div>
                  <div class="text-h6" :class="{'text-negative': (detalleFactura?.monto_total - detalleFactura?.pagado) > 0}">
                    {{ formatCurrency(detalleFactura?.monto_total - detalleFactura?.pagado) }}
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <div class="q-mt-md">
            <div class="text-subtitle2">Observaciones:</div>
            <div class="q-pa-sm bg-grey-3 rounded-borders">{{ detalleFactura?.observaciones || 'Sin observaciones' }}</div>
          </div>
        </q-card-section>

        <q-card-section>
          <div class="text-h6 q-mb-sm">Pagos Realizados</div>
          <q-markup-table dense bordered v-if="detalleFactura?.pagos?.length">
            <thead>
            <tr>
              <th>Fecha</th>
              <th>Monto</th>
              <th>Método</th>
              <th>Vendedor</th>
              <th>Referencia</th>
              <th>Observaciones</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(pago, index) in detalleFactura.pagos" :key="index">
              <td>{{ formatDate(pago.fecha_pago) }}</td>
              <td>{{ formatCurrency(pago.monto) }}</td>
              <td>{{ pago.metodo_pago || '-' }}</td>
              <td>{{ pago.vendedor || '-' }}</td>
              <td>{{ pago.referencia || '-' }}</td>
              <td>{{ pago.observaciones || '-' }}</td>
            </tr>
            </tbody>
          </q-markup-table>
          <div v-else class="text-center text-grey q-pa-lg">
            No se han registrado pagos para esta factura
          </div>
        </q-card-section>

        <q-card-section v-if="detalleFactura?.buys?.length">
          <div class="text-h6 q-mb-sm">Productos Comprados</div>
          <q-markup-table dense bordered>
            <thead>
            <tr>
              <th>Producto</th>
              <th>Lote</th>
              <th>Cantidad</th>
              <th>Precio Unit.</th>
              <th>Subtotal</th>
              <th>Vencimiento</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(compra, index) in detalleFactura.buys" :key="index">
              <td>{{ compra.product?.nombre || 'Producto no encontrado' }}</td>
              <td>{{ compra.lote }}</td>
              <td>{{ compra.quantity }}</td>
              <td>{{ formatCurrency(compra.price/1.3) }}</td>
              <td>{{ formatCurrency(compra.quantity * compra.price/1.3) }}</td>
              <td>{{ formatDate(compra.dateExpiry) }}</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { date } from 'quasar'

export default {
  name: 'FacturacionPage',
  data () {
    return {
      proveedor_id: null,
      proveedores: [],
      agencias: [
        { label: 'Almacén Central', value: 0 }
      ],
      agenciaSeleccionada: 0,
      facturas: [],
      columnas: [
        { name: 'numero_factura', label: 'Factura/Proveedor', field: 'numero_factura', align: 'left', sortable: true },
        { name: 'fecha_compra', label: 'Fecha Compra', field: 'fecha_compra', align: 'left', sortable: true },
        { name: 'fecha_vencimiento', label: 'Vencimiento', field: 'fecha_vencimiento', align: 'left', sortable: true },
        { name: 'monto_total', label: 'Monto', field: 'monto_total', align: 'right', sortable: true },
        { name: 'pagado', label: 'Pagado', field: 'pagado', align: 'right', sortable: true },
        { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'right', sortable: true },
        { name: 'tipo_pago', label: 'Tipo', field: 'tipo_pago', align: 'center', sortable: true },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center', sortable: true },
        { name: 'sucursal', label: 'Sucursal', field: 'sucursal', align: 'left', sortable: true },
        { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' }
      ],
      filtroProveedor: '',
      filtroNumero: '',
      filtroTipo: '',
      filtroEstado: '',
      dialogoAbierto: false,
      dialogoPago: false,
      verDialogoDetalle: false,
      facturaSeleccionada: null,
      facturaEnPago: null,
      detalleFactura: null,
      montoPago: 0,
      metodoPagoSeleccionado: 'Efectivo',
      referenciaPago: '',
      vendedorPago: '',
      observacionesPago: '',
      loading: false,
      loadingGuardar: false,
      loadingPago: false,
      pagination: {
        sortBy: 'fecha_compra',
        descending: true,
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0
      },
      form: {
        numero_factura: '',
        proveedor: '',
        vendedor: '',
        fecha_compra: date.formatDate(new Date(), 'YYYY-MM-DD'),
        monto_total: '',
        tipo_pago: 'Contado',
        metodo_pago: 'Efectivo',
        fecha_vencimiento: '',
        estado: 'Pendiente',
        observaciones: '',
        agencia_id: 0,
        proveedor_id: null,
        pagado: 0
      },
      resumenFacturas: {
        total_facturas: 0,
        monto_total: 0,
        pagado_total: 0,
        pendiente_total: 0,
        porcentaje_pagado: 0
      }
    }
  },
  computed: {
    userAgencia () {
      return this.$store.user?.agencia_id || 0
    }
  },
  methods: {
    formatCurrency (value) {
      if (!value) return 'Bs. 0.00'
      return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2
      }).format(value)
    },
    formatDate (dateString) {
      if (!dateString) return '-'
      return date.formatDate(dateString, 'DD/MM/YYYY')
    },
    estaPorVencer (fechaVencimiento) {
      if (!fechaVencimiento) return false
      const hoy = new Date()
      const vencimiento = new Date(fechaVencimiento)
      const diffTime = vencimiento - hoy
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays > 0 && diffDays <= 30
    },
    estaVencida (fechaVencimiento) {
      if (!fechaVencimiento) return false
      const hoy = new Date()
      const vencimiento = new Date(fechaVencimiento)
      return vencimiento < hoy
    },
    getEstadoColor (estado) {
      switch (estado) {
        case 'Pagado': return 'positive'
        case 'Parcial': return 'warning'
        case 'Pendiente': return 'negative'
        default: return 'grey'
      }
    },
    onRequest (props) {
      this.pagination = props.pagination
      this.cargarFacturas()
    },
    async cargarFacturas () {
      this.loading = true
      try {
        const params = {
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage,
          sort_by: this.pagination.sortBy,
          descending: this.pagination.descending,
          agencia_id: this.agenciaSeleccionada,
          proveedor: this.filtroProveedor,
          numero_factura: this.filtroNumero,
          tipo_pago: this.filtroTipo,
          estado: this.filtroEstado
        }

        const response = await this.$axios.get('facturas', { params })
        this.facturas = response.data.data
        this.pagination.rowsNumber = response.data.total
        this.pagination.page = response.data.current_page
        this.pagination.rowsPerPage = response.data.per_page

        // Cargar resumen
        await this.cargarResumen()
      } catch (error) {
        console.error('Error cargando facturas:', error)
        this.$alert.error('Error al cargar las facturas')
      } finally {
        this.loading = false
      }
    },
    async cargarResumen () {
      try {
        const response = await this.$axios.get('facturas-resumen', {
          params: { agencia_id: this.agenciaSeleccionada }
        })
        this.resumenFacturas = response.data
      } catch (error) {
        console.error('Error cargando resumen:', error)
      }
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
    async guardarFactura () {
      try {
        this.loadingGuardar = true

        // Validaciones básicas
        if (!this.form.numero_factura || !this.form.proveedor || !this.form.fecha_compra || !this.form.monto_total) {
          this.$alert.error('Complete todos los campos obligatorios')
          this.loadingGuardar = false
          return
        }

        if (this.form.tipo_pago === 'Crédito' && this.form.estado === 'Pagado' && parseFloat(this.form.pagado) < parseFloat(this.form.monto_total)) {
          this.$alert.error('Para marcar como Pagado, el monto pagado debe ser igual al total')
          this.loadingGuardar = false
          return
        }

        // let response
        if (this.facturaSeleccionada) {
          await this.$axios.put(`facturas/${this.facturaSeleccionada.id}`, this.form)
          this.$alert.success('Factura actualizada correctamente')
        } else {
          await this.$axios.post('facturas', {
            ...this.form,
            user_id: this.$store.user.id
          })
          this.$alert.success('Factura registrada exitosamente')
        }

        this.dialogoAbierto = false
        await this.cargarFacturas()
      } catch (error) {
        console.error('Error guardando factura:', error)
        this.$alert.error(error.response?.data?.message || 'Error al guardar la factura')
      } finally {
        this.loadingGuardar = false
      }
    },
    async eliminarFactura (factura) {
      await this.$q.dialog({
        title: 'Confirmar eliminación',
        message: `¿Estás seguro de eliminar la factura ${factura.numero_factura}?`,
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          await this.$axios.delete(`facturas/${factura.id}`)
          this.$alert.success('Factura eliminada correctamente')
          await this.cargarFacturas()
        } catch (error) {
          console.error('Error eliminando factura:', error)
          this.$alert.error(error.response?.data?.message || 'Error al eliminar la factura')
        }
      })
    },
    abrirPago (factura) {
      this.facturaEnPago = factura
      this.montoPago = 0
      this.metodoPagoSeleccionado = 'Efectivo'
      this.referenciaPago = ''
      this.vendedorPago = ''
      this.observacionesPago = ''
      this.dialogoPago = true
    },
    async registrarPago () {
      try {
        this.loadingPago = true

        if (!this.montoPago || this.montoPago <= 0) {
          this.$alert.error('Ingrese un monto válido')
          this.loadingPago = false
          return
        }

        const saldoPendiente = this.facturaEnPago.monto_total - this.facturaEnPago.pagado
        if (this.montoPago > saldoPendiente) {
          this.$alert.error(`El monto no puede exceder el saldo pendiente (${this.formatCurrency(saldoPendiente)})`)
          this.loadingPago = false
          return
        }

        await this.$axios.post(`facturas/${this.facturaEnPago.id}/pagar`, {
          monto: this.montoPago,
          metodo_pago: this.metodoPagoSeleccionado,
          referencia: this.referenciaPago,
          vendedor: this.vendedorPago,
          observaciones: this.observacionesPago
        })

        this.$alert.success('Pago registrado exitosamente')
        this.dialogoPago = false
        await this.cargarFacturas()
      } catch (error) {
        console.error('Error registrando pago:', error)
        this.$alert.error(error.response?.data?.message || 'Error al registrar el pago')
      } finally {
        this.loadingPago = false
      }
    },
    async verDetalle (factura) {
      try {
        this.loading = true
        const response = await this.$axios.get(`facturas/${factura.id}`)
        this.detalleFactura = response.data
        this.verDialogoDetalle = true
      } catch (error) {
        console.error('Error cargando detalle:', error)
        this.$alert.error('Error al cargar el detalle de la factura')
      } finally {
        this.loading = false
      }
    },
    resetForm () {
      this.form = {
        numero_factura: '',
        proveedor: '',
        vendedor: '',
        fecha_compra: date.formatDate(new Date(), 'YYYY-MM-DD'),
        monto_total: '',
        tipo_pago: 'Contado',
        metodo_pago: 'Efectivo',
        fecha_vencimiento: '',
        estado: 'Pendiente',
        observaciones: '',
        agencia_id: this.userAgencia,
        proveedor_id: null,
        pagado: 0
      }
    },
    filtrarFacturas () {
      this.pagination.page = 1
      this.cargarFacturas()
    },
    sucursalGet () {
      this.$axios.get('sucursales')
        .then(response => {
          console.log('Sucursales cargadas:', response.data)
          this.agencias = response.data.map(sucursal => ({
            label: sucursal.nombre,
            value: sucursal.id
          }))
          this.agencias.unshift({ label: 'Almacén Central', value: 0 })
        })
        .catch(error => {
          console.error('Error cargando sucursales:', error)
          this.$alert.error('Error al cargar las sucursales')
        })
    },
    limpiarFiltros () {
      this.filtroProveedor = ''
      this.filtroNumero = ''
      this.filtroTipo = ''
      this.filtroEstado = ''
      this.pagination.page = 1
      this.cargarFacturas()
    }
  },
  watch: {
    agenciaSeleccionada () {
      this.pagination.page = 1
      this.cargarFacturas()
    }
  },
  mounted () {
    // Si el usuario no es de agencia 1, solo puede ver su agencia
    if (this.userAgencia !== 1) {
      this.agenciaSeleccionada = this.userAgencia
    }
    this.cargarFacturas()
    // sucursalget
    this.sucursalGet()
    // llenar provedores
    this.$axios.get('providers')
      .then(response => {
        this.proveedores = response.data
      })
      .catch(error => {
        console.error('Error cargando proveedores:', error)
        this.$alert.error('Error al cargar los proveedores')
      })
  }
}
</script>

<style scoped>
.q-table__card {
  border-radius: 8px;
}

.q-card {
  border-radius: 8px;
}

.text-h6 {
  font-weight: 600;
}

.text-subtitle2 {
  opacity: 0.7;
}

.q-badge {
  font-weight: 500;
}

.q-markup-table {
  border-radius: 6px;
  overflow: hidden;
}

.q-input, .q-select {
  border-radius: 6px;
}

.q-dialog__inner > div {
  border-radius: 12px;
}
</style>
