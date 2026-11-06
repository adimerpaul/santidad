<template>
  <span class="sale-combo-control"><q-btn unelevated no-caps class="combo-trigger" color="deep-purple" icon="inventory_2" label="Combos TRAE" :disable="disabled" @click="open" /><q-dialog v-model="dialog"><q-card class="picker-card"><q-card-section class="row items-center"><div><div class="text-h6 text-weight-bold">Combos internos TRAE</div><div class="text-caption text-grey-7">Al cargar un combo, toda la venta se registra como TRAE.</div></div><q-space /><q-btn flat round icon="close" v-close-popup /></q-card-section><q-card-section class="q-pt-none"><q-input v-model="search" outlined dense clearable debounce="300" placeholder="Buscar combo…" @update:model-value="page = 1; load()"><template #prepend><q-icon name="search" /></template></q-input><q-linear-progress v-if="loading" indeterminate color="deep-purple" class="q-mt-md" /><div v-if="errorMessage" class="text-negative q-mt-md">{{ errorMessage }}</div><div v-for="combo in combos" :key="combo.id" class="combo-choice"><div class="row items-start q-gutter-sm"><div class="col"><div class="text-caption text-grey-6">COMBO #{{ combo.id }}</div><strong>{{ combo.nombre }}</strong><div class="text-h6 text-deep-purple">Bs {{ money(combo.precio_total) }}</div><div class="text-caption text-grey-7">{{ combo.items.map(p => `${p.cantidad} × ${p.nombre}`).join(' · ') }}</div><div class="text-caption q-mt-xs" :class="combo.combos_disponibles ? 'text-teal' : 'text-negative'">{{ !combo.combos_disponibles && canLoad(combo) ? 'Se puede cargar · requiere sustituir el producto sin stock' : `${combo.combos_disponibles} combos disponibles en esta sucursal` }}</div></div><q-btn v-if="combo.items.some(p => p.reemplazable)" outline color="deep-purple" no-caps icon="swap_horiz" label="Sustituir producto" :disable="disabled || !!loadingId" @click="openSubstitutes(combo)" /><q-btn unelevated color="deep-purple" no-caps :label="!combo.combos_disponibles && canLoad(combo) ? 'Cargar y sustituir' : 'Cargar combo'" :disable="disabled || loading || !canLoad(combo)" :loading="loadingId === combo.id" @click="choose(combo)" /></div></div><div v-if="!loading && !combos.length" class="q-pa-lg text-center text-grey-6">No hay combos vigentes para esta venta.</div><q-pagination v-model="page" :max="lastPage" :max-pages="5" color="deep-purple" class="justify-center q-mt-md" @update:model-value="load" /></q-card-section></q-card></q-dialog>
    <q-dialog v-model="replacementDialog"><q-card class="picker-card">
      <q-card-section class="row items-center"><div class="col"><div class="text-h6">Sustituir producto</div><div class="text-caption">{{ replacementCombo?.nombre }} · Total Bs {{ money(replacementCombo?.precio_total) }}</div></div><q-btn flat round icon="close" v-close-popup /></q-card-section>
      <q-card-section class="q-pt-none">
        <q-banner dense rounded class="bg-deep-purple-1 q-mb-md">{{ replacementCombo?.items.find(p => p.reemplazable)?.cantidad }} × {{ replacementCombo?.items.find(p => p.reemplazable)?.nombre }} · Bs {{ money(replacementCombo?.items.find(p => p.reemplazable)?.precio_unitario) }} c/u. Se conserva la cantidad y el precio del producto original. Solo cambia el producto entregado y su stock.</q-banner>
        <q-input v-model="replacementSearch" outlined dense clearable debounce="300" label="Buscar sustituto por nombre o código" @update:model-value="replacementPage = 1; loadSubstitutes()" />
        <q-linear-progress v-if="replacementLoading" indeterminate color="deep-purple" />
        <div v-if="replacementError" class="text-negative q-my-sm">{{ replacementError }}</div>
        <q-list separator><q-item v-for="product in substitutes" :key="product.id"><q-item-section avatar><q-img :src="productImage(product.imagen)" :alt="product.nombre" width="52px" height="52px" fit="contain" class="substitute-image"><template #error><div class="absolute-full flex flex-center bg-grey-2 text-grey-5"><q-icon name="image_not_supported" size="24px" /></div></template></q-img></q-item-section><q-item-section><q-item-label>{{ product.nombre }}</q-item-label><q-item-label caption>{{ product.stock }} disponibles{{ product.es_original ? ' · Producto original' : '' }}</q-item-label></q-item-section><q-item-section side><q-btn no-caps color="deep-purple" label="Elegir y cargar" :loading="loadingId === replacementCombo?.id" :disable="disabled || replacementLoading || !product.stock || !!loadingId" @click="choose(replacementCombo, product.id)" /></q-item-section></q-item></q-list>
        <div v-if="!replacementLoading && !substitutes.length" class="text-grey q-pa-md">Sin resultados.</div>
        <q-pagination v-model="replacementPage" :max="replacementLastPage" :max-pages="5" @update:model-value="loadSubstitutes()" />
      </q-card-section>
    </q-card></q-dialog></span>
</template>

<script>
export default {
  name: 'SaleComboPicker',
  props: { disabled: Boolean, agenciaId: [String, Number] },
  emits: ['select'],
  data () { return { dialog: false, search: '', page: 1, lastPage: 1, combos: [], loading: false, loadingId: null, errorMessage: '', sequence: 0, selectionSequence: 0, disposed: false, replacementDialog: false, replacementCombo: null, replacementSearch: '', replacementPage: 1, replacementLastPage: 1, substitutes: [], replacementLoading: false, replacementError: '', replacementSequence: 0, basketGroup: null } },
  watch: {
    replacementDialog (open) { if (!open) this.replacementSequence++ },
    dialog (open) { if (!open) { this.replacementDialog = false; this.replacementSequence++; this.sequence++; this.selectionSequence++; this.loading = false } },
    agenciaId () { this.replacementDialog = false; this.replacementSequence++; this.sequence++; this.selectionSequence++; this.combos = []; this.page = 1; if (this.dialog) this.load() }
  },
  beforeUnmount () { this.replacementSequence++; this.sequence++; this.selectionSequence++; this.disposed = true },
  methods: {
    canLoad (combo) { return combo.combos_disponibles > 0 || (combo.items?.some(p => p.reemplazable) && combo.items.every(p => p.reemplazable || p.stock >= p.cantidad)) },
    productImage (image) { if (/^https?:\/\//i.test(image || '')) return image; return new URL('../images/' + encodeURIComponent(image || 'productDefault.jpg'), this.$url).href },
    money (v) { return Number(v || 0).toFixed(2) },
    open () { if (this.disabled) return; if (!Number(this.agenciaId)) { this.$alert.error('Selecciona la sucursal antes de cargar un combo.'); return } this.basketGroup = null; this.selectionSequence++; this.dialog = true; this.page = 1; this.load() },
    async load () { const seq = ++this.sequence; const agency = this.agenciaId; this.loading = true; this.errorMessage = ''; this.combos = []; try { const { data } = await this.$axios.get('internal-combos/available', { params: { search: this.search || undefined, page: this.page, agencia_id: agency, canal_venta: 'trae' } }); if (seq !== this.sequence || this.disposed || Number(agency) !== Number(this.agenciaId)) return; this.combos = data.data; this.lastPage = data.last_page } catch (e) { if (seq === this.sequence && !this.disposed) this.errorMessage = 'No se pudieron cargar los combos.' } finally { if (seq === this.sequence) this.loading = false } },
    openSubstitutes (combo, group = null) {
      if (this.disabled || this.loadingId) return
      this.basketGroup = group; this.dialog = true; this.replacementCombo = combo; this.replacementSearch = ''; this.replacementPage = 1
      this.replacementDialog = true; this.loadSubstitutes()
    },
    async loadSubstitutes () {
      const combo = this.replacementCombo; const agency = this.agenciaId; const seq = ++this.replacementSequence
      if (!combo || !this.replacementDialog) return
      this.substitutes = []; this.replacementLoading = true; this.replacementError = ''
      try {
        const { data } = await this.$axios.get(`internal-combos/${combo.id}/substitutes`, { params: { agencia_id: agency, search: this.replacementSearch || undefined, page: this.replacementPage } })
        if (seq !== this.replacementSequence || this.disposed || !this.replacementDialog || Number(agency) !== Number(this.agenciaId)) return
        this.substitutes = data.data; this.replacementLastPage = data.last_page
      } catch (e) { if (seq === this.replacementSequence) this.replacementError = e.response?.data?.message || 'No se pudieron cargar los sustitutos.' } finally { if (seq === this.replacementSequence) this.replacementLoading = false }
    },
    async choose (combo, replacementId = null) { if (this.disabled || this.loadingId) return; this.loadingId = combo.id; const agency = this.agenciaId; const selection = this.selectionSequence; const replacementSeq = this.replacementSequence; try { const { data } = await this.$axios.post(`internal-combos/${combo.id}/load`, { agencia_id: agency, canal_venta: 'trae', cantidad: 1, ...(!replacementId ? { permitir_pendiente: true } : {}), ...(replacementId ? { reemplazo_product_id: replacementId } : {}) }); if ((replacementId && (!this.replacementDialog || replacementSeq !== this.replacementSequence)) || this.disposed || this.disabled || !this.dialog || selection !== this.selectionSequence || Number(agency) !== Number(this.agenciaId)) return; this.$emit('select', data, this.basketGroup); this.replacementDialog = false; this.dialog = false } catch (e) { if (!this.disposed && selection === this.selectionSequence) this.$alert.error(e.response?.data?.message || 'No se pudo cargar el combo.') } finally { this.loadingId = null } }
  }
}
</script>

<style scoped>
.sale-combo-control { display: inline-flex; }
.combo-trigger { min-height: 36px; border-radius: 10px; padding: 0 12px; font-size: 12px; font-weight: 600; background: #7545bc !important; }
.combo-trigger :deep(.q-icon.on-left) { font-size: 19px; margin-right: 6px; }
.substitute-image { border: 1px solid #eee9f4; border-radius: 9px; background: white; }
.picker-card { width: 720px; max-width: 95vw; border-radius: 18px; }
.combo-choice { padding: 18px 0; border-bottom: 1px solid #ebe5f1; }
</style>
