<template>
  <div class="internal-combos">
    <q-banner dense rounded class="bg-deep-purple-1 text-deep-purple-9 q-mb-md"><template #avatar><q-icon name="lock" /></template>Combos internos de caja. No se publican en la API de delivery. Al cargarlos, la venta se registra automáticamente como TRAE.</q-banner>
    <div class="row q-gutter-sm items-center q-mb-md"><q-input v-model="search" outlined dense debounce="300" clearable placeholder="Buscar combo…" class="col" @update:model-value="page = 1; load()"><template #prepend><q-icon name="search" /></template></q-input><q-btn unelevated color="deep-purple" icon="add" label="Nuevo combo" no-caps @click="openCreate()" /><q-btn flat round icon="refresh" :loading="loading" @click="load" /></div>
    <q-linear-progress v-if="loading" indeterminate color="deep-purple" />
    <div v-if="errorMessage" class="text-negative q-pa-sm">{{ errorMessage }}</div>
    <div class="combo-grid">
      <q-card v-for="combo in combos" :key="combo.id" flat bordered class="combo-card">
        <q-card-section><div class="row items-start no-wrap"><q-avatar icon="inventory" color="deep-purple-1" text-color="deep-purple" /><div class="col q-ml-sm"><div class="text-caption text-grey-6">COMBO #{{ combo.id }}</div><div class="text-subtitle1 text-weight-bold">{{ combo.nombre }}</div></div><q-badge :color="combo.activo ? 'teal' : 'grey'">{{ combo.activo ? 'Habilitado' : 'Pausado' }}</q-badge></div><div class="combo-price">Bs {{ money(combo.precio_total) }} <small>Normal Bs {{ money(combo.precio_base) }}</small></div><div class="text-caption text-grey-7">{{ combo.items.length }} productos · {{ Number(combo.porcentaje_efectivo).toFixed(2) }} % de descuento efectivo</div><div class="combo-ingredients"><div v-for="item in combo.items" :key="item.product_id">{{ item.cantidad }} × {{ item.nombre }}<span>Bs {{ money(item.precio_unitario) }} c/u</span></div></div><div class="text-caption text-grey-6">{{ combo.permanente ? 'Vigencia indefinida' : `Desde ${combo.fecha_inicio} hasta ${combo.fecha_fin} (Bolivia)` }}</div></q-card-section>
        <q-card-actions align="right"><q-btn flat no-caps icon="edit" label="Editar" color="deep-purple" @click="edit(combo)" /><q-btn flat no-caps icon="content_copy" label="Copiar" @click="copy(combo)" /><q-btn flat round icon="delete_outline" color="negative" :aria-label="`Eliminar ${combo.nombre}`" @click="remove(combo)" /></q-card-actions>
      </q-card>
    </div>
    <div v-if="!loading && !combos.length" class="text-center text-grey-6 q-pa-xl"><q-icon name="inventory" size="44px" /><p>Crea tu primer combo, asígnale un nombre y elige sus productos.</p></div>
    <q-pagination v-model="page" :max="lastPage" :max-pages="5" color="deep-purple" class="q-mt-md justify-center" @update:model-value="load" />
    <q-dialog v-model="dialog" persistent maximized-on-mobile><q-card class="combo-editor">
      <q-form @submit.prevent="save">
        <q-card-section class="row items-center"><q-avatar icon="inventory" color="deep-purple-1" text-color="deep-purple" /><div class="q-ml-md"><div class="text-h6 text-weight-bold">{{ form.id ? `Editar combo #${form.id}` : 'Crear combo interno' }}</div><div class="text-caption text-grey-7">Sus precios son propios y no modifican el catálogo público.</div></div><q-space /><q-btn flat round dense icon="close" aria-label="Cerrar combo" :disable="saving || calculating" v-close-popup /></q-card-section>
        <q-separator />
        <q-card-section class="combo-editor-body">
          <q-input v-model.trim="form.nombre" outlined label="Nombre del combo" placeholder="Ej. COMBO CONVITE" maxlength="100" :rules="[v => !!v?.trim() || 'Escribe el nombre']" :disable="saving || calculating" />
          <q-select v-model="picker" :options="options" option-label="nombre" use-input input-debounce="300" outlined dense clearable label="Agregar producto por nombre o código" :loading="searching" :disable="saving || calculating || form.items.length >= 50" @filter="findProducts" @update:model-value="addProduct"><template #prepend><q-icon name="search" /></template><template #no-option><q-item><q-item-section>Sin resultados</q-item-section></q-item></template></q-select>
          <q-btn-toggle v-model="form.modo" :options="modes" spread no-caps unelevated toggle-color="deep-purple" color="grey-2" text-color="grey-8" class="q-my-md" :disable="saving || calculating" @update:model-value="modeChanged" />
          <div v-if="form.modo === 'conjunto'" class="q-mb-md"><q-input v-model.number="form.porcentaje" type="number" outlined dense label="Descuento para todo el combo" suffix="%" min="0" max="99.99" step="0.01" :disable="saving || calculating" :rules="[percentRule]" @update:model-value="globalChanged" /></div>
          <div v-if="form.modo === 'total'" class="row q-col-gutter-sm q-mb-md"><div class="col"><q-input v-model.number="form.precio_objetivo" type="number" outlined dense label="Precio total del combo" prefix="Bs" min="0.1" step="0.1" :disable="saving || calculating" @update:model-value="previewOK = false" /></div><div class="col-auto"><q-btn unelevated no-caps color="deep-purple" label="Repartir total" :loading="calculating" :disable="saving || !form.items.length" @click="calculateTotal" /></div><div class="col-12 text-caption text-grey-7">Si cambias el total, los productos o sus cantidades, pulsa Repartir total. Cambiar la sustitución conserva los precios guardados.</div></div>
          <div class="combo-items-table scroll"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Normal c/u</th><th>Descuento %</th><th>Precio combo c/u</th><th>Total</th><th /></tr></thead><tbody><tr v-for="(item, i) in form.items" :key="item.product_id"><td><strong>{{ item.nombre }}</strong><div class="combo-replacement-row"><q-btn
                class="combo-replacement-toggle" :class="{ 'is-selected': item.reemplazable }"
                flat no-caps rounded dense :icon="item.reemplazable ? 'check_circle' : 'swap_horiz'"
                :label="item.reemplazable ? 'Sustitución permitida' : 'Permitir sustitución'"
                :aria-pressed="!!item.reemplazable" :disable="saving || calculating"
                @click="toggleReplaceable(item)"
              /></div><small v-if="item.excluido">Excluido de descuentos</small></td><td><q-input v-model.number="item.cantidad" type="number" dense outlined min="1" max="1000" :disable="saving || calculating" :rules="[v => Number.isInteger(Number(v)) && v >= 1 && v <= 1000 || 'Cantidad inválida']" @update:model-value="quantitiesChanged" /></td><td>Bs {{ money(item.precio_base) }}</td><td><q-input :model-value="linePercent(item)" type="number" dense outlined min="0" max="100" step="any" :aria-label="`Descuento de ${item.nombre}`" :disable="saving || calculating || item.excluido" @update:model-value="linePercentage(item, $event)" @blur="finishLinePercentage(item)" /></td><td><q-input v-model.number="item.precio_unitario" type="number" dense outlined min="0.1" step="0.1" :disable="saving || calculating || item.excluido" @update:model-value="linePrice(item)" @blur="normalizeLinePrice(item)" /></td><td class="text-weight-bold">Bs {{ money(item.cantidad * item.precio_unitario) }}</td><td><q-btn flat round dense icon="close" :disable="saving || calculating" :aria-label="`Quitar ${item.nombre}`" @click="form.items.splice(i, 1); quantitiesChanged()" /></td></tr></tbody></table></div>
          <div v-if="!form.items.length" class="text-grey-6 text-center q-pa-md">Agrega al menos un producto. Máximo 50 por combo.</div>
          <div class="combo-summary"><div>Precio normal<strong>Bs {{ money(totals.base) }}</strong></div><div>Descuento efectivo<strong>{{ totals.porcentaje.toFixed(2) }} % · Bs {{ money(totals.ahorro) }}</strong></div><div class="combo-summary-total">Total del combo<strong>Bs {{ money(totals.total) }}</strong></div></div>
          <div class="text-caption text-grey-7 q-mb-md">Editar un precio o porcentaje individual cambia al modo Por producto. Los precios guardados se mantienen hasta editar el combo; al editar se usa el precio normal actual como referencia.</div>
          <TraeValidity v-model="form.validity" :disabled="saving || calculating" /><q-toggle v-model="form.activo" color="deep-purple" label="Disponible para cargar en Ventas" :disable="saving || calculating" />
          <q-banner dense rounded class="bg-amber-1 text-amber-10 q-mt-sm">Se carga completo y descuenta el stock de cada producto. La venta queda marcada como TRAE y registra su comisión.</q-banner>
        </q-card-section>
        <q-separator /><q-card-actions align="right" class="q-pa-md"><q-btn flat no-caps label="Cancelar" :disable="saving || calculating" v-close-popup /><q-btn type="submit" unelevated no-caps color="deep-purple" label="Guardar combo" :loading="saving" :disable="!form.items.length || (form.modo === 'total' && !previewOK)" /></q-card-actions>
      </q-form>
    </q-card></q-dialog>
  </div>
</template>

<script>
import TraeValidity from './TraeValidity.vue'
import { comboTotals, comboUnitPrice } from 'src/utils/internal-combos'
import { roundSalePrice } from 'src/utils/money'

export default {
  name: 'InternalCombos',
  components: { TraeValidity },
  emits: ['created'],
  data () { return { combos: [], search: '', page: 1, lastPage: 1, loading: false, errorMessage: '', dialog: false, saving: false, calculating: false, previewOK: false, picker: null, options: [], searching: false, sequence: 0, searchSequence: 0, form: { items: [] }, modes: [{ label: 'Porcentaje global', value: 'conjunto' }, { label: 'Total fijo', value: 'total' }, { label: 'Por producto', value: 'producto' }] } },
  computed: { totals () { return comboTotals(this.form.items) } },
  mounted () { this.load() },
  beforeUnmount () { this.sequence++; this.searchSequence++ },
  methods: {
    toggleReplaceable (item) { const selected = !item.reemplazable; this.form.items.forEach(p => { p.reemplazable = Number(p.product_id) === Number(item.product_id) && selected }) },
    money (n) { return Number(n || 0).toFixed(2) },
    percentRule (n) { return (n !== '' && n !== null && Number.isFinite(Number(n)) && n >= 0 && n <= 99.99) || 'Porcentaje entre 0 y 99,99' },
    error (e) { const errors = e.response?.data?.errors; this.$q.notify({ type: 'negative', message: (errors && Object.values(errors).flat()[0]) || e.response?.data?.message || e.message || 'No se pudo completar la operación.' }) },
    async load () { const seq = ++this.sequence; this.loading = true; this.errorMessage = ''; try { const { data } = await this.$axios.get('internal-combos', { params: { search: this.search || undefined, page: this.page } }); if (seq !== this.sequence) return; this.combos = data.data; this.lastPage = data.last_page } catch (e) { if (seq === this.sequence) this.errorMessage = 'No se pudieron cargar los combos.' } finally { if (seq === this.sequence) this.loading = false } },
    openCreate (selection = []) {
      if (selection.length > 50) { this.$q.notify({ type: 'negative', message: 'Selecciona como máximo 50 productos para un combo.' }); return }
      this.form = { nombre: '', modo: 'conjunto', porcentaje: 0, precio_objetivo: null, activo: true, validity: { permanente: true, fecha_inicio: '', fecha_fin: '' }, items: selection.map(p => this.makeItem(p)) }
      this.previewOK = false; this.dialog = true
    },
    makeItem (p) { return { reemplazable: !!p.reemplazable, product_id: p.product_id ?? p.id, nombre: p.nombre, cantidad: p.cantidad ?? 1, precio_base: Number(p.precio_actual ?? p.precio_original ?? p.precio_base), precio_unitario: Number(p.precio_unitario ?? roundSalePrice(p.precio_original)), excluido: p.excluido || p.origen === 'EXCLUIDO' } },
    edit (combo) { this.form = { ...combo, validity: { permanente: !!combo.permanente, fecha_inicio: (combo.fecha_inicio || '').slice(0, 16).replace(' ', 'T'), fecha_fin: (combo.fecha_fin || '').slice(0, 16).replace(' ', 'T') }, items: combo.items.map(p => this.makeItem(p)) }; this.previewOK = this.form.modo === 'total' && Number(this.form.precio_objetivo) > 0 && this.totals.total === Number(this.form.precio_objetivo) && this.form.items.every(p => Number(p.precio_unitario) > 0); if (this.form.modo === 'conjunto') this.globalChanged(); this.dialog = true },
    copy (combo) { this.edit(combo); delete this.form.id; delete this.form.version; this.form.nombre = this.form.nombre.slice(0, 94) + ' copia' },
    async findProducts (value, update) { const seq = ++this.searchSequence; this.searching = true; try { const { data } = await this.$axios.get('trae/products', { params: { search: value || undefined, per_page: 20 } }); if (seq === this.searchSequence) update(() => { this.options = data.data }) } catch (e) { update(() => { this.options = [] }); this.error(e) } finally { if (seq === this.searchSequence) this.searching = false } },
    addProduct (p) { if (!p) return; if (!this.form.items.some(i => Number(i.product_id) === Number(p.id))) this.form.items.push(this.makeItem(p)); this.picker = null; this.quantitiesChanged() },
    globalChanged () { this.form.items.forEach(p => { delete p.porcentajeEdicion; p.precio_unitario = comboUnitPrice(p.precio_base, this.form.porcentaje, p.excluido) }); this.previewOK = false },
    modeChanged () { this.previewOK = false; if (this.form.modo === 'conjunto') this.globalChanged(); if (this.form.modo === 'total') this.form.precio_objetivo = this.totals.total },
    quantitiesChanged () { if (this.form.modo === 'conjunto') this.globalChanged(); this.previewOK = false },
    linePercent (p) { if (p.porcentajeEdicion !== undefined) return p.porcentajeEdicion; return p.precio_base ? Number(Math.max(0, (p.precio_base - p.precio_unitario) * 100 / p.precio_base).toFixed(2)) : 0 },
    linePercentage (p, n) { this.form.modo = 'producto'; p.porcentajeEdicion = n; p.precio_unitario = comboUnitPrice(p.precio_base, n, p.excluido); this.previewOK = false },
    finishLinePercentage (p) { delete p.porcentajeEdicion },
    linePrice (p) { delete p.porcentajeEdicion; this.form.modo = 'producto'; this.previewOK = false },
    normalizeLinePrice (p) { if (p.precio_unitario !== '') p.precio_unitario = roundSalePrice(p.precio_unitario) },
    payload () { const v = this.form.validity; if (!v.permanente && (!v.fecha_inicio || !v.fecha_fin || v.fecha_fin <= v.fecha_inicio)) throw new Error('Revisa el inicio y fin de la vigencia.'); return { nombre: this.form.nombre || 'Vista previa', modo: this.form.modo, porcentaje: Number(this.form.porcentaje || 0), precio_objetivo: this.form.modo === 'total' ? this.form.precio_objetivo : null, activo: this.form.activo, permanente: v.permanente, fecha_inicio: v.permanente ? null : v.fecha_inicio.replace('T', ' ') + ':00', fecha_fin: v.permanente ? null : v.fecha_fin.replace('T', ' ') + ':00', version: this.form.version, items: this.form.items.map(p => ({ product_id: p.product_id, cantidad: Number(p.cantidad), precio_unitario: Number(p.precio_unitario), reemplazable: !!p.reemplazable })) } },
    async calculateTotal () { if (this.calculating || this.saving) return; this.calculating = true; this.previewOK = false; try { const { data } = await this.$axios.post('internal-combos/preview', this.payload()); const map = new Map(data.items.map(p => [Number(p.product_id), p])); this.form.items.forEach(p => Object.assign(p, map.get(Number(p.product_id)))); this.previewOK = true } catch (e) { this.error(e) } finally { this.calculating = false } },
    async save () { if (this.saving || this.calculating) return; this.saving = true; try { const payload = this.payload(); const url = this.form.id ? `internal-combos/${this.form.id}` : 'internal-combos'; await this.$axios[this.form.id ? 'put' : 'post'](url, payload); this.dialog = false; this.$emit('created'); await this.load(); this.$q.notify({ type: 'positive', message: 'Combo interno guardado. Ya puedes cargarlo desde Ventas cuando esté vigente.' }) } catch (e) { this.error(e) } finally { this.saving = false } },
    remove (combo) { this.$q.dialog({ title: 'Eliminar combo', message: `¿Eliminar ${combo.nombre}? Las ventas anteriores conservarán sus precios.`, cancel: true }).onOk(async () => { try { await this.$axios.delete(`internal-combos/${combo.id}`); await this.load() } catch (e) { this.error(e) } }) }
  }
}
</script>

<style scoped>
.internal-combos { padding: 20px; }
.combo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
.combo-card { border-radius: 15px; }
.combo-price { font-size: 24px; color: #693bb5; font-weight: 700; margin: 17px 0 4px; }
.combo-price small { display: block; font-size: 11px; color: #8b8797; font-weight: 400; }
.combo-ingredients { margin: 14px 0; max-height: 150px; overflow-y: auto; font-size: 12px; color: #656071; }
.combo-ingredients > div { padding: 5px 0; border-bottom: 1px solid #f0edf5; }
.combo-ingredients span { display: block; color: #968caa; font-size: 10px; }
.combo-editor { width: 1050px; max-width: 96vw; border-radius: 18px; }
.combo-editor-body { max-height: 72vh; overflow-y: auto; }
.combo-items-table table { border-collapse: collapse; width: 100%; min-width: 750px; font-size: 12px; }
.combo-items-table th { color: #91869f; text-align: left; padding: 10px; background: #f7f4fb; font-size: 10px; }
.combo-items-table td { padding: 9px 6px; border-bottom: 1px solid #f0edf4; min-width: 100px; }
.combo-items-table td:first-child { min-width: 180px; }
.combo-replacement-row { margin-top: 7px; }
.combo-replacement-toggle { padding: 3px 10px; min-height: 28px; font-size: 11px; line-height: 1.3; color: #756a84; background: #f5f3f8; border: 1px solid #e7e1ed; }
.combo-replacement-toggle.is-selected { color: #693bb5; background: #eee7fa; border-color: #cbb8ec; }
.combo-replacement-toggle :deep(.q-icon) { font-size: 16px; margin-right: 5px; }
.combo-items-table td:last-child { min-width: 30px; }
.combo-items-table small { display: block; color: #a07f47; font-size: 10px; }
.combo-summary { display: grid; grid-template-columns: 1fr 1fr 1fr; margin: 20px 0; border: 1px solid #e7dff1; border-radius: 12px; overflow: hidden; }
.combo-summary > div { padding: 16px; font-size: 11px; color: #8a7e99; }
.combo-summary strong { display: block; color: #635272; margin-top: 5px; font-size: 18px; }
.combo-summary .combo-summary-total { background: #f3edfc; }
.combo-summary-total strong { color: #693bb5; }
@media (max-width: 599px) { .internal-combos { padding: 12px; } .combo-summary { grid-template-columns: 1fr; } .combo-summary > div { padding: 10px 14px; } .combo-summary strong { font-size: 16px; } .combo-editor-body { max-height: 68vh; } }
</style>
