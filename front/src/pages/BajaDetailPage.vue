<template>
  <q-page class="q-pa-md">
    <div v-if="report" class="row items-center q-mb-md">
      <div class="col">
        <q-breadcrumbs class="text-grey-7 q-mb-xs" active-color="primary">
          <q-breadcrumbs-el :label="report.tipo === 'CONTEO FISICO' ? 'Control de Inventario' : 'Informes de Bajas'" icon="description" to="/informesBajas" />
          <q-breadcrumbs-el :label="(report.tipo === 'CONTEO FISICO' ? 'Control #' : 'Informe #') + report.id" />
        </q-breadcrumbs>
        <div class="text-h5 text-bold text-primary flex items-center">
          {{ report.tipo === 'CONTEO FISICO' ? 'Control de Inventario' : 'Informe de Bajas' }} - {{ report.agencia?.nombre }} ({{ getMesNombre(report.mes) }} {{ report.anio }})
          <q-chip
            :color="getTipoReporteColor(getReportDisplayTipo(report))"
            :text-color="getTipoReporteTextColor(getReportDisplayTipo(report))"
            :icon="getTipoReporteIcon(getReportDisplayTipo(report))"
            class="q-ml-md text-weight-bold"
            dense
          >
            {{ getReportDisplayTipo(report) }}
          </q-chip>
          <q-chip
            :color="report.estado === 'ABIERTO' ? 'green-1' : (report.estado === 'PENDIENTE' ? 'orange-1' : (report.estado === 'OBSERVADO' ? 'red-2' : 'grey-2'))"
            :text-color="report.estado === 'ABIERTO' ? 'green-9' : (report.estado === 'PENDIENTE' ? 'orange-9' : (report.estado === 'OBSERVADO' ? 'red-10' : 'grey-8'))"
            :icon="report.estado === 'ABIERTO' ? 'lock_open' : (report.estado === 'PENDIENTE' ? 'send' : (report.estado === 'OBSERVADO' ? 'warning' : 'lock'))"
            class="q-ml-xs text-weight-bold"
            dense
          >
            {{ report.estado }}
          </q-chip>
        </div>
      </div>
      <div class="col-12 col-md-auto text-right flex justify-end q-gutter-sm">
        <q-btn
          v-if="(report.estado === 'ABIERTO' || report.estado === 'OBSERVADO') && report.items && report.items.length > 0 && String($store.user?.id) !== '1'"
          color="primary"
          icon="send"
          label="Enviar Informe"
          @click="confirmSend"
          no-caps
          unelevated
          :disable="loading"
        />
        <template v-if="(report.estado === 'PENDIENTE' || report.estado === 'OBSERVADO') && String($store.user?.id) === '1'">
          <q-btn
            color="positive"
            icon="check_circle"
            label="Marcar como Revisado"
            @click="confirmClose"
            no-caps
            unelevated
            :disable="loading"
          />
          <q-btn
            v-if="report.estado === 'PENDIENTE'"
            color="orange-8"
            icon="undo"
            label="Reabrir Informe"
            @click="confirmReopen"
            no-caps
            unelevated
            :disable="loading"
          />
        </template>
        <template v-if="String($store.user?.id) === '1' && (report.estado === 'PENDIENTE' || report.estado === 'OBSERVADO')">
          <q-btn
            color="green-8"
            icon="done_all"
            label="Marcar todos como Revisados"
            @click="confirmMarkAllAsReviewed"
            no-caps
            unelevated
            :disable="loading"
          />
        </template>
        <template v-if="report.estado === 'REVISADO'">
          <q-btn
            v-if="report.tipo !== 'CONTEO FISICO' || String($store.user?.id) === '1'"
            color="red-7"
            icon="picture_as_pdf"
            label="PDF"
            @click="generatePDF(false)"
            no-caps
            unelevated
            :disable="loading"
          />
          <q-btn
            v-if="String($store.user?.id) === '1'"
            color="green-7"
            icon="fa-brands fa-whatsapp"
            label="WhatsApp"
            @click="shareWhatsApp"
            no-caps
            unelevated
            :disable="loading"
          />
        </template>
      </div>
    </div>

    <!-- Banner especial para Informes Mensuales (Legible y moderno) -->
    <div
      v-if="report && isMonthlyReport"
      class="q-pa-md q-mb-md rounded-borders text-white flex items-center justify-between shadow-2"
      style="background: linear-gradient(135deg, #1A237E 0%, #3F51B5 100%); border-radius: 8px;"
    >
      <div class="flex items-center">
        <q-icon name="calendar_month" size="md" class="q-mr-md" />
        <div>
          <div class="text-h6 text-bold">Sección: Informes Mensuales de Bajas</div>
          <div class="text-caption">Revisión y validación de productos vencidos o para devolución a proveedor de sucursales.</div>
        </div>
      </div>
      <q-badge color="white" text-color="indigo-10" class="text-bold q-px-sm q-py-xs" style="font-size: 12px; border-radius: 4px;">
        BAJAS MENSUALES
      </q-badge>
    </div>

    <!-- Banner especial para Control de Inventario (Legible y moderno) -->
    <div
      v-if="report && report.tipo === 'CONTEO FISICO'"
      class="q-pa-md q-mb-md rounded-borders text-white flex items-center justify-between shadow-2"
      style="background: linear-gradient(135deg, #004D40 0%, #00796B 100%); border-radius: 8px;"
    >
      <div class="flex items-center">
        <q-icon name="assignment" size="md" class="q-mr-md" />
        <div>
          <div class="text-h6 text-bold">Sección: Control de Inventario (Conteo Físico)</div>
          <div class="text-caption">Revisión y conciliación de stock físico en sucursales contra el stock registrado en el sistema.</div>
        </div>
      </div>
      <q-badge color="white" text-color="teal-10" class="text-bold q-px-sm q-py-xs" style="font-size: 12px; border-radius: 4px;">
        CONTROL INVENTARIO
      </q-badge>
    </div>

    <!-- Banner especial para Bajas por Motivos Sanitarios (Legible y moderno) -->
    <div
      v-if="report && report.tipo === 'MOTIVOS SANITARIOS'"
      class="q-pa-md q-mb-md rounded-borders text-white flex items-center justify-between shadow-2"
      style="background: linear-gradient(135deg, #880E4F 0%, #AD1457 100%); border-radius: 8px;"
    >
      <div class="flex items-center">
        <q-icon name="health_and_safety" size="md" class="q-mr-md" />
        <div>
          <div class="text-h6 text-bold">Sección: Bajas por Motivos Sanitarios</div>
          <div class="text-caption">Retiros por alertas sanitarias, orden de AGEMED/SEDES, fallas de calidad, o suspensiones de registro.</div>
        </div>
      </div>
      <q-badge color="white" text-color="pink-10" class="text-bold q-px-sm q-py-xs" style="font-size: 12px; border-radius: 4px;">
        MOTIVOS SANITARIOS
      </q-badge>
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
              <div class="col-12 col-sm-3">
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
                  :disable="isAgencyFilterDisabled"
                >
                  <template v-slot:prepend>
                    <q-icon name="apartment" />
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
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
              <div class="col-12 col-sm-3">
                <q-select
                  v-model="limitLotes"
                  :options="[
                    { label: 'Últimos 3 lotes', value: '3' },
                    { label: 'Últimos 5 lotes', value: '5' },
                    { label: 'Últimos 7 lotes', value: '7' },
                    { label: 'Mostrar todos', value: 'all' }
                  ]"
                  label="Lotes a Mostrar"
                  outlined
                  dense
                  emit-value
                  map-options
                  class="bg-white"
                  @update:model-value="searchInventory"
                >
                  <template v-slot:prepend>
                    <q-icon name="layers" />
                  </template>
                </q-select>
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
              <template v-slot:body-cell-agencia_usuario="props">
                <q-td :props="props">
                  <div style="font-size: 11px; line-height: 1.2;">
                    <div class="text-bold text-primary">{{ props.row.agencia?.nombre || 'Almacén' }}</div>
                    <div class="text-grey-7">{{ props.row.user?.name || 'N/A' }}</div>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-producto="props">
                <q-td :props="props">
                  <div class="row items-center no-wrap">
                    <q-avatar size="32px" class="q-mr-sm bg-grey-3 rounded-borders overflow-hidden">
                      <q-img
                        v-if="props.row.product?.imagen"
                        :src="props.row.product.imagen.includes('http') ? props.row.product.imagen : ($url + '../images/' + props.row.product.imagen)"
                        spinner-color="primary"
                        fit="cover"
                        style="height: 100%; width: 100%;"
                        loading="lazy"
                      >
                        <template v-slot:error>
                          <q-icon name="image" size="xs" color="grey-5" />
                        </template>
                      </q-img>
                      <q-icon v-else name="image" size="xs" color="grey-5" />
                    </q-avatar>
                    <div>
                      <div class="text-weight-bold" style="white-space: normal; min-width: 180px; max-width: 340px; word-break: break-word; line-height: 1.2;">
                        {{ props.row.product?.nombre }}
                      </div>
                      <div class="text-caption text-grey-6" style="font-size: 10px;" v-if="props.row.product?.barra">
                        Cod. Barra: {{ props.row.product.barra }}
                      </div>
                    </div>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-vence="props">
                <q-td :props="props" class="text-center">
                  <div class="row items-center justify-center no-wrap">
                    <q-icon
                      v-if="props.row.dateExpiry"
                      :name="getExpiryIcon(props.row.dateExpiry)"
                      :color="getExpiryColor(props.row.dateExpiry)"
                      size="xs"
                      class="q-mr-xs"
                    />
                    <span :class="'text-' + getExpiryColor(props.row.dateExpiry)" class="text-weight-bold">
                      {{ props.row.dateExpiry || 'N/A' }}
                    </span>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-stock_actual="props">
                <q-td :props="props" class="text-center text-weight-bold text-teal">
                  {{ props.value }}
                </q-td>
              </template>
              <template v-slot:body-cell-proveedor="props">
                <q-td :props="props">
                  <div class="ellipsis" style="max-width: 80px">
                    {{ props.row.proveedor?.nombreRazonSocial || 'N/A' }}
                    <q-tooltip v-if="props.row.proveedor?.nombreRazonSocial">
                      {{ props.row.proveedor.nombreRazonSocial }}
                    </q-tooltip>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-actions="props">
                <q-td :props="props" class="text-center">
                  <q-btn
                    color="primary"
                    icon="playlist_add"
                    label="Baja"
                    dense
                    size="sm"
                    @click="preparaBaja(props.row)"
                    no-caps
                    class="q-px-sm"
                  >
                    <q-tooltip>Seleccionar para Baja</q-tooltip>
                  </q-btn>
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
        <template v-slot:body="props">
          <q-tr :props="props" :class="getRowClass(props.row)">
            <q-td v-for="col in props.cols" :key="col.name" :props="props">
              <!-- producto -->
              <template v-if="col.name === 'producto'">
                <div class="row items-center no-wrap">
                  <q-avatar size="32px" class="q-mr-sm bg-grey-3 rounded-borders overflow-hidden">
                    <q-img
                      v-if="props.row.product?.imagen"
                      :src="props.row.product.imagen.includes('http') ? props.row.product.imagen : ($url + '../images/' + props.row.product.imagen)"
                      spinner-color="primary"
                      fit="cover"
                      style="height: 100%; width: 100%;"
                      loading="lazy"
                    >
                      <template v-slot:error>
                        <q-icon name="image" size="xs" color="grey-5" />
                      </template>
                    </q-img>
                    <q-icon v-else name="image" size="xs" color="grey-5" />
                  </q-avatar>
                  <div>
                    <div class="text-weight-bold" style="white-space: normal; min-width: 180px; max-width: 340px; word-break: break-word; line-height: 1.2;">
                      {{ props.row.product?.nombre }}
                    </div>
                    <div class="text-caption text-grey-6" style="font-size: 10px;" v-if="props.row.product?.barra">
                      Cod. Barra: {{ props.row.product.barra }}
                    </div>
                  </div>
                </div>
              </template>
              <!-- agencia -->
              <template v-else-if="col.name === 'agencia'">
                <div>
                  <div class="text-bold text-grey-9">{{ props.row.agencia?.nombre || (props.row.buy?.agencia?.nombre || 'Almacen') }}</div>
                  <div class="text-caption text-grey-6 flex items-center q-mt-xs" style="font-size: 11px;">
                    <q-icon name="person" size="13px" class="q-mr-xs" />
                    <span>{{ props.row.user?.name || (report.user?.name || 'N/A') }}</span>
                  </div>
                </div>
              </template>
              <!-- stock_sucursal -->
              <template v-else-if="col.name === 'stock_sucursal'">
                <q-badge color="blue-grey-2" text-color="blue-grey-9" class="text-bold" style="font-size: 11.5px; padding: 4px 8px;">
                  {{ col.value }}
                </q-badge>
              </template>
              <!-- vence -->
              <template v-else-if="col.name === 'vence'">
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
              </template>
              <!-- user -->
              <template v-else-if="col.name === 'user'">
                <div class="ellipsis" style="max-width: 100px">
                  {{ props.row.buy?.user?.name || 'N/A' }}
                  <q-tooltip v-if="props.row.buy?.user?.name">{{ props.row.buy.user.name }}</q-tooltip>
                </div>
              </template>
              <!-- proveedor -->
              <template v-else-if="col.name === 'proveedor'">
                <div class="ellipsis" style="max-width: 120px">
                  {{ props.row.buy?.proveedor?.nombreRazonSocial || 'N/A' }}
                  <q-tooltip v-if="props.row.buy?.proveedor?.nombreRazonSocial">{{ props.row.buy.proveedor.nombreRazonSocial }}</q-tooltip>
                </div>
              </template>
              <!-- cantidad -->
              <template v-else-if="col.name === 'cantidad'">
                <span :class="props.row.cantidad > 0 ? 'text-positive text-bold' : 'text-negative text-bold'">
                  {{ formatCantidad(props.row) }}
                </span>
              </template>
              <!-- tipo -->
              <template v-else-if="col.name === 'tipo'">
                <q-chip
                  :color="getTipoColor(props.row.tipo)"
                  :text-color="getTipoTextColor(props.row.tipo)"
                  dense
                  size="sm"
                  class="text-weight-bold"
                >
                  {{ props.row.tipo }}
                </q-chip>
              </template>

              <!-- descripcion -->
              <template v-else-if="col.name === 'descripcion'">
                <div v-if="report.tipo === 'CONTEO FISICO'" class="q-mb-xs">
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
              </template>
              <!-- actions -->
              <template v-else-if="col.name === 'actions'">
                <div class="row justify-end q-gutter-x-xs no-wrap">
                  <template v-if="String($store.user?.id) === '1' && (report.estado === 'PENDIENTE' || report.estado === 'OBSERVADO')">
                    <!-- Selector de Estados e Interacciones del Administrador -->
                    <q-btn-dropdown
                      dense
                      unelevated
                      no-caps
                      :color="getBtnStatusColor(props.row.estado)"
                      :label="getBtnStatusLabel(props.row.estado)"
                      :icon="getBtnStatusIcon(props.row.estado)"
                      class="text-weight-bold q-px-sm"
                      style="border-radius: 6px; font-size: 13px;"
                    >
                      <q-list style="min-width: 220px" class="q-py-xs">
                        <!-- Conteo Físico: Acciones -->
                        <template v-if="report.tipo === 'CONTEO FISICO'">
                          <!-- Aprobar -->
                          <q-item clickable v-close-popup @click="setItemStatus(props.row, 'ACEPTADO')" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="green-1" text-color="green-9" icon="check_circle" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-green-9">Aprobar / Revisado</q-item-label>
                            </q-item-section>
                          </q-item>

                          <!-- Observar -->
                          <q-item clickable v-close-popup @click="preparaEstadoDirecto(props.row, 'OBSERVADO')" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="orange-1" text-color="orange-9" icon="warning" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-orange-9">Observar (Pedir Corrección)</q-item-label>
                            </q-item-section>
                          </q-item>

                          <!-- Rechazar -->
                          <q-item clickable v-close-popup @click="preparaEstadoDirecto(props.row, 'RECHAZADO')" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="red-1" text-color="red-9" icon="block" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-red-9">Rechazar Definitivamente</q-item-label>
                            </q-item-section>
                          </q-item>
                        </template>

                        <!-- Reporte Normal -->
                        <template v-else>
                          <!-- Aprobar -->
                          <q-item clickable v-close-popup @click="setItemStatus(props.row, 'ACEPTADO')" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="green-1" text-color="green-9" icon="check_circle" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-green-9">Aprobar / Revisar</q-item-label>
                            </q-item-section>
                          </q-item>

                          <!-- Prórroga -->
                          <q-item clickable v-close-popup @click="preparaProrroga(props.row)" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="blue-1" text-color="blue-9" icon="hourglass_top" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-blue-9">Dar Prórroga</q-item-label>
                            </q-item-section>
                          </q-item>

                          <!-- Observar -->
                          <q-item clickable v-close-popup @click="preparaEstadoDirecto(props.row, 'OBSERVADO')" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="orange-1" text-color="orange-9" icon="warning" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-orange-9">Observar (Pedir Corrección)</q-item-label>
                            </q-item-section>
                          </q-item>

                          <!-- Rechazar -->
                          <q-item clickable v-close-popup @click="preparaEstadoDirecto(props.row, 'RECHAZADO')" class="q-py-md">
                            <q-item-section avatar>
                              <q-avatar color="red-1" text-color="red-9" icon="block" size="md" />
                            </q-item-section>
                            <q-item-section>
                              <q-item-label class="text-bold text-red-9">Rechazar Definitivamente</q-item-label>
                            </q-item-section>
                          </q-item>
                        </template>

                        <q-separator />

                        <!-- Nota Admin -->
                        <q-item clickable v-close-popup @click="preparaNota(props.row)" class="q-py-md">
                          <q-item-section avatar>
                            <q-avatar color="indigo-1" text-color="indigo-9" icon="rate_review" size="md" />
                          </q-item-section>
                          <q-item-section>
                            <q-item-label class="text-bold text-indigo-9">Nota del Administrador</q-item-label>
                          </q-item-section>
                        </q-item>
                      </q-list>
                    </q-btn-dropdown>
                  </template>

                  <q-btn
                    v-if="String($store.user?.id) !== '1' && report.tipo === 'CONTEO FISICO' && report.estado === 'OBSERVADO' && props.row.estado === 'OBSERVADO'"
                    flat round dense color="warning" icon="build" size="sm"
                    @click="preparaSubsanar(props.row)"
                  ><q-tooltip>Subsanar Observación (Conteo Físico)</q-tooltip></q-btn>
                  <q-btn
                    v-if="String($store.user?.id) === '1' || report.estado === 'ABIERTO' || (String($store.user?.id) !== '1' && report.tipo !== 'CONTEO FISICO' && report.estado === 'OBSERVADO' && props.row.estado === 'OBSERVADO')"
                    flat round dense color="primary" icon="edit" size="sm"
                    @click="preparaEditItem(props.row)"
                  ><q-tooltip>{{ String($store.user?.id) === '1' ? 'Editar Ítem (Admin)' : 'Corregir Observación' }}</q-tooltip></q-btn>
                  <q-btn
                    v-if="report.estado === 'ABIERTO' || (report.estado === 'OBSERVADO' && props.row.estado === 'OBSERVADO')"
                    flat round dense color="negative" icon="remove_circle_outline" size="sm"
                    @click="removeItem(props.row)"
                  ><q-tooltip>Quitar del informe</q-tooltip></q-btn>
                </div>
              </template>
              <!-- default -->
              <template v-else>{{ col.value }}</template>
            </q-td>
          </q-tr>
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
                :options="filteredTiposBaja"
                label="Motivo de la Baja *"
                outlined
                dense
                emit-value
                map-options
                :rules="[val => !!val || 'Requerido']"
                @update:model-value="onTipoBajaChange"
              />
            </div>
            <div class="col-12">
              <q-input label="Producto" :model-value="selectedBuy.product.nombre" readonly outlined dense bg-color="grey-2" />
            </div>
            <div class="col-6">
              <q-input
                label="Lote *"
                v-model="bajaForm.lote"
                :readonly="!editLoteMode"
                outlined
                dense
                :bg-color="editLoteMode ? 'amber-1' : ''"
              >
                <template v-slot:append>
                  <q-btn
                    flat
                    round
                    dense
                    :color="editLoteMode ? 'positive' : 'grey-7'"
                    :icon="editLoteMode ? 'check' : 'edit'"
                    @click="editLoteMode = !editLoteMode"
                  >
                    <q-tooltip>{{ editLoteMode ? 'Confirmar lote' : 'Corregir lote manualmente' }}</q-tooltip>
                  </q-btn>
                </template>
              </q-input>
            </div>
            <div class="col-6">
              <q-select
                v-model="bajaForm.agencia_id"
                :options="dialogAgenciasOptions"
                label="Sucursal *"
                outlined
                dense
                emit-value
                map-options
                option-value="id"
                option-label="nombre"
                :rules="[val => val !== null || 'Requerido']"
                @update:model-value="updateSelectedStock"
                :disable="String($store.user?.id) !== '1' && String($store.user?.agencia_id) !== '1'"
              />
            </div>
            <div class="col-6">
              <q-input label="Stock Sistema" :model-value="selectedStock" readonly outlined dense bg-color="green-1" />
            </div>

            <!-- Caso Conteo Fisico -->
            <template v-if="report.tipo === 'CONTEO FISICO'">
              <div class="col-6">
                <q-input
                  label="Conteo Físico *"
                  v-model.number="bajaForm.fisico"
                  type="number"
                  min="0"
                  outlined
                  dense
                  autofocus
                  :rules="[val => val !== null && val >= 0 || 'Debe ser mayor o igual a 0']"
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
                :label="bajaForm.tipo === 'OTRO' ? 'Escriba el motivo manualmente *' : 'Descripción / Observaciones'"
                v-model="bajaForm.descripcion"
                type="textarea"
                outlined
                dense
                rows="2"
                :rules="bajaForm.tipo === 'OTRO' ? [val => !!val || 'Requerido'] : []"
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
                :options="filteredTiposBaja"
                label="Motivo de la Baja *"
                outlined
                dense
                emit-value
                map-options
                @update:model-value="onEditTipoChange"
              />
            </div>
            <div class="col-12">
              <q-input label="Producto" :model-value="editingItem.product?.nombre" readonly outlined dense bg-color="grey-2" />
            </div>
            <div class="col-12">
              <q-input
                label="Lote *"
                v-model="editItemForm.lote"
                :readonly="!editItemLoteMode"
                outlined
                dense
                :bg-color="editItemLoteMode ? 'amber-1' : ''"
              >
                <template v-slot:append>
                  <q-btn
                    flat
                    round
                    dense
                    :color="editItemLoteMode ? 'positive' : 'grey-7'"
                    :icon="editItemLoteMode ? 'check' : 'edit'"
                    @click="editItemLoteMode = !editItemLoteMode"
                  >
                    <q-tooltip>{{ editItemLoteMode ? 'Confirmar lote' : 'Corregir lote manualmente' }}</q-tooltip>
                  </q-btn>
                </template>
              </q-input>
            </div>
            <div class="col-12">
              <q-select
                v-model="editItemForm.agencia_id"
                :options="dialogAgenciasOptions"
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
            <template v-if="report.tipo === 'CONTEO FISICO'">
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
                  min="0"
                  outlined
                  dense
                  :rules="[val => val !== null && val >= 0 || 'Debe ser mayor o igual a 0']"
                  @update:model-value="calculateEditFromFisico"
                />
              </div>
            </template>

            <div class="col-12">
              <q-input
                :label="report.tipo === 'CONTEO FISICO' ? 'Diferencia calculada' : 'Cantidad a retirar *'"
                v-model.number="editItemForm.cantidad"
                type="number"
                outlined
                dense
                :rules="[
                  val => val !== null || 'Inválido',
                  val => report.tipo === 'CONTEO FISICO' || val > 0 || 'Debe ser mayor a 0',
                  val => report.tipo === 'CONTEO FISICO' || val <= selectedStock || 'Stock insuficiente en sucursal'
                ]"
                :readonly="report.tipo === 'CONTEO FISICO'"
                :bg-color="report.tipo === 'CONTEO FISICO' ? 'grey-2' : ''"
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

            <div class="col-12" v-if="editingItem.admin_descripcion">
              <q-input
                label="Observación del Administrador"
                :model-value="editingItem.admin_descripcion"
                readonly
                outlined
                dense
                bg-color="red-1"
                type="textarea"
                rows="2"
              />
            </div>
            <div class="col-12" v-if="String($store.user?.id) === '1'">
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

    <!-- Dialogo para PRÓRROGA (Admin) -->
    <q-dialog v-model="showProrrogaDialog" persistent>
      <q-card style="min-width: 350px">
        <q-card-section class="row items-center bg-blue-8 text-white">
          <div class="text-h6">Establecer Prórroga de Venta</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-md" v-if="prorrogaItem">
          <div class="q-mb-md text-weight-bold text-primary">
            {{ prorrogaItem.product?.nombre }} (Lote: {{ prorrogaItem.buy?.lote }})
          </div>
          <q-input
            v-model="prorrogaForm.prorroga_hasta"
            type="date"
            label="Fecha Límite de Prórroga *"
            outlined
            dense
            :rules="[val => !!val || 'Requerido']"
          />
          <q-input
            v-model="prorrogaForm.admin_descripcion"
            type="textarea"
            label="Indicación o Motivo"
            outlined
            dense
            rows="2"
            placeholder="Ej: Intentar vender con descuento en mostrador..."
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cancelar" color="grey" flat v-close-popup />
          <q-btn label="Guardar Prórroga" color="primary" unelevated @click="saveProrroga" :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo para RECHAZO / OBSERVACIÓN (Admin) -->
    <q-dialog v-model="showObservarDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center bg-negative text-white">
          <div class="text-h6">Rechazar u Observar Ítem</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-md" v-if="observarItem">
          <div class="q-mb-md text-weight-bold text-negative">
            {{ observarItem.product?.nombre }} (Lote: {{ observarItem.buy?.lote }})
          </div>
          <div class="q-gutter-sm q-mb-md">
            <q-radio v-model="observarForm.estado" val="OBSERVADO" label="Observar (Pedir corrección a sucursal)" color="orange-8" @update:model-value="onEstadoObservacionChange" />
            <q-radio v-model="observarForm.estado" val="RECHAZADO" label="Rechazar definitivamente" color="red-8" @update:model-value="onEstadoObservacionChange" />
          </div>
          <div class="q-mb-md">
            <q-select
              v-model="selectedMotivoObservacion"
              :options="motivosObservacionOptions"
              label="Motivo predefinido (opcional)"
              outlined
              dense
              emit-value
              map-options
              @update:model-value="onMotivoObservacionSelect"
            />
          </div>
          <q-input
            v-model="observarForm.admin_descripcion"
            type="textarea"
            label="Instrucción / Motivo de Rechazo *"
            outlined
            dense
            rows="3"
            placeholder="Ej: Contar físicamente de nuevo, o motivo de rechazo..."
            :rules="[val => !!val || 'Requerido']"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cancelar" color="grey" flat v-close-popup />
          <q-btn label="Confirmar" color="negative" unelevated @click="saveObservacion" :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo para NOTA DEL ADMINISTRADOR (Admin) -->
    <q-dialog v-model="showNoteDialog" persistent>
      <q-card style="min-width: 380px">
        <q-card-section class="row items-center bg-indigo-9 text-white">
          <div class="text-h6">Observación del Administrador</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-md" v-if="noteItem">
          <div class="q-mb-sm text-weight-bold text-indigo-9">
            {{ noteItem.product?.nombre }} (Lote: {{ noteItem.buy?.lote }})
          </div>
          <q-input
            v-model="noteFormText"
            type="textarea"
            label="Nota / Observación libre *"
            outlined
            dense
            rows="4"
            placeholder="Escriba aquí sus observaciones o comentarios..."
            autofocus
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cancelar" color="grey" flat v-close-popup />
          <q-btn label="Guardar Nota" color="indigo-9" unelevated @click="saveNote" :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo para SUBSANAR Observación (Sucursal) -->
    <q-dialog v-model="showSubsanarDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center bg-green-8 text-white">
          <div class="text-h6">Subsanar Observación - Conteo Físico</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-md" v-if="subsanarItem">
          <div class="q-mb-sm text-weight-bold text-primary">
            {{ subsanarItem.product?.nombre }} (Lote: {{ subsanarItem.buy?.lote }})
          </div>
          <div class="q-mb-md text-subtitle2 text-grey-8 flex items-center">
            <q-icon name="store" class="q-mr-xs" color="blue-8" size="18px" />
            Sucursal: <span class="text-bold text-blue-10 q-ml-xs">{{ subsanarItem.agencia?.nombre || 'Almacén' }}</span>
          </div>
          <div class="text-caption text-red-9 q-mb-md" v-if="subsanarItem.admin_descripcion">
            <strong>Observación del Administrador:</strong> {{ subsanarItem.admin_descripcion }}
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-input label="Stock Sistema" :model-value="subsanarItem.stock_sistema" readonly outlined dense bg-color="grey-2" />
            </div>
            <div class="col-6">
              <q-input
                label="Conteo Físico *"
                v-model.number="subsanarForm.conteo_fisico"
                type="number"
                min="0"
                outlined
                dense
                autofocus
                :rules="[val => val !== null && val >= 0 || 'Debe ser mayor o igual a 0']"
              />
            </div>
            <div class="col-12">
              <q-input
                label="Diferencia calculada"
                :model-value="subsanarForm.conteo_fisico - subsanarItem.stock_sistema"
                readonly
                outlined
                dense
                bg-color="blue-1"
              />
            </div>
            <div class="col-12">
              <q-select
                v-model="subsanarForm.motivo"
                :options="motivosSubsanacion"
                label="Razón de la discrepancia *"
                outlined
                dense
                :rules="[val => !!val || 'Requerido']"
              />
            </div>
            <div class="col-12" v-if="subsanarForm.motivo === 'Otro'">
              <q-input
                v-model="subsanarForm.customMotivo"
                label="Escriba la razón *"
                outlined
                dense
                :rules="[val => !!val || 'Requerido']"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cancelar" color="grey" flat v-close-popup />
          <q-btn label="Guardar Corrección" color="positive" unelevated @click="saveSubsanar" :loading="loading" />
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
      limitLotes: '3',
      agenciasOptions: [],

      inventory: [],
      loadingInventory: false,
      columnsInventory: [
        { name: 'lote', align: 'left', label: 'Lote', field: 'lote', sortable: true },
        {
          name: 'agencia_usuario',
          align: 'left',
          label: 'Agencia / Usuario',
          field: row => {
            const ag = row.agencia?.nombre || 'Almacén'
            const us = row.user?.name || 'N/A'
            return `${ag} (${us})`
          },
          sortable: true
        },
        { name: 'producto', align: 'left', label: 'Producto', field: row => row.product.nombre, sortable: true },
        { name: 'vence', align: 'center', label: 'Vence', field: 'dateExpiry', sortable: true },
        { name: 'quantity', align: 'center', label: 'Cant. Cargado', field: 'quantity', sortable: true, style: 'width: 80px', headerStyle: 'width: 80px' },
        { name: 'stock_actual', align: 'center', label: 'Stock Actual', field: row => (row.agencia_id === 0 || row.agencia_id === null) ? row.product.cantidadAlmacen : (row.product['cantidadSucursal' + row.agencia_id] || 0), sortable: true, style: 'width: 80px', headerStyle: 'width: 80px' },
        { name: 'price', align: 'right', label: 'Precio', field: 'price', sortable: true },
        { name: 'total', align: 'right', label: 'Total', field: 'total', sortable: true },
        { name: 'compra', align: 'center', label: 'Fecha Compra', field: 'date', sortable: true },
        { name: 'proveedor', align: 'left', label: 'Proveedor', field: row => row.proveedor?.nombreRazonSocial || 'N/A' },
        { name: 'actions', align: 'center', label: 'Acción', field: 'actions' }
      ],

      columnsDetail: [
        { name: 'lote', align: 'left', label: 'Lote', field: row => row.buy?.lote || 'N/A' },
        { name: 'agencia', align: 'left', label: 'Agencia Retiro', field: row => row.agencia?.nombre || (row.buy?.agencia?.nombre || 'Almacen') },
        { name: 'producto', align: 'left', label: 'Producto', field: row => row.product.nombre, sortable: true, style: 'white-space: normal; min-width: 220px; max-width: 380px; word-break: break-word; line-height: 1.2;' },
        { name: 'cantidad', align: 'center', label: 'Cantidad', field: 'cantidad', sortable: true },
        {
          name: 'stock_sucursal',
          align: 'center',
          label: 'Cant. Sucursal',
          field: row => {
            const agId = row.agencia_id === null ? 0 : row.agencia_id
            if (agId === 0) return row.product?.cantidadAlmacen || 0
            return row.product?.['cantidadSucursal' + agId] || 0
          },
          sortable: true
        },
        { name: 'tipo', align: 'center', label: 'Motivo', field: 'tipo', sortable: true },
        { name: 'vence', align: 'left', label: 'Vence', field: row => row.buy?.dateExpiry || 'N/A' },
        { name: 'proveedor', align: 'left', label: 'Proveedor', field: row => row.buy?.proveedor?.nombreRazonSocial || 'N/A' },
        { name: 'descripcion', align: 'left', label: 'Descripción', field: 'descripcion' },
        { name: 'actions', align: 'right', label: 'Acciones', field: 'actions' }
      ],

      showProrrogaDialog: false,
      showObservarDialog: false,
      prorrogaItem: null,
      observarItem: null,
      prorrogaForm: {
        prorroga_hasta: '',
        admin_descripcion: ''
      },
      observarForm: {
        estado: 'OBSERVADO',
        admin_descripcion: ''
      },
      selectedMotivoObservacion: null,
      showSubsanarDialog: false,
      subsanarItem: null,
      subsanarForm: {
        conteo_fisico: 0,
        motivo: 'Error en el conteo',
        customMotivo: ''
      },
      showNoteDialog: false,
      noteItem: null,
      noteFormText: '',
      motivosSubsanacion: [
        'Error en el conteo',
        'Vendido sin sistema',
        'Producto perdido',
        'Otro'
      ],

      showBajaDialog: false,
      showEditItemDialog: false,
      selectedBuy: null,
      editingItem: null,
      selectedStock: 0,
      editLoteMode: false,
      editItemLoteMode: false,

      bajaForm: {
        agencia_id: null,
        cantidad: 0,
        fisico: 0,
        tipo: 'VENCIMIENTO',
        descripcion: '',
        lote: ''
      },
      editItemForm: {
        agencia_id: null,
        cantidad: 0,
        tipo: 'VENCIMIENTO',
        admin_descripcion: '',
        stock_sistema: 0,
        conteo_fisico: 0,
        lote: ''
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
        { label: 'ENVIADO A CENTRAL PARA DEVOLUCION', value: 'ENVIADO A CENTRAL PARA DEVOLUCION' },
        { label: 'CONTEO FISICO', value: 'CONTEO FISICO' },
        { label: 'ERROR AL CARGAR AL SISTEMA', value: 'ERROR AL CARGAR AL SISTEMA' },
        { label: 'PRODUCTO CRUZADO', value: 'PRODUCTO CRUZADO' },
        { label: 'VENCIDO HACE MUCHO TIEMPO', value: 'VENCIDO HACE MUCHO TIEMPO' },
        { label: 'ROBO / PERDIDA', value: 'ROBO / PERDIDA' },
        { label: 'ROTURA / MAL ESTADO', value: 'ROTURA / MAL ESTADO' },
        { label: 'DONACION / REGALO', value: 'DONACION / REGALO' },
        { label: 'PRODUCTO DAÑADO', value: 'PRODUCTO DAÑADO' },
        { label: 'RETIRO ORDEN AGEMED / SEDES', value: 'RETIRO ORDEN AGEMED / SEDES' },
        { label: 'RETIRO VOLUNTARIO PROVEEDOR', value: 'RETIRO VOLUNTARIO PROVEEDOR' },
        { label: 'ALERTA SANITARIA / FALSIFICACION', value: 'ALERTA SANITARIA / FALSIFICACION' },
        { label: 'FALLA DE CALIDAD / IMPUREZAS', value: 'FALLA DE CALIDAD / IMPUREZAS' },
        { label: 'SUSPENSION DE REGISTRO SANITARIO', value: 'SUSPENSION DE REGISTRO SANITARIO' },
        { label: 'OTRO', value: 'OTRO' }
      ]
    }
  },
  computed: {
    isAgencyFilterDisabled () {
      // Allow changing search agency filter to find products in other sucursales
      return false
    },
    isMonthlyReport () {
      if (!this.report) return false
      const tipo = this.report.tipo
      return tipo === 'VENCIMIENTO' ||
             tipo === 'DEVOLUCION' ||
             tipo === 'VENCIMIENTO/DEVOLUCION' ||
             tipo === 'VENCIDOS/DEVOLUCIONES'
    },
    motivosObservacionOptions () {
      if (!this.report) return []

      const tipo = this.report.tipo
      const isMensual = tipo === 'VENCIMIENTO' ||
                        tipo === 'DEVOLUCION' ||
                        tipo === 'VENCIMIENTO/DEVOLUCION' ||
                        tipo === 'VENCIDOS/DEVOLUCIONES'

      if (isMensual) {
        if (this.observarForm.estado === 'RECHAZADO') {
          return [
            'Producto con rotación (SE DEBE VENDER)',
            'Producto no corresponde a esta sucursal',
            'Presentación o empaque dañado irrecuperable',
            'El lote indicado no existe en compras de sistema',
            'Otro (describir manualmente)'
          ]
        } else {
          // OBSERVADO
          return [
            'Lote erróneo / No coincide',
            'Revisar cantidad física ingresada',
            'Fecha de vencimiento incorrecta',
            'Falta adjuntar documento de descargo',
            'Detallar motivo de la devolución',
            'Otro (describir manualmente)'
          ]
        }
      }

      // Para otros tipos de reportes (Conteo Físico, Sanitarios, etc.)
      return [
        'Cantidades no coinciden, volver a contar',
        'Producto incorrecto',
        'Lote no coincide',
        'Fecha de vencimiento errónea',
        'Mal estado / Embalaje dañado',
        'Otro (describir manualmente)'
      ]
    },
    dialogAgenciasOptions () {
      const list = []
      const isCentralOrAdmin = String(this.$store.user?.id) === '1' || String(this.$store.user?.agencia_id) === '1'
      if (isCentralOrAdmin) {
        list.push({ id: 0, nombre: 'Almacén' })
      }
      this.agenciasOptions.forEach(opt => {
        if (opt.id !== null) {
          if (opt.id === 1) {
            list.push({ id: 1, nombre: 'Casa Matriz Velasco' })
          } else {
            list.push(opt)
          }
        }
      })
      return list
    },
    filteredTiposBaja () {
      if (!this.report || !this.report.tipo) return this.tiposBaja
      const tipoReporte = this.report.tipo
      if (tipoReporte.includes('VENCIMIENTO') || tipoReporte.includes('VENCIDOS') || tipoReporte === 'VENCIMIENTO/DEVOLUCION') {
        return this.tiposBaja.filter(t =>
          t.value === 'VENCIMIENTO' ||
          t.value === 'DEVOLUCION A PROVEEDOR' ||
          t.value === 'ENVIADO A CENTRAL PARA DEVOLUCION'
        )
      }
      if (tipoReporte.includes('CONTEO') || tipoReporte === 'CONTEO FISICO') {
        return this.tiposBaja.filter(t =>
          t.value === 'CONTEO FISICO' ||
          t.value === 'ERROR AL CARGAR AL SISTEMA' ||
          t.value === 'PRODUCTO CRUZADO' ||
          t.value === 'VENCIDO HACE MUCHO TIEMPO' ||
          t.value === 'ROBO / PERDIDA' ||
          t.value === 'ROTURA / MAL ESTADO' ||
          t.value === 'DONACION / REGALO' ||
          t.value === 'OTRO'
        )
      }
      if (tipoReporte.includes('SANITARIOS') || tipoReporte === 'MOTIVOS SANITARIOS') {
        return this.tiposBaja.filter(t =>
          t.value === 'RETIRO ORDEN AGEMED / SEDES' ||
          t.value === 'RETIRO VOLUNTARIO PROVEEDOR' ||
          t.value === 'ALERTA SANITARIA / FALSIFICACION' ||
          t.value === 'FALLA DE CALIDAD / IMPUREZAS' ||
          t.value === 'SUSPENSION DE REGISTRO SANITARIO' ||
          t.value === 'OTRO'
        )
      }
      if (tipoReporte.includes('DAÑADOS') || tipoReporte === 'PRODUCTOS DAÑADOS') {
        return this.tiposBaja.filter(t => t.value === 'PRODUCTO DAÑADO')
      }
      return this.tiposBaja
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
        this.applyAgencyFilterSettings()
      }).catch(err => {
        console.error('Error al cargar agencias:', err)
        this.$q.notify({ color: 'negative', message: 'Error al cargar el listado de sucursales' })
      })
    },
    applyAgencyFilterSettings () {
      if (!this.report || this.agenciasOptions.length === 0) return

      if (this.$store && this.$store.user) {
        // Default the search filter to the user's logged-in agency, but keep all options enabled
        this.searchAgenciaId = this.$store.user.agencia_id
      }
    },
    getReport () {
      this.loading = true
      this.$axios.get(`withdrawal-reports/${this.id}`).then(res => {
        this.report = res.data
        this.applyAgencyFilterSettings()
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
      if (!this.search || this.search.trim().length < 3) {
        this.inventory = []
        return
      }
      this.loadingInventory = true
      this.$axios.get('withdrawal-reports/search-products', {
        params: {
          search: this.search,
          agencia_id: this.searchAgenciaId === null ? 'all' : this.searchAgenciaId,
          limit_lotes: this.limitLotes,
          report_id: this.id
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
      const isCentralOrAdmin = String(this.$store.user?.id) === '1' || String(this.$store.user?.agencia_id) === '1'
      let originAgenciaId = this.report?.agencia_id

      if (isCentralOrAdmin && (buy.agencia_id === null || buy.agencia_id === 0)) {
        originAgenciaId = 0
      } else if (originAgenciaId === undefined || originAgenciaId === null) {
        originAgenciaId = (buy.agencia_id === null || buy.agencia_id === undefined) ? 0 : buy.agencia_id
      }
      this.selectedStock = this.getAgenciaStock(buy.product, originAgenciaId)

      let sugerido = 'OTRO'
      if (this.report?.tipo === 'CONTEO FISICO') {
        sugerido = 'CONTEO FISICO'
      } else if (this.report?.tipo?.includes('SANITARIOS')) {
        sugerido = 'RETIRO ORDEN AGEMED / SEDES'
      } else if (buy.dateExpiry) {
        const vence = moment(buy.dateExpiry)
        if (vence.isBefore(moment())) {
          sugerido = 'VENCIMIENTO'
        }
      }

      // Filter based on filteredTiposBaja
      const allowedValues = this.filteredTiposBaja.map(t => t.value)
      if (!allowedValues.includes(sugerido)) {
        sugerido = allowedValues.length > 0 ? allowedValues[0] : 'OTRO'
      }

      this.bajaForm = {
        agencia_id: originAgenciaId,
        cantidad: this.report?.tipo === 'CONTEO FISICO' ? -this.selectedStock : 1,
        fisico: this.report?.tipo === 'CONTEO FISICO' ? 0 : this.selectedStock,
        tipo: sugerido,
        descripcion: '',
        lote: buy.lote
      }
      this.editLoteMode = false
      this.showBajaDialog = true
    },
    onTipoBajaChange (val) {
      if (this.report?.tipo === 'CONTEO FISICO') {
        this.bajaForm.fisico = 0
        this.calculateFromFisico(0)
      } else {
        this.bajaForm.cantidad = 1
      }
    },
    onEditTipoChange (val) {
      if (this.report?.tipo === 'CONTEO FISICO') {
        this.editItemForm.stock_sistema = this.selectedStock
        this.editItemForm.conteo_fisico = 0
        this.calculateEditFromFisico()
      } else {
        this.editItemForm.cantidad = Math.abs(this.editItemForm.cantidad) || 1
      }
    },
    updateSelectedStock (val) {
      this.selectedStock = this.getAgenciaStock(this.selectedBuy.product, val)
      if (this.report?.tipo === 'CONTEO FISICO') {
        this.calculateFromFisico(this.bajaForm.fisico)
      }
    },
    calculateFromFisico (val) {
      this.bajaForm.cantidad = (val || 0) - this.selectedStock
    },
    getAgenciaStock (product, agenciaId) {
      if (agenciaId === 0 || agenciaId === null) return product.cantidadAlmacen || 0
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
      if (this.report?.tipo === 'CONTEO FISICO') {
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
      const cantidadEnviar = this.report?.tipo === 'CONTEO FISICO' ? this.bajaForm.cantidad : -Math.abs(this.bajaForm.cantidad)

      this.$axios.post(`withdrawal-reports/${this.id}/items`, {
        buy_id: this.selectedBuy.id,
        product_id: this.selectedBuy.product_id,
        agencia_id: this.bajaForm.agencia_id === 0 ? null : this.bajaForm.agencia_id,
        cantidad: cantidadEnviar,
        stock_sistema: this.report?.tipo === 'CONTEO FISICO' ? this.selectedStock : null,
        conteo_fisico: this.report?.tipo === 'CONTEO FISICO' ? this.bajaForm.fisico : null,
        tipo: this.bajaForm.tipo,
        descripcion: this.bajaForm.descripcion,
        lote: this.bajaForm.lote
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
    confirmMarkAllAsReviewed () {
      this.$q.dialog({
        title: 'Marcar todos como Revisados',
        message: '¿Estás seguro de marcar todos los productos de este informe como revisados/aceptados?',
        cancel: true,
        persistent: true,
        ok: { color: 'green-8', label: 'Confirmar', unelevated: true }
      }).onOk(() => {
        this.markAllAsReviewed()
      })
    },
    markAllAsReviewed () {
      this.loading = true
      this.$axios.put(`withdrawal-reports/${this.id}/items-bulk`, {
        estado: 'ACEPTADO'
      }).then(() => {
        this.$q.notify({ color: 'positive', message: 'Todos los productos han sido marcados como revisados' })
        this.getReport()
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al actualizar productos' })
      }).finally(() => {
        this.loading = false
      })
    },
    preparaEditItem (item) {
      this.editingItem = item
      this.editItemForm = {
        agencia_id: item.agencia_id === null ? 0 : item.agencia_id,
        cantidad: this.report?.tipo === 'CONTEO FISICO' ? item.cantidad : Math.abs(item.cantidad),
        tipo: item.tipo,
        admin_descripcion: item.admin_descripcion || '',
        stock_sistema: item.stock_sistema || 0,
        conteo_fisico: item.conteo_fisico || 0,
        lote: item.buy?.lote
      }
      this.editItemLoteMode = false
      this.selectedStock = this.getAgenciaStock(item.product, item.agencia_id)
      this.showEditItemDialog = true
    },
    preparaSubsanar (item) {
      this.subsanarItem = item
      this.subsanarForm = {
        conteo_fisico: item.conteo_fisico !== null ? item.conteo_fisico : item.stock_sistema,
        motivo: 'Error en el conteo',
        customMotivo: ''
      }
      this.showSubsanarDialog = true
    },
    saveSubsanar () {
      if (this.subsanarForm.conteo_fisico === null || this.subsanarForm.conteo_fisico < 0) {
        this.$q.notify({ color: 'negative', message: 'Debe ingresar un conteo físico válido' })
        return
      }
      if (this.subsanarForm.motivo === 'Otro' && !this.subsanarForm.customMotivo) {
        this.$q.notify({ color: 'negative', message: 'Debe escribir la razón de la discrepancia' })
        return
      }

      this.loading = true
      const calculoCantidad = this.subsanarForm.conteo_fisico - this.subsanarItem.stock_sistema
      const razonDesc = this.subsanarForm.motivo === 'Otro' ? this.subsanarForm.customMotivo : this.subsanarForm.motivo

      const data = {
        cantidad: calculoCantidad,
        tipo: 'CONTEO FISICO',
        agencia_id: (this.subsanarItem.agencia_id === 0 || this.subsanarItem.agencia_id === null) ? null : this.subsanarItem.agencia_id,
        conteo_fisico: this.subsanarForm.conteo_fisico,
        stock_sistema: this.subsanarItem.stock_sistema,
        descripcion: razonDesc
      }

      this.$axios.put(`withdrawal-reports/${this.id}/items/${this.subsanarItem.id}`, data)
        .then(() => {
          this.$q.notify({ color: 'positive', message: 'Observación subsanada correctamente' })
          this.showSubsanarDialog = false
          this.getReport()
        })
        .catch(err => {
          console.error(err)
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al subsanar observación' })
        })
        .finally(() => {
          this.loading = false
        })
    },
    calculateEditFromFisico (val) {
      this.editItemForm.cantidad = (this.editItemForm.conteo_fisico || 0) - (this.editItemForm.stock_sistema || 0)
    },
    updateEditStock (val) {
      this.selectedStock = this.getAgenciaStock(this.editingItem.product, val)
      if (this.report?.tipo === 'CONTEO FISICO') {
        this.editItemForm.stock_sistema = this.selectedStock
        this.calculateEditFromFisico()
      }
    },
    updateItem () {
      if (this.loading) return

      const isConteo = this.report?.tipo === 'CONTEO FISICO'
      const qty = Number(this.editItemForm.cantidad)
      const physicalQty = this.editItemForm.conteo_fisico

      if (isConteo) {
        if (physicalQty === null || physicalQty === undefined || physicalQty < 0) {
          this.$q.notify({ color: 'negative', message: 'Debe ingresar un conteo físico válido mayor o igual a 0' })
          return
        }
        if (qty === 0) {
          this.$q.notify({ color: 'warning', message: 'No hay diferencia en el conteo físico' })
          return
        }
      } else {
        if (qty <= 0) {
          this.$q.notify({ color: 'negative', message: 'La cantidad debe ser mayor a 0' })
          return
        }
        if (qty > this.selectedStock) {
          this.$q.notify({ color: 'negative', message: 'Stock insuficiente en la sucursal seleccionada' })
          return
        }
      }

      this.loading = true
      const data = { ...this.editItemForm }
      if (!isConteo) {
        data.cantidad = -Math.abs(data.cantidad)
      }
      if (data.agencia_id === 0) {
        data.agencia_id = null
      }

      this.$axios.put(`withdrawal-reports/${this.id}/items/${this.editingItem.id}`, data)
        .then(() => {
          this.$q.notify({ color: 'positive', message: 'Ítem actualizado correctamente' })
          this.showEditItemDialog = false
          this.getReport()
        }).catch(err => {
          console.error(err)
          this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al actualizar ítem' })
          this.loading = false
        })
    },
    setItemStatus (item, status) {
      this.loading = true
      this.$axios.put(`withdrawal-reports/${this.id}/items/${item.id}`, {
        cantidad: item.cantidad,
        tipo: item.tipo,
        agencia_id: item.agencia_id,
        estado: status,
        prorroga_hasta: null
      }).then(() => {
        this.$q.notify({ color: 'positive', message: 'Estado del producto actualizado' })
        this.getReport()
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al actualizar estado' })
      }).finally(() => {
        this.loading = false
      })
    },
    preparaEstadoDirecto (item, estado) {
      this.observarItem = item
      this.observarForm = {
        estado,
        admin_descripcion: item.admin_descripcion || ''
      }
      this.selectedMotivoObservacion = null
      this.showObservarDialog = true
    },
    preparaNota (item) {
      this.noteItem = item
      this.noteFormText = item.admin_descripcion || ''
      this.showNoteDialog = true
    },
    saveNote () {
      this.loading = true
      this.$axios.put(`withdrawal-reports/${this.id}/items/${this.noteItem.id}`, {
        cantidad: this.noteItem.cantidad,
        tipo: this.noteItem.tipo,
        agencia_id: this.noteItem.agencia_id,
        admin_descripcion: this.noteFormText,
        estado: this.noteItem.estado
      }).then(() => {
        this.$q.notify({ color: 'positive', message: 'Observación del administrador guardada con éxito' })
        this.showNoteDialog = false
        this.getReport()
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: err.response?.data?.message || 'Error al guardar la nota' })
      }).finally(() => {
        this.loading = false
      })
    },
    preparaProrroga (item) {
      this.prorrogaItem = item
      this.prorrogaForm = {
        prorroga_hasta: item.prorroga_hasta || '',
        admin_descripcion: item.admin_descripcion || ''
      }
      this.showProrrogaDialog = true
    },
    saveProrroga () {
      if (!this.prorrogaForm.prorroga_hasta) {
        this.$q.notify({ color: 'negative', message: 'Debe ingresar una fecha límite' })
        return
      }
      this.loading = true
      this.$axios.put(`withdrawal-reports/${this.id}/items/${this.prorrogaItem.id}`, {
        cantidad: this.prorrogaItem.cantidad,
        tipo: this.prorrogaItem.tipo,
        agencia_id: this.prorrogaItem.agencia_id,
        estado: 'PRORROGADO',
        prorroga_hasta: this.prorrogaForm.prorroga_hasta,
        admin_descripcion: this.prorrogaForm.admin_descripcion
      }).then(() => {
        this.$q.notify({ color: 'positive', message: 'Prórroga establecida correctamente' })
        this.showProrrogaDialog = false
        this.getReport()
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: 'Error al establecer prórroga' })
      }).finally(() => {
        this.loading = false
      })
    },
    preparaObservacion (item) {
      this.observarItem = item
      this.observarForm = {
        estado: item.estado === 'RECHAZADO' ? 'RECHAZADO' : 'OBSERVADO',
        admin_descripcion: item.admin_descripcion || ''
      }
      this.selectedMotivoObservacion = null
      this.showObservarDialog = true
    },
    onMotivoObservacionSelect (val) {
      if (val && val !== 'Otro (describir manualmente)') {
        this.observarForm.admin_descripcion = val
      } else {
        this.observarForm.admin_descripcion = ''
      }
    },
    onEstadoObservacionChange (val) {
      this.selectedMotivoObservacion = null
      this.observarForm.admin_descripcion = ''
    },
    saveObservacion () {
      if (!this.observarForm.admin_descripcion) {
        this.$q.notify({ color: 'negative', message: 'Debe ingresar un motivo u observación' })
        return
      }
      this.loading = true
      this.$axios.put(`withdrawal-reports/${this.id}/items/${this.observarItem.id}`, {
        cantidad: this.observarItem.cantidad,
        tipo: this.observarItem.tipo,
        agencia_id: this.observarItem.agencia_id,
        estado: this.observarForm.estado,
        admin_descripcion: this.observarForm.admin_descripcion,
        prorroga_hasta: null
      }).then(() => {
        this.$q.notify({ color: 'positive', message: 'Estado del producto actualizado' })
        this.showObservarDialog = false
        this.getReport()
      }).catch(err => {
        console.error(err)
        this.$q.notify({ color: 'negative', message: 'Error al guardar estado' })
      }).finally(() => {
        this.loading = false
      })
    },
    getRowClass (row) {
      switch (row.estado) {
        case 'ACEPTADO': return 'bg-aceptado'
        case 'OBSERVADO': return 'bg-observado'
        case 'RECHAZADO': return 'bg-rechazado'
        case 'PRORROGADO': return 'bg-prorrogado'
        case 'SUBSANADO': return 'bg-subsanado'
        default: return ''
      }
    },
    getBtnStatusColor (status) {
      switch (status) {
        case 'ACEPTADO': return 'positive'
        case 'OBSERVADO': return 'warning'
        case 'RECHAZADO': return 'negative'
        case 'PRORROGADO': return 'blue'
        case 'SUBSANADO': return 'teal'
        default: return 'grey-7'
      }
    },
    getBtnStatusLabel (status) {
      switch (status) {
        case 'ACEPTADO': return 'APROBADO'
        case 'OBSERVADO': return 'OBSERVADO'
        case 'RECHAZADO': return 'RECHAZADO'
        case 'PRORROGADO': return 'PRORROGADO'
        case 'SUBSANADO': return 'SUBSANADO'
        default: return 'PENDIENTE'
      }
    },
    getBtnStatusIcon (status) {
      switch (status) {
        case 'ACEPTADO': return 'check_circle'
        case 'OBSERVADO': return 'warning'
        case 'RECHAZADO': return 'block'
        case 'PRORROGADO': return 'hourglass_top'
        case 'SUBSANADO': return 'build'
        default: return 'help'
      }
    },

    generatePDF (withWatermark = false) {
      const doc = jsPDF('p', 'mm', 'letter')
      const pW = doc.internal.pageSize.getWidth()
      const pH = doc.internal.pageSize.getHeight()

      // Función para dibujar marca de agua y pie de página en cada hoja
      const drawCommonElements = (data) => {
        // --- MARCA DE AGUA "REVISADO" ---
        if (withWatermark) {
          doc.saveGraphicsState()
          doc.setGState(new doc.GState({ opacity: 0.4 }))
          doc.setTextColor(46, 204, 113)
          doc.setFontSize(120)
          doc.setFont('helvetica', 'bold')
          doc.text('REVISADO', pW * 0.7, pH * 0.75, { align: 'center', angle: 45 })
          doc.restoreGraphicsState()
        }

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
      doc.text(String(this.getReportDisplayTipo(this.report) || 'INFORME DE BAJAS').toUpperCase(), 14, 25)
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
        if (this.report?.tipo === 'CONTEO FISICO') {
          const dateStr = this.formatDate(item.created_at)
          const userStr = item.user?.name || (this.report.user?.name || 'N/A')
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
      this.generatePDF(true)
      const tituloUpper = String(this.getReportDisplayTipo(this.report) || 'INFORME DE BAJAS').toUpperCase()
      const text = `*${tituloUpper} #${this.report.id}*\n📍 *Agencia:* ${this.report.agencia?.nombre}\n📅 *Periodo:* ${this.getMesNombre(this.report.mes)} ${this.report.anio}\n👤 *Responsable:* ${this.report.user?.name}`
      window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank')
    },
    getExpiryColor (date) {
      if (!date || date === 'N/A') return 'grey-7'
      const expiry = moment(date)
      if (!expiry.isValid()) return 'grey-7'
      if (expiry.isBefore(moment())) return 'red-9'
      if (expiry.diff(moment(), 'months') < 3) return 'orange-9'
      return 'green-8'
    },
    getExpiryIcon (date) {
      if (!date || date === 'N/A') return 'help_outline'
      const expiry = moment(date)
      if (!expiry.isValid()) return 'help_outline'
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

.bg-aceptado {
  background-color: #e8f5e9 !important; /* Soft green */
}
.bg-observado {
  background-color: #fff3e0 !important; /* Soft orange */
}
.bg-rechazado {
  background-color: #ffebee !important; /* Soft red */
}
.bg-prorrogado {
  background-color: #e3f2fd !important; /* Soft blue */
}
.bg-subsanado {
  background-color: #e0f2f1 !important; /* Soft teal */
}
</style>
