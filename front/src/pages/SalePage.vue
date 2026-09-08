<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-12 col-md-8">
        <div class="row">
          <div class="col-12 col-md-6 bg-white">
            <q-input outlined v-model="search" label="Buscar producto" dense clearable @update:model-value="productsGet" debounce="500">
              <template v-slot:prepend>
                <q-icon name="search" class="cursor-pointer" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6 flex">
            <q-btn :loading="loading" icon="refresh" dense label="Actualizar" color="indigo" no-caps class="text-bold" @click="productsGet">
              <q-tooltip>Actualizar</q-tooltip>
            </q-btn>
             <q-btn
              class="q-ml-sm"
              icon="cloud_download"
              dense
              label="Pedido Online"
              color="teal"
              no-caps
              :loading="loadingPedidoOnline"
              @click="cargarPedidoOnline"
            >
              <q-tooltip>Cargar al carrito desde un número de pedido</q-tooltip>
            </q-btn>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="category" option-value="id" option-label="name" :options="categories"
                      @update:model-value="productsGet"
            >
            </q-select>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="subcategoria" option-value="id" option-label="name" :options="subcategories"
                      @update:model-value="productsGet"
                      label="Subcategoria"
            >
            </q-select>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                      :options="orders" map-options emit-value
                      option-value="value" option-label="label"
                      @update:model-value="productsGet"
            />
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Agencia" dense outlined v-model="agencia_id"
                      :options="agencias" map-options emit-value
                      option-value="id" option-label="nombre"
                      @update:model-value="productsGet"
                      :disable="!($store.user.id=='1')"
            />
          </div>
          <div class="col-12 flex flex-center">
            <q-pagination
              v-model="current_page"
              :max="last_page"
              :max-pages="6"
              boundary-numbers
              @update:model-value="productsGet"
            />
          </div>
          <div class="col-12">
            <q-card>
              <q-card-section class="q-pa-none">
                <div class="row cursor-pointer" v-if="products.length>0">
                  <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                  <q-card @click="clickAddSale(p)" class="q-pa-xs" flat bordered
                          :class="getProductCardClass(p)">
                    <q-img
                        :src="p.imagen.includes('http') ? p.imagen : `${$url}../images/${p.imagen}`"
                        width="100%"
                        height="160px"
                        fit="contain"
                        class="bg-white q-pa-sm"
                        style="transition: all 0.2s ease;"
                        :style="$store.productosVenta.find(item => item.id === p.id) ? 'border: 3px solid #21ba45;' : ''"
                      >
                        <q-badge color="red" floating style="padding: 5px 8px; margin: 0px; z-index: 15;" v-if="p.porcentaje">
                          {{p.porcentaje}}%
                        </q-badge>

                        <q-badge
                          color="positive"
                          text-color="white"
                          v-if="$store.productosVenta.find(item => item.id === p.id)"
                          style="position: absolute; top: 24px; right: -4px; padding: 5px 8px; margin: 0px; font-weight: bold; font-size: 12px; z-index: 15;"
                          class="shadow-2"
                        >
                          {{ $store.productosVenta.find(item => item.id === p.id)?.cantidadVenta }}
                        </q-badge>

                        <q-badge
                          color="black"
                          text-color="white"
                          v-if="p.cantidadReal <= 0"
                          style="position: absolute; top: -4px; left: -4px; padding: 5px 8px; margin: 0px; font-weight: bold; font-size: 11px; z-index: 15;"
                        >
                          SIN STOCK
                        </q-badge>

                        <div
                          v-if="$store.productosVenta.find(item => item.id === p.id)"
                          class="absolute-full"
                          style="background: rgba(33, 186, 69, 0.10); z-index: 5;"
                        ></div>

                        <div class="absolute-bottom text-center text-subtitle2"
                            style="padding: 4px 0px; line-height: 1.1; background: rgba(0,0,0,0.6); z-index: 10;">
                          {{p.nombre}}
                        </div>
                      </q-img>
                      <q-card-section class="q-pa-none q-ma-none">
                        <div
                          class="product-sale-price-card"
                          :class="{ 'product-sale-price-card--offer': Number(p.porcentaje) > 0 }"
                        >
                          <div class="product-sale-price-card__heading">
                            <q-icon name="point_of_sale" />
                            <span>Precio de venta</span>
                          </div>
                          <div class="product-sale-price-card__amount">
                            <small>Bs</small>
                            <strong>{{ formatCurrency(p.precioVenta ?? p.precio) }}</strong>
                          </div>
                          <div v-if="Number(p.porcentaje) > 0" class="product-sale-price-card__comparison">
                            <span class="product-sale-price-card__metric">
                              <small>Antes</small>
                              <s>Bs {{ formatCurrency(p.precio) }}</s>
                            </span>
                            <span class="product-sale-price-card__metric product-sale-price-card__metric--saving">
                              <small><q-icon name="savings" /> Ahorro</small>
                              <strong>Bs {{ ahorroProducto(p) }}</strong>
                            </span>
                          </div>
                        </div>
                        <div :class="getStockTextClass(p)" class="flex items-center justify-center">
                          <span>{{ p.cantidadReal }} {{ $q.screen.lt.md?'Dis':'Disponible' }}</span>
                          <q-btn
                            flat
                            round
                            dense
                            size="xs"
                            color="primary"
                            icon="zoom_in"
                            class="q-ml-xs"
                            @click.stop.prevent="verImagenCompleta($event, p)"
                          >
                            <q-tooltip>Ver foto / Zoom</q-tooltip>
                          </q-btn>
                        </div>
                        <div v-if="$store.user?.agencia_id == 1 && p.cantidadAlmacen !== undefined"
                          class="text-center text-caption text-lead">
                        Stock Almacén: {{ p.cantidadAlmacen }}
                      </div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>
                <q-card v-else>
                  <q-card-section>
                    <div class="row">
                      <div class="col-12 flex flex-center">
                        <q-avatar size="150px" font-size="150px" color="white" text-color="grey" icon="view_in_ar" />
                      </div>
                      <div class="col-12">
                        <div class="text-bold text-grey text-center">No encontramos productos para tu búsqueda.</div>
                        <div class="text-bold text-grey text-center">Intenta con otra palabra o agrega productos a tu Inventario.</div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section class="q-pa-none q-ma-none ">
            <div class="row">
              <div class="col-6 text-h6 q-pt-xs q-pl-lg">
                Canasta
                ({{ $store.productosVenta.length }})
              </div>
              <div class="col-6 text-right"><q-btn class="text-subtitle1 text-blue-10 text-bold" style="text-decoration: underline;" label="Vaciar canasta" @click="vaciarCanasta" no-caps flat outline/></div>
            </div>
          </q-card-section>
          <q-separator></q-separator>
          <q-card-section class="q-pa-none q-ma-none" >
            <div v-if="$store.productosVenta.length==0" class="flex flex-center q-pa-lg">
              <q-icon name="o_shopping_basket" color="grey" size="100px"/>
              <div class="q-pa-lg text-grey text-center noSelect">
                Aún no tienes productos en tu canasta. Haz clic sobre un producto para agregarlo.
              </div>
            </div>
            <q-scroll-area v-else style="height: 400px;">
              <q-table dense flat bordered hide-bottom hide-header :rows="$store.productosVenta" :columns="columnsProductosVenta" :rows-per-page-options="[0]">
                <template v-slot:body="props">
                  <q-tr :props="props">
                    <q-td key="borrar" :props="props" style="padding: 0px;margin: 0px" auto-width>
                      <q-btn flat dense @click="deleteProductosVenta(props.row,props.pageIndex)" icon="delete" color="red" size="10px" class="q-pa-none q-ma-none" />
                    </q-td>
                    <q-td key="nombre" :props="props">
                      <div>
                        <q-img :src="props.row.imagen.includes('http')?props.row.imagen:`${$url}../images/${props.row.imagen}`"
                               width="40px" height="40px"
                               style="padding: 0px; margin: 0px; border-radius: 0px;position: absolute;crop: auto;object-fit: cover;"
                        />
                        <div style="padding-left: 42px">
                          <div class="text-caption" style="max-width: 120px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">
                            {{props.row.nombre}}
                          </div>
                          <div class="text-grey">Stock Real: {{props.row.cantidadReal}}
                            (
                            <span style="font-size: 10px">{{props.row.precio ? formatCurrency(props.row.precio) : props.row.precio}} Bs </span>
                            <span style="font-size: 10px" class="text-red text-bold" v-if="props.row.porcentaje">{{formatCurrency(props.row.precioVenta)}} Bs</span>
                            )
                          </div>
                          <div class="cart-sale-price-label">
                            <q-icon name="sell" />
                            Precio de venta
                          </div>
                          <q-input
                            v-model="props.row.precioVenta"
                            class="cart-sale-price-input"
                            step="0.1"
                            type="number"
                            dense
                            outlined
                            readonly
                          >
                            <template v-slot:prepend>
                              <div style="font-size: 10px">Bs.</div>
                            </template>
                          </q-input>
                        </div>
                      </div>
                    </q-td>
                    <q-td key="cantidadVenta" :props="props">
                      <q-input dense outlined bottom-slots min="1"
                               v-model="props.row.cantidadVenta"
                               @update:model-value="cambioNumero(props.row,props.pageIndex)"
                               :rules="ruleNumber"
                               type="number"
                               input-class="text-center"
                               required>
                        <template v-slot:prepend>
                          <q-btn style="cursor: pointer" dense flat icon="remove_circle_outline" @click="removeCantidad(props.row,props.pageIndex)"/>
                        </template>
                        <template v-slot:append>
                          <q-btn style="cursor: pointer" dense flat icon="add_circle_outline" @click="addCantidad(props.row,props.pageIndex)"/>
                        </template>
                      </q-input>
                      <div class="text-grey">= Bs {{redondeo(props.row.cantidadVenta*props.row.precioVenta)}}</div>
                    </q-td>
                    <q-td key="lotes" :props="props">
                      <div v-for="(lote, index) in props.row.buys" :key="index" class="q-mb-xs">
                        <q-badge color="blue-10" text-color="white" class="q-mr-xs">{{ lote.lote }}</q-badge>
                        <span class="text-grey-8" style="font-size: 12px;">
                          {{ lote.cantidadVendida }}u | {{ lote.price }} Bs <br>
                          {{ lote.dateExpiry }} <input type="number" min="1" v-model="lote.cantidadAVender" style="width: 50px; text-align: center; padding: 0px; margin: 0px; border-radius: 0px; border: 1px solid #ccc;" class="q-pa-xs" required>
                        </span>
                      </div>
                    </q-td>
                  </q-tr>
                </template>
              </q-table>
            </q-scroll-area>
          </q-card-section>
          <q-card-section>
            <div class="sale-summary-card">
              <div class="sale-summary-total">
                <div class="sale-summary-total__identity">
                  <div class="sale-summary-icon">
                    <q-icon name="payments" size="24px" />
                  </div>
                  <div>
                    <div class="sale-summary-eyebrow">RESUMEN DE VENTA</div>
                    <div class="sale-summary-title">TOTAL A COBRAR</div>
                  </div>
                </div>
                <div class="sale-summary-amount">
                  <span class="sale-summary-currency">Bs</span>
                  <span>{{totalCanasta}}</span>
                </div>
              </div>

              <div class="sale-summary-details">
                <div class="sale-summary-details__title">Detalle del cálculo</div>

                <div class="sale-summary-row">
                  <div class="sale-summary-label">
                    <q-icon name="inventory_2" />
                    <span>Referencias en canasta</span>
                  </div>
                  <div class="sale-summary-value">{{$store.productosVenta.length}}</div>
                </div>

                <div class="sale-summary-row">
                  <div class="sale-summary-label">
                    <q-icon name="sell" />
                    <span>Descuentos del sistema</span>
                    <q-icon name="info_outline" size="15px" class="sale-summary-info">
                      <q-tooltip>Descuentos automáticos aplicados a los productos</q-tooltip>
                    </q-icon>
                  </div>
                  <div class="sale-summary-value sale-summary-value--discount">− Bs {{totalDescuentoSistema}}</div>
                </div>

                <div class="sale-summary-row">
                  <div class="sale-summary-label">
                    <q-icon name="receipt_long" />
                    <span>Monto sin descuentos</span>
                  </div>
                  <div class="sale-summary-value">Bs {{totalSinDescuentos}}</div>
                </div>

              </div>
            </div>

            <q-btn @click="clickSale" class="full-width q-mt-sm sale-confirm-button" no-caps label="Confirmar venta"
                   :color="$store.productosVenta.length==0?'grey':'warning'"
                   :disable="$store.productosVenta.length==0"
                   :loading="loading"/>

            <!-- ✅ Alerta de productos que sobrepasaron stock -->
            <div v-if="productosSobrepasaronStock.length > 0" class="q-mt-sm">
              <q-banner dense class="bg-red-1 text-red-9">
                <template v-slot:avatar>
                  <q-icon name="warning" color="red" />
                </template>
                <div class="text-caption text-bold">Productos que exceden el stock disponible:</div>
                <div v-for="producto in productosSobrepasaronStock" :key="producto.nombre" class="text-caption">
                  • {{ producto.nombre }}: Solicitado {{ producto.cantidadSolicitada }}, Disponible {{ producto.stockDisponible }}
                </div>
                <div class="text-caption text-bold q-mt-xs">Por favor ajuste las cantidades antes de confirmar la venta.</div>
              </q-banner>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <q-dialog v-model="saleDialog" persistent>
      <q-card style="width: 850px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Realizar venta</div>
          <q-space />
          <q-btn
            :flat="!clientDisplayVisible"
            :unelevated="clientDisplayVisible"
            no-caps
            dense
            :icon="clientDisplayVisible ? 'cast_connected' : 'cast'"
            :label="$q.screen.gt.sm
              ? (clientDisplayVisible ? 'Mostrando al cliente' : 'Mostrar al cliente')
              : (clientDisplayVisible ? 'En pantalla' : '')"
            :color="clientDisplayVisible ? 'positive' : 'indigo'"
            class="q-mr-sm client-display-button"
            :class="{ 'client-display-button--active': clientDisplayVisible }"
            @click="toggleClientDisplay"
          >
            <q-badge
              v-if="clientDisplayVisible"
              rounded
              floating
              color="light-green-3"
              class="client-display-live-badge"
            />
            <q-tooltip>
              {{ clientDisplayVisible
                ? 'La información está visible para el cliente. Clic para ocultarla.'
                : 'Mostrar la información en la pantalla del cliente.' }}
            </q-tooltip>
          </q-btn>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="saleInsert">
          <q-card-section>
            <div class="row">
              <div class="col-6 col-md-3">
                <q-input outlined dense label="NIT/CARNET" @keyup="searchClient" required v-model="client.numeroDocumento" :loading="loadingClientSearch" />
              </div>
              <div class="col-6 col-md-3">
                <q-input outlined dense label="Complemento" @keyup="searchClientComplemento" v-model="client.complemento" style="text-transform: uppercase" :loading="loadingClientSearch" />
              </div>
              <div class="col-12 col-md-6">
                <q-input outlined dense label="Nombre Razon Social" required v-model="client.nombreRazonSocial" />
              </div>
              <div class="col-12 col-md-6">
                <q-select v-model="document" outlined dense :options="documents" />
              </div>
              <div class="col-12 col-md-6">
                <q-input outlined dense label="Email"  v-model="client.email" type="email" />
              </div>
            </div>
          </q-card-section>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col-6 col-md-2">
                <q-input outlined dense label="TOTAL A PAGAR:" readonly v-model="totalFinal" :rules="ruleNumber"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="EFECTIVO BS." v-model="efectivo" type="number" step="0.1" :rules="ruleNumber"
                         @update:model-value="calcularCambio"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="Aporte" v-model="aporte" type="number" step="0.01" :rules="ruleNumber"
                          @update:model-value="onAporteChange"/>
              </div>

              <!-- Campo de descuento manual -->
              <div class="col-6 col-md-2">
                <q-input
                  outlined
                  dense
                  label="Descuento (Bs.)"
                  v-model="descuento"
                  type="number"
                  step="0.01"
                  :rules="ruleNumber"
                  @update:model-value="actualizarDesdeMonto"
                >
                  <template v-slot:append>
                    <q-icon name="attach_money" class="cursor-pointer">
                      <q-tooltip>Descuento adicional en monto fijo</q-tooltip>
                    </q-icon>
                  </template>
                </q-input>
              </div>

              <!-- Campo de descuento porcentual -->
              <div class="col-6 col-md-2">
                <q-input
                  outlined
                  dense
                  label="Descuento (%)"
                  v-model="descuentoPorcentaje"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  :rules="rulePorcentaje"
                  @update:model-value="actualizarDesdePorcentaje"
                >
                  <template v-slot:append>
                    <q-icon name="percent" class="cursor-pointer">
                      <q-tooltip>Descuento adicional en porcentaje</q-tooltip>
                    </q-icon>
                  </template>
                  <template v-slot:hint>
                    <div class="text-caption text-grey">
                      {{ calcularMontoDesdePorcentaje() }} Bs.
                    </div>
                  </template>
                </q-input>
              </div>

              <!-- CAMPO DE CAMBIO CORREGIDO -->
              <div class="col-6 col-md-2">
                <q-input outlined dense label="CAMBIO:" readonly v-model="cambioCalculado"
                         :bg-color="cambioCalculado < 0 ? 'red' : 'green'"
                         label-color="white"/>
              </div>
              <div class="col-6 col-md-2">
                <q-select dense outlined v-model="metodoPago" label="Metodo de pago"
                          :options="$metodoPago" hint="Metodo de pago del gasto" />
              </div>
            </div>

            <!-- Campos para método de pago personalizado -->
            <div v-if="metodoPago === 'Personalizado'" class="row q-col-gutter-sm q-mt-sm q-pa-sm bg-purple-1 rounded-borders items-center">
              <div class="col-6 col-md-3">
                <q-input outlined dense label="Monto en Efectivo" v-model.number="montoEfectivoPersonalizado" type="number" step="0.1"
                         @update:model-value="onMontoEfectivoChange">
                  <template v-slot:prepend>
                    <q-icon name="payments" color="green" />
                  </template>
                </q-input>
              </div>
              <div class="col-6 col-md-3">
                <q-input outlined dense label="Monto en QR" v-model.number="montoQrPersonalizado" type="number" step="0.1"
                         @update:model-value="onMontoQrChange">
                  <template v-slot:prepend>
                    <q-icon name="qr_code" color="blue" />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-md-6 text-purple text-bold flex items-center">
                <q-icon name="info" class="q-mr-xs" size="20px" />
                Pago Dividido: La suma debe ser exactamente {{ totalFinal }} Bs.
              </div>
            </div>

            <!-- Pago con QR (Baneco) -->
            <div v-if="metodoPago === 'Qr' || (metodoPago === 'Personalizado' && montoQrPersonalizado > 0)"
                 class="row q-col-gutter-sm q-mt-sm q-pa-sm bg-blue-1 rounded-borders items-center">
              <div class="col-12" v-if="!qrId">
                <q-btn
                  class="full-width"
                  color="primary"
                  icon="qr_code_2"
                  no-caps
                  :label="`Generar QR por ${montoParaQr} Bs.`"
                  :loading="qrGenerando"
                  :disable="montoParaQr <= 0"
                  @click="generarQr"
                />
              </div>
              <template v-else>
                <div class="col-12 col-md-4 text-center">
                  <img :src="qrImage" alt="QR de pago" style="max-width: 180px; width: 100%;" />
                </div>
                <div class="col-12 col-md-8">
                  <div class="text-blue-9 text-bold q-mb-xs flex items-center">
                    <q-spinner-dots color="primary" size="20px" class="q-mr-xs" />
                    Esperando confirmación de pago...
                  </div>
                  <div class="text-caption text-grey-8 q-mb-sm">
                    El cliente debe escanear el código QR con su app bancaria para pagar {{ montoParaQr }} Bs.
                  </div>
                  <q-btn
                    color="negative"
                    icon="cancel"
                    no-caps
                    label="Cancelar QR"
                    :loading="qrCancelando"
                    @click="cancelarQr"
                  />
                </div>
              </template>
            </div>

            <!-- Información de descuentos y total final -->
            <div class="row q-mt-sm" v-if="descuento > 0 || Number(totalDescuentoSistema) > 0">
              <div class="col-12">
                <q-banner dense class="bg-blue-1 text-blue-9">
                  <template v-slot:avatar>
                    <q-icon name="discount" color="blue" />
                  </template>
                  <div class="text-caption">
                    <strong>Descuento adicional aplicado:</strong> {{ formatCurrency(descuento) }} Bs.
                    <span v-if="descuentoPorcentaje > 0">({{ descuentoPorcentaje }}% del total sin descuentos)</span>
                  </div>
                  <div class="text-caption">
                    <strong>Total sin descuentos:</strong> {{ totalSinDescuentos }} Bs.<br>
                    <strong>Descuento del sistema:</strong> {{ totalDescuentoSistema }} Bs.<br>
                    <strong>Total con descuento del sistema:</strong> {{ totalConDescuentoSistema }} Bs.<br>
                    <strong>Total a pagar:</strong> {{ totalFinal }} Bs.
                  </div>
                </q-banner>
              </div>
            </div>
          </q-card-section>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col-6">
                <q-btn type="submit" class="full-width" icon="o_add_circle" label="Realizar venta" :loading="loading"
                       :disable="!!qrId" no-caps color="green"  />
              </div>
              <div class="col-6">
                <q-btn class="full-width" icon="undo" v-close-popup label="Atras" no-caps color="red" :loading="loading" />
              </div>
            </div>
          </q-card-section>
        </q-form>
      </q-card>
    </q-dialog>
    <div id="myElement" class="hidden"></div>
    <!-- Diálogo para ver imagen completa del producto con Zoom interactivo -->
    <q-dialog v-model="dialogImagenCompleta" @show="resetZoom">
      <q-card style="max-width: 92vw; max-height: 92vh; min-width: 320px; background: rgba(18, 18, 18, 0.95); color: white; border: 1px solid #333;" class="q-pa-sm text-center">
        <q-bar class="bg-transparent text-white q-mb-xs">
          <div class="text-subtitle1 text-bold ellipsis" style="max-width: 45vw;">{{ productoImagenSeleccionado?.nombre }}</div>
          <q-space />

          <!-- Controles de Zoom -->
          <div class="row items-center q-gutter-xs q-mr-sm">
            <q-btn dense flat round icon="zoom_out" color="white" size="sm" @click="zoomOut" :disable="zoomScale <= 0.6">
              <q-tooltip>Alejar (-)</q-tooltip>
            </q-btn>
            <span class="text-caption text-grey-4 text-bold" style="min-width: 40px;">{{ Math.round(zoomScale * 100) }}%</span>
            <q-btn dense flat round icon="zoom_in" color="white" size="sm" @click="zoomIn" :disable="zoomScale >= 4">
              <q-tooltip>Acercar (+)</q-tooltip>
            </q-btn>
            <q-btn dense flat round icon="restart_alt" color="white" size="sm" @click="resetZoom">
              <q-tooltip>Restablecer tamaño original</q-tooltip>
            </q-btn>
          </div>

          <q-btn dense flat icon="close" v-close-popup color="white" round>
            <q-tooltip>Cerrar</q-tooltip>
          </q-btn>
        </q-bar>

        <!-- Contenedor con soporte de Rueda del mouse (Wheel Zoom), Arrastre (Drag) y Doble Clic -->
        <div
          class="img-zoom-container flex flex-center q-pa-xs"
          @wheel.prevent="onWheelZoom"
          @mousedown="startDrag"
          @mousemove="onDrag"
          @mouseup="stopDrag"
          @mouseleave="stopDrag"
          @dblclick="toggleDblClickZoom"
          :style="{ cursor: zoomScale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in' }"
          style="overflow: hidden; max-height: 75vh; max-width: 86vw; min-height: 280px; position: relative; user-select: none;"
        >
          <img
            :src="urlImagenCompleta"
            :style="{
              transform: `translate(${panX}px, ${panY}px) scale(${zoomScale})`,
              transition: isDragging ? 'none' : 'transform 0.15s ease-out',
              maxHeight: '70vh',
              maxWidth: '80vw',
              objectFit: 'contain',
              pointerEvents: 'none'
            }"
            class="rounded-borders"
            alt="Producto"
          />
        </div>

        <div class="q-mt-xs text-caption text-grey-5 row justify-between items-center q-px-sm">
          <span>💡 <b>Rueda del mouse</b> o botones para Zoom • <b>Arrastra</b> para mover</span>
          <span class="text-white text-bold">
            Precio: {{ productoImagenSeleccionado?.precio ? formatCurrency(productoImagenSeleccionado.precio) : productoImagenSeleccionado?.precio }} Bs
            <span v-if="productoImagenSeleccionado?.porcentaje" class="text-red-4 q-ml-xs">
              ({{ formatCurrency(productoImagenSeleccionado.precioVenta) }} Bs)
            </span>
          </span>
        </div>
      </q-card>
    </q-dialog>

    <!-- Configuración discreta del equipo. Requiere contraseña para modificarla. -->
    <q-btn
      class="terminal-config-trigger"
      flat
      round
      dense
      size="xs"
      icon="settings"
      :aria-label="caja_numero ? `Configurar terminal Caja ${caja_numero}` : 'Configurar terminal'"
      @click="abrirConfigTerminal(false)"
    >
      <q-tooltip>
        {{ caja_numero ? `Terminal configurada: Caja ${caja_numero}` : 'Terminal sin configurar' }}
      </q-tooltip>
    </q-btn>
  </q-page>
</template>

<script>
import { Imprimir } from 'src/addons/Imprimir'
import { formatCurrency as formatMoney, formatPayable, roundCurrency, roundPayable } from 'src/utils/money'

const TERMINAL_CONFIG_PASSWORD = '2202'
const CAJA_STORAGE_KEY = 'caja_numero'
const CAJAS_VALIDAS = [1, 2, 3, 4]
const EVENTOS_PANTALLA_CLIENTE = new Set([
  'clienteDisplayData',
  'clienteQrData',
  'clienteSaleComplete',
  'clienteDisplayClose'
])

function normalizarCaja (value) {
  const caja = Number.parseInt(value, 10)
  return CAJAS_VALIDAS.includes(caja) ? caja : null
}

function redondearMoneda (value) {
  return roundCurrency(value)
}

export default {
  name: 'SalePage',
  data () {
    return {
      agencia_id: parseInt(localStorage.getItem('agencia_id')),
      caja_numero: normalizarCaja(localStorage.getItem(CAJA_STORAGE_KEY)),
      terminalConfigDialogOpen: false,
      cajasOptions: [
        { label: 'Caja 1', value: 1 },
        { label: 'Caja 2', value: 2 },
        { label: 'Caja 3', value: 3 },
        { label: 'Caja 4', value: 4 }
      ],
      saleDialog: false,
      saleCompleted: false,
      dialogImagenCompleta: false,
      productoImagenSeleccionado: null,
      zoomScale: 1,
      panX: 0,
      panY: 0,
      isDragging: false,
      dragStartX: 0,
      dragStartY: 0,
      clientDisplayVisible: false,
      clientDisplayWindow: null,
      client: {},
      aporte: 0,
      descuento: 0,
      descuentoPorcentaje: 0,
      qr: false,
      qrId: null,
      qrImage: null,
      qrGenerando: false,
      qrCancelando: false,
      documents: [],
      metodoPago: 'Efectivo',
      montoEfectivoPersonalizado: 0,
      montoQrPersonalizado: 0,
      document: {},
      current_page: 1,
      last_page: 1,
      loadingPedidoOnline: false,
      loadingClientSearch: false,
      ruleNumber: [
        val => (val !== null && val !== '') || 'Por favor escriba su cantidad',
        val => (val >= 0 && val < 10000) || 'Por favor escriba una cantidad real'
      ],
      rulePorcentaje: [
        val => (val !== null && val !== '') || 'Por favor escriba el porcentaje',
        val => (val >= 0 && val <= 100) || 'El porcentaje debe estar entre 0 y 100'
      ],
      search: '',
      efectivo: '',
      loading: false,
      products: [],
      totalProducts: 0,
      agencias: [],
      product: { cantidad: 0, nombre: '', barra: '', costo: 0, precio: 0, descripcion: '', category_id: 0 },
      category: 0,
      categories: [
        { name: 'Ver todas las categorias', id: 0 }
      ],
      categoriesTable: [],
      categorySelected: {},
      categoriesTableColumns: [
        { name: 'name', label: 'Nombre', field: 'name', align: 'left', sortable: true },
        { name: 'actions', label: 'Acciones', field: 'actions', align: 'right', sortable: false }
      ],
      order: 'cantidad desc',
      columnsProductosVenta: [
        { label: 'borrar', field: 'borrar', name: 'borrar', align: 'left' },
        { label: 'nombre', field: 'nombre', name: 'nombre', align: 'left' },
        { label: 'cantidadVenta', field: 'cantidadVenta', name: 'cantidadVenta' }
      ],
      orders: [
        { label: 'Ordenar por', value: 'id' },
        { label: 'Menor precio', value: 'precio asc' },
        { label: 'Mayor precio', value: 'precio desc' },
        { label: 'Menor cantidad', value: 'cantidad asc' },
        { label: 'Mayor cantidad', value: 'cantidad desc' },
        { label: 'Orden alfabetico', value: 'nombre asc' }
      ],
      subcategories: [],
      subcategoria: ''
    }
  },
  watch: {
    'client.numeroDocumento' () { this.syncClientDisplayDebounced() },
    'client.complemento' () { this.syncClientDisplayDebounced() },
    'client.nombreRazonSocial' () { this.syncClientDisplayDebounced() },
    'client.email' () { this.syncClientDisplayDebounced() },
    document: {
      handler () { this.syncClientDisplayDebounced() },
      deep: true
    },

    saleDialog (val) {
      if (val) {
        this.saleCompleted = false
        this.clientDisplayVisible = false
      } else {
        // Diálogo cerrado (botón X, Atrás, o después de venta)
        if (this.clientDisplayVisible) {
          this.clientDisplayVisible = false
          this.clearClientDisplayData()
        }
        this.resetQrState()
      }
    },

    metodoPago (val, oldVal) {
      if (val === 'Personalizado') {
        this.montoEfectivoPersonalizado = parseFloat(this.totalFinal || 0)
        this.montoQrPersonalizado = 0
      }
      // El QR generado quedó atado a un monto/modo anterior: se invalida al cambiar
      if (oldVal !== val && this.qrId) {
        this.cancelarQr()
      }
    },

    totalFinal (val) {
      if (this.metodoPago === 'Personalizado') {
        this.montoEfectivoPersonalizado = parseFloat(val || 0)
        this.montoQrPersonalizado = 0
      }
    }
  },
  mounted () {
    this.productsGet()
    this.catalogosGet()
    this.verificarConfiguracionTerminal()
    window.addEventListener('keydown', this.handleSecretTerminalKey)
    window.addEventListener('storage', this.handleTerminalStorageChange)
  },
  beforeUnmount () {
    window.removeEventListener('keydown', this.handleSecretTerminalKey)
    window.removeEventListener('storage', this.handleTerminalStorageChange)
    this.closeClientDisplay()
    this.detenerPollingQr()
  },
  computed: {
    urlImagenCompleta () {
      if (!this.productoImagenSeleccionado?.imagen) return ''
      const img = this.productoImagenSeleccionado.imagen
      return img.includes('http') ? img : `${this.$url}../images/${img}`
    },
    // ✅ PRODUCTOS QUE SOBREPASARON STOCK (se muestra en tiempo real)
    productosSobrepasaronStock () {
      const productos = []

      this.$store.productosVenta.forEach(product => {
        const stockDisponible = product.cantidadReal
        if (product.cantidadVenta > stockDisponible) {
          productos.push({
            nombre: product.nombre,
            cantidadSolicitada: product.cantidadVenta,
            stockDisponible
          })
        }
      })

      return productos
    },

    // Total SIN NINGÚN descuento (precio original * cantidad)
    totalSinDescuentos () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        const precio = roundCurrency(p.precio)
        s = s + parseFloat(precio * p.cantidadVenta)
      })
      return formatMoney(s)
    },

    // Descuento aplicado por el sistema (diferencia entre precio original y precio con descuento)
    totalDescuentoSistema () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        const precioOriginal = roundCurrency(p.precio)
        const precioConDescuento = roundCurrency(p.precioVenta)
        s = s + ((precioOriginal - precioConDescuento) * p.cantidadVenta)
      })
      return formatMoney(s)
    },

    // Total CON descuento del sistema (lo que ya venías usando)
    totalConDescuentoSistema () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        const precioConDescuento = roundCurrency(p.precioVenta)
        s = s + parseFloat(precioConDescuento * p.cantidadVenta)
      })
      return formatMoney(s)
    },

    // Resultado exacto antes del único redondeo comercial.
    totalCalculado () {
      const totalConSistema = parseFloat(this.totalConDescuentoSistema)
      const descAdicional = redondearMoneda(this.descuento)
      const aporte = redondearMoneda(this.aporte)
      return formatMoney(totalConSistema + aporte - descAdicional)
    },

    totalFinal () {
      return formatPayable(this.totalCalculado)
    },

    totalCanasta () {
      return formatPayable(this.totalConDescuentoSistema)
    },

    // CAMBIO CALCULADO CORREGIDO
    cambioCalculado () {
      const efectivo = parseFloat(this.efectivo || 0)
      const totalFinal = parseFloat(this.totalFinal || 0)

      // Fórmula corregida: (Efectivo - Aporte) - Total Final
      const cambio = efectivo - totalFinal

      return roundPayable(cambio)
    },

    totalganancia () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        const precio = roundCurrency(p.precio)
        s = s + (precio - roundCurrency(p.precioVenta)) * p.cantidadVenta
      })
      return formatMoney(s)
    },

    // Mantener compatibilidad con código existente
    total () {
      return this.totalConDescuentoSistema
    },

    // Monto a cobrar por QR: el total completo, o solo la parte QR en pago dividido
    montoParaQr () {
      if (this.metodoPago === 'Personalizado') {
        return parseFloat(this.montoQrPersonalizado || 0)
      }
      return parseFloat(this.totalFinal || 0)
    }
  },
  methods: {
    formatCurrency (value) {
      return formatMoney(value)
    },
    ahorroProducto (product) {
      const precioAnterior = Number(product?.precio ?? 0)
      const precioVenta = Number(product?.precioVenta ?? product?.precio ?? 0)
      return formatMoney(Math.max(0, precioAnterior - precioVenta))
    },
    // ✅ VERIFICAR STOCK AL CONFIRMAR VENTA
    verificarStockCanasta () {
      const productosSinStock = []

      this.$store.productosVenta.forEach(product => {
        const stockDisponible = product.cantidadReal
        if (product.cantidadVenta > stockDisponible) {
          productosSinStock.push({
            nombre: product.nombre,
            cantidadSolicitada: product.cantidadVenta,
            stockDisponible
          })
        }
      })

      return productosSinStock
    },

    // Clases dinámicas para productos
    getProductCardClass (product) {
      if (product.cantidadReal <= 0) {
        return 'bg-grey-3 cursor-not-allowed'
      }
      return 'bg-white cursor-pointer'
    },

    getStockTextClass (product) {
      if (product.cantidadReal <= 0) {
        return 'text-center text-bold text-red'
      } else if (product.cantidadReal <= 5) {
        return 'text-center text-bold text-orange'
      }
      return 'text-center text-bold'
    },

    // Método para calcular el cambio
    calcularCambio () {
      this.$forceUpdate()
    },

    onAporteChange () {
      if (this.qrId) this.cancelarQr()
      this.calcularCambio()
    },

    onMontoEfectivoChange (val) {
      const total = parseFloat(this.totalFinal || 0)
      const efe = parseFloat(val || 0)
      this.montoQrPersonalizado = Math.max(0, redondearMoneda(total - efe))
      // El monto del QR cambió: el QR ya generado quedó desactualizado
      if (this.qrId) this.cancelarQr()
    },

    onMontoQrChange (val) {
      const total = parseFloat(this.totalFinal || 0)
      const qr = parseFloat(val || 0)
      this.montoEfectivoPersonalizado = Math.max(0, redondearMoneda(total - qr))
      // El monto del QR cambió: el QR ya generado quedó desactualizado
      if (this.qrId) this.cancelarQr()
    },

    // Actualizar descuento desde monto fijo
    actualizarDesdeMonto (nuevoMonto) {
      if (nuevoMonto === '' || nuevoMonto === null) {
        this.descuento = 0
        this.descuentoPorcentaje = 0
      } else {
        this.descuento = redondearMoneda(nuevoMonto)

        // Calcular el porcentaje equivalente sobre el total SIN descuentos
        if (this.totalSinDescuentos > 0) {
          this.descuentoPorcentaje = parseFloat(((this.descuento / this.totalSinDescuentos) * 100).toFixed(2))
        } else {
          this.descuentoPorcentaje = 0
        }
      }
      if (this.qrId) this.cancelarQr()
      this.calcularCambio()
    },

    // Actualizar descuento desde porcentaje
    actualizarDesdePorcentaje (nuevoPorcentaje) {
      if (nuevoPorcentaje === '' || nuevoPorcentaje === null) {
        this.descuentoPorcentaje = 0
        this.descuento = 0
      } else {
        this.descuentoPorcentaje = parseFloat(nuevoPorcentaje)

        // Calcular el monto equivalente sobre el total SIN descuentos
        this.descuento = redondearMoneda(this.totalSinDescuentos * (this.descuentoPorcentaje / 100))
      }
      if (this.qrId) this.cancelarQr()
      this.calcularCambio()
    },

    // Calcular monto desde porcentaje (para el hint)
    calcularMontoDesdePorcentaje () {
      if (this.descuentoPorcentaje > 0 && this.totalSinDescuentos > 0) {
        return formatMoney(this.totalSinDescuentos * (this.descuentoPorcentaje / 100))
      }
      return '0.00'
    },

    // Categorías, subcategorías, agencias y documentos en una sola petición,
    // cacheada en el store: al volver a esta página ya no se pide nada.
    async catalogosGet () {
      await this.$store.fetchCatalogos(this.$axios, [
        'categories',
        'subcategories',
        'agencias',
        'documents'
      ])

      this.categories = [{ name: 'Ver todas las categorias', id: 0 }, ...this.$store.categories]
      this.categoriesTable = this.$store.categories
      this.subcategories = this.$store.subcategories
      this.agencias = [{ nombre: 'Selecciona una agencia', id: 0 }, ...this.$store.agencias]

      this.documents = this.$store.documents.map(r => ({ ...r, label: r.descripcion }))
      this.document = this.documents[0]
    },

    saleInsert () {
      const totalVenta = redondearMoneda(this.totalFinal)
      if (this.metodoPago === 'Personalizado') {
        const totalDividido = redondearMoneda(
          redondearMoneda(this.montoEfectivoPersonalizado) + redondearMoneda(this.montoQrPersonalizado)
        )
        if (totalDividido !== totalVenta) {
          this.$alert.error('La suma del pago en efectivo y QR debe ser igual al total de la venta.')
          return
        }
      }

      this.loading = true
      this.detenerPollingQr()
      this.client.codigoTipoDocumentoIdentidad = this.document.codigoClasificador
      this.$store.productosVenta.forEach(p => {
        p.precioVenta = redondearMoneda(p.precioVenta)
        p.subTotal = redondearMoneda(p.cantidadPedida * p.precioVenta)
      })
      const data = {
        montoTotal: this.totalFinal,
        client: this.client,
        aporte: redondearMoneda(this.aporte),
        descuento: redondearMoneda(this.descuento),
        qr: this.qr,
        qrId: this.qrId,
        efectivo: this.efectivo,
        products: this.$store.productosVenta,
        metodoPago: this.metodoPago,
        montoEfectivo: this.metodoPago === 'Personalizado' ? redondearMoneda(this.montoEfectivoPersonalizado) : null,
        montoQr: this.metodoPago === 'Personalizado' ? redondearMoneda(this.montoQrPersonalizado) : null,
        agencia_id: this.agencia_id
      }
      this.$axios.post('sales', data).then(res => {
        this.loading = false
        this.$alert.success('Venta realizada con exito')
        // Indicar que la venta se completó antes de cerrar el diálogo
        this.saleCompleted = true
        this.saleDialog = false

        // Notificar a la pantalla del cliente: "Gracias por su compra"
        this.notifySocket('clienteSaleComplete', {
          timestamp: Date.now().toString(),
          agencia_id: this.agencia_id,
          caja: this.caja_numero
        })

        this.$store.productosVenta = []
        this.client = {}
        this.aporte = 0
        this.qr = false
        this.efectivo = ''
        this.descuento = 0
        this.descuentoPorcentaje = 0
        this.montoEfectivoPersonalizado = 0
        this.montoQrPersonalizado = 0
        this.resetQrState()
        this.products.forEach(p => {
          p.cantidadPedida = 0
        })
        this.totalProducts = 0
        Imprimir.factura(res.data).then(r => {})
      }).catch(err => {
        this.loading = false
        const errores = err.response?.data?.errors
        const primerError = errores ? Object.values(errores).flat()[0] : null
        this.$alert.error(primerError || err.response?.data?.message || 'No se pudo registrar la venta.')
      })
    },

    guardarCajaNumero (val) {
      const caja = normalizarCaja(val)
      if (!caja) {
        this.$q.notify({ type: 'negative', message: 'El número de caja no es válido.' })
        return
      }

      this.caja_numero = caja
      localStorage.setItem(CAJA_STORAGE_KEY, caja.toString())
      window.dispatchEvent(new CustomEvent('terminal-caja-changed', {
        detail: { caja }
      }))
      this.$q.notify({
        type: 'positive',
        message: `Esta computadora quedó configurada como Caja ${caja}.`,
        timeout: 2500
      })
    },

    abrirConfigTerminal (configuracionInicial = false) {
      if (this.terminalConfigDialogOpen) return
      this.terminalConfigDialogOpen = true

      const dialog = this.$q.dialog({
        title: configuracionInicial ? 'Configuración inicial del terminal' : 'Acceso restringido',
        message: configuracionInicial
          ? 'Esta computadora todavía no tiene una caja asignada. Ingrese la contraseña de administración.'
          : 'Ingrese la contraseña para cambiar la caja asignada a esta computadora.',
        prompt: {
          model: '',
          type: 'password',
          label: 'Contraseña',
          outlined: true,
          isValid: val => String(val || '').length > 0
        },
        ok: { label: 'Continuar', color: 'primary' },
        cancel: configuracionInicial ? false : { label: 'Cancelar', flat: true },
        persistent: true
      })
      this._terminalConfigDialog = dialog

      dialog.onOk(password => {
        if (String(password) !== TERMINAL_CONFIG_PASSWORD) {
          this.$q.notify({
            type: 'negative',
            message: 'Contraseña incorrecta.',
            timeout: 2000
          })
          if (configuracionInicial && !this.caja_numero) {
            setTimeout(() => this.abrirConfigTerminal(true), 300)
          }
          return
        }

        this.seleccionarCajaTerminal(configuracionInicial)
      }).onDismiss(() => {
        if (this._terminalConfigDialog === dialog) {
          this._terminalConfigDialog = null
          this.terminalConfigDialogOpen = false
        }
      })
    },

    seleccionarCajaTerminal (configuracionInicial = false) {
      const dialog = this.$q.dialog({
        title: 'Caja asignada a esta computadora',
        message: 'Seleccione la caja física correspondiente. Se conservará aunque cambie el usuario o el turno.',
        options: {
          type: 'radio',
          model: this.caja_numero || 1,
          items: this.cajasOptions.map(c => ({ label: c.label, value: c.value }))
        },
        ok: { label: 'Guardar configuración', color: 'primary' },
        cancel: configuracionInicial ? false : { label: 'Cancelar', flat: true },
        persistent: true
      })
      this._terminalConfigDialog = dialog
      this.terminalConfigDialogOpen = true

      dialog.onOk(val => this.guardarCajaNumero(val)).onDismiss(() => {
        if (this._terminalConfigDialog === dialog) {
          this._terminalConfigDialog = null
          this.terminalConfigDialogOpen = false
        }
      })
    },

    verificarConfiguracionTerminal () {
      const cajaGuardada = normalizarCaja(localStorage.getItem(CAJA_STORAGE_KEY))
      this.caja_numero = cajaGuardada

      if (!cajaGuardada) {
        this.$nextTick(() => this.abrirConfigTerminal(true))
      }
    },

    handleTerminalStorageChange (e) {
      if (e.key !== CAJA_STORAGE_KEY) return

      const nuevaCaja = normalizarCaja(e.newValue)
      if (nuevaCaja === this.caja_numero) return

      this.caja_numero = nuevaCaja
      if (nuevaCaja) {
        if (this._terminalConfigDialog && typeof this._terminalConfigDialog.hide === 'function') {
          this._terminalConfigDialog.hide()
        }
        this.$q.notify({
          type: 'info',
          message: `Configuración sincronizada: Caja ${nuevaCaja}.`,
          timeout: 1800
        })
      } else {
        this.abrirConfigTerminal(true)
      }
    },

    handleSecretTerminalKey (e) {
      // Atajo secreto para el dueño/administrador: Ctrl + Alt + C
      if (e.ctrlKey && e.altKey && (e.key === 'c' || e.key === 'C')) {
        e.preventDefault()
        this.abrirConfigTerminal(false)
      }
    },

    // ===== PAGO CON QR (Baneco) =====
    generarQr () {
      const amount = this.montoParaQr
      if (!amount || amount <= 0) return

      this.qrGenerando = true
      this.$axios.post('qr/generar', {
        amount,
        description: `Venta ${this.client?.nombreRazonSocial || ''}`.trim()
      }).then(res => {
        this.qrGenerando = false
        this.qrId = res.data.qrId
        this.qrImage = res.data.qrImage
        this.iniciarPollingQr()
        // Mostrar el QR también en la pantalla del cliente (pantallaCobro)
        this.notifySocket('clienteQrData', {
          qrImage: this.qrImage,
          monto: amount,
          visible: true,
          agencia_id: this.agencia_id,
          caja: this.caja_numero
        })
      }).catch(err => {
        this.qrGenerando = false
        this.$alert.error(err.response?.data?.message || 'No se pudo generar el QR')
      })
    },

    iniciarPollingQr () {
      this.detenerPollingQr()
      // Consultar cada 3 segundos si el QR ya fue pagado
      this._qrPollInterval = setInterval(() => {
        this.consultarEstadoQr()
      }, 3000)
    },

    detenerPollingQr () {
      if (this._qrPollInterval) {
        clearInterval(this._qrPollInterval)
        this._qrPollInterval = null
      }
    },

    consultarEstadoQr () {
      if (!this.qrId) return
      this.$axios.get(`qr/estado/${this.qrId}`).then(res => {
        const statusQrCode = res.data.statusQrCode
        if (statusQrCode === 1) {
          // Pagado: la venta se realiza directamente (metodoPago ya es 'Qr' o 'Personalizado')
          this.detenerPollingQr()
          this.saleInsert()
        } else if (statusQrCode === 9) {
          // Anulado (desde el banco o por otro medio)
          this.$alert.error('El QR fue anulado')
          this.resetQrState()
        }
      }).catch(err => {
        console.warn('Error consultando estado de QR:', err)
      })
    },

    cancelarQr () {
      if (!this.qrId) return
      this.qrCancelando = true
      this.$axios.post('qr/cancelar', { qrId: this.qrId }).then(() => {
        this.qrCancelando = false
        this.resetQrState()
      }).catch(err => {
        this.qrCancelando = false
        this.$alert.error(err.response?.data?.message || 'No se pudo cancelar el QR')
      })
    },

    resetQrState () {
      this.detenerPollingQr()
      const teniaQr = !!this.qrId
      this.qrId = null
      this.qrImage = null
      this.qrGenerando = false
      this.qrCancelando = false
      if (teniaQr) {
        this.notifySocket('clienteQrData', {
          qrImage: '',
          monto: '',
          visible: false,
          agencia_id: this.agencia_id,
          caja: this.caja_numero
        })
      }
    },

    clientSearch () {
      this.$axios.post('searchClient', this.client).then(res => {
        this.loadingClientSearch = false
        if (res.data.nombreRazonSocial !== undefined) {
          this.client.nombreRazonSocial = res.data.nombreRazonSocial
          this.client.email = res.data.email
          this.client.id = res.data.id
          const documento = this.documents.find(r => r.codigoClasificador === res.data.codigoTipoDocumentoIdentidad)
          documento.label = documento.descripcion
          this.document = documento
        }
      }).catch(() => {
        this.loadingClientSearch = false
      })
    },

    searchClient () {
      this.document = this.documents[0]
      this.client.nombreRazonSocial = ''
      this.client.complemento = ''
      this.client.email = ''
      this.client.id = undefined
      clearTimeout(this._searchTimer)
      if (this.client.numeroDocumento === '0' || this.client.numeroDocumento.length >= 5) {
        this.loadingClientSearch = true
        this._searchTimer = setTimeout(() => { this.clientSearch() }, 400)
      }
    },

    searchClientComplemento () {
      this.client.nombreRazonSocial = ''
      this.client.email = ''
      this.client.id = undefined
      clearTimeout(this._searchTimer)
      if (this.client.numeroDocumento.length >= 1) {
        this.loadingClientSearch = true
        this._searchTimer = setTimeout(() => { this.clientSearch() }, 400)
      }
    },

    async clickSale () {
      // ✅ VERIFICACIÓN DE STOCK SOLO AL CONFIRMAR
      const productosSinStock = this.verificarStockCanasta()

      if (productosSinStock.length > 0) {
        let mensaje = 'No se puede realizar la venta por falta de stock:\n\n'
        productosSinStock.forEach(producto => {
          mensaje += `• ${producto.nombre}: Solicitado ${producto.cantidadSolicitada}, Disponible ${producto.stockDisponible}\n`
        })
        mensaje += '\nPor favor ajuste las cantidades antes de confirmar la venta.'
        this.$alert.error(mensaje)
        return
      }

      let hayProblema = false
      this.$store.productosVenta.forEach(p => {
        if (!p.precioVenta || p.precioVenta <= 0) {
          this.$alert.error(`El precio de venta de "${p.nombre}" debe ser mayor a 0.`)
          hayProblema = true
        }
      })
      if (hayProblema) return

      try {
        this.loading = true

        // VERIFICACIÓN EN BACKEND
        const productos = this.$store.productosVenta.map(p => ({
          id: p.id,
          cantidadVenta: p.cantidadVenta
        }))

        const { data: verificacion } = await this.$axios.post('verificar-stock-venta', {
          productos,
          agencia_id: this.agencia_id
        })

        const precios = new Map((verificacion.precios || []).map(p => [Number(p.id), p]))
        this.$store.productosVenta.forEach(producto => {
          const precioActual = precios.get(Number(producto.id))
          if (!precioActual) return
          producto.precio = formatMoney(precioActual.precio)
          producto.precioVenta = formatMoney(precioActual.precioVenta)
          producto.porcentajeEfectivo = Number(precioActual.porcentajeEfectivo ?? 0)
          producto.porcentaje = producto.porcentajeEfectivo
          producto.promocion = precioActual.promocion
        })

        this.aporte = 0
        this.descuento = 0
        this.descuentoPorcentaje = 0
        this.saleDialog = true
        this.efectivo = 0
        this.qr = false
        this.client = {
          numeroDocumento: '0',
          nombreRazonSocial: 'SN',
          email: '',
          complemento: ''
        }
        this.metodoPago = 'Efectivo'
      } catch (error) {
        this.loading = false
        if (error.response && error.response.data && error.response.data.errores) {
          error.response.data.errores.forEach(msg => {
            this.$alert.error(msg)
          })
        } else {
          this.$alert.error('Error al verificar stock')
        }
      } finally {
        this.loading = false
      }
    },

    // ✅ CORREGIDO: Usa cantidadReal para verificar stock
    async clickAddSale (product) {
      // Verificación inmediata usando cantidadReal
      if (product.cantidadReal <= 0) {
        this.$q.notify({
          color: 'negative',
          message: 'Producto sin stock disponible',
          icon: 'error',
          position: 'top'
        })
        return
      }

      // Verificación de stock considerando lo ya en canasta
      const productoEnCanasta = this.$store.productosVenta.find(p => p.id === product.id)
      const cantidadReservada = productoEnCanasta ? productoEnCanasta.cantidadVenta : 0
      const stockDisponible = product.cantidadReal - cantidadReservada

      if (stockDisponible <= 0) {
        this.$q.notify({
          color: 'negative',
          message: 'Stock insuficiente',
          icon: 'error',
          position: 'top'
        })
        return
      }

      // Solo actualizar el stock visual para mostrar
      product.cantidad = stockDisponible - 1

      const precioVenta = product.precioVenta ?? (product.porcentaje
        ? this.$filters.precioRebajaVenta(product.precio, product.porcentaje)
        : product.precio)
      product.precioVenta = precioVenta != null ? formatMoney(precioVenta) : precioVenta

      if (productoEnCanasta) {
        productoEnCanasta.cantidadVenta++
        productoEnCanasta.cantidadPedida++
      } else {
        product.cantidadVenta = 1
        product.cantidadPedida = 1
        this.$store.productosVenta.push(product)
      }
    },

    precioVenta (n) {
      if (n.precioVenta === '') {
        n.precioVenta = 1
      }
    },

    redondeo (n) {
      return formatMoney(n)
    },

    addCantidad (n, i) {
      n.cantidadPedida++
      n.cantidadVenta = parseInt(n.cantidadVenta) + 1
    },

    // ✅ PERMITE CUALQUIER VALOR - solo valida al confirmar
    cambioNumero (n, i) {
      if (n.cantidadVenta !== '') {
        const nuevaCantidad = parseInt(n.cantidadVenta)

        if (!isNaN(nuevaCantidad)) {
          n.cantidadPedida = nuevaCantidad
        }

        // Si es 0 o negativo, eliminar del carrito
        if (nuevaCantidad <= 0) {
          this.$store.productosVenta.splice(i, 1)
          n.cantidadVenta = 0
          n.cantidadPedida = 0
        }
      }
    },

    removeCantidad (n, i) {
      n.cantidadPedida--
      if (n.cantidadVenta > 1) {
        n.cantidadVenta = parseInt(n.cantidadVenta) - 1
      } else if (n.cantidadVenta === 1) {
        this.$store.productosVenta.splice(i, 1)
      }
    },

    deleteProductosVenta (p, i) {
      this.$store.productosVenta.splice(i, 1)
      p.cantidadVenta = 0
      p.cantidadPedida = 0
    },

    async vaciarCanasta () {
      await this.$store.productosVenta.forEach(p => {
        p.cantidadVenta = 0
        p.cantidadPedida = 0
      })
      this.$store.productosVenta = []
    },

    productsGet () {
      this.loading = true
      this.products = []
      this.$axios.get(`productsSale?page=${this.current_page}&paginate=18&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.agencia_id}&subcategory=${this.subcategoria}`).then(res => {
        this.loading = false
        this.totalProducts = res.data.products.total
        this.last_page = res.data.products.last_page
        this.current_page = res.data.products.current_page
        this.costoTotalProducts = parseFloat(res.data.costoTotal).toFixed(1)
        res.data.products.data.forEach(p => {
          p.cantidadPedida = 0
          p.cantidadReal = p.cantidad // ✅ Guardar stock real
          p.porcentaje = Number(p.porcentajeEfectivo ?? p.porcentaje ?? 0)
          p.precio = p.precio ? formatMoney(p.precio) : p.precio
          const precioVenta = p.precioVenta ?? (p.porcentaje
            ? this.$filters.precioRebajaVenta(p.precio, p.porcentaje)
            : p.precio)
          p.precioVenta = precioVenta != null ? formatMoney(precioVenta) : precioVenta
          p.cantidadAlmacen = p.cantidadAlmacen || 0
          this.products.push(p)
        })
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    },
    cargarPedidoOnline () {
      this.$q.dialog({
        title: 'Pedido Online',
        message: 'Ingrese el número de pedido',
        prompt: {
          model: '',
          type: 'text',
          isValid: val => !!(val && String(val).trim().length > 0)
        },
        cancel: true,
        persistent: true
      }).onOk(val => {
        const raw = String(val || '').trim()
        // 👇 Si son solo dígitos, construye el formato que tu API espera.
        const numero = /^\d+$/.test(raw) ? `PEDIDOWEB_Nº${raw}` : raw
        this.importarPedidoDesdeApi(numero)
      })
    },

    async importarPedidoDesdeApi (orderNumber) {
      try {
        this.loadingPedidoOnline = true

        const encoded = encodeURIComponent(orderNumber)
        // ⚠️ Ajusta la ruta si tu backend usa otra
        const { data: order } = await this.$axios.get(`orders/${encoded}`)

        if (!order || !Array.isArray(order.items) || order.items.length === 0) {
          this.$alert.error('Ese pedido no tiene ítems o no existe.')
          return
        }

        // Para cada item del pedido: solo usamos product_id y quantity
        for (const it of order.items) {
          const cantidad = Number(it.quantity ?? it.cantidad ?? 0)
          const productId = it.product_id ?? it.id ?? it.productId

          if (!productId || cantidad <= 0) continue

          const base = await this.obtenerProductoCanonico(productId)
          if (!base) {
            this.$alert.error(`No se pudo cargar el producto ID ${productId}.`)
            continue
          }

          // Agregar tal cual “click” N veces (aplica tus descuentos y validaciones)
          this.agregarComoClicks(base, cantidad)
        }

        this.$alert.success(`Pedido ${orderNumber} importado a la canasta.`)
      } catch (e) {
        console.error(e)
        this.$alert.error(e?.response?.data?.message || 'No se pudo recuperar el pedido.')
      } finally {
        this.loadingPedidoOnline = false
      }
    },

    async obtenerProductoCanonico (id) {
      // 1) ¿Ya está cargado en tu grid?
      const enGrid = this.products.find(p => p.id === id)
      if (enGrid) {
        // Aseguramos campos que usa tu canasta
        if (!Array.isArray(enGrid.buys)) this.$set(enGrid, 'buys', [])
        if (typeof enGrid.cantidadReal !== 'number') enGrid.cantidadReal = Number(enGrid.cantidad ?? 0)
        return enGrid
      }

      // 2) Si no está en el grid, pedir al backend
      try {
        // ⚠️ Ajusta la ruta si tu API usa otra
        const { data } = await this.$axios.get(`products/${id}`)
        const raw = data?.product || data
        if (!raw) return null

        // Normaliza campos mínimos para que clickAddSale funcione igual que con el grid
        const base = {
          ...raw,
          precio: raw.precio ? formatMoney(raw.precio) : raw.precio,
          // stock para las validaciones de clickAddSale
          cantidadReal: Number(raw.cantidadReal ?? raw.cantidad ?? raw.stock ?? 0),
          // El backend entrega el precio unitario oficial ya redondeado.
          precioVenta: raw.precioVenta ?? (raw.precio ? roundCurrency(raw.precio) : 0),
          porcentajeEfectivo: Number(raw.porcentajeEfectivo ?? raw.porcentaje ?? 0),
          porcentaje: Number(raw.porcentajeEfectivo ?? raw.porcentaje ?? 0),
          cantidadVenta: 0,
          cantidadPedida: 0,
          buys: Array.isArray(raw.buys) ? raw.buys : []
        }
        return base
      } catch (e) {
        console.error('No se pudo obtener el producto', id, e)
        return null
      }
    },

    agregarComoClicks (producto, cantidad) {
      // Reutiliza TODA tu lógica de clickAddSale (descuento, stock, etc.)
      for (let i = 0; i < cantidad; i++) {
        this.clickAddSale(producto)
      }
    },

    // ===== PANTALLA CLIENTE =====
    toggleClientDisplay () {
      if (this.clientDisplayVisible) {
        this.closeClientDisplay()
        return
      }

      this.openClientDisplay()
    },

    async openClientDisplay () {
      if (!normalizarCaja(this.caja_numero)) {
        this.abrirConfigTerminal(true)
        return
      }

      this.clientDisplayVisible = true
      this.syncClientDisplay(true)
    },

    syncClientDisplay (show = false) {
      const payload = {
        numeroDocumento: this.client?.numeroDocumento || '',
        complemento: this.client?.complemento || '',
        nombreRazonSocial: this.client?.nombreRazonSocial || '',
        email: this.client?.email || '',
        tipoDocumento: this.document?.descripcion || this.document?.label || '',
        visible: show,
        agencia_id: this.agencia_id,
        caja: this.caja_numero
      }

      // Notificar por Sockets para la aplicación de PC
      this.notifySocket('clienteDisplayData', payload)
    },

    // Evita mandar un notify por cada tecla presionada: espera 350ms de inactividad
    syncClientDisplayDebounced () {
      if (!this.clientDisplayVisible) return
      clearTimeout(this._clientSyncDebounce)
      this._clientSyncDebounce = setTimeout(() => {
        this.syncClientDisplay(true)
      }, 350)
    },

    clearClientDisplayData () {
      clearTimeout(this._clientSyncDebounce)
      this.clientDisplayVisible = false
      const payload = {
        numeroDocumento: '',
        complemento: '',
        nombreRazonSocial: '',
        email: '',
        tipoDocumento: '',
        visible: false,
        agencia_id: this.agencia_id,
        caja: this.caja_numero
      }
      this.notifySocket('clienteDisplayData', payload)
    },

    closeClientDisplay () {
      if (this.clientDisplayVisible) {
        this.clearClientDisplayData()
        this.notifySocket('clienteDisplayClose', {
          agencia_id: this.agencia_id,
          caja: this.caja_numero
        })
      }
    },

    notifySocket (event, data) {
      if (EVENTOS_PANTALLA_CLIENTE.has(event) && !normalizarCaja(this.caja_numero)) {
        console.warn(`No se envió ${event}: esta computadora no tiene una caja configurada.`)
        return
      }

      // const socketUrl = 'http://' + window.location.hostname + ':3000'
      // env VITE_API_SOCKET
      const socketUrl = import.meta.env.VITE_API_SOCKET || 'http://localhost:3000'
      fetch(`${socketUrl}/notify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ event, data })
      }).catch(err => console.warn('Could not notify socket server:', err))
    },

    verImagenCompleta (e, product) {
      if (e) {
        if (typeof e.stopPropagation === 'function') e.stopPropagation()
        if (typeof e.preventDefault === 'function') e.preventDefault()
        if (e.cancelBubble !== undefined) e.cancelBubble = true
      }
      this.productoImagenSeleccionado = product
      this.resetZoom()
      this.dialogImagenCompleta = true
    },

    resetZoom () {
      this.zoomScale = 1
      this.panX = 0
      this.panY = 0
      this.isDragging = false
    },

    zoomIn () {
      this.zoomScale = Math.min(4, Math.round((this.zoomScale + 0.25) * 100) / 100)
    },

    zoomOut () {
      this.zoomScale = Math.max(0.6, Math.round((this.zoomScale - 0.25) * 100) / 100)
      if (this.zoomScale <= 1) {
        this.panX = 0
        this.panY = 0
      }
    },

    onWheelZoom (e) {
      const delta = e.deltaY < 0 ? 0.2 : -0.2
      const newScale = Math.min(4, Math.max(0.6, Math.round((this.zoomScale + delta) * 100) / 100))
      this.zoomScale = newScale
      if (this.zoomScale <= 1) {
        this.panX = 0
        this.panY = 0
      }
    },

    toggleDblClickZoom () {
      if (this.zoomScale > 1.2) {
        this.resetZoom()
      } else {
        this.zoomScale = 2.2
      }
    },

    startDrag (e) {
      if (this.zoomScale <= 1) return
      this.isDragging = true
      this.dragStartX = e.clientX - this.panX
      this.dragStartY = e.clientY - this.panY
    },

    onDrag (e) {
      if (!this.isDragging) return
      this.panX = e.clientX - this.dragStartX
      this.panY = e.clientY - this.dragStartY
    },

    stopDrag () {
      this.isDragging = false
    }

  }
}
</script>

<style scoped>
.cursor-not-allowed {
  cursor: not-allowed;
}

.sale-summary-card {
  overflow: hidden;
  border: 1px solid #dfe5f1;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 10px 28px rgba(34, 51, 84, 0.12);
}

.sale-summary-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 16px;
  color: #fff;
  background: linear-gradient(135deg, #273c75 0%, #4c5fd7 58%, #667eea 100%);
}

.sale-summary-total__identity,
.sale-summary-label {
  display: flex;
  align-items: center;
}

.sale-summary-total__identity {
  gap: 11px;
  min-width: 0;
}

.sale-summary-icon {
  display: grid;
  flex: 0 0 42px;
  width: 42px;
  height: 42px;
  place-items: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 13px;
  background: rgba(255, 255, 255, 0.14);
}

.sale-summary-eyebrow {
  margin-bottom: 2px;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.16em;
  opacity: 0.76;
}

.sale-summary-title {
  font-size: 14px;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: 0.025em;
}

.sale-summary-amount {
  display: flex;
  align-items: baseline;
  gap: 5px;
  white-space: nowrap;
  font-size: 29px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.035em;
  font-variant-numeric: tabular-nums;
}

.sale-summary-currency {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0;
  opacity: 0.78;
}

.sale-summary-details {
  padding: 11px 14px 13px;
}

.sale-summary-details__title {
  padding: 2px 2px 7px;
  color: #8a94a6;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.sale-summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 37px;
  padding: 7px 2px;
  border-bottom: 1px solid #edf0f5;
}

.sale-summary-label {
  gap: 8px;
  min-width: 0;
  color: #687386;
  font-size: 12px;
  line-height: 1.25;
}

.sale-summary-label > .q-icon:first-child {
  flex: 0 0 auto;
  color: #6b78c8;
  font-size: 17px;
}

.sale-summary-info {
  flex: 0 0 auto;
  color: #a3aabc;
  cursor: help;
}

.sale-summary-value {
  flex: 0 0 auto;
  color: #273247;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}

.sale-summary-value--discount {
  color: #15965a;
}

.sale-confirm-button {
  min-height: 42px;
  border-radius: 12px;
  font-weight: 700;
  letter-spacing: 0.01em;
}

@media (max-width: 420px) {
  .sale-summary-total {
    align-items: flex-start;
    flex-direction: column;
  }

  .sale-summary-amount {
    align-self: flex-end;
  }
}

.terminal-config-trigger {
  position: fixed;
  right: 4px;
  bottom: 4px;
  z-index: 2100;
  opacity: 0.14;
  transition: opacity 0.2s ease;
}

.terminal-config-trigger:hover,
.terminal-config-trigger:focus-visible {
  opacity: 0.8;
}

.client-display-button {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.client-display-button--active {
  box-shadow: 0 3px 10px rgba(33, 186, 69, 0.32);
  transform: translateY(-1px);
}

.client-display-live-badge {
  width: 9px;
  height: 9px;
  min-width: 9px;
  padding: 0;
  box-shadow: 0 0 0 0 rgba(139, 195, 74, 0.7);
  animation: client-display-pulse 1.5s infinite;
}

@keyframes client-display-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(139, 195, 74, 0.7);
  }
  70% {
    box-shadow: 0 0 0 7px rgba(139, 195, 74, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(139, 195, 74, 0);
  }
}
</style>
