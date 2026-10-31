<template>
  <section class="trae-page" :class="{ 'has-selection': selected.length }">
    <q-banner v-if="!isAdmin" class="bg-red-1 text-negative rounded-borders">Solo el administrador puede configurar TRAE.</q-banner>
    <template v-else>
      <header class="trae-header">
        <div class="row items-center no-wrap"><div class="channel-icon"><q-icon name="delivery_dining" size="29px" /></div><div><div class="eyebrow">TU CANAL DE DELIVERY</div><h1>TRAE <span>Ventas y descuentos</span></h1></div></div>
        <q-btn flat color="deep-purple" icon="refresh" label="Actualizar" no-caps :loading="loading" :disable="saving" @click="refresh" />
      </header>
      <q-tabs v-model="section" dense align="left" active-color="deep-purple" indicator-color="transparent" class="section-tabs">
        <q-tab name="sales" icon="receipt_long" label="Ventas TRAE" no-caps /><q-tab name="pricing" icon="tune" label="Precios y descuentos" no-caps />
      </q-tabs>
      <TraeSales v-if="section === 'sales'" ref="sales" :agencias="agencias" />
      <template v-else>
        <div v-if="configError" class="load-error q-mb-md"><q-icon name="error_outline" />{{ configError }}<q-btn flat dense label="Reintentar" no-caps @click="load" /></div>
        <q-card flat class="price-source-card">
          <q-icon :name="usesOwnPrices ? 'tune' : 'sync'" size="30px" color="deep-purple" />
          <div class="col"><div class="overline-label">DESCUENTOS PARA LA API DE DELIVERY</div><h2>{{ configLoaded ? priceSourceLabel : 'Cargando configuración…' }}</h2><p>{{ usesOwnPrices ? 'Configura descuentos exclusivos de TRAE o usa los de otro canal.' : 'Se actualizan automáticamente con el canal elegido. Las reglas propias de TRAE quedan guardadas.' }}</p></div>
          <q-btn outline color="deep-purple" no-caps label="Elegir origen de descuentos" :disable="!configLoaded || loading || saving" @click="openSettings" />
        </q-card>
        <div class="pricing-overview">
          <div class="overview-card"><div class="overview-icon"><q-icon name="percent" /></div><div class="overview-body"><div class="overline-label">{{ usesOwnPrices ? 'Descuento general' : 'Origen activo' }}</div><strong>{{ configLoaded ? (usesOwnPrices ? `${Number(settings.descuento_general)} %` : sourceLabel('HEREDADO_' + settings.fuente_precios.toUpperCase())) : '—' }}</strong><span>{{ usesOwnPrices ? 'Para productos sin una regla propia' : 'Actualización automática desde este canal' }}</span></div><q-btn flat round dense icon="edit" color="deep-purple" aria-label="Editar configuración general" :disable="!configLoaded || loading || saving" @click="openSettings"><q-tooltip>Editar configuración general</q-tooltip></q-btn></div>
          <div class="overview-card"><div class="overview-icon neutral"><q-icon name="account_balance_wallet" /></div><div class="overview-body"><div class="overline-label">Comisión TRAE</div><strong>{{ configLoaded ? `${Number(settings.comision_porcentaje)} %` : '—' }}</strong><span>Se registra aparte de cada venta</span></div><q-btn flat round dense icon="settings" color="grey-7" aria-label="Editar comisión TRAE" :disable="!configLoaded || loading || saving" @click="openSettings"><q-tooltip>Editar comisión TRAE</q-tooltip></q-btn></div>
          <div class="overview-card rules-summary"><div class="overview-icon green"><q-icon name="rule" /></div><div class="overview-body"><div class="overline-label">{{ usesOwnPrices ? 'Reglas activas' : 'Reglas propias guardadas' }}</div><strong>{{ usesOwnPrices ? activeRules.length : rules.length }}</strong><span>{{ usesOwnPrices ? `${activeProductRules} por producto · ${activeCategoryRules} por categoría` : 'Disponibles al volver a Propios de TRAE' }}</span></div></div>
        </div>
        <q-banner v-if="!usesOwnPrices" rounded class="bg-deep-purple-1 text-deep-purple-9 q-mb-md">{{ priceSourceLabel }}. Los precios del catálogo y de caja TRAE siguen este origen. Las reglas propias quedan guardadas sin aplicarse. Puedes seguir creando combos internos.</q-banner><div v-else class="priority-note"><q-icon name="info_outline" size="17px" /><span>Se aplica una sola regla: <b>producto</b><q-icon name="chevron_right" /><b>categoría</b><q-icon name="chevron_right" /><b>general</b>.</span><q-btn flat dense no-caps label="Cómo funciona" color="deep-purple" @click="showHelp = true" /></div>
        <div class="pricing-workspace" :class="{ 'combos-workspace': tab === 'combos' }">
          <q-card flat class="catalog-card">
            <div v-if="tab !== 'combos' && usesOwnPrices" class="catalog-heading"><div><h2>Elige dónde aplicar el descuento</h2><p>Selecciona productos o configura una categoría completa.</p></div><q-btn unelevated color="deep-purple" icon="category" label="Por categoría" no-caps :disable="!configLoaded || loading || saving" @click="openCategory" /></div>
            <q-tabs v-model="tab" align="left" dense active-color="deep-purple" indicator-color="deep-purple" class="catalog-tabs"><q-tab name="products" label="Catálogo de productos" no-caps /><q-tab name="rules" :label="`Reglas configuradas (${rules.length})`" no-caps /><q-tab name="combos" icon="inventory" label="Combos internos" no-caps /></q-tabs>
            <q-separator />
            <template v-if="tab === 'products'">
              <div class="catalog-filters"><q-input v-model="search" outlined dense clearable debounce="300" placeholder="Nombre o código de barras…" aria-label="Buscar productos TRAE" class="product-search" @update:model-value="resetPage"><template #prepend><q-icon name="search" color="grey-6" /></template></q-input><q-select v-model="category" :options="filteredCategories" option-value="id" option-label="name" emit-value map-options use-input input-debounce="0" outlined dense clearable label="Categoría" class="category-filter" @filter="filterCategories" @update:model-value="resetPage" /></div>
              <div class="catalog-meta"><span>{{ total }} producto{{ total === 1 ? '' : 's' }} <span v-if="search || category">en esta búsqueda</span></span><q-btn v-if="$q.screen.lt.sm" flat dense no-caps :label="pageSelection === true ? 'Quitar esta página' : 'Seleccionar página'" color="deep-purple" :disable="saving || !selectableProducts.length" @click="togglePageSelection" /><q-btn v-else-if="search || category" flat dense no-caps label="Limpiar filtros" color="deep-purple" @click="clearFilters" /><span v-else>Selecciona con las casillas</span></div>
              <div v-if="productsError" class="load-error q-mx-md q-mb-sm"><q-icon name="wifi_off" />{{ productsError }}<q-btn flat dense label="Reintentar" no-caps @click="loadProducts" /></div>
              <q-table :rows="products" :columns="productColumns" row-key="id" selection="multiple" :selected="selected" :loading="loadingProducts" :rows-per-page-options="[0]" hide-pagination flat :grid="$q.screen.lt.sm" class="product-table" no-data-label="No se encontraron productos. Prueba otro nombre o categoría.">
                <template #header-selection><q-checkbox :model-value="pageSelection" color="deep-purple" :disable="saving || !selectableProducts.length" aria-label="Seleccionar productos de esta página" @update:model-value="togglePageSelection" /></template>
                <template #body-selection="props"><q-checkbox :model-value="isSelected(props.row)" color="deep-purple" :disable="saving || props.row.origen === 'EXCLUIDO'" :aria-label="`Seleccionar ${props.row.nombre}`" @update:model-value="toggleProduct(props.row)" /></template>
                <template #body-cell-product="props"><q-td :props="props"><div class="product-identity"><q-avatar class="product-avatar" size="38px" icon="inventory_2" /><div><div class="product-name">{{ props.row.nombre }}</div><div class="product-category">{{ props.row.categoria || 'Sin categoría' }}<span v-if="props.row.barra"> · {{ props.row.barra }}</span></div></div></div></q-td></template>
                <template #body-cell-price="props"><q-td :props="props"><div class="trae-price">Bs {{ money(props.row.precio_venta) }}</div><div class="base-price">Normal Bs {{ money(props.row.precio_original) }}</div></q-td></template>
                <template #body-cell-discount="props"><q-td :props="props"><span class="discount-pill" :class="{ 'no-discount': !Number(props.row.porcentaje) }">{{ Number(props.row.porcentaje) }} %</span><div class="rule-source">{{ sourceLabel(props.row.origen) }}</div></q-td></template>
                <template #item="props"><div class="col-12 q-px-md q-pb-sm"><div class="mobile-product" :class="{ chosen: isSelected(props.row) }"><div class="row items-start no-wrap"><q-checkbox :model-value="isSelected(props.row)" color="deep-purple" :disable="saving || props.row.origen === 'EXCLUIDO'" :aria-label="`Seleccionar ${props.row.nombre}`" @update:model-value="toggleProduct(props.row)" /><div class="q-pt-sm col"><div class="product-name">{{ props.row.nombre }}</div><div class="product-category">{{ props.row.categoria || 'Sin categoría' }}</div><div v-if="props.row.barra" class="product-category">{{ props.row.barra }}</div><div class="row items-center justify-between q-mt-sm"><div><strong class="trae-price">Bs {{ money(props.row.precio_venta) }}</strong><div class="base-price">Normal Bs {{ money(props.row.precio_original) }}</div></div><div class="text-right"><span class="discount-pill">{{ Number(props.row.porcentaje) }} %</span><div class="rule-source">{{ sourceLabel(props.row.origen) }}</div></div></div></div></div></div></div></template>
              </q-table>
              <div class="catalog-footer"><q-select v-model="perPage" :options="[20, 40, 60]" dense borderless suffix="por página" aria-label="Productos por página" :disable="loadingProducts" @update:model-value="resetPage" /><q-pagination v-model="page" :max="lastPage" :max-pages="4" color="deep-purple" direction-links :disable="loadingProducts" @update:model-value="loadProducts" /></div>
            </template>
            <template v-else-if="tab === 'rules'">
              <div class="catalog-filters"><q-input v-model="ruleSearch" outlined dense clearable placeholder="Buscar regla por nombre…" aria-label="Buscar reglas TRAE" class="product-search"><template #prepend><q-icon name="search" /></template></q-input><q-select v-model="ruleScope" :options="scopeOptions" outlined dense emit-value map-options class="category-filter" label="Tipo de regla" /></div>
              <q-table :rows="filteredRules" :columns="ruleColumns" row-key="id" :rows-per-page-options="[10, 25, 50]" flat wrap-cells class="rules-table" no-data-label="No hay reglas para mostrar. Crea una por categoría o selecciona productos en el catálogo.">
                <template #body-cell-target="props"><q-td :props="props"><div class="product-name">{{ props.row.producto || props.row.categoria }}</div><div class="product-category"><q-icon :name="props.row.product_id ? 'inventory_2' : 'category'" /> {{ props.row.product_id ? 'Producto' : 'Categoría completa' }}</div></q-td></template>
                <template #body-cell-porcentaje="props"><q-td :props="props"><span class="discount-pill">{{ Number(props.row.porcentaje) }} %</span></q-td></template>
                <template #body-cell-validity="props"><q-td :props="props"><q-badge :color="stateColor(props.row)" class="q-mb-xs">{{ ruleState(props.row) }}</q-badge><div v-if="props.row.permanente == null || Number(props.row.permanente)" class="product-category">Indefinida</div><div v-else class="product-category">{{ validityDate(props.row.fecha_inicio) }}<br>hasta {{ validityDate(props.row.fecha_fin) }}<div>Hora de Bolivia</div></div></q-td></template>
                <template #body-cell-activo="props"><q-td :props="props"><q-toggle :model-value="!!Number(props.row.activo)" color="deep-purple" :disable="saving || !usesOwnPrices" :label="Number(props.row.activo) ? 'Habilitada' : 'Pausada'" @update:model-value="toggleRule(props.row, $event)" /></q-td></template>
                <template #body-cell-actions="props"><q-td :props="props" class="no-wrap"><q-btn flat round dense color="deep-purple" icon="edit" :disable="saving || !usesOwnPrices" :aria-label="`Editar regla ${props.row.producto || props.row.categoria}`" @click="editRule(props.row)"><q-tooltip>Editar</q-tooltip></q-btn><q-btn flat round dense color="grey-7" icon="delete_outline" :disable="saving || !usesOwnPrices" :aria-label="`Eliminar regla ${props.row.producto || props.row.categoria}`" @click="deleteRule(props.row)"><q-tooltip>Eliminar y heredar descuento</q-tooltip></q-btn></q-td></template>
              </q-table>
            </template>
            <InternalCombos v-else ref="internalCombos" @created="selected = []" />
          </q-card>
          <aside v-if="tab !== 'combos'" ref="selectionPanel" class="selection-column">
            <q-card flat class="selection-card">
              <div class="selection-title"><div class="step-icon">{{ selected.length || '＋' }}</div><div><h2>Tu selección</h2><span>{{ selected.length ? `${selected.length} de 200 productos` : 'Arma tu grupo de productos' }}</span></div><q-btn v-if="selected.length" flat round dense icon="delete_sweep" color="grey-6" aria-label="Limpiar selección" :disable="saving" @click="selected = []"><q-tooltip>Limpiar selección</q-tooltip></q-btn></div>
              <template v-if="selected.length">
                <div class="selection-hint"><q-icon name="check_circle" />Puedes seguir buscando; tu selección se conserva entre páginas.</div>
                <div class="selected-products"><div v-for="product in selected" :key="product.id" class="selected-product"><div class="col"><div class="selected-name">{{ product.nombre }}</div><div class="selected-preview"><span>Bs {{ money(product.precio_venta) }}</span><template v-if="usesOwnPrices"><q-icon name="arrow_forward" /><strong>Bs {{ money(previewPrice(product)) }}</strong></template></div></div><q-btn flat round dense icon="close" size="sm" :aria-label="`Quitar ${product.nombre}`" :disable="saving" @click="toggleProduct(product)" /></div></div>
                <q-btn outline no-caps color="deep-purple" icon="inventory" label="Crear combo con selección" class="q-ma-md" :disable="saving || selected.length > 50" @click="createComboFromSelection" /><q-form v-if="usesOwnPrices" class="bulk-form" @submit.prevent="applySelected"><label class="field-label">Nuevo descuento para este grupo</label><q-input v-model.number="bulkPercentage" type="number" outlined min="0" max="99.99" step="0.01" suffix="%" aria-label="Descuento para productos seleccionados" :rules="percentageRules" :disable="saving" class="bulk-input" /><div class="preset-label">Accesos rápidos</div><div class="discount-presets"><button v-for="percent in presets" :key="percent" type="button" :class="{ active: Number(bulkPercentage) === percent }" :disabled="saving" @click="bulkPercentage = percent">{{ percent }} %</button></div><div class="zero-note"><q-icon name="lightbulb_outline" size="17px" />0 % deja estos productos sin descuento.</div><TraeValidity v-model="bulkValidity" :disabled="saving" /><q-btn type="submit" unelevated color="deep-purple" :label="`Aplicar a ${selected.length} producto${selected.length === 1 ? '' : 's'}`" icon="check" no-caps class="full-width apply-button" :loading="saving" :disable="!configLoaded || loading || loadingProducts || !validPercentage(bulkPercentage) || !validValidity(bulkValidity)" /><div class="preview-note">Precio previsto por unidad durante la vigencia. El servidor valida el cambio al guardar.</div></q-form>
              </template>
              <div v-else class="empty-selection"><div class="empty-selection-art"><q-icon name="playlist_add" size="40px" /></div><strong>{{ usesOwnPrices ? 'Elige, revisa y aplica' : 'Crea un combo con tu selección' }}</strong><p v-if="usesOwnPrices">Marca las casillas del catálogo.<br>Aquí podrás ajustar el descuento<br>y ver los nuevos precios.</p><p v-else>Los descuentos siguen el origen elegido.<br>Selecciona productos para crear<br>un combo interno independiente.</p><div class="empty-selection-tip"><q-icon name="touch_app" />También puedes seleccionar una página completa.</div></div>
            </q-card>
            <div class="channel-note"><q-icon name="verified_user" size="20px" /><span>Estos descuentos se aplican a TRAE. Las promociones de tu web, APK y sucursal se gestionan en su propia pestaña.</span></div>
          </aside>
        </div>
      </template>
      <div v-if="section === 'pricing' && tab !== 'combos' && selected.length && !selectionInView && $q.screen.width <= 1000" class="mobile-selection-bar"><div><strong>{{ selected.length }} seleccionado{{ selected.length === 1 ? '' : 's' }}</strong><span>{{ usesOwnPrices ? 'Elige el porcentaje antes de aplicar' : 'Crea un combo con estos productos' }}</span></div><q-btn unelevated color="deep-purple" no-caps label="Ver selección" icon="arrow_forward" @click="$refs.selectionPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' })" /></div>
      <q-dialog v-model="settingsDialog" persistent><q-card class="editor-dialog"><q-form @submit.prevent="saveSettings">
        <q-card-section class="editor-heading"><div class="overview-icon"><q-icon name="tune" /></div><div><h2>Configuración general</h2><p>La base de precios de tu canal TRAE</p></div><q-space /><q-btn flat round dense icon="close" aria-label="Cerrar configuración" :disable="saving" v-close-popup /></q-card-section>
        <q-card-section class="q-pt-none">
          <q-select v-model="settingsDraft.fuente_precios" :options="priceSourceOptions" emit-value map-options outlined label="Origen de los descuentos" :disable="saving" />
          <template v-if="settingsDraft.fuente_precios !== 'propio'">
            <q-select v-model="settingsDraft.fuente_agencia_id" :options="agencias" option-label="nombre" option-value="id" emit-value map-options clearable outlined label="Sucursal de referencia" class="q-mt-md" :disable="saving" :loading="agenciasLoading" :rules="[v => settingsDraft.fuente_precios !== 'physical' || !!v || 'Selecciona la sucursal']" :hint="settingsDraft.fuente_precios === 'physical' ? 'TRAE usará los mismos precios y promociones de esta sucursal.' : 'Opcional. Sin sucursal se usan solo las promociones generales del canal; con sucursal también sus promociones específicas.'" />
            <q-banner rounded class="bg-deep-purple-1 text-deep-purple-9 q-mt-md">TRAE seguirá los descuentos vigentes de este origen, incluido el descuento habitual del producto. Tus reglas propias quedan guardadas y no se suman. Los combos conservan sus precios propios.</q-banner>
          </template>
          <q-input v-else v-model.number="settingsDraft.descuento_general" type="number" min="0" max="99.99" step="0.01" outlined label="Descuento general" suffix="%" :rules="percentageRules" hint="Se aplica cuando no existe una regla de producto o categoría." :disable="saving" class="q-mt-md" />
          <q-input v-model.number="settingsDraft.comision_porcentaje" type="number" min="0" max="100" step="0.01" outlined label="Comisión del delivery" suffix="%" :rules="commissionRules" hint="Se registra aparte. Conserva la comisión histórica de cada venta." class="q-mt-lg" :disable="saving" />
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md"><q-btn flat label="Cancelar" no-caps :disable="saving" v-close-popup /><q-btn type="submit" unelevated color="deep-purple" label="Guardar configuración" no-caps :loading="saving" /></q-card-actions>
      </q-form></q-card></q-dialog>
      <q-dialog v-model="dialog" persistent><q-card class="editor-dialog"><q-form @submit.prevent="saveRule">
        <q-card-section class="editor-heading"><div class="overview-icon"><q-icon :name="form.product_ids ? 'inventory_2' : 'category'" /></div><div><h2>{{ form.product_ids ? 'Descuento del producto' : 'Descuento por categoría' }}</h2><p>{{ form.product_ids ? form.targetName : 'Incluye los productos actuales y futuros' }}</p></div><q-space /><q-btn flat round dense icon="close" aria-label="Cerrar editor de regla" :disable="saving" v-close-popup /></q-card-section>
        <q-card-section class="q-pt-none"><q-select v-if="!form.product_ids" v-model="form.category_id" :options="filteredCategories" option-value="id" option-label="name" emit-value map-options use-input input-debounce="0" outlined label="Buscar categoría" :rules="[v => !!v || 'Selecciona una categoría']" :disable="saving" @filter="filterCategories" @update:model-value="selectRuleCategory" /><q-input v-model.number="form.porcentaje" outlined type="number" min="0" max="99.99" step="0.01" label="Descuento TRAE" suffix="%" :rules="percentageRules" class="q-mt-md" :disable="saving" /><div class="discount-presets q-mb-md"><button v-for="percent in presets" :key="percent" type="button" :class="{ active: Number(form.porcentaje) === percent }" :disabled="saving" @click="form.porcentaje = percent">{{ percent }} %</button></div><TraeValidity v-model="form.validity" :disabled="saving" /><q-toggle v-model="form.activo" color="deep-purple" label="Regla habilitada" :disable="saving" /><div class="editor-explanation">{{ form.activo ? (form.product_ids ? 'Esta regla tiene prioridad sobre la categoría y el descuento general.' : 'Las reglas individuales de producto tienen prioridad sobre esta categoría.') : 'Al pausar, se vuelve a aplicar la siguiente regla disponible.' }} Usa 0 % para dejarlo sin descuento.</div></q-card-section>
        <q-card-actions align="right" class="q-pa-md"><q-btn flat label="Cancelar" no-caps :disable="saving" v-close-popup /><q-btn type="submit" unelevated color="deep-purple" label="Guardar regla" no-caps :loading="saving" /></q-card-actions>
      </q-form></q-card></q-dialog>
      <q-dialog v-model="showHelp"><q-card class="editor-dialog"><q-card-section class="editor-heading"><div><h2>Un precio, una regla</h2><p>El descuento de mayor prioridad reemplaza los demás.</p></div><q-space /><q-btn flat round dense icon="close" aria-label="Cerrar ayuda" v-close-popup /></q-card-section><q-card-section class="q-pt-none"><div v-for="(step, i) in ['Producto: descuento individual para los productos elegidos.', 'Categoría: se aplica si el producto no tiene una regla activa.', 'General: se utiliza si no hay ninguna regla anterior.']" :key="i" class="help-step"><span>{{ i + 1 }}</span>{{ step }}</div><div class="editor-explanation">0 % significa sin descuento. Pausar o eliminar una regla permite heredar otra. Los medicamentos controlados conservan su exclusión. El neto estimado todavía debe cubrir mercadería, impuestos y gastos.</div></q-card-section></q-card></q-dialog>
    </template>
  </section>
</template>

<script>
import TraeSales from 'components/TraeSales.vue'
import TraeValidity from 'components/TraeValidity.vue'
import InternalCombos from 'components/InternalCombos.vue'
import { roundSalePrice } from 'src/utils/money'

export default {
  name: 'TraePage',
  components: { TraeSales, TraeValidity, InternalCombos },
  data () {
    return {
      loading: false,
      section: 'sales',
      agencias: [],
      agenciasLoading: false,
      saving: false,
      configLoaded: false,
      configError: '',
      loadingProducts: false,
      productsError: '',
      productsRequest: 0,
      productsAbort: null,
      settings: { descuento_general: 0, comision_porcentaje: 12, fuente_precios: 'propio', fuente_agencia_id: null, revision: null },
      priceSourceOptions: [{ label: 'Propios de TRAE', value: 'propio' }, { label: 'Igual que página web', value: 'web' }, { label: 'Igual que APK móvil', value: 'app' }, { label: 'Igual que sucursal', value: 'physical' }],
      settingsDraft: {},
      settingsDialog: false,
      showHelp: false,
      rules: [],
      categories: [],
      filteredCategories: [],
      products: [],
      selected: [],
      bulkPercentage: 5,
      bulkValidity: { permanente: true, fecha_inicio: '', fecha_fin: '' },
      clockNow: Date.now(),
      serverClockOffset: 0,
      clockTimer: null,
      selectionInView: false,
      selectionObserver: null,
      presets: [0, 2, 5, 8, 10, 12, 15],
      search: '',
      category: null,
      page: 1,
      perPage: 20,
      lastPage: 1,
      total: 0,
      tab: 'products',
      ruleSearch: '',
      ruleScope: 'all',
      scopeOptions: [{ label: 'Todas las reglas', value: 'all' }, { label: 'Por producto', value: 'product' }, { label: 'Por categoría', value: 'category' }],
      dialog: false,
      form: {},
      percentageRules: [v => (v !== '' && v !== null && Number.isFinite(Number(v)) && Number(v) >= 0 && Number(v) <= 99.99) || 'Porcentaje entre 0 y 99,99'],
      commissionRules: [v => (v !== '' && v !== null && Number.isFinite(Number(v)) && Number(v) >= 0 && Number(v) <= 100) || 'Porcentaje entre 0 y 100'],
      productColumns: [
        { name: 'product', label: 'Producto', field: 'nombre', align: 'left' },
        { name: 'price', label: 'Precio TRAE', field: 'precio_venta', align: 'right' },
        { name: 'discount', label: 'Descuento actual', field: 'porcentaje', align: 'right' }
      ],
      ruleColumns: [
        { name: 'target', label: 'Producto o categoría', field: row => row.producto || row.categoria, align: 'left' },
        { name: 'porcentaje', label: 'Descuento', field: 'porcentaje', align: 'right' },
        { name: 'validity', label: 'Vigencia', field: 'fecha_fin', align: 'left' },
        { name: 'activo', label: 'Estado', field: 'activo', align: 'left' },
        { name: 'actions', label: '', field: 'id', align: 'right' }
      ]
    }
  },
  computed: {
    isAdmin () { return Number(this.$store.user?.id) === 1 },
    usesOwnPrices () { return (this.settings.fuente_precios || 'propio') === 'propio' },
    priceSourceLabel () {
      const label = this.priceSourceOptions.find(o => o.value === this.settings.fuente_precios)?.label || 'Propios de TRAE'
      const branch = this.agencias.find(a => Number(a.id) === Number(this.settings.fuente_agencia_id))
      return label + (!this.usesOwnPrices && branch ? ` · ${branch.nombre}` : '')
    },
    activeRules () { return this.usesOwnPrices ? this.rules.filter(r => this.ruleState(r) === 'ACTIVA') : [] },
    activeProductRules () { return this.activeRules.filter(r => r.product_id).length },
    activeCategoryRules () { return this.activeRules.filter(r => r.category_id).length },
    selectableProducts () { return this.products.filter(p => p.origen !== 'EXCLUIDO') },
    selectedIds () { return new Set(this.selected.map(p => Number(p.id))) },
    pageSelection () { const count = this.selectableProducts.filter(p => this.isSelected(p)).length; return count === 0 ? false : count === this.selectableProducts.length ? true : null },
    filteredRules () { const search = this.normalized(this.ruleSearch); return this.rules.filter(r => (!search || this.normalized(r.producto || r.categoria).includes(search)) && (this.ruleScope === 'all' || (this.ruleScope === 'product' ? r.product_id : r.category_id))) }
  },
  watch: {
    isAdmin: {
      immediate: true,
      handler (value) {
        if (!value) return
        this.loadAgencias()
        if (this.section === 'pricing') this.load()
      }
    },
    section (value) { if (value === 'pricing' && !this.configLoaded && this.isAdmin) this.load(); this.observeSelection() },
    tab () { this.observeSelection() }
  },
  mounted () { this.clockTimer = setInterval(() => { this.clockNow = Date.now() }, 30000) },
  beforeUnmount () { this.productsRequest++; this.productsAbort?.abort(); clearInterval(this.clockTimer); this.selectionObserver?.disconnect() },
  methods: {
    createComboFromSelection () { const selected = [...this.selected]; this.tab = 'combos'; this.$nextTick(() => this.$refs.internalCombos?.openCreate(selected)) },
    observeSelection () {
      this.$nextTick(() => {
        this.selectionObserver?.disconnect()
        this.selectionInView = false
        const panel = this.$refs.selectionPanel
        if (!panel || typeof IntersectionObserver === 'undefined') return
        this.selectionObserver = new IntersectionObserver(entries => { this.selectionInView = entries.some(entry => entry.isIntersecting) }, { rootMargin: '-60px 0px -90px 0px', threshold: 0.05 })
        this.selectionObserver.observe(panel)
      })
    },
    refresh () {
      if (this.section === 'sales') this.$refs.sales?.loadSales()
      else this.load()
    },
    async loadAgencias () {
      if (this.agenciasLoading) return
      this.agenciasLoading = true
      try { this.agencias = await this.$store.fetchAgencias(this.$axios) } catch (e) { this.error(e) } finally { this.agenciasLoading = false }
    },
    money (value) { return Number(value || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    normalized (value) { return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim() },
    validPercentage (value) { return value !== '' && value !== null && Number.isFinite(Number(value)) && Number(value) >= 0 && Number(value) <= 99.99 },
    sourceLabel (value) { return { PRODUCTO: 'Regla de producto', CATEGORIA: 'Regla de categoría', GENERAL: 'Descuento general', EXCLUIDO: 'Excluido de descuentos', HEREDADO_WEB: 'Igual que página web', HEREDADO_APP: 'Igual que APK móvil', HEREDADO_PHYSICAL: 'Igual que sucursal' }[value] || value },
    readValidity (rule = {}) {
      return { permanente: rule.permanente == null || !!Number(rule.permanente), fecha_inicio: (rule.fecha_inicio || '').slice(0, 16).replace(' ', 'T'), fecha_fin: (rule.fecha_fin || '').slice(0, 16).replace(' ', 'T') }
    },
    validValidity (value) {
      return !!value && (value.permanente || (!!value.fecha_inicio && !!value.fecha_fin && Number.isFinite(Date.parse(value.fecha_inicio)) && Number.isFinite(Date.parse(value.fecha_fin)) && value.fecha_fin > value.fecha_inicio))
    },
    validityPayload (value) {
      if (!this.validValidity(value)) { this.$q.notify({ type: 'negative', message: 'Indica inicio y fin de la vigencia; el fin debe ser posterior al inicio.' }); return null }
      return { permanente: value.permanente, fecha_inicio: value.permanente ? null : value.fecha_inicio.slice(0, 16).replace('T', ' ') + ':00', fecha_fin: value.permanente ? null : value.fecha_fin.slice(0, 16).replace('T', ' ') + ':00' }
    },
    ruleState (rule) {
      if (!Number(rule.activo)) return 'PAUSADA'
      if (rule.permanente == null || Number(rule.permanente)) return 'ACTIVA'
      const now = this.clockNow + this.serverClockOffset
      const start = rule.inicio_ms ?? Date.parse((rule.fecha_inicio || '').replace(' ', 'T') + '-04:00')
      const end = rule.fin_ms ?? Date.parse((rule.fecha_fin || '').replace(' ', 'T') + '-04:00')
      if (!Number.isFinite(start) || !Number.isFinite(end)) return 'VENCIDA'
      return now < start ? 'PROGRAMADA' : now >= end ? 'VENCIDA' : 'ACTIVA'
    },
    stateColor (rule) { return { ACTIVA: 'teal', PROGRAMADA: 'blue', VENCIDA: 'grey-6', PAUSADA: 'orange-8' }[this.ruleState(rule)] },
    validityDate (value) { if (!value) return '—'; const [date, time] = value.replace('T', ' ').split(' '); return date.split('-').reverse().join('/') + ' ' + (time || '').slice(0, 5) },
    previewPrice (product) { return roundSalePrice(Number(product.precio_original) * (1 - (this.validPercentage(this.bulkPercentage) ? Number(this.bulkPercentage) : Number(product.porcentaje)) / 100)) },
    message (e) { const errors = e.response?.data?.errors; return (errors && Object.values(errors).flat()[0]) || e.response?.data?.message || 'No se pudo completar la operación.' },
    error (e) { this.$q.notify({ type: 'negative', message: this.message(e) }) },
    applyConfig (data) {
      const previousSource = `${this.settings.fuente_precios}:${this.settings.fuente_agencia_id}`
      this.settings = { descuento_general: Number(data.settings.descuento_general), comision_porcentaje: Number(data.settings.comision_porcentaje), fuente_precios: data.settings.fuente_precios || 'propio', fuente_agencia_id: data.settings.fuente_agencia_id ? Number(data.settings.fuente_agencia_id) : null, revision: data.settings.revision || null }
      if (previousSource !== `${this.settings.fuente_precios}:${this.settings.fuente_agencia_id}`) this.selected = []
      this.rules = data.rules
      if (Number.isFinite(data.server_time_ms)) this.serverClockOffset = data.server_time_ms - Date.now()
      this.clockNow = Date.now()
      this.configLoaded = true
      this.configError = ''
    },
    async load () {
      if (this.loading || this.saving) return
      this.loading = true
      this.configError = ''
      try {
        const [config, categories] = await Promise.all([this.$axios.get('trae'), this.$axios.get('categories')])
        this.applyConfig(config.data)
        this.categories = categories.data
        this.filteredCategories = categories.data
        await this.loadProducts()
      } catch (e) { this.configError = this.message(e) } finally { this.loading = false }
    },
    filterCategories (value, update) { update(() => { const term = this.normalized(value); this.filteredCategories = this.categories.filter(c => this.normalized(c.name).includes(term)) }) },
    resetPage () { this.page = 1; this.loadProducts() },
    clearFilters () { this.search = ''; this.category = null; this.resetPage() },
    async loadProducts () {
      const sequence = ++this.productsRequest
      this.productsAbort?.abort()
      const controller = new AbortController()
      this.productsAbort = controller
      this.loadingProducts = true
      this.productsError = ''
      try {
        const { data } = await this.$axios.get('trae/products', { params: { search: this.search || undefined, category_id: this.category || undefined, page: this.page, per_page: this.perPage }, signal: controller.signal })
        if (sequence !== this.productsRequest) return
        this.products = data.data
        this.lastPage = data.last_page
        this.total = data.total
        const fresh = new Map(data.data.map(p => [Number(p.id), p]))
        this.selected = this.selected.map(p => fresh.get(Number(p.id)) || p).filter(p => p.origen !== 'EXCLUIDO')
      } catch (e) {
        if (sequence !== this.productsRequest || e.code === 'ERR_CANCELED') return
        this.products = []
        this.productsError = this.message(e)
      } finally { if (sequence === this.productsRequest) this.loadingProducts = false }
    },
    isSelected (product) { return this.selectedIds.has(Number(product.id)) },
    toggleProduct (product) {
      if (this.saving || product.origen === 'EXCLUIDO') return
      if (this.isSelected(product)) { this.selected = this.selected.filter(p => Number(p.id) !== Number(product.id)); return }
      if (this.selected.length >= 200) { this.$q.notify({ type: 'info', message: 'Puedes aplicar un descuento a un máximo de 200 productos por vez.' }); return }
      this.selected = [...this.selected, product]
    },
    togglePageSelection () {
      if (this.saving) return
      const ids = new Set(this.selectableProducts.map(p => Number(p.id)))
      if (this.pageSelection === true) { this.selected = this.selected.filter(p => !ids.has(Number(p.id))); return }
      const additions = this.selectableProducts.filter(p => !this.isSelected(p))
      const capacity = 200 - this.selected.length
      this.selected = [...this.selected, ...additions.slice(0, capacity)]
      if (additions.length > capacity) this.$q.notify({ type: 'info', message: 'Seleccionaste el máximo de 200 productos. Aplica este grupo antes de continuar.' })
    },
    openSettings () { this.settingsDraft = { ...this.settings }; this.settingsDialog = true },
    async saveSettings () {
      if (this.saving || this.loading || !this.configLoaded) return
      if (this.settingsDraft.fuente_precios === 'physical' && !this.settingsDraft.fuente_agencia_id) { this.$q.notify({ type: 'negative', message: 'Selecciona la sucursal cuyos descuentos usará TRAE.' }); return }
      this.saving = true
      try {
        const { data } = await this.$axios.put('trae/settings', this.settingsDraft)
        this.applyConfig(data)
        this.settingsDialog = false
        await this.loadProducts()
        this.$q.notify({ type: 'positive', message: 'Configuración TRAE guardada.' })
      } catch (e) {
        if (e.response?.status === 409) {
          try {
            const { data } = await this.$axios.get('trae')
            this.applyConfig(data)
            this.settingsDraft = { ...this.settings }
            await this.loadProducts()
            this.$q.notify({ type: 'warning', message: 'La configuración cambió en otra sesión. Se cargaron los valores actuales; revísalos antes de guardar.' })
          } catch (refreshError) { this.error(refreshError) }
        } else this.error(e)
      } finally { this.saving = false }
    },
    openCategory () { this.form = { category_id: this.category, porcentaje: 0, activo: true, validity: this.readValidity() }; this.selectRuleCategory(this.category); this.dialog = true },
    selectRuleCategory (id) { const rule = this.rules.find(r => Number(r.category_id) === Number(id) && r.category_id !== null); this.form.porcentaje = rule ? Number(rule.porcentaje) : 0; this.form.activo = rule ? !!Number(rule.activo) : true; this.form.validity = this.readValidity(rule) },
    editRule (rule) { this.form = { ...this.ruleTarget(rule), targetName: rule.producto || rule.categoria, porcentaje: Number(rule.porcentaje), activo: !!Number(rule.activo), validity: this.readValidity(rule) }; this.dialog = true },
    ruleTarget (rule) { return rule.product_id ? { product_ids: [rule.product_id] } : { category_id: rule.category_id } },
    async writeRule (payload) {
      if (this.saving || this.loading || !this.configLoaded || !this.usesOwnPrices) return false
      this.saving = true
      try {
        const { data } = await this.$axios.post('trae/rules', payload)
        this.applyConfig(data)
        await this.loadProducts()
        return true
      } catch (e) { this.error(e); return false } finally { this.saving = false }
    },
    async applySelected () {
      if (!this.selected.length || !this.validPercentage(this.bulkPercentage)) return
      const validity = this.validityPayload(this.bulkValidity)
      if (!validity) return
      const count = this.selected.length
      if (await this.writeRule({ product_ids: this.selected.map(p => p.id), porcentaje: Number(this.bulkPercentage), activo: true, ...validity })) { this.selected = []; this.$q.notify({ type: 'positive', message: `Descuento guardado para ${count} producto${count === 1 ? '' : 's'}.` }) }
    },
    async saveRule () {
      const target = this.form.product_ids ? { product_ids: this.form.product_ids } : { category_id: this.form.category_id }
      const validity = this.validityPayload(this.form.validity)
      if (!validity) return
      if (await this.writeRule({ ...target, porcentaje: this.form.porcentaje, activo: this.form.activo, ...validity })) { this.dialog = false; this.$q.notify({ type: 'positive', message: 'Regla TRAE guardada.' }) }
    },
    toggleRule (rule, active) { this.writeRule({ ...this.ruleTarget(rule), porcentaje: Number(rule.porcentaje), activo: active }) },
    deleteRule (rule) {
      if (!this.usesOwnPrices) return
      this.$q.dialog({ title: 'Eliminar regla TRAE', message: 'Se volverá a aplicar la regla de categoría o el descuento general. Para dejarlo sin descuento, edita esta regla a 0 %.', cancel: true, ok: { label: 'Eliminar regla', color: 'negative' } }).onOk(async () => {
        if (this.saving) return
        this.saving = true
        try {
          await this.$axios.delete(`trae/rules/${rule.id}`)
          const { data } = await this.$axios.get('trae')
          this.applyConfig(data)
          await this.loadProducts()
          this.$q.notify({ type: 'positive', message: 'Regla eliminada. Se aplicará el descuento heredado.' })
        } catch (e) { this.error(e) } finally { this.saving = false }
      })
    }
  }
}
</script>

<style scoped>
.trae-page { --trae-accent: #6d3dd1; --trae-ink: #202637; --trae-muted: #788095; padding: 26px; color: var(--trae-ink); background: #f5f6fa; max-width: 1800px; margin: 0 auto; }
.trae-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.price-source-card { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 20px; margin: 24px 0 16px; border: 1px solid #e4dcf3; border-radius: 16px; }
.price-source-card p { font-size: 12px; color: var(--trae-muted); margin: 4px 0 0; }
.price-source-card .col { min-width: 180px; }
.channel-icon { display: grid; place-items: center; width: 54px; height: 54px; background: #ebe5fa; color: var(--trae-accent); border: 1px solid #ded2f6; border-radius: 16px; margin-right: 14px; }
.eyebrow { color: var(--trae-accent); font-size: 10px; font-weight: 700; letter-spacing: 1.5px; margin-bottom: 3px; }
h1 { font-size: 25px; font-weight: 750; line-height: 1.3; margin: 0; letter-spacing: -.5px; }
h1 span { color: #7b8190; font-size: 18px; font-weight: 400; margin-left: 10px; letter-spacing: -.2px; }
h2 { font-size: 16px; line-height: 1.4; font-weight: 700; margin: 0; }
.section-tabs { display: inline-flex; background: #eaeaf2; border-radius: 12px; padding: 4px; margin-bottom: 24px; max-width: 100%; }
.section-tabs :deep(.q-tab) { min-height: 39px; border-radius: 9px; padding: 0 18px; }
.section-tabs :deep(.q-tab__content) { flex-direction: row; flex-wrap: nowrap; gap: 8px; min-width: 0; }
.section-tabs :deep(.q-tab__icon) { font-size: 19px; width: 19px; height: 19px; margin: 0; }
.section-tabs :deep(.q-tab--active) { background: #fff; box-shadow: 0 2px 6px #2722400d; }
.section-tabs :deep(.q-tab__label) { font-size: 13px; font-weight: 600; }
.pricing-overview { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.overview-card { display: flex; align-items: center; gap: 13px; background: white; border: 1px solid #e5e7ef; border-radius: 15px; padding: 18px; }
.overview-icon { display: grid; place-items: center; width: 41px; height: 41px; background: #f1edfb; color: var(--trae-accent); border-radius: 12px; flex-shrink: 0; font-size: 23px; }
.overview-icon.neutral { color: #5e7295; background: #eef2f8; }
.overview-icon.green { color: #16876d; background: #eaf7f1; }
.overview-body { flex: 1; min-width: 0; }
.overline-label { color: #777e90; font-size: 11px; font-weight: 600; }
.overview-body strong { display: block; font-size: 25px; line-height: 1.4; letter-spacing: -.7px; }
.overview-body > span { display: block; font-size: 10px; color: #788095; }
.priority-note { display: flex; align-items: center; gap: 8px; color: #757d90; margin: 8px 0 16px; font-size: 11px; flex-wrap: wrap; }
.priority-note > span { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
.priority-note b { color: #596175; font-weight: 600; }
.priority-note .q-btn { margin-left: auto; font-size: 11px; }
.pricing-workspace.combos-workspace { grid-template-columns: minmax(0, 1fr); }
.pricing-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 310px; gap: 20px; align-items: start; }
.catalog-card { border: 1px solid #e3e5ed; border-radius: 17px; overflow: hidden; }
.catalog-heading { padding: 22px 20px 17px; display: flex; align-items: center; gap: 14px; justify-content: space-between; flex-wrap: wrap; }
.catalog-heading p { color: var(--trae-muted); font-size: 11px; margin: 5px 0 0; }
.catalog-heading .q-btn { border-radius: 9px; font-size: 12px; }
.catalog-tabs { padding: 0 10px; color: #747c8d; }
.catalog-tabs :deep(.q-tab__label) { font-size: 12px; font-weight: 600; }
.catalog-filters { display: flex; gap: 10px; padding: 18px 18px 8px; }
.product-search { flex: 1; min-width: 0; }
.category-filter { width: 205px; }
.catalog-filters :deep(.q-field__control) { border-radius: 9px; }
.catalog-filters :deep(.q-field__native), .catalog-filters :deep(.q-field__label) { font-size: 12px; }
.catalog-meta { display: flex; justify-content: space-between; align-items: center; padding: 4px 20px 10px; min-height: 33px; color: #788095; font-size: 10px; }
.catalog-meta .q-btn { font-size: 10px; }
.product-table :deep(th), .rules-table :deep(th) { color: #788095; background: #fafbfe; font-size: 10px; font-weight: 600; border-top: 1px solid #f0f1f6; }
.product-table :deep(td) { height: 77px; border-color: #f0f1f6; }
.product-table :deep(tr.selected) { background: #f7f3ff; }
.product-table :deep(td:first-child), .product-table :deep(th:first-child) { padding-left: 12px; padding-right: 0; width: 42px; }
.product-identity { display: flex; align-items: center; gap: 11px; max-width: 350px; white-space: normal; }
.product-avatar { color: #9a92b1; background: #f4f2f8; flex-shrink: 0; }
.product-name { font-size: 12px; font-weight: 600; line-height: 1.5; color: #363d50; }
.product-category { font-size: 10px; line-height: 1.7; color: #788095; margin-top: 3px; }
.trae-price { font-size: 14px; font-weight: 700; color: #6240ab; white-space: nowrap; }
.base-price { font-size: 10px; color: #858b9b; margin-top: 4px; white-space: nowrap; }
.discount-pill { display: inline-block; color: #6d45b5; background: #f0eafa; border-radius: 6px; font-size: 11px; font-weight: 700; padding: 4px 8px; white-space: nowrap; }
.discount-pill.no-discount { background: #f0f2f5; color: #747c8d; }
.rule-source { font-size: 9px; color: #788095; margin-top: 5px; }
.catalog-footer { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; border-top: 1px solid #f0f1f6; padding: 10px 16px; }
.catalog-footer .q-select { width: 125px; font-size: 11px; }
.catalog-footer :deep(.q-btn) { font-size: 11px; min-height: 28px; min-width: 28px; }
.selection-column { position: sticky; top: 74px; }
.selection-card { max-height: calc(100vh - 160px); overflow-y: auto; border-radius: 17px; border: 1px solid #ded5f1; box-shadow: 0 5px 22px #4c2e8110; }
.selection-title { display: flex; align-items: center; gap: 10px; padding: 20px 18px; border-bottom: 1px solid #eeeaf5; background: #fcfbff; }
.selection-title h2 { font-size: 15px; }
.selection-title span { font-size: 10px; color: #81778f; }
.selection-title .q-btn { margin-left: auto; }
.step-icon { width: 33px; height: 33px; display: grid; place-items: center; color: #7953bb; background: #eee7fa; border-radius: 10px; font-size: 15px; font-weight: 700; flex-shrink: 0; }
.selection-hint { display: flex; align-items: center; gap: 7px; margin: 12px 16px 0; font-size: 10px; color: #81778f; line-height: 1.6; }
.selection-hint .q-icon { color: #21856d; flex-shrink: 0; }
.selected-products { max-height: 245px; overflow-y: auto; padding: 5px 16px; }
.selected-product { display: flex; align-items: center; gap: 7px; padding: 11px 0; border-bottom: 1px solid #f0edf6; }
.selected-name { font-size: 11px; color: #53586a; line-height: 1.5; }
.selected-preview { display: flex; gap: 6px; align-items: center; font-size: 10px; color: #8b8298; margin-top: 3px; }
.selected-preview strong { color: #7950bf; font-weight: 600; }
.bulk-form { padding: 16px; }
.field-label { display: block; font-size: 11px; font-weight: 600; color: #626276; margin-bottom: 9px; }
.bulk-input :deep(.q-field__control) { border-radius: 10px; }
.bulk-input :deep(.q-field__native) { font-size: 23px; font-weight: 700; color: #6d3dd1; }
.bulk-input :deep(.q-field__append) { color: #8d7bab; font-size: 19px; }
.preset-label { font-size: 9px; font-weight: 600; color: #82768f; margin-bottom: 7px; }
.discount-presets { display: flex; flex-wrap: wrap; gap: 5px; }
.discount-presets button { border: 1px solid #e7e3ef; border-radius: 7px; color: #776b87; background: white; padding: 6px 9px; cursor: pointer; font: inherit; font-size: 10px; transition: background .15s, color .15s; }
.discount-presets button.active { border-color: #9263de; color: #7644bf; background: #f1eafb; font-weight: 600; }
.discount-presets button:disabled { cursor: default; opacity: .6; }
.zero-note { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #82768f; padding: 15px 0; }
.apply-button { border-radius: 9px; font-size: 12px; min-height: 42px; }
.preview-note { text-align: center; color: #82768f; font-size: 9px; line-height: 1.6; margin-top: 10px; }
.empty-selection { padding: 29px 16px 19px; text-align: center; }
.empty-selection-art { background: #f8f5fd; color: #b8a2dc; border: 1px dashed #dfd4ef; display: grid; place-items: center; width: 81px; height: 81px; border-radius: 24px; margin: 0 auto 18px; }
.empty-selection strong { font-size: 13px; color: #71657f; }
.empty-selection p { font-size: 11px; line-height: 1.9; color: #897a98; margin: 10px 0 20px; }
.empty-selection-tip { font-size: 9px; color: #897a98; border-top: 1px solid #f1ecf6; padding-top: 15px; }
.channel-note { display: flex; gap: 9px; align-items: flex-start; color: #818799; font-size: 10px; line-height: 1.8; padding: 17px 8px; }
.channel-note .q-icon { color: #91819f; flex-shrink: 0; margin-top: 2px; }
.editor-dialog { width: 490px; max-width: 95vw; border-radius: 18px; color: #363d50; }
.editor-heading { display: flex; align-items: center; flex-wrap: nowrap; gap: 12px; padding: 24px; }
.editor-heading h2 { font-size: 18px; }
.editor-heading p { font-size: 11px; color: #81778f; margin: 4px 0 0; }
.editor-dialog > .q-card__section, .editor-dialog form > .q-card__section { padding-left: 24px; padding-right: 24px; }
.editor-explanation { font-size: 11px; line-height: 1.8; color: #81748e; background: #f6f2fb; padding: 13px; border-radius: 9px; margin-top: 16px; }
.help-step { display: flex; gap: 12px; align-items: center; padding: 12px 0; color: #6a6479; font-size: 12px; }
.help-step > span { display: grid; place-items: center; width: 27px; height: 27px; border-radius: 8px; background: #f1eafb; color: #7f4fbe; flex-shrink: 0; }
.load-error { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; padding: 10px 14px; border-radius: 10px; background: #fff0f1; color: #b34c60; font-size: 12px; }
.load-error .q-btn { margin-left: auto; }
.mobile-product { border: 1px solid #e9e6ef; border-radius: 12px; padding: 6px 12px 12px 3px; }
.mobile-product.chosen { border-color: #bf9ae9; background: #faf7ff; }
.mobile-selection-bar { position: fixed; bottom: 12px; left: 12px; right: 12px; z-index: 1000; display: flex; justify-content: space-between; align-items: center; gap: 8px; border: 1px solid #ded2ef; background: #fff; padding: 12px; border-radius: 13px; box-shadow: 0 5px 25px #35215330; }
.mobile-selection-bar strong { display: block; font-size: 12px; color: #6c43a5; }
.mobile-selection-bar span { display: block; font-size: 9px; color: #81778f; margin-top: 3px; }
.mobile-selection-bar .q-btn { font-size: 11px; border-radius: 8px; }
@media (max-width: 1350px) {
  .trae-page { padding: 20px; }
  .pricing-workspace { grid-template-columns: minmax(0, 1fr) 280px; gap: 14px; }
  .overview-card { padding: 15px 12px; gap: 9px; }
  .overview-body > span { font-size: 9px; }
  .overview-icon { width: 34px; height: 34px; font-size: 20px; }
  .category-filter { width: 175px; }
  .product-avatar { display: none; }
}
@media (max-width: 1000px) {
  .trae-page.has-selection { padding-bottom: 95px; }
  .pricing-workspace { grid-template-columns: minmax(0, 1fr); }
  .selection-column { position: static; }
  .selection-card { max-height: none; }
  .selected-products { max-height: 200px; }
  .empty-selection { padding: 20px; }
  .empty-selection-art { display: none; }
  .empty-selection p br { display: none; }
  .empty-selection p { margin-bottom: 10px; }
  .channel-note { padding: 12px 6px; }
  .rules-summary { display: none; }
  .pricing-overview { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 599px) {
  .trae-page { padding: 16px 12px; }
  .channel-icon { width: 40px; height: 40px; border-radius: 12px; margin-right: 10px; }
  .eyebrow { font-size: 8px; letter-spacing: 1.2px; }
  h1 { font-size: 22px; }
  h1 span { display: block; font-size: 11px; margin: 2px 0 0; letter-spacing: 0; }
  .trae-header { margin-bottom: 17px; gap: 8px; }
  .trae-header > .q-btn { padding: 4px; }
  .trae-header > .q-btn :deep(.q-btn__content > span) { display: none; }
  .section-tabs { display: flex; width: 100%; margin-bottom: 18px; }
  .section-tabs :deep(.q-tab) { padding: 0 8px; }
  .section-tabs :deep(.q-tab__label) { font-size: 11px; }
  .pricing-overview { gap: 8px; }
  .overview-card { padding: 13px 10px; flex-wrap: wrap; gap: 8px; position: relative; }
  .overview-body { flex-basis: 100%; }
  .overview-body strong { font-size: 26px; }
  .overview-body > span { font-size: 9px; line-height: 1.6; }
  .overview-card > .q-btn { position: absolute; right: 7px; top: 12px; }
  .overline-label { font-size: 10px; }
  .priority-note { gap: 6px; line-height: 1.8; margin-bottom: 12px; }
  .priority-note .q-btn { margin-left: 0; }
  .catalog-heading { padding: 18px 16px 14px; gap: 12px; }
  .catalog-heading h2 { font-size: 14px; }
  .catalog-heading p { font-size: 10px; }
  .catalog-heading > .q-btn { width: 100%; }
  .catalog-tabs { padding: 0; }
  .catalog-tabs :deep(.q-tab__label) { font-size: 11px; }
  .catalog-tabs :deep(.q-tab) { padding: 0 10px; }
  .catalog-filters { padding: 14px 16px 6px; flex-wrap: wrap; gap: 8px; }
  .product-search, .category-filter { width: 100%; flex-basis: 100%; }
  .catalog-meta { padding: 6px 16px 14px; }
  .catalog-footer { justify-content: center; }
  .selection-title { padding: 17px; }
  .editor-heading { padding: 20px; }
  .editor-heading h2 { font-size: 16px; }
}
</style>
