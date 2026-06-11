<template>
  <q-page class="q-pa-md">
    <div v-if="report" class="row items-center q-mb-md">
      <div class="col">
        <q-breadcrumbs class="text-grey-7 q-mb-xs" active-color="primary">
          <q-breadcrumbs-el label="Informes de Bajas" icon="description" to="/informesBajas" />
          <q-breadcrumbs-el :label="'Informe #' + report.id" />
        </q-breadcrumbs>
        <div class="text-h5 text-bold text-primary flex items-center">
          Informe de Bajas - {{ report.agencia?.nombre }} ({{ getMesNombre(report.mes) }} {{ report.anio }})
          <q-chip
            :color="report.estado === 'ABIERTO' ? 'green-1' : (report.estado === 'PENDIENTE' ? 'orange-1' : 'grey-2')"
            :text-color="report.estado === 'ABIERTO' ? 'green-9' : (report.estado === 'PENDIENTE' ? 'orange-9' : 'grey-8')"
            :icon="report.estado === 'ABIERTO' ? 'lock_open' : (report.estado === 'PENDIENTE' ? 'send' : 'lock')"
            class="q-ml-md text-weight-bold"
            dense
          >
            {{ report.estado }}
          </q-chip>
        </div>
      </div>
      <div class="col-auto q-gutter-sm">
        <q-btn
          v-if="report.estado === 'ABIERTO' && report.items && report.items.length > 0"
          color="primary"
          icon="send"
          label="Enviar Informe"
          @click="confirmSend"
          no-caps
          unelevated
        />
        <template v-if="report.estado === 'PENDIENTE' && String($store.user?.id) === '1'">
          <q-btn
            color="positive"
            icon="check_circle"
            label="Marcar como Revisado"
            @click="confirmClose"
            no-caps
            unelevated
          />
          <q-btn
            color="orange-8"
            icon="undo"
            label="Reabrir Informe"
            @click="confirmReopen"
            no-caps
            unelevated
          />
        </template>
        <template v-if="report.estado === 'REVISADO'">
          <q-btn
            color="red-7"
            icon="picture_as_pdf"
            label="PDF"
            @click="generatePDF"
            no-caps
            unelevated
          />
          <q-btn
            color="green-7"
            icon="fa-brands fa-whatsapp"
            label="WhatsApp"
            @click="shareWhatsApp"
            no-caps
            unelevated
          />
        </template>
      </div>
    </div>

    <!-- Sección de Búsqueda de Productos -->
    <div v-if="report && report.estado === 'ABIERTO'" class="row q-col-gutter-md q-mb-lg">
      <div class="col-12">
        <q-card flat bordered class="shadow-1 bg-blue-grey-1">
          <q-card-section class="q-pb-none">
            <div class="text-subtitle1 text-bold flex items-center text-primary">
              <q-icon name="search" class="q-mr-sm" />
              Buscador de Inventario (Todas las agencias)
            </div>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-12 col-sm-4">
                <q-select
                  v-model="searchAgenciaId"
                  :options="agenciasOptions"
                  label="Filtrar por Agencia"
                  outlined
                  dense
                  emit-value
                  map-options
                  option-value="id"
                  option-label="nombre"
                  class="bg-white"
                  @update:model-value="searchInventory"
                  :disable="String($store.user?.id) !== '1'"
                >
                  <template v-slot:prepend>
                    <q-icon name="apartment" />
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-8">
                <q-input
                  v-model="search"
                  placeholder="Producto, Lote o Factura..."
                  outlined
                  dense
                  debounce="500"
                  clearable
                  class="bg-white"
                  @update:model-value="onSearchUpdate"
                >
                  <template v-slot:prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
            </div>
            <q-table
              :rows="inventory"
              :columns="columnsInventory"
              row-key="id"
              dense
              flat
              bordered
              :loading="loadingInventory"
              class="bg-white"
              :rows-per-page-options="[10, 20, 50]"
            >
              <template v-slot:body-cell-vence="props">
                <q-td :props="props">
                  <q-chip
                    :color="getExpiryColor(props.row.dateExpiry)"
                    text-color="white"
                    dense
                    class="text-weight-bold"
                    style="min-width: 95px; justify-content: center;"
                  >
                    <q-icon :name="getExpiryIcon(props.row.dateExpiry)" size="xs" class="q-mr-xs" />
                    {{ props.row.dateExpiry }}
                  </q-chip>
                </q-td>
              </template>
              <template v-slot:body-cell-user="props">
                <q-td :props="props">
                  <div class="ellipsis" style="max-width: 100px">
                    {{ props.row.user?.name || 'N/A' }}
                    <q-tooltip v-if="props.row.user?.name">
                      {{ props.row.user.name }}
                    </q-tooltip>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-proveedor="props">
                <q-td :props="props">
                  <div class="ellipsis" style="max-width: 120px">
                    {{ props.row.proveedor?.nombreRazonSocial || 'N/A' }}
                    <q-tooltip v-if="props.row.proveedor?.nombreRazonSocial">
                      {{ props.row.proveedor.nombreRazonSocial }}
                    </q-tooltip>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-actions="props">
                <q-td :props="props" class="text-right">
                  <q-btn color="primary" label="Seleccionar para Baja" dense size="sm" @click="preparaBaja(props.row)" no-caps />
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Detalle del Informe -->
    <q-card flat bordered class="shadow-1">
      <q-card-section class="bg-grey-1 q-py-sm">
        <div class="text-subtitle1 text-bold flex items-center">
          <q-icon name="list" class="q-mr-sm" />
          Detalle del Informe (Productos Retirados)
        </div>
      </q-card-section>
      <q-table
        :rows="report ? report.items : []"
        :columns="columnsDetail"
        row-key="id"
        flat
        :rows-per-page-options="[0]"
        :loading="loading"
      >
        <template v-slot:body-cell-vence="props">
          <q-td :props="props">
            <q-chip
              :color="getExpiryColor(props.row.buy?.dateExpiry)"
              text-color="white"
              dense
              class="text-weight-bold"
              style="min-width: 95px; justify-content: center;"
            >
              <q-icon :name="getExpiryIcon(props.row.buy?.dateExpiry)" size="xs" class="q-mr-xs" />
              {{ props.row.buy?.dateExpiry || 'N/A' }}
            </q-chip>
          </q-td>
        </template>
        <template v-slot:body-cell-user="props">
          <q-td :props="props">
            <div class="ellipsis" style="max-width: 100px">
              {{ props.row.buy?.user?.name || 'N/A' }}
              <q-tooltip v-if="props.row.buy?.user?.name">
                {{ props.row.buy.user.name }}
              </q-tooltip>
            </div>
          </q-td>
        </template>
        <template v-slot:body-cell-proveedor="props">
          <q-td :props="props">
            <div class="ellipsis" style="max-width: 120px">
              {{ props.row.buy?.proveedor?.nombreRazonSocial || 'N/A' }}
              <q-tooltip v-if="props.row.buy?.proveedor?.nombreRazonSocial">
                {{ props.row.buy.proveedor.nombreRazonSocial }}
              </q-tooltip>
            </div>
          </q-td>
        </template>
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
              <div class="text-caption text-grey-8">
                <q-icon name="person" /> {{ report.user?.name || 'N/A' }} | <q-icon name="schedule" /> {{ formatDate(props.row.created_at) }}
              </div>
            </div>
            <div style="white-space: pre-wrap;">{{ props.row.descripcion }}</div>
            <div v-if="props.row.admin_descripcion" class="q-mt-xs q-pa-xs bg-grey-2 rounded-borders">
              <div class="text-caption text-bold text-primary">Obs. Admin:</div>
              <div style="white-space: pre-wrap;" class="text-caption">{{ props.row.admin_descripcion }}</div>
            </div>
            <div v-if="props.row.cantidad_original !== null" class="text-caption text-orange-9 text-bold">
              [Modificado: Cant. Original era {{ props.row.cantidad_original }}]
            </div>
          </q-td>
        </template>
        <template v-slot:body-cell-actions="props">
          <q-td :props="props" class="text-right q-gutter-x-sm">
            <q-btn
              v-if="String($store.user?.id) === '1'"
              flat
              round
              dense
              color="primary"
              icon="edit"
              size="sm"
              @click="preparaEditItem(props.row)"
            >
              <q-tooltip>Modificar ítem (Admin)</q-tooltip>
            </q-btn>
            <q-btn
              v-if="report.estado === 'ABIERTO'"
              flat
              round
              dense
              color="negative"
              icon="remove_circle_outline"
              size="sm"
              @click="removeItem(props.row)"
            >
              <q-tooltip>Quitar del informe</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
      <q-card-section v-if="report && report.items && report.items.length === 0" class="text-center q-pa-lg text-grey">
        No hay productos agregados a este informe todavía.
      </q-card-section>
    </q-card>

    <!-- Dialogo para procesar baja -->
    <q-dialog v-model="showBajaDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center bg-primary text-white">
          <div class="text-h6">Procesar Baja de Producto</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md" v-if="selectedBuy">
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-select
                v-model="bajaForm.tipo"
                :options="tiposBaja"
                label="Motivo de la Baja *"
                outlined
                dense
                emit-value
                map-options
                :rules="[val => !!val || 'Requerido']"
              />
            </div>
            <div class="col-12">
              <q-input label="Producto" :model-value="selectedBuy.product.nombre" readonly outlined dense bg-color="grey-2" />
            </div>
            <div class="col-6">
              <q-input label="Lote" :model-value="selectedBuy.lote" readonly outlined dense />
            </div>
            <div class="col-6">
              <q-select
                v-model="bajaForm.agencia_id"
                :options="agenciasOptions"
                label="Sucursal *"
                outlined
                dense
                emit-value
                map-options
                option-value="id"
                option-label="nombre"
                :rules="[val => val !== null || 'Requerido']"
                @update:model-value="updateSelectedStock"
                :disable="String($store.user?.id) !== '1'"
              />
            </div>
            <div class="col-6">
              <q-input label="Stock Sistema" :model-value="selectedStock" readonly outlined dense bg-color="green-1" />
            </div>

            <!-- Caso Conteo Fisico -->
            <template v-if="bajaForm.tipo === 'CONTEO FISICO'">
              <div class="col-6">
                <q-input
                  label="Conteo Físico *"
                  v-model.number="bajaForm.fisico"
                  type="number"
                  outlined
                  dense
                  autofocus
                  :rules="[val => val !== null && val >= 0 || 'Inválido']"
                  @update:model-value="calculateFromFisico"
                />
              </div>
              <div class="col-6">
                <q-input
                  :label="bajaForm.cantidad > 0 ? 'Cantidad a agregar' : 'Cantidad a retirar'"
                  :model-value="Math.abs(bajaForm.cantidad)"
                  readonly
                  outlined
                  dense
                  :bg-color="bajaForm.cantidad > 0 ? 'blue-1' : (bajaForm.cantidad < 0 ? 'orange-1' : 'grey-2')"
                >
                  <template v-slot:append>
                    <q-icon :name="bajaForm.cantidad > 0 ? 'add' : 'remove'" />
                  </template>
                </q-input>
              </div>
            </template>

            <!-- Caso Normal -->
            <template v-else>
              <div class="col-12">
                <q-input
                  label="Cantidad a retirar *"
                  v-model.number="bajaForm.cantidad"
                  type="number"
                  outlined
                  dense
                  autofocus
                  :rules="[val => !!val && val > 0 || 'Inválido', val => val <= selectedStock || 'No hay suficiente stock']"
                />
              </div>
            </template>

            <div class="col-12">
              <q-input
                label="Descripción / Observaciones"
                v-model="bajaForm.descripcion"
                type="textarea"
                outlined
                dense
                rows="2"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn label="Cancelar" color="grey" flat v-close-popup />
          <q-btn label="Confirmar Baja" color="primary" unelevated @click="addItem" :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo para EDITAR ítem (Admin) -->
    <q-dialog v-model="showEditItemDialog" persistent>
      <q-card style="min-width: 450px">
        <q-card-section class="row items-center bg-orange-8 text-white">
          <div class="text-h6">Modificar Ítem (Admin)</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md" v-if="editingItem">
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-select
                v-model="editItemForm.tipo"
                :options="tiposBaja"
                label="Motivo de la Baja *"
                outlined
                dense
                emit-value
                map-options
              />
            </div>
            <div class="col-12 text-subtitle2 text-bold text-primary">
              {{ editingItem.product?.nombre }} (Lote: {{ editingItem.buy?.lote }})
            </div>
            <div class="col-12">
              <q-select
                v-model="editItemForm.agencia_id"
                :options="agenciasOptions"
                label="Sucursal de Retiro *"
                outlined
                dense
                emit-value
                map-options
                option-value="id"
                option-label="nombre"
                :rules="[val => val !== null || 'Requerido']"
                @update:model-value="updateEditStock"
              />
            </div>

            <!-- Caso Conteo Fisico -->
            <template v-if="editItemForm.tipo === 'CONTEO FISICO'">
              <div class="col-6">
                <q-input
                  label="Stock Sistema"
                  v-model.number="editItemForm.stock_sistema"
                  type="number"
                  outlined
                  dense
                  @update:model-value="calculateEditFromFisico"
                />
              </div>
              <div class="col-6">
                <q-input
                  label="Conteo Físico"
                  v-model.number="editItemForm.conteo_fisico"
                  type="number"
                  outlined
                  dense
                  @update:model-value="calculateEditFromFisico"
                />
              </div>
            </template>

            <div class="col-12">
              <q-input
                :label="editItemForm.tipo === 'CONTEO FISICO' ? 'Diferencia calculada' : 'Cantidad a retirar *'"
                v-model.number="editItemForm.cantidad"
                type="number"
                outlined
                dense
                :rules="[val => val !== null || 'Inválido']"
                :readonly="editItemForm.tipo === 'CONTEO FISICO'"
                :bg-color="editItemForm.tipo === 'CONTEO FISICO' ? 'grey-2' : ''"
              >
                <template v-slot:append>
                  <q-chip
                    v-if="editItemForm.cantidad !== 0"
                    dense
                    :color="editItemForm.cantidad > 0 ? 'positive' : 'negative'"
                    text-color="white"
                    class="text-bold"
                  >
                    {{ editItemForm.cantidad > 0 ? '+' : '-' }}{{ Math.abs(editItemForm.cantidad) }}
                  </q-chip>
                </template>
              </q-input>
              <div class="text-caption text-grey">
                <span v-if="editItemForm.cantidad > 0" class="text-positive text-bold">Se sumarán {{ Math.abs(editItemForm.cantidad) }} unidades al stock.</span>
                <span v-else-if="editItemForm.cantidad < 0" class="text-negative text-bold">Se restarán {{ Math.abs(editItemForm.cantidad) }} unidades del stock.</span>
                <span v-else>No hay cambio en el stock.</span>
              </div>
            </div>

            <div class="col-12">
              <q-input
                label="Observaciones del Administrador"
                v-model="editItemForm.admin_descripcion"
                type="textarea"
                outlined
                dense
                rows="3"
                placeholder="Explique el motivo del cambio..."
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn label="Cancelar" color="grey" flat v-close-popup />
          <q-btn label="Guardar Cambios" color="orange-9" unelevated @click="updateItem" :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <div id="printArea" class="hidden"></div>
  </q-page>
</template>

<script>
import moment from 'moment'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

export default {
  data () {
    return {
      id: this.$route.params.id,
      report: null,
      loading: false,
      search: '',
      searchAgenciaId: null,
      agenciasOptions: [],

      inventory: [],
      loadingInventory: false,
      columnsInventory: [
        { name: 'lote', align: 'left', label: 'Lote', field: 'lote', sortable: true },
        { name: 'agencia', align: 'left', label: 'Agencia', field: row => row.agencia?.nombre || 'Almacen', sortable: true },
        { name: 'producto', align: 'left', label: 'Producto', field: row => row.product.nombre, sortable: true },
        { name: 'vence', align: 'center', label: 'Vence', field: 'dateExpiry', sortable: true },
        { name: 'quantity', align: 'center', label: 'Stock', field: 'quantity', sortable: true, style: 'width: 70px', headerStyle: 'width: 70px' },
        { name: 'price', align: 'right', label: 'Precio', field: 'price', sortable: true },
        { name: 'total', align: 'right', label: 'Total', field: 'total', sortable: true },
        { name: 'compra', align: 'left', label: 'Fecha Compra', field: row => row.date + ' ' + row.time, sortable: true },
        { name: 'user', align: 'left', label: 'Usuario', field: row => row.user?.name || 'N/A' },
        { name: 'proveedor', align: 'left', label: 'Proveedor', field: row => row.proveedor?.nombreRazonSocial || 'N/A' },
        { name: 'actions', align: 'right', label: 'Acción', field: 'actions' }
      ],

      columnsDetail: [
        { name: 'lote', align: 'left', label: 'Lote', field: row => row.buy?.lote || 'N/A' },
        { name: 'agencia', align: 'left', label: 'Agencia Retiro', field: row => row.agencia?.nombre || (row.buy?.agencia?.nombre || 'Almacen') },
        { name: 'producto', align: 'left', label: 'Producto', field: row => row.product.nombre, sortable: true },
        { name: 'cantidad', align: 'center', label: 'Cantidad', field: 'cantidad', sortable: true },
        { name: 'tipo', align: 'center', label: 'Motivo', field: 'tipo', sortable: true },
        { name: 'vence', align: 'left', label: 'Vence', field: row => row.buy?.dateExpiry || 'N/A' },
        { name: 'proveedor', align: 'left', label: 'Proveedor', field: row => row.buy?.proveedor?.nombreRazonSocial || 'N/A' },
        { name: 'descripcion', align: 'left', label: 'Descripción', field: 'descripcion' },
        { name: 'actions', align: 'right', label: 'Acciones', field: 'actions' }
      ],

      showBajaDialog: false,
      showEditItemDialog: false,
      selectedBuy: null,
      editingItem: null,
      selectedStock: 0,
      bajaForm: {
        agencia_id: null,
        cantidad: 0,
        fisico: 0,
        tipo: 'VENCIMIENTO',
        descripcion: ''
      },
      editItemForm: {
        agencia_id: null,
        cantidad: 0,
        tipo: 'VENCIMIENTO',
        admin_descripcion: '',
        stock_sistema: 0,
        conteo_fisico: 0
      },

      meses: [
        { label: 'Enero', value: 1 }, { label: 'Febrero', value: 2 }, { label: 'Marzo', value: 3 },
        { label: 'Abril', value: 4 }, { label: 'Mayo', value: 5 }, { label: 'Junio', value: 6 },
        { label: 'Julio', value: 7 }, { label: 'Agosto', value: 8 }, { label: 'Septiembre', value: 9 },
        { label: 'Octubre', value: 10 }, { label: 'Noviembre', value: 11 }, { label: 'Diciembre', value: 12 }
      ],
      tiposBaja: [
        { label: 'VENCIMIENTO', value: 'VENCIMIENTO' },
        { label: 'DEVOLUCION A PROVEEDOR', value: 'DEVOLUCION A PROVEEDOR' },
        { label: 'CONTEO FISICO', value: 'CONTEO FISICO' },
        { label: 'PRODUCTO DAÑADO', value: 'PRODUCTO DAÑADO' },
        { label: 'OTRO', value: 'OTRO' }
      ]
    }
  },
  created () {
    this.getAgencias()
    this.getReport()
  },
  methods: {
    getAgencias () {
      this.$axios.get('agencias').then(res => {
        const otrasAgencias = res.data.filter(a => a.id !== 1)
        this.agenciasOptions = [
          { id: null, nombre: 'Todas las agencias' },
          { id: 1, nombre: 'Casa Matriz Velasco + Almacén' },
          ...otrasAgencias
        ]

        if (this.$store && this.$store.user) {
          if (String(this.$store.user.id) !== '1') {
            this.searchAgenciaId = this.$store.user.agencia_id
            this.agenciasOptions = this.agenciasOptions.filter(a => a.id === this.$store.user.agencia_id)
          }
        }
      })
    },
    getReport () {
      this.loading = true
      this.$axios.get(`withdrawal-reports/${this.id}`).then(res => {
        this.report = res.data
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: 'Error al cargar informe' })
        this.$router.push('/informesBajas')
      }).finally(() => {
        this.loading = false
      })
    },
    getMesNombre (num) {
      const m = this.meses.find(m => m.value === num)
      return m ? m.label : num
    },
    onSearchUpdate (val) {
      // Mimic sales behavior: when search text changes, perform search
      this.searchInventory()
    },
    searchInventory () {
      if (this.search.length < 3 && this.searchAgenciaId === null) return
      this.loadingInventory = true
      this.$axios.get('withdrawal-reports/search-products', {
        params: {
          search: this.search,
          agencia_id: this.searchAgenciaId
        }
      }).then(res => {
        this.inventory = res.data.data
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: 'Error en la búsqueda' })
      }).finally(() => {
        this.loadingInventory = false
      })
    },
    preparaBaja (buy) {
      this.selectedBuy = buy
      let originAgenciaId = this.report?.agencia_id
      if (originAgenciaId === undefined || originAgenciaId === null) {
        originAgenciaId = (buy.agencia_id === null || buy.agencia_id === undefined) ? 0 : buy.agencia_id
      }
      this.selectedStock = this.getAgenciaStock(buy.product, originAgenciaId)

      let sugerido = 'OTRO'
      if (buy.dateExpiry) {
        const vence = moment(buy.dateExpiry)
        if (vence.isBefore(moment())) {
          sugerido = 'VENCIMIENTO'
        }
      }

      this.bajaForm = {
        agencia_id: originAgenciaId,
        cantidad: 1,
        fisico: this.selectedStock,
        tipo: sugerido,
        descripcion: ''
      }
      this.showBajaDialog = true
    },
    updateSelectedStock (val) {
      this.selectedStock = this.getAgenciaStock(this.selectedBuy.product, val)
      if (this.bajaForm.tipo === 'CONTEO FISICO') {
        this.calculateFromFisico(this.bajaForm.fisico)
      }
    },
    calculateFromFisico (val) {
      this.bajaForm.cantidad = (val || 0) - this.selectedStock
    },
    getAgenciaStock (product, agenciaId) {
      if (agenciaId === 0 || agenciaId === null) return product.cantidadAlmacen
      return product['cantidadSucursal' + agenciaId] || 0
    },
    formatDate (dateString) {
      if (!dateString) return 'N/A'
      return moment(dateString).format('DD/MM/YYYY HH:mm')
    },
    formatCantidad (row) {
      if (!row) return ''
      if (row.cantidad > 0) return `+${row.cantidad}`
      if (row.cantidad < 0) return `${row.cantidad}`
      return row.cantidad
    },
    addItem () {
      if (this.bajaForm.tipo === 'CONTEO FISICO') {
        if (this.bajaForm.cantidad === 0) {
          this.$q.notify({ color: 'warning', message: 'No hay diferencia en el conteo físico' })
          return
        }
      } else {
        // Normal withdrawal: we ensure input is positive but store it as negative
        if (this.bajaForm.cantidad <= 0 || this.bajaForm.cantidad > this.selectedStock) {
          this.$q.notify({ color: 'negative', message: 'Cantidad no válida o insuficiente stock' })
          return
        }
      }

      this.loading = true
      // CONVERT TO NEGATIVE IF IT'S A NORMAL WITHDRAWAL
      const cantidadEnviar = this.bajaForm.tipo === 'CONTEO FISICO' ? this.bajaForm.cantidad : -Math.abs(this.bajaForm.cantidad)

      this.$axios.post(`withdrawal-reports/${this.id}/items`, {
        buy_id: this.selectedBuy.id,
        product_id: this.selectedBuy.product_id,
        agencia_id: this.bajaForm.agencia_id,
        cantidad: cantidadEnviar,
        stock_sistema: this.bajaForm.tipo === 'CONTEO FISICO' ? this.selectedStock : null,
        conteo_fisico: this.bajaForm.tipo === 'CONTEO FISICO' ? this.bajaForm.fisico : null,
        tipo: this.bajaForm.tipo,
        descripcion: this.bajaForm.descripcion
      }).then(() => {
        this.$q.notify({ color: 'positive', message: 'Producto agregado al informe', icon: 'check' })
        this.showBajaDialog = false
        this.getReport()
        if (this.search || this.searchAgenciaId !== null) this.searchInventory()
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al agregar producto' })
      }).finally(() => {
        this.loading = false
      })
    },
    removeItem (item) {
      this.$q.dialog({
        title: 'Quitar Producto',
        message: '¿Estás seguro de quitar este producto del informe? El stock será restaurado.',
        cancel: true,
        ok: { color: 'negative', flat: true }
      }).onOk(() => {
        this.loading = true
        this.$axios.delete(`withdrawal-reports/${this.id}/items/${item.id}`)
          .then(() => {
            this.$q.notify({ color: 'positive', message: 'Producto quitado del informe' })
            this.getReport()
            if (this.search || this.searchAgenciaId !== null) this.searchInventory()
          })
          .catch(err => {
            console.error(err)
            this.$q.notify({ color: 'negative', message: 'Error al quitar producto' })
          })
          .finally(() => {
            this.loading = false
          })
      })
    },
    confirmClose () {
      this.$q.dialog({
        title: 'Confirmar Revisión',
        message: 'Al marcar como revisado se descontará el stock de los productos. ¿Deseas continuar?',
        cancel: true,
        ok: { color: 'positive', label: 'Marcar como Revisado', unelevated: true }
      }).onOk(() => {
        this.loading = true
        this.$axios.post(`withdrawal-reports/${this.id}/close`)
          .then(() => {
            this.$q.notify({ color: 'positive', message: 'Informe revisado correctamente', icon: 'check' })
            this.getReport()
          })
          .catch(err => {
            this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al cerrar informe' })
          })
          .finally(() => {
            this.loading = false
          })
      })
    },
    confirmSend () {
      this.$q.dialog({
        title: 'Enviar Informe',
        message: '¿Estás seguro de enviar este informe para revisión? Ya no podrás modificarlo.',
        cancel: true,
        ok: { color: 'primary', label: 'Enviar Informe', unelevated: true }
      }).onOk(() => {
        this.sendReport()
      })
    },
    sendReport () {
      this.loading = true
      this.$axios.post(`withdrawal-reports/${this.id}/send`)
        .then(() => {
          this.$q.notify({ color: 'positive', message: 'Informe enviado correctamente', icon: 'check' })
          this.getReport()
        })
        .catch(err => {
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al enviar informe' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    confirmReopen () {
      this.$q.dialog({
        title: 'Reabrir Informe',
        message: '¿Estás seguro de reabrir este informe? El usuario podrá modificarlo nuevamente.',
        cancel: true,
        ok: { color: 'orange-8', label: 'Reabrir Informe', unelevated: true }
      }).onOk(() => {
        this.reopenReport()
      })
    },
    reopenReport () {
      this.loading = true
      this.$axios.post(`withdrawal-reports/${this.id}/reopen`)
        .then(() => {
          this.$q.notify({ color: 'positive', message: 'Informe reabierto correctamente', icon: 'check' })
          this.getReport()
        })
        .catch(err => {
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al reabrir informe' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    preparaEditItem (item) {
      this.editingItem = item
      this.editItemForm = {
        agencia_id: item.agencia_id,
        cantidad: item.cantidad,
        tipo: item.tipo,
        admin_descripcion: item.admin_descripcion || '',
        stock_sistema: item.stock_sistema || 0,
        conteo_fisico: item.conteo_fisico || 0
      }
      this.selectedStock = this.getAgenciaStock(item.product, item.agencia_id)
      this.showEditItemDialog = true
    },
    calculateEditFromFisico (val) {
      this.editItemForm.cantidad = (this.editItemForm.conteo_fisico || 0) - (this.editItemForm.stock_sistema || 0)
    },
    updateEditStock (val) {
      this.selectedStock = this.getAgenciaStock(this.editingItem.product, val)
    },
    updateItem () {
      this.loading = true
      const data = { ...this.editItemForm }
      if (data.tipo !== 'CONTEO FISICO') {
        data.cantidad = -Math.abs(data.cantidad)
      }

      this.$axios.put(`withdrawal-reports/${this.id}/items/${this.editingItem.id}`, data)
        .then(() => {
          this.$q.notify({ color: 'positive', message: 'Ítem actualizado correctamente' })
          this.showEditItemDialog = false
          this.getReport()
        })
        .catch(err => {
          console.error(err)
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al actualizar ítem' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    generatePDF () {
      const doc = jsPDF()
      const pW = doc.internal.pageSize.getWidth()
      const pH = doc.internal.pageSize.getHeight()

      // Función para dibujar marca de agua y pie de página en cada hoja
      const drawCommonElements = (data) => {
        // --- MARCA DE AGUA "REVISADO" ---
        doc.saveGraphicsState()
        doc.setGState(new doc.GState({ opacity: 0.4 }))
        doc.setTextColor(46, 204, 113)
        doc.setFontSize(120)
        doc.setFont('helvetica', 'bold')
        doc.text('REVISADO', pW * 0.7, pH * 0.75, { align: 'center', angle: 45 })
        doc.restoreGraphicsState()

        // --- PIE DE PÁGINA EMPRESARIAL ---
        doc.setFontSize(8)
        doc.setTextColor(150)
        const footerText = 'Sistema Santidad Divina - Informe de Gestión Farmacéutica'
        doc.text(footerText, pW / 2, pH - 10, { align: 'center' })
        doc.text(`Página ${data.pageNumber}`, pW - 20, pH - 10, { align: 'right' })
        doc.line(14, pH - 15, pW - 14, pH - 15) // Línea decorativa
      }

      // --- CABECERA (Solo primera página) ---
      doc.setFillColor(41, 128, 185)
      doc.rect(0, 0, pW, 40, 'F')
      doc.setTextColor(255, 255, 255)
      doc.setFontSize(22)
      doc.setFont('helvetica', 'bold')
      doc.text('INFORME DE BAJAS', 14, 25)
      doc.setFontSize(12)
      doc.setFont('helvetica', 'normal')
      doc.text(`Informe Nro: #${this.report.id}`, pW - 14, 25, { align: 'right' })

      // --- INFORMACIÓN DEL INFORME ---
      doc.setTextColor(40, 40, 40)
      doc.setFillColor(248, 248, 248)
      doc.setDrawColor(220, 220, 220)
      doc.roundedRect(14, 45, pW - 28, 25, 2, 2, 'FD')
      doc.setFontSize(10); doc.setFont('helvetica', 'bold'); doc.text('DATOS DEL INFORME', 18, 52)
      doc.setFontSize(9); doc.text('Agencia:', 18, 59); doc.text('Periodo:', 18, 65)
      doc.setFont('helvetica', 'normal'); doc.text(`${this.report.agencia?.nombre}`, 35, 59)
      doc.text(`${this.getMesNombre(this.report.mes)} ${this.report.anio}`, 35, 65)
      doc.setFont('helvetica', 'bold'); doc.text('Responsable:', pW / 2, 59); doc.text('Fecha Emisión:', pW / 2, 65)
      doc.setFont('helvetica', 'normal'); doc.text(`${this.report.user?.name}`, (pW / 2) + 25, 59)
      doc.text(`${moment().format('DD/MM/YYYY HH:mm')}`, (pW / 2) + 25, 65)

      // --- TABLA ---
      const tableData = this.report.items.map(item => {
        let pdfDesc = item.descripcion || '-'
        if (item.tipo === 'CONTEO FISICO') {
          const dateStr = this.formatDate(item.created_at)
          const userStr = this.report.user?.name || 'N/A'
          pdfDesc = `[Sis: ${item.stock_sistema} | Físico: ${item.conteo_fisico}]\nReg: ${userStr} el ${dateStr}\n${pdfDesc}`
        }

        if (item.admin_descripcion) {
          pdfDesc += `\n[Obs Admin: ${item.admin_descripcion}]`
        }

        if (item.cantidad_original !== null && item.cantidad_original !== item.cantidad) {
          const originalFormatted = this.formatCantidad({ cantidad: item.cantidad_original })
          pdfDesc += `\n[CANTIDAD ORIGINAL: ${originalFormatted}]`
        }

        return [
          item.buy?.lote || 'N/A',
          item.agencia?.nombre || (item.buy?.agencia?.nombre || 'Almacen'),
          item.product?.nombre,
          {
            content: this.formatCantidad(item),
            styles: { fontStyle: 'bold', halign: 'center', textColor: item.cantidad > 0 ? [46, 204, 113] : [231, 76, 60] }
          },
          item.buy?.dateExpiry || 'N/A',
          item.tipo,
          pdfDesc
        ]
      })

      autoTable(doc, {
        startY: 75,
        head: [['LOTE', 'AGENCIA RETIRO', 'PRODUCTO', 'CANT.', 'VENCE', 'MOTIVO', 'DESCRIPCIÓN']],
        body: tableData,
        theme: 'grid', // Cambiado a grid para tener líneas
        headStyles: {
          fillColor: [41, 128, 185],
          fontSize: 8,
          fontStyle: 'bold',
          halign: 'center',
          lineColor: [255, 255, 255],
          lineWidth: 0.1
        },
        bodyStyles: {
          fontSize: 7.5,
          lineColor: [220, 220, 220], // Líneas plomas elegantes
          lineWidth: 0.1
        },
        columnStyles: { 0: { cellWidth: 20 }, 1: { cellWidth: 25 }, 3: { cellWidth: 15 }, 4: { cellWidth: 20 } },
        didDrawPage: drawCommonElements,
        margin: { top: 15, bottom: 25 } // Margen superior reducido para páginas siguientes
      })

      // --- OBSERVACIONES Y FIRMA (Al final de la tabla) ---
      let finalY = doc.lastAutoTable?.finalY || 80
      // Si no hay espacio para la firma, añadir página nueva
      if (finalY > pH - 60) {
        doc.addPage()
        finalY = 30
      }

      doc.setFontSize(9)
      doc.setFont('helvetica', 'bold')
      doc.text('OBSERVACIONES ADICIONALES:', 14, finalY + 15)
      doc.setFont('helvetica', 'normal')
      doc.setFontSize(8)
      doc.text(doc.splitTextToSize(this.report.observaciones || 'Sin observaciones.', pW - 28), 14, finalY + 22)

      const sigY = finalY + 50
      doc.setDrawColor(150)
      doc.line(pW / 2 - 30, sigY, pW / 2 + 30, sigY)
      doc.text('Firma Responsable', pW / 2, sigY + 5, { align: 'center' })

      doc.save(`Informe_Bajas_${this.report.id}.pdf`)
    },
    shareWhatsApp () {
      this.generatePDF()
      const text = `*INFORME DE BAJAS #${this.report.id}*\n📍 *Agencia:* ${this.report.agencia?.nombre}\n📅 *Periodo:* ${this.getMesNombre(this.report.mes)} ${this.report.anio}\n👤 *Responsable:* ${this.report.user?.name}`
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank')
    },
    getExpiryColor (date) {
      if (!date) return 'grey-7'
      const expiry = moment(date)
      if (expiry.isBefore(moment())) return 'red-9'
      if (expiry.diff(moment(), 'months') < 3) return 'orange-9'
      return 'green-8'
    },
    getExpiryIcon (date) {
      if (!date) return 'help_outline'
      const expiry = moment(date)
      if (expiry.isBefore(moment())) return 'report_gmailerrorred'
      if (expiry.diff(moment(), 'months') < 3) return 'warning_amber'
      return 'check_circle_outline'
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
    }
  }
}
</script>

<style scoped>
@media print {
  .q-header, .q-drawer, .q-btn, .q-tabs, .q-tab-panels, .q-breadcrumbs {
    display: none !important;
  }
}
</style>
