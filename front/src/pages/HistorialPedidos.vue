<template>
  <q-page class="bg-grey-2 q-pa-md">
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row items-center q-mb-md">
          <div class="col-12 col-md-6">
            <div class="text-h5 text-weight-bold">
              <q-icon name="history" size="sm" class="q-mr-sm" />
              Historial de Pedidos
            </div>
          </div>
          <div class="col-12 col-md-6 text-right">
            <q-btn
              icon="refresh"
              label="Actualizar"
              color="primary"
              @click="cargarPedidos"
              :loading="loading"
              unelevated
            />
          </div>
        </div>

        <q-separator class="q-mb-md" />

        <div class="text-subtitle2 text-grey-7 q-mb-sm">
          <q-icon name="filter_list" /> Filtros de búsqueda
        </div>

        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-6 col-md-2">
            <q-input
              v-model="filters.fecha_inicio"
              label="Desde"
              type="date"
              outlined
              dense
            />
          </div>

          <div class="col-12 col-sm-6 col-md-2">
            <q-input
              v-model="filters.fecha_fin"
              label="Hasta"
              type="date"
              outlined
              dense
            />
          </div>

          <div class="col-12 col-sm-6 col-md-2">
            <q-select
              v-model="filters.estado"
              :options="estadosOptions"
              label="Estado"
              outlined
              dense
              clearable
              emit-value
              map-options
            />
          </div>

          <div v-if="esAdmin" class="col-12 col-sm-6 col-md-2">
            <q-select
              v-model="filters.agencia_id"
              :options="agenciasFiltro"
              label="Sucursal"
              outlined
              dense
              clearable
              emit-value
              map-options
            />
          </div>

          <div class="col-12 col-sm-6 col-md-2">
            <q-select
              v-model="filters.proveedor_id"
              :options="proveedores"
              label="Proveedor"
              outlined
              dense
              clearable
              use-input
              input-debounce="0"
              @filter="filterProveedores"
              option-value="id"
              option-label="nombreRazonSocial"
              emit-value
              map-options
            >
              <template v-slot:no-option>
                <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
              </template>
            </q-select>
          </div>

          <div class="col-12 col-sm-6 col-md-2">
            <q-btn
              label="Buscar"
              color="primary"
              @click="aplicarFiltros"
              class="full-width"
              unelevated
              icon="search"
            />
          </div>
        </div>

        <div v-if="filtrosActivos.length > 0" class="row q-mt-md">
          <div class="col-12">
            <q-chip
              v-for="filtro in filtrosActivos"
              :key="filtro.key"
              removable
              @remove="limpiarFiltro(filtro.key)"
              color="primary"
              text-color="white"
              icon="filter_alt"
            >
              {{ filtro.label }}: {{ filtro.value }}
            </q-chip>
            <q-btn
              v-if="filtrosActivos.length > 1"
              label="Limpiar todos"
              size="sm"
              flat
              color="negative"
              @click="limpiarTodosFiltros"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-card>
      <q-card-section>
        <q-table
          :rows="pedidos"
          :columns="columns"
          row-key="id"
          :loading="loading"
          v-model:pagination="pagination"
          @request="onRequest"
          binary-state-sort
          flat
          bordered
          :rows-per-page-options="[10, 25, 50]"
        >
          <template v-slot:no-data>
            <div class="full-width row flex-center text-grey q-gutter-sm q-py-xl">
              <q-icon size="2em" name="inbox" />
              <span>No se encontraron pedidos</span>
            </div>
          </template>

          <template v-slot:body-cell-estado="props">
            <q-td :props="props">
              <q-badge
                :color="getColorEstado(props.value)"
                :label="getLabelEstado(props.value)"
                class="q-pa-sm"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-agencia="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-icon name="store" size="xs" class="q-mr-xs" />
                {{ props.row.agencia?.nombre || 'N/A' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-proveedor="props">
            <q-td :props="props">
              <div class="text-weight-medium text-blue-9" v-if="props.row.proveedor">
                {{ props.row.proveedor.nombreRazonSocial }}
              </div>
              <div class="text-grey-5" v-else>
                --
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-solicitante="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar size="24px" color="primary" text-color="white" class="q-mr-xs">
                  {{ getIniciales(props.row.user?.name) }}
                </q-avatar>
                {{ props.row.user?.name || 'N/A' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-total_productos="props">
            <q-td :props="props">
              <q-chip dense color="blue-1" text-color="blue-9">
                <q-icon name="inventory_2" size="xs" class="q-mr-xs" />
                {{ props.row.detalles?.length || 0 }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-total_unidades="props">
            <q-td :props="props">
              <q-chip dense color="orange-1" text-color="orange-9">
                {{ props.row.total_unidades || 0 }} und
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-acciones="props">
            <q-td :props="props">
              <div class="row no-wrap justify-center items-center">

                <div style="width: 42px;" class="row justify-center">
                  <q-btn
                    icon="visibility"
                    color="info"
                    dense
                    round
                    flat
                    @click="verDetallePedido(props.row)"
                  >
                    <q-tooltip>Ver detalles</q-tooltip>
                  </q-btn>
                </div>

                <div style="width: 42px;" class="row justify-center">
                  <q-btn
                    v-if="esAdmin && (props.row.estado === 'APROBADO' || props.row.estado === 'APROBAR')"
                    icon="shopping_cart"
                    color="teal"
                    dense
                    round
                    flat
                    @click="abrirDialogoAccion('comprado', props.row)"
                  >
                    <q-tooltip>Marcar comprado</q-tooltip>
                  </q-btn>
                </div>

                <div style="width: 42px;" class="row justify-center">
                  <q-btn
                    v-if="puedeAnular(props.row.estado)"
                    icon="block"
                    color="grey-7"
                    dense
                    round
                    flat
                    @click="abrirDialogoAccion('anular', props.row)"
                  >
                    <q-tooltip>Anular</q-tooltip>
                  </q-btn>
                </div>

              </div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dialogDetalle" maximized>
      <q-card style="width: 90vw; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none bg-primary text-white">
          <q-icon name="receipt_long" size="md" class="q-mr-md" />
          <div class="text-h6">Detalles del Pedido #{{ pedidoSeleccionado.id }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-md-3">
              <div class="text-caption text-grey-7">Sucursal</div>
              <div class="text-subtitle1 text-weight-bold">
                <q-icon name="store" class="q-mr-xs" />
                {{ pedidoSeleccionado.agencia?.nombre || 'N/A' }}
              </div>
            </div>

            <div class="col-12 col-md-3">
              <div class="text-caption text-grey-7">Proveedor</div>
              <div class="text-subtitle1 text-weight-bold text-blue-9">
                <q-icon name="local_shipping" class="q-mr-xs" />
                {{ pedidoSeleccionado.proveedor?.nombreRazonSocial || 'No especificado' }}
              </div>
            </div>

            <div class="col-12 col-md-3">
              <div class="text-caption text-grey-7">Fecha</div>
              <div class="text-subtitle1 text-weight-bold">
                <q-icon name="event" class="q-mr-xs" />
                {{ formatFecha(pedidoSeleccionado.fecha_pedido) }}
              </div>
            </div>
            <div class="col-12 col-md-3">
              <div class="text-caption text-grey-7">Estado</div>
              <q-badge
                :color="getColorEstado(pedidoSeleccionado.estado)"
                :label="getLabelEstado(pedidoSeleccionado.estado)"
                class="q-pa-sm"
              />
            </div>
          </div>

          <div v-if="pedidoSeleccionado.observacion" class="q-mb-md">
            <div class="text-caption text-grey-7">Observaciones</div>
            <div class="text-body2">{{ pedidoSeleccionado.observacion }}</div>
          </div>

          <q-separator class="q-my-lg" />

          <q-tabs v-model="tab" dense class="text-grey" active-color="primary" indicator-color="primary" align="left">
            <q-tab name="productos" icon="inventory_2" label="Productos" />
            <q-tab name="historial" icon="history" label="Historial" v-if="esAdmin" />
          </q-tabs>

          <q-separator />

          <q-tab-panels v-model="tab" animated class="bg-transparent">
            <q-tab-panel name="productos" class="q-pa-none q-mt-md">
              <div class="text-h6 q-mb-md">Productos solicitados</div>
              <q-table
                :rows="detallesPedido"
                :columns="columnsDetalles"
                row-key="id"
                dense
                flat
                bordered
                :pagination="{ rowsPerPage: 0 }"
                hide-pagination
              >
                <template v-slot:body-cell-imagen="props">
                  <q-td :props="props">
                    <q-img
                      :src="getUrlImagen(props.row.product?.imagen)"
                      width="50px"
                      height="50px"
                      class="rounded-borders"
                    >
                      <template v-slot:error>
                        <div class="absolute-full flex flex-center bg-grey-3">
                          <q-icon name="image_not_supported" size="sm" />
                        </div>
                      </template>
                    </q-img>
                  </q-td>
                </template>

                <template v-slot:body-cell-stock_sucursales="props" v-if="esAdmin">
                  <q-td :props="props" class="text-center">
                    <q-btn
                      dense
                      flat
                      round
                      icon="visibility"
                      color="indigo"
                      @click="verStockGlobal(props.row)"
                    >
                      <q-tooltip>Ver stock en sucursales</q-tooltip>
                    </q-btn>
                  </q-td>
                </template>

               <template v-slot:body-cell-cantidad_aprobada="props">
                  <q-td :props="props">
                    <q-chip dense :color="props.row.cantidad_aprobada !== props.row.cantidad ? 'orange' : 'green-2'" :text-color="props.row.cantidad_aprobada !== props.row.cantidad ? 'white' : 'green-9'">
                      {{ props.row.cantidad_aprobada || props.row.cantidad }}
                    </q-chip>
                  </q-td>
                </template>

                <template v-slot:body-cell-acciones_producto="props" v-if="esAdmin && pedidoSeleccionado.estado === 'PENDIENTE'">
                  <q-td :props="props">
                    <div class="row no-wrap q-gutter-xs items-center">
                      <q-select
                        v-model="props.row.accion_recomendada"
                        :options="accionesRecomendadas"
                        dense
                        outlined
                        style="min-width: 160px"
                        label="Acción"
                        emit-value
                        map-options
                      />
                      <q-input
                        v-model.number="props.row.cantidad_aprobada"
                        type="number"
                        dense
                        outlined
                        style="width: 80px"
                        label="Cant."
                        :max="props.row.cantidad"
                        min="0"
                      />
                    </div>
                  </q-td>
                </template>

                <template v-slot:body-cell-accion_aplicada="props">
              <q-td :props="props">
                <q-badge
                  v-if="props.row.accion_aplicada"
                  :color="getColorAccionAplicada(props.row.accion_aplicada)"
                  class="q-pa-sm text-subtitle2"
                >
                  {{ props.row.accion_aplicada }}
                </q-badge>
                <span v-else class="text-grey-5">--</span>
              </q-td>
            </template>
              </q-table>
            </q-tab-panel>

            <q-tab-panel name="historial" class="q-pa-none q-mt-md" v-if="esAdmin">
              <div class="text-h6 q-mb-md">Historial de modificaciones</div>

              <q-timeline v-if="historialModificaciones.length" color="secondary">
                <q-timeline-entry
                  v-for="mod in historialModificaciones"
                  :key="mod.id"
                  :title="mod.accion"
                  :subtitle="formatFecha(mod.fecha)"
                  :icon="getIconoModificacion(mod.accion)"
                  :color="getColorModificacion(mod.accion)"
                >
                  <div>
                    <strong>Usuario:</strong> {{ mod.usuario_nombre }}<br>
                    <strong>Observación:</strong> {{ mod.observacion || 'Sin observación' }}

                    <div v-if="mod.detalles?.length" class="q-mt-sm">
                      <strong>Cambios:</strong>
                      <ul class="q-pl-md">
                        <li v-for="det in mod.detalles" :key="det.id">
                          {{ obtenerNombreProducto(det) }}: {{ det.cantidad_anterior }} → {{ det.cantidad_nueva }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </q-timeline-entry>
              </q-timeline>

              <div v-else class="text-center text-grey q-py-lg">
                <q-icon name="history" size="lg" />
                <div>Sin historial de modificaciones</div>
              </div>
            </q-tab-panel>
          </q-tab-panels>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md" v-if="esAdmin && pedidoSeleccionado.estado === 'PENDIENTE'">
          <q-btn label="Cerrar" color="grey" flat v-close-popup />

          <q-btn
            label="Anular Pedido"
            color="negative"
            flat
            icon="block"
            @click="abrirDialogoAccion('anular', pedidoSeleccionado)"
          />

          <q-btn
            label="Aprobar con Cambios"
            color="positive"
            unelevated
            icon="check_circle"
            @click="aprobarConModificaciones"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogAccion" persistent>
      <q-card style="min-width: 500px; max-width: 90vw;">
        <q-card-section class="row items-center">
          <q-icon :name="iconoAccion" :color="colorAccion" size="md" class="q-mr-md" />
          <div class="text-h6">{{ tituloAccion }}</div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="text-body1 q-mb-md">{{ mensajeConfirmacion }}</div>

          <q-input
            v-model="observacionAccion"
            label="Observación"
            type="textarea"
            rows="3"
            outlined
            counter
            maxlength="500"
            hint="Opcional: agregue comentarios"
          >
            <template v-slot:prepend>
              <q-icon name="comment" />
            </template>
          </q-input>
          <div class="q-pa-sm bg-green-1 q-mt-md rounded-borders" v-if="accionActual === 'aprobado'">
            <div class="text-subtitle2 text-green-9 q-mb-xs">
              <q-icon name="whatshot" /> Comunicación
            </div>

            <div class="row q-col-gutter-sm items-center">
              <div class="col-12 col-md-5">
                <q-checkbox
                  v-model="enviarWhatsapp"
                  label="Enviar WhatsApp"
                  color="green"
                  dense
                />
              </div>

              <div class="col-12 col-md-7">
                <q-select
                  v-if="enviarWhatsapp"
                  v-model="vendedorSeleccionado"
                  :options="vendedores"
                  option-label="nombre"
                  label="Seleccionar Vendedor"
                  dense
                  outlined
                  bg-color="white"
                  :rules="[val => !!val || 'Requerido']"
                >
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section>
                        <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                        <q-item-label caption>📱 {{ scope.opt.celular }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey text-caption">Sin vendedores</q-item-section></q-item>
                  </template>
                </q-select>
              </div>
            </div>
          </div>

          <div v-if="detallesParaModificar.length > 0 && accionActual !== 'anular'" class="q-mt-md">
            <div class="text-subtitle2 text-weight-bold q-mb-sm">
              <q-icon name="edit" class="q-mr-xs" />
              Productos con acciones ({{ detallesParaModificar.length }})
            </div>

            <q-list bordered dense class="rounded-borders">
              <q-item v-for="det in detallesParaModificar" :key="det.pedido_detail_id">
                <q-item-section avatar>
                  <q-avatar color="primary" text-color="white" icon="inventory_2" />
                </q-item-section>

                <q-item-section>
                  <q-item-label>Producto #{{ det.pedido_detail_id }}</q-item-label>
                  <q-item-label caption>
                     {{ det.accion }} - Cant: {{ det.cantidad_aprobada }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </div>

          <q-banner v-if="esAccionCritica" class="bg-warning text-white q-mt-md" dense rounded>
            <template v-slot:avatar>
              <q-icon name="warning" color="white" />
            </template>
            Esta acción no se puede deshacer. Por favor confirme que desea continuar.
          </q-banner>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn label="Cancelar" flat color="grey-7" v-close-popup icon="close" />
          <q-btn
            :label="labelBotonConfirmar"
            :color="colorAccion"
            unelevated
            :icon="iconoAccion"
            @click="confirmarAccion"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
        <q-dialog v-model="dialogStock">
  <q-card style="min-width: 350px; max-width: 90vw;">
    <q-card-section class="bg-indigo text-white row items-center">
      <div class="text-h6 ellipsis">{{ productStockSelected.nombre }}</div>
      <q-space />
      <q-btn icon="close" flat round dense v-close-popup />
    </q-card-section>

    <q-card-section>
      <div class="text-subtitle2 q-mb-sm text-center text-grey-8">Disponibilidad en Sucursales</div>
      <q-list bordered separator dense>
        <q-item>
          <q-item-section avatar>
            <q-icon name="warehouse" color="brown" />
          </q-item-section>
          <q-item-section>Almacén Central</q-item-section>
          <q-item-section side>
            <q-badge :color="productStockSelected.cantidadAlmacen > 0 ? 'green' : 'red'">
              {{ productStockSelected.cantidadAlmacen || 0 }}
            </q-badge>
          </q-item-section>
        </q-item>

        <q-item v-for="agencia in agencias" :key="agencia.id">
          <q-item-section avatar>
            <q-icon name="store" color="indigo" />
          </q-item-section>
          <q-item-section>{{ agencia.nombre }}</q-item-section>
          <q-item-section side>
            <q-badge :color="(productStockSelected['cantidadSucursal'+agencia.id] || 0) > 0 ? 'blue' : 'grey'">
              {{ productStockSelected['cantidadSucursal'+agencia.id] || 0 }}
            </q-badge>
          </q-item-section>
        </q-item>
      </q-list>
    </q-card-section>

    <q-card-actions align="right">
      <q-btn flat label="Cerrar" color="indigo" v-close-popup />
    </q-card-actions>
  </q-card>
</q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'HistorialPedidosPage',

  data () {
    return {
      loading: false,
      pedidos: [],
      pedidoSeleccionado: {},
      detallesPedido: [],
      historialModificaciones: [],
      dialogDetalle: false,
      dialogStock: false,
      pproductStockSelected: {},
      dialogAccion: false,
      accionActual: '',
      observacionAccion: '',
      detallesParaModificar: [],
      tab: 'productos',
      enviarWhatsapp: true, // Tikeado por defecto
      vendedores: [], // Lista de vendedores del proveedor actual
      vendedorSeleccionado: null,

      // --- Variables de Proveedores ---
      proveedores: [],
      proveedoresAll: [],
      // -------------------------------

      filters: {
        fecha_inicio: null,
        fecha_fin: null,
        estado: null,
        agencia_id: null,
        proveedor_id: null // Nuevo filtro
      },

      pagination: {
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0
      },

      estadosOptions: [
        { label: 'PENDIENTE', value: 'PENDIENTE' },
        { label: 'APROBADO', value: 'APROBADO' },
        { label: 'COMPRADO', value: 'COMPRADO' },
        { label: 'ANULADO', value: 'ANULADO' }
      ],

      accionesRecomendadas: [
        { label: 'Comprar', value: 'COMPRAR' },
        { label: 'Transferir', value: 'TRANSFERIR' },
        { label: 'Rechazar Producto', value: 'RECHAZAR_PRODUCTO' }
      ],

      agenciasFiltro: []
    }
  },

  computed: {
    esAdmin () {
      try {
        if (this.$store?.state?.user?.id === 1) return true
        if (this.$store?.state?.auth?.user?.id === 1) return true
        if (this.$store?.user?.id === 1) return true
        return false
      } catch (error) {
        return false
      }
    },

    columns () {
      return [
        { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true, style: 'width: 80px' },
        { name: 'fecha_pedido', label: 'Fecha', field: 'fecha_pedido', align: 'left', sortable: true, format: val => this.formatFecha(val) },
        { name: 'agencia', label: 'Sucursal', field: 'agencia', align: 'left', sortable: true },
        // Columna de Proveedor en la tabla principal
        { name: 'proveedor', label: 'Proveedor', field: row => row.proveedor?.nombreRazonSocial || 'N/A', align: 'left' },
        { name: 'solicitante', label: 'Solicitante', field: 'user', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center', sortable: true },
        { name: 'total_productos', label: 'Productos', field: row => row.detalles?.length || 0, align: 'center', sortable: true },
        { name: 'total_unidades', label: 'Unidades', field: row => row.total_unidades || 0, align: 'center', sortable: true },
        { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center', style: 'width: 200px' }
      ]
    },

    columnsDetalles () {
      const cols = [
        { name: 'imagen', label: '', field: 'imagen', align: 'center', style: 'width: 70px' },
        { name: 'producto', label: 'Producto', field: row => row.product?.nombre || 'N/A', align: 'left' },
        { name: 'cantidad', label: 'Cant. Solicitada', field: 'cantidad', align: 'center' }
      ]

      const estado = this.pedidoSeleccionado.estado

      // Columnas EXCLUSIVAS del Administrador cuando está PENDIENTE (Para editar)
      if (this.esAdmin && estado === 'PENDIENTE') {
        cols.push({ name: 'stock_sucursales', label: 'Stock por Sucursal', field: 'stock_sucursales', align: 'left' })
        cols.push({
          name: 'acciones_producto',
          label: 'Acción y Cantidad',
          field: 'acciones_producto',
          align: 'left',
          style: 'min-width: 250px'
        })
      } else if (estado !== 'PENDIENTE') {
        cols.push({ name: 'cantidad_aprobada', label: 'Cant. Aprobada', field: 'cantidad_aprobada', align: 'center' })
        cols.push({ name: 'accion_aplicada', label: 'Acción Realizada', field: 'accion_aplicada', align: 'center' })
      }

      return cols
    },

    filtrosActivos () {
      const activos = []
      if (this.filters.fecha_inicio) activos.push({ key: 'fecha_inicio', label: 'Desde', value: this.filters.fecha_inicio })
      if (this.filters.fecha_fin) activos.push({ key: 'fecha_fin', label: 'Hasta', value: this.filters.fecha_fin })
      if (this.filters.estado) activos.push({ key: 'estado', label: 'Estado', value: this.filters.estado })
      if (this.filters.agencia_id) activos.push({ key: 'agencia_id', label: 'Sucursal', value: 'Seleccionada' })
      if (this.filters.proveedor_id) {
        const prov = this.proveedoresAll.find(p => p.id === this.filters.proveedor_id)
        activos.push({ key: 'proveedor_id', label: 'Proveedor', value: prov ? prov.nombreRazonSocial : 'Seleccionado' })
      }
      return activos
    },

    tituloAccion () {
      const titulos = {
        aprobado: 'Aprobar Pedido',
        comprado: 'Marcar como Comprado',
        anular: 'Anular Pedido'
      }
      return titulos[this.accionActual] || 'Confirmar Acción'
    },

    mensajeConfirmacion () {
      const mensajes = {
        aprobado: '¿Está seguro de aprobar este pedido?',
        comprado: '¿Marcar este pedido como comprado?',
        anular: '¿Está seguro de anular este pedido? Esta acción no se puede deshacer.'
      }
      return mensajes[this.accionActual] || '¿Confirmar esta acción?'
    },

    colorAccion () {
      const colores = { aprobado: 'positive', comprado: 'teal', anular: 'negative' }
      return colores[this.accionActual] || 'primary'
    },

    iconoAccion () {
      const iconos = { aprobado: 'check_circle', comprado: 'shopping_cart', anular: 'block' }
      return iconos[this.accionActual] || 'check'
    },

    labelBotonConfirmar () {
      return this.accionActual === 'anular' ? 'Anular Pedido' : 'Confirmar'
    },

    esAccionCritica () {
      return ['anular'].includes(this.accionActual)
    }
  },

  async mounted () {
    this.inicializarFechas()
    await this.cargarAgencias() // ← Agregar esta línea
    this.cargarAgenciasFiltro()
    this.proveedoresGet()
    this.cargarPedidos()
  },

  methods: {
    inicializarFechas () {
      const hoy = new Date()
      const hace7Dias = new Date()
      hace7Dias.setDate(hoy.getDate() - 7)
      this.filters.fecha_inicio = hace7Dias.toISOString().split('T')[0]
      this.filters.fecha_fin = hoy.toISOString().split('T')[0]
    },

    // --- CARGAR PROVEEDORES (IGUAL QUE EN OTRAS PÁGINAS) ---
    proveedoresGet () {
      this.$axios.get('providers').then(res => {
        this.proveedores = res.data
        this.proveedoresAll = res.data
      }).catch(err => console.error('Error proveedores', err))
    },

    filterProveedores (val, update, abort) {
      if (val === '') {
        update(() => { this.proveedores = this.proveedoresAll })
        return
      }
      const needle = val.toLowerCase()
      update(() => {
        this.proveedores = this.proveedoresAll.filter(v => v.nombreRazonSocial.toLowerCase().indexOf(needle) > -1)
      })
    },
    // ------------------------------------------------------

    async cargarAgenciasFiltro () {
      if (!this.esAdmin) return
      try {
        const res = await this.$axios.get('agencias')
        this.agenciasFiltro = [
          { label: 'Todas las sucursales', value: null },
          ...res.data.map(a => ({ label: a.nombre, value: a.id }))
        ]
      } catch (error) {
        console.error('Error', error)
      }
    },

    async cargarPedidos () {
      this.loading = true
      try {
        const params = {
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage,
          ...this.filters
        }
        Object.keys(params).forEach(key => {
          if (params[key] === null || params[key] === undefined) delete params[key]
        })

        const res = await this.$axios.get('pedidos/historial', { params })
        this.pedidos = res.data.data
        this.pagination.rowsNumber = res.data.total
      } catch (error) {
        console.error('Error', error)
      } finally {
        this.loading = false
      }
    },

    async verDetallePedido (pedido) {
      this.pedidoSeleccionado = pedido
      this.tab = 'productos'
      this.dialogDetalle = true

      try {
        const res = await this.$axios.get(`pedidos/${pedido.id}/detalles`)

        this.detallesPedido = res.data.detalles.map(d => {
          let accionGuardada = d.accion || d.accion_aplicada

          if (!accionGuardada && (pedido.estado === 'ANULADO' || pedido.estado === 'ANULAR')) {
            accionGuardada = 'ANULADO'
          }

          return {
            ...d,
            accion_aplicada: accionGuardada,
            cantidad_aprobada: d.cantidad_aprobada !== undefined ? d.cantidad_aprobada : d.cantidad,
            // CAMBIO: Forzamos 'COMPRAR' para que siempre sea el valor por defecto en el select
            accion_recomendada: 'COMPRAR',
            stock_sucursales: []
          }
        })

        if (this.esAdmin) {
          await this.cargarInfoAdicional()
        }
      } catch (error) {
        console.error('Error', error)
      }
    },

    async cargarInfoAdicional () {
      const promesas = this.detallesPedido.map(async (detalle) => {
        try {
          const resStock = await this.$axios.get(`products/${detalle.product_id}/stock-sucursales`)

          // 🔥 TRANSFORMACIÓN: Convertir array a propiedades
          const stockArray = resStock.data

          // Agregar las propiedades directamente al producto
          stockArray.forEach(sucursal => {
            if (sucursal.agencia_id === 1) {
              // Si es almacén (ID 1)
              detalle.product.cantidadAlmacen = sucursal.stock
            } else {
              // Si es una sucursal
              detalle.product['cantidadSucursal' + sucursal.agencia_id] = sucursal.stock
            }
          })

          // Guardar también el array original por si lo necesitas
          detalle.stock_sucursales = stockArray
        } catch (error) {
          console.error('Error cargando stock:', error)
        }
      })

      await Promise.all(promesas)

      try {
        const resHist = await this.$axios.get(`pedidos/${this.pedidoSeleccionado.id}/modificaciones`)
        this.historialModificaciones = resHist.data
      } catch (error) {
        this.historialModificaciones = []
      }
    },

    async abrirDialogoAccion (accion, row) {
      // CORRECCIÓN: Usar los nombres correctos definidos en data()
      this.accionActual = accion // Antes decías: this.accionSeleccionada
      this.pedidoSeleccionado = row
      this.dialogAccion = true // Antes decías: this.dialogoAccion

      // Lógica de vendedores (solo si es APROBAR, no necesario para COMPRADO)
      this.vendedores = []
      this.vendedorSeleccionado = null
      this.enviarWhatsapp = true

      if (accion === 'APROBAR' && this.pedidoSeleccionado.proveedor_id) {
        try {
          const { data } = await this.$axios.get(`vendedores-por-proveedor/${this.pedidoSeleccionado.proveedor_id}`)
          this.vendedores = data

          if (this.vendedores.length === 1) {
            this.vendedorSeleccionado = this.vendedores[0]
          }
        } catch (e) {
          console.error('Error cargando vendedores', e)
        }
      }

      // Cargar detalles para que no de error si intentas leerlos
      this.detallesPedido = [] // Usar variable correcta detallesPedido en lugar de detalles
      try {
        const { data } = await this.$axios.get(`pedidos/${row.id}/detalles`)
        const lista = data.detalles || data

        // CORRECCIÓN: Usar this.detallesPedido que es lo que usas en el template
        this.detallesPedido = lista.map(d => ({
          ...d,
          cantidad_aprobada: d.cantidad_aprobada || d.cantidad,
          accion_aplicada: d.accion_aplicada || 'SIN_CAMBIOS'
        }))
      } catch (error) {
        console.error(error)
      }
    },

    async confirmarAccion () {
      try {
        let accionFinal = this.accionActual.toUpperCase()
        if (accionFinal === 'ANULAR') accionFinal = 'ANULADO'
        if (accionFinal === 'APROBAR') accionFinal = 'APROBADO'
        if (accionFinal === 'APROBADO') accionFinal = 'APROBADO'

        const data = {
          pedido_id: this.pedidoSeleccionado.id,
          accion: accionFinal,
          observacion: this.observacionAccion
        }

        if (this.detallesParaModificar.length > 0) {
          data.modificaciones = this.detallesParaModificar
        }

        await this.$axios.post('pedidos/accion', data)

        this.$q.notify({
          color: 'positive',
          message: 'Pedido procesado correctamente',
          icon: 'check_circle',
          position: 'top'
        })

        // WHATSAPP AUTOMÁTICO
        if (this.enviarWhatsapp && this.vendedorSeleccionado && (this.accionActual === 'aprobado' || this.accionActual === 'APROBADO')) {
          this.enviarMensajeWhatsapp()
        }

        this.dialogAccion = false
        this.dialogDetalle = false
        this.cargarPedidos()
      } catch (error) {
        this.$q.notify({
          color: 'negative',
          message: error.response?.data?.message || 'Error',
          icon: 'error',
          position: 'top'
        })
      }
    },

    async aprobarConModificaciones () {
      // 1. Preparar los detalles para modificar
      const modificaciones = this.detallesPedido.map(det => ({
        pedido_detail_id: det.id,
        accion: det.accion_recomendada,
        cantidad_aprobada: det.cantidad_aprobada,
        observacion: `Acción: ${det.accion_recomendada}`
      }))

      this.detallesParaModificar = modificaciones
      this.accionActual = 'aprobado'
      this.observacionAccion = 'Aprobación de pedido'

      // 2. Cargar Vendedores y Recuperar el guardado
      this.vendedores = []
      this.vendedorSeleccionado = null
      this.enviarWhatsapp = true

      if (this.pedidoSeleccionado.proveedor_id) {
        try {
          const { data } = await this.$axios.get(`vendedores-por-proveedor/${this.pedidoSeleccionado.proveedor_id}`)
          this.vendedores = data

          if (this.pedidoSeleccionado.vendedor) {
            const vendedorGuardado = this.vendedores.find(v => v.id === this.pedidoSeleccionado.vendedor.id)
            if (vendedorGuardado) {
              this.vendedorSeleccionado = vendedorGuardado
            } else {
              this.vendedorSeleccionado = this.pedidoSeleccionado.vendedor
            }
          } else if (this.vendedores.length === 1) {
            // Comentario movido aquí adentro para no romper el código
            this.vendedorSeleccionado = this.vendedores[0]
          }
        } catch (e) {
          console.error('Error cargando vendedores', e)
        }
      }

      this.dialogAccion = true
    },
    async cargarStockProducto (productId) {
      try {
        const { data } = await this.$axios.get(`products/${productId}/stock-sucursales`)
        this.productoStockSeleccionado.stock_sucursales = data
      } catch (error) {
        console.error('Error cargando stock:', error)
        this.productoStockSeleccionado.stock_sucursales = []
      }
    },
    async cargarAgencias () {
      try {
        const res = await this.$axios.get('agencias')
        this.agencias = res.data
      } catch (error) {
        console.error('Error cargando agencias:', error)
      }
    },
    aplicarFiltros () {
      this.pagination.page = 1
      this.cargarPedidos()
    },

    limpiarFiltro (key) {
      this.filters[key] = null
      this.aplicarFiltros()
    },

    limpiarTodosFiltros () {
      this.filters = { fecha_inicio: null, fecha_fin: null, estado: null, agencia_id: null, proveedor_id: null }
      this.inicializarFechas()
      this.aplicarFiltros()
    },

    onRequest (props) {
      this.pagination = props.pagination
      this.cargarPedidos()
    },

    formatFecha (fecha) {
      if (!fecha) return ''
      return new Date(fecha).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    },

    getLabelEstado (estado) {
      if (estado === 'APROBAR') return 'APROBADO'
      if (estado === 'ANULAR') return 'ANULADO'
      return estado
    },

    getColorEstado (estado) {
      const colores = {
        PENDIENTE: 'orange',
        APROBADO: 'positive',
        APROBAR: 'positive',
        COMPRADO: 'teal',
        ANULADO: 'negative',
        ANULAR: 'negative'
      }
      return colores[estado] || 'grey'
    },

    getColorAccionAplicada (accion) {
      if (!accion) return 'grey'
      const accionNorm = accion.toUpperCase()
      const colores = {
        TRANSFERIR: 'orange-8',
        COMPRAR: 'teal',
        RECHAZAR_PRODUCTO: 'red',
        APROBADO: 'green',
        APROBAR: 'green',
        ANULADO: 'grey-8',
        ANULAR: 'grey-8'
      }
      return colores[accionNorm] || 'blue-grey'
    },

    getIconoModificacion (accion) {
      return 'info'
    },
    getColorModificacion (accion) {
      return 'primary'
    },
    getUrlImagen (imagen) {
      if (!imagen) return '/img/no-image.png'
      if (imagen.includes('http')) return imagen
      return `${this.$url}../images/${imagen}`
    },
    getIniciales (nombre) {
      if (!nombre) return '?'
      return nombre.substring(0, 2).toUpperCase()
    },
    puedeAnular (estado) {
      return !['ANULADO', 'ANULAR', 'CANCELADO', 'COMPRADO'].includes(estado)
    },
    obtenerNombreProducto (detalle) {
      return detalle.producto_nombre || 'Producto'
    },

    enviarMensajeWhatsapp () {
      if (!this.vendedorSeleccionado || !this.vendedorSeleccionado.celular) return

      const pedido = this.pedidoSeleccionado
      const sucursal = pedido.agencia ? pedido.agencia.nombre : 'Principal'
      // Usamos tu función formatFecha si existe, sino usamos la fecha directa
      const fecha = typeof this.formatFecha === 'function' ? this.formatFecha(pedido.fecha_pedido) : pedido.fecha_pedido
      // 1. CABECERA ELEGANTE
      let texto = `👋 *HOLA ${this.vendedorSeleccionado.nombre.toUpperCase()}*\n`
      texto += 'Le envío una nueva orden de compra aprobada. 📝\n\n'
      // 2. DATOS DEL PEDIDO (Bloque separado)
      texto += '━━━━━━━━━━━━━━━━━━\n'
      texto += '📌 *DATOS GENERALES*\n'
      texto += `🏢 *Sucursal:* ${sucursal}\n`
      texto += `📅 *Fecha:* ${fecha}\n`
      texto += `📄 *Nro Pedido:* ${pedido.id}\n`
      texto += '━━━━━━━━━━━━━━━━━━\n\n'

      // 3. DETALLE DE PRODUCTOS
      texto += '📦 *LISTA DE PRODUCTOS:*\n'

      this.detallesPedido.forEach(d => {
        const accion = d.accion_recomendada || d.accion_aplicada
        const cantidad = d.cantidad_aprobada || d.cantidad

        // Solo enviamos lo que tiene acción COMPRAR y cantidad > 0
        if ((accion === 'COMPRAR' || !accion) && cantidad > 0) {
          const nombreProd = d.product ? d.product.nombre.trim() : 'Producto desconocido'
          // TRUCO DE DISEÑO:
          // Ponemos la cantidad primero y en negrita para que destaque.
          // Usamos un guion largo "—" para separar visualmente.
          texto += `🔹 *${cantidad} u.* — ${nombreProd}\n`
        }
      })

      // 4. CIERRE
      texto += '\n━━━━━━━━━━━━━━━━━━\n'
      texto += '✅ *Por favor confirmar recepción.*'

      // Limpieza del número de celular
      let celular = this.vendedorSeleccionado.celular.replace(/\D/g, '')
      if (celular.length === 8) celular = '591' + celular // Prefijo Bolivia

      // Abrir WhatsApp
      const url = `https://wa.me/${celular}?text=${encodeURIComponent(texto)}`
      window.open(url, '_blank')
    },
    // Método CORREGIDO para HistorialPedidos
    verStockGlobal (detalle) {
      this.productStockSelected = detalle.product // ← Usar el mismo nombre
      this.dialogStock = true
    }
  }
}
</script>

<style scoped>
.q-timeline {
  max-width: 800px;
}
</style>
