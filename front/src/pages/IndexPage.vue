<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-6 col-md-2 bg-white">
        <q-input dense outlined v-model="dateIni" label="Fecha inicial" type="datetime-local" required />
      </div>
      <div class="col-6 col-md-2 bg-white">
        <q-input dense outlined v-model="dateFin" label="Fecha final" type="datetime-local" required />
      </div>
      <div class="col-12 col-md-2 bg-white">
        <q-select dense outlined v-model="agencia" label="Agencia" :options="agencias" hint="Agencia de la venta"
                  emit-value map-options option-value="id" option-label="nombre" :disable="this.$store.user.id!=1" />
        <!--        <pre>{{agencias}}</pre>-->
      </div>
      <div class="col-12 col-md-2 bg-white">
        <q-select dense outlined v-model="user" label="Usuario" :options="users" hint="Usuario de la venta"
                  emit-value map-options option-value="id" option-label="name" />
        <!--        <pre>{{users}}</pre>-->
      </div>
      <div class="col-6 col-md-1 text-center">
        <q-btn icon="refresh" dense size="sm" no-caps @click="salesGet" label="Actualizar" color="primary">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>
      <div class="col-12 col-md-1 text-center">
        <q-btn color="black" no-caps flat icon="o_file_download" size="sm">
          <div class="q-page-xs subrayado">  Reportes</div>
          <q-menu>
            <q-list>
              <q-item clickable v-close-popup @click="reportTotal('')">
                <q-item-section>Reporte de ventas</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="reportTotal('Ingreso')">
                <q-item-section>Reporte de ingresos</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="reportTotal('Egreso')">
                <q-item-section>Reporte de egresos</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </div>
      <div class="col-12 col-md-2 text-right">
        <q-btn :loading="loading" color="green-4" size="sm" dense rounded no-caps icon="add_circle_outline" label="Nuevo venta" to="/sale">
          <q-tooltip>Crear nueva venta</q-tooltip>
        </q-btn>
        <q-btn :loading="loading" color="red-4" size="sm" dense rounded no-caps icon="remove_circle_outline" label="Nuevo gasto" @click="saleAddGasto" v-if="this.$store.user.id==1">
          <q-tooltip>Crear nuevo gasto</q-tooltip>
        </q-btn>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
        <q-card class="" flat bordered>
          <q-tooltip anchor="top middle" self="bottom middle">
            Total de ventas
          </q-tooltip>
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_stacked_bar_chart" size="22px" color="grey-7" class="bg-grey-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Total referencia</q-item-label>
                <q-item-label class="text-bold text-h6">{{totalGanancias}} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
        <q-card class="" flat bordered>
          <q-tooltip anchor="top middle" self="bottom middle">
            Total Ingresos
          </q-tooltip>
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_local_atm" size="22px" color="green-7" class="bg-green-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Ventas totales</q-item-label>
                <q-item-label :class="`text-bold text-h6 text-green`">{{totalIngresos}} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
        <q-card class="" flat bordered>
          <q-tooltip anchor="top middle" self="bottom middle">
            Total Gastos
          </q-tooltip>
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_local_atm" size="22px" color="red-7" class="bg-red-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Gastos totales</q-item-label>
                <q-item-label :class="`text-bold text-h6 text-red`">{{totalEgresos}} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12">
        <q-table :columns="columns" :rows="sales" dense :rows-per-page-options="[0]" :filter="filter" :loading="loading" wrap-cells
                 no-data-label="No hay ventas" no-results-label="No hay ventas"
        >
          <template v-slot:top-right>
            <q-input outlined v-model="filter" debounce="300" placeholder="Buscar" dense>
              <template v-slot:append>
                <q-btn flat round dense icon="search" />
              </template>
            </q-input>
          </template>
          <template v-slot:body="props">
            <q-tr :props="props">
              <q-td key="opcion" :props="props" auto-width >
<!--                <pre>{{props.row.modificado}}</pre>-->
                <q-chip v-if="props.row.modificado=='SI'" color="red" size="8px" text-color="white" dense flat label="S"/>
                <q-btn-dropdown dense icon="more_vert" align="right" label="Opciones" no-caps color="pink-9" size="10px" v-if="props.row.estado=='ACTIVO'">
                  <q-item clickable v-close-popup class="text-center">
                    <q-btn dense label="Anular" color="red-4" size="10px" class="full-width"
                           no-caps no-wrap icon="o_highlight_off" @click="saleDelete(props.row.id)">
                      <q-tooltip>Anular venta</q-tooltip>
                    </q-btn>
                  </q-item>
                  <q-item clickable v-close-popup class="text-center">
                    <q-btn dense label="Imprimir" color="green-4" size="10px" class="full-width"
                           no-caps no-wrap icon="print" @click="reimprimirNota(props.row)">
                      <q-tooltip>Imprimir nota</q-tooltip>
                    </q-btn>
                  </q-item>
                  <q-item clickable v-close-popup class="text-center">
                    <q-btn dense label="Modificar" color="orange-4" size="10px" class="full-width"
                           no-caps no-wrap icon="o_edit" @click="modificarNota(props.row)">
                      <q-tooltip>Modificar nota</q-tooltip>
                    </q-btn>
                  </q-item>
                </q-btn-dropdown>

                <div v-else>
                  <q-btn dense label="Anulado" color="grey-4" size="10px" no-caps no-wrap icon="o_highlight_off" />
                </div>
              </q-td>
              <q-td key="concepto" :props="props" class="">
                <div>
                  <q-btn icon="o_local_atm" size="15px" :color="`${props.row.tipoVenta=='Ingreso'?'green':'red'}-7`"
                         :class="`bg-${props.row.tipoVenta=='Ingreso'?'green':'red'}-2`" dense flat
                         style="padding: 0px; margin: 0px; border-radius: 0px;position: absolute;top: 5px;left: 0px;"/>
                  <div style="padding-left: 15px">
                    <div class="text-grey q-ml-xs" style="width: 400px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">{{ props.row.concepto }}</div>
                  </div>
                </div>
              </q-td>
              <q-td key="montoTotal" :props="props">
                <span class="text-grey">{{ props.row.montoTotal }} Bs</span>
              </q-td>
              <q-td key="agencia" :props="props" class="text-grey">
                <div class="text-caption" style="width: 100px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">{{ props.row.agencia?.nombre }}</div>
              </q-td>
              <q-td key="proveedorcliente" :props="props">
                <div class="text-grey" v-if="props.row.client">{{ props.row.client.nombreRazonSocial }}</div>
              </q-td>
              <q-td key="fechayhora" :props="props">
                <p>{{ $filters.dateDmYHis(props.row.fechaEmision) }}</p>
              </q-td>
              <q-td key="egresoingreso" :props="props">
                <q-chip :color="`${props.row.tipoVenta=='Ingreso'?'green':'red'}-5`" text-color="white" dense flat :label="props.row.tipoVenta"/>
              </q-td>
              <q-td key="user" :props="props">
                <p>{{ props.row.user?.name }}</p>
              </q-td>
            </q-tr>
          </template>
        </q-table>
      </div>
    </div>
    <q-dialog v-model="dialogSale" position="right" maximized>
      <q-card style="width: 500px; max-width: 80vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            Nuevo gasto
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form @submit="addSale">
            <div class="text-grey">Los campos marcados con asterisco (<span class="text-red">*</span>) son obligatorios</div>
            <q-input dense outlined v-model="sale.montoTotal" label="Monto total*" type="number" step="0.01"
                     :rules="[val => !!val || 'El monto total es requerido']" required hint="Monto total del gasto" />
            <q-input dense outlined v-model="sale.concepto" label="Concepto" hint="Concepto del gasto" />
            <q-select dense outlined v-model="sale.metodoPago" label="Metodo de pago"
                      :options="$metodoPago" hint="Metodo de pago del gasto" />
            <q-select proveedor dense outlined v-model="sale.client_id" label="Proveedor"
                      map-options emit-value option-value="id" option-label="nombreRazonSocial"
                      :options="proveedores" hint="Proveedor del gasto" >
              <template v-slot:after>
                <q-btn flat round dense icon="add_circle_outline" @click="proveedorAdd">
                  <q-tooltip>
                    Crear nuevo proveedor
                  </q-tooltip>
                </q-btn>
              </template>
            </q-select>
            <q-btn :loading="loading" color="green-4" class="full-width" no-caps icon="add_circle_outline" label="Guardar" type="submit" />
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogProveedor">
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            Nuevo proveedor
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-mt-none">
          <q-form @submit="addProveedor">
            <div class="text-grey">Los campos marcados con asterisco (<span class="text-red">*</span>) son obligatorios</div>
            <q-input dense outlined v-model="proveedor.nombreRazonSocial" label="Nombre o razon social*" type="text"
                     :rules="[val => !!val || 'El nombre o razon social es requerido']" required hint="Nombre o razon social del proveedor" />
            <q-input dense outlined v-model="proveedor.numeroDocumento" label="Numero de documento*" type="text"
                     :rules="[val => !!val || 'El numero de documento es requerido']" required hint="Numero de documento del proveedor" />
            <q-input dense outlined v-model="proveedor.telefono" label="Telefono" type="text" hint="Telefono del proveedor" />
            <q-btn :loading="loading" color="green-4" class="full-width" no-caps icon="add_circle_outline" label="Guardar" type="submit" />
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="saleDialogUpdate">
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            Modificar venta
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form @submit="saleModificar">
            <q-markup-table>
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Cantidad</th>
                  <th>Precio unitario</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
<!--              <pre>{{saleUpate.details}}</pre>-->
                <tr v-for="detail in saleUpate.details" :key="detail.id">
                  <td>
                    <div style="width: 200px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">
                      {{detail.descripcion}}
                    </div>
                  </td>
                  <td>
                    <q-input v-model="detail.cantidad" dense outlined type="number" style="width: 70px;"/>
                  </td>
                  <td>{{detail.precioUnitario}}</td>
                  <td>
                    {{detail.cantidad*detail.precioUnitario}}
                  </td>
                </tr>
              <tr>
                <td colspan="3" class="text-right">Total</td>
                <td>{{montoTotalUpdate}}</td>
              </tr>
              </tbody>
            </q-markup-table>
            <div class="row">
              <div class="col-12">
                <q-btn color="green-4" class="full-width" no-caps icon="add_circle_outline" label="Guardar" type="submit" dense :loading="loading" />
              </div>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <div id="myElement" class="hidden"></div>
  </q-page>
</template>

<script>
// import { date } from 'quasar'
import moment from 'moment'
import { Imprimir } from 'src/addons/Imprimir'

export default {
  data () {
    return {
      filter: '',
      saleDialogUpdate: false,
      saleUpate: {},
      dateIni: moment().startOf('day').format('YYYY-MM-DDTHH:mm'),
      dateFin: moment().add(2, 'minutes').format('YYYY-MM-DDTHH:mm'),
      loading: false,
      dialogSale: false,
      sale: {},
      sales: [],
      dialogProveedor: false,
      columns: [
        { name: 'opcion', label: 'Opcion', align: 'left', field: 'opcion' },
        { name: 'concepto', label: 'Concepto', align: 'left', field: 'concepto', sortable: true },
        { name: 'montoTotal', label: 'Monto total', align: 'left', field: 'montoTotal', sortable: true },
        { name: 'agencia', label: 'Agencia', align: 'left', field: 'agencia', sortable: true },
        // { name: 'metodoPago', label: 'Metodo de pago', align: 'left', field: 'metodoPago', sortable: true },
        { name: 'proveedorcliente', label: 'Proveedor / cliente', align: 'left', field: 'proveedor / cliente', sortable: true },
        { name: 'fechayhora', label: 'Fecha y hora', align: 'left', field: 'fechayhora', sortable: true },
        { name: 'egresoingreso', label: 'Egreso / ingreso', align: 'left', field: 'egreso / ingreso', sortable: true },
        { name: 'user', label: 'Usuario', align: 'left', field: (row) => row.user.name, sortable: true }
      ],
      proveedores: [],
      proveedor: { nombreRazonSocial: '', numeroDocumento: '', telefono: '', clienteProveedor: 'Proveedor' },
      agencias: [],
      users: [],
      agencia: '',
      user: ''
    }
  },
  created () {
    this.proveedores = [{ id: 0, nombreRazonSocial: 'Busca o selecciona un proveedor' }]
    const agencia = localStorage.getItem('agencia_id')
    this.agencia = parseInt(agencia)
    this.proveedorGet()
    this.salesGet()
    this.agenciasGet()
    this.usersGet()
  },
  methods: {
    saleModificar () {
      this.loading = true
      this.$axios.put('sales/' + this.saleUpate.id, this.saleUpate).then(res => {
        this.loading = false
        this.saleDialogUpdate = false
        this.salesGet()
        this.$alert.success('Venta modificada correctamente')
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    usersGet () {
      this.$axios.get('user').then(res => {
        this.users = res.data
      })
    },
    agenciasGet () {
      this.agencias = [{ id: '', nombre: 'TODO' }]
      this.$axios.get('agencias').then(res => {
        // this.agencias = res.data
        this.agencias = [...this.agencias, ...res.data]
      })
    },
    reportTotal (title) {
      this.loading = true
      this.$axios.get(`reportTotal${title}/${this.dateIni}/${this.dateFin}`).then(res => {
        this.loading = false
        this.$alert.success('Reporte descargado correctamente')
        this.$imprimir.reportTotal(res.data, title)
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    reimprimirNota (sale) {
      Imprimir.nota(sale).then(r => {
        // console.log(r)
      })
    },
    modificarNota (sale) {
      this.saleDialogUpdate = true
      this.saleUpate = sale
    },
    saleDelete (id) {
      this.$q.dialog({
        title: 'Anular venta',
        message: '¿Estas seguro de anular esta venta?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.loading = true
        this.$axios.get(`salesAnular/${id}`).then(res => {
          this.loading = false
          this.salesGet()
          this.$alert.success('Venta anulada correctamente')
        }).catch(err => {
          this.loading = false
          this.$alert.error(err.response.data.message)
        })
      }).onCancel(() => {
      }).onDismiss(() => {
      })
    },
    proveedorGet () {
      this.proveedores = [{ id: 0, nombreRazonSocial: 'Busca o selecciona un proveedor' }]
      this.$axios.get('providers').then(res => {
        this.proveedores = [...this.proveedores, ...res.data]
      })
    },
    addProveedor () {
      this.loading = true
      this.$axios.post('clients', this.proveedor).then(res => {
        this.loading = false
        this.dialogProveedor = false
        this.proveedorGet()
        this.proveedor = { nombreRazonSocial: '', numeroDocumento: '', telefono: '', clienteProveedor: 'Proveedor' }
        this.$alert.success('Proveedor agregado correctamente')
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    proveedorAdd () {
      this.dialogProveedor = true
      this.proveedor = { nombreRazonSocial: '', numeroDocumento: '', telefono: '', clienteProveedor: 'Proveedor' }
    },
    salesGet () {
      this.loading = true
      this.$axios.get(`betweenDates/${this.dateIni}/${this.dateFin}`, {
        params: {
          agencia: this.agencia,
          user: this.user
        }
      }).then(res => {
        this.loading = false
        this.sales = res.data
      })
    },
    addSale () {
      this.loading = true
      this.$axios.post('salesGasto', this.sale).then(res => {
        this.loading = false
        this.dialogSale = false
        this.salesGet()
        this.$alert.success('Gasto agregado correctamente')
        this.$imprimir.nota(res.data)
      })
      //     .catch(err => {
      //   this.loading = false
      //   this.$alert.error(err.response.data.message)
      // })
    },
    saleAddGasto () {
      this.sale = { client_id: 0, montoTotal: '', concepto: '', metodoPago: 'Efectivo' }
      this.dialogSale = true
    }
  },
  computed: {
    montoTotalUpdate () {
      let total = 0
      this.saleUpate.details.forEach(detail => {
        total += detail.cantidad * detail.precioUnitario
      })
      return total.toFixed(2)
    },
    totalIngresos () {
      const monto = this.sales.filter(sale => sale.tipoVenta === 'Ingreso' && sale.estado === 'ACTIVO').reduce((a, b) => parseFloat(a) + parseFloat(b.montoTotal), 0)
      console.log('monto', monto)
      return Math.round(monto * 100) / 100
    },
    totalEgresos () {
      const monto = this.sales.filter(sale => sale.tipoVenta === 'Egreso' && sale.estado === 'ACTIVO').reduce((a, b) => parseFloat(a) + parseFloat(b.montoTotal), 0)
      return Math.round(monto * 100) / 100
    },
    totalGanancias () {
      const monto = this.totalIngresos - this.totalEgresos
      return Math.round(monto * 100) / 100
    }
  }
}
</script>
<style lang="scss">
.super-small.q-field--dense {
  .q-field__control-container,
  .q-field__native {
    padding-top: 10px !important;
  }

  .q-field__control {
    height: 32px !important;
    min-height: 32px !important;
  }

  .q-field__marginal {
    height: 32px !important;
  }

  .q-field__label {
    top: 6px !important;
  }
}
</style>
