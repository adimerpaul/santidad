<template>
  <span class="sale-combo-control"><q-btn unelevated no-caps class="combo-trigger" color="deep-purple" icon="inventory_2" label="Combos TRAE" :disable="disabled" @click="open" /><q-dialog v-model="dialog"><q-card class="picker-card"><q-card-section class="row items-center"><div><div class="text-h6 text-weight-bold">Combos internos TRAE</div><div class="text-caption text-grey-7">Al cargar un combo, toda la venta se registra como TRAE.</div></div><q-space /><q-btn flat round icon="close" v-close-popup /></q-card-section><q-card-section class="q-pt-none"><q-input v-model="search" outlined dense clearable debounce="300" placeholder="Buscar combo…" @update:model-value="page = 1; load()"><template #prepend><q-icon name="search" /></template></q-input><q-linear-progress v-if="loading" indeterminate color="deep-purple" class="q-mt-md" /><div v-if="errorMessage" class="text-negative q-mt-md">{{ errorMessage }}</div><div v-for="combo in combos" :key="combo.id" class="combo-choice"><div class="row items-start q-gutter-sm"><div class="col"><div class="text-caption text-grey-6">COMBO #{{ combo.id }}</div><strong>{{ combo.nombre }}</strong><div class="text-h6 text-deep-purple">Bs {{ money(combo.precio_total) }}</div><div class="text-caption text-grey-7">{{ combo.items.map(p => `${p.cantidad} × ${p.nombre}`).join(' · ') }}</div><div class="text-caption q-mt-xs" :class="combo.combos_disponibles ? 'text-teal' : 'text-negative'">{{ combo.combos_disponibles }} combos disponibles en esta sucursal</div></div><q-btn unelevated color="deep-purple" no-caps label="Cargar combo" :disable="disabled || loading || !combo.combos_disponibles" :loading="loadingId === combo.id" @click="choose(combo)" /></div></div><div v-if="!loading && !combos.length" class="q-pa-lg text-center text-grey-6">No hay combos vigentes para esta venta.</div><q-pagination v-model="page" :max="lastPage" :max-pages="5" color="deep-purple" class="justify-center q-mt-md" @update:model-value="load" /></q-card-section></q-card></q-dialog></span>
</template>

<script>
export default {
  name: 'SaleComboPicker',
  props: { disabled: Boolean, agenciaId: [String, Number] },
  emits: ['select'],
  data () { return { dialog: false, search: '', page: 1, lastPage: 1, combos: [], loading: false, loadingId: null, errorMessage: '', sequence: 0, selectionSequence: 0, disposed: false } },
  watch: {
    dialog (open) { if (!open) { this.sequence++; this.selectionSequence++; this.loading = false } },
    agenciaId () { this.sequence++; this.selectionSequence++; this.combos = []; this.page = 1; if (this.dialog) this.load() }
  },
  beforeUnmount () { this.sequence++; this.selectionSequence++; this.disposed = true },
  methods: {
    money (v) { return Number(v || 0).toFixed(2) },
    open () { if (this.disabled) return; if (!Number(this.agenciaId)) { this.$alert.error('Selecciona la sucursal antes de cargar un combo.'); return } this.selectionSequence++; this.dialog = true; this.page = 1; this.load() },
    async load () { const seq = ++this.sequence; const agency = this.agenciaId; this.loading = true; this.errorMessage = ''; this.combos = []; try { const { data } = await this.$axios.get('internal-combos/available', { params: { search: this.search || undefined, page: this.page, agencia_id: agency, canal_venta: 'trae' } }); if (seq !== this.sequence || this.disposed || Number(agency) !== Number(this.agenciaId)) return; this.combos = data.data; this.lastPage = data.last_page } catch (e) { if (seq === this.sequence && !this.disposed) this.errorMessage = 'No se pudieron cargar los combos.' } finally { if (seq === this.sequence) this.loading = false } },
    async choose (combo) { if (this.disabled || this.loadingId) return; this.loadingId = combo.id; const agency = this.agenciaId; const selection = this.selectionSequence; try { const { data } = await this.$axios.post(`internal-combos/${combo.id}/load`, { agencia_id: agency, canal_venta: 'trae', cantidad: 1 }); if (this.disposed || this.disabled || !this.dialog || selection !== this.selectionSequence || Number(agency) !== Number(this.agenciaId)) return; this.$emit('select', data); this.dialog = false } catch (e) { if (!this.disposed && selection === this.selectionSequence) this.$alert.error(e.response?.data?.message || 'No se pudo cargar el combo.') } finally { this.loadingId = null } }
  }
}
</script>

<style scoped>
.sale-combo-control { display: inline-flex; }
.combo-trigger { min-height: 36px; border-radius: 10px; padding: 0 12px; font-size: 12px; font-weight: 600; background: #7545bc !important; }
.combo-trigger :deep(.q-icon.on-left) { font-size: 19px; margin-right: 6px; }
.picker-card { width: 720px; max-width: 95vw; border-radius: 18px; }
.combo-choice { padding: 18px 0; border-bottom: 1px solid #ebe5f1; }
</style>
