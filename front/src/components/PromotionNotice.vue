<template>
  <aside
    v-if="current"
    class="promotion-notice"
    aria-label="Promociones vigentes"
    @mouseenter="pauseRotation"
    @mouseleave="startRotation"
  >
    <transition name="notice-swap" mode="out-in">
      <div :key="current.id" class="promotion-notice__content">
        <div class="promotion-notice__discount">
          <span>{{ percentage }}<small>%</small></span>
          <span class="promotion-notice__off">descuento</span>
        </div>
        <div class="promotion-notice__details">
          <div class="promotion-notice__scope ellipsis">
            {{ scope }}
          </div>
          <div class="promotion-notice__period ellipsis">
            <span v-if="current.fin_ms">{{ period }} · {{ remaining }}</span>
            <span v-else>Sin fecha de fin</span>
          </div>
        </div>
      </div>
    </transition>
    <div v-if="active.length > 1" class="promotion-notice__navigation">
      <span>{{ currentIndex + 1 }}/{{ active.length }}</span>
      <q-btn flat round dense size="xs" icon="chevron_right" aria-label="Siguiente promoción" @click="nextPromotion" />
    </div>
    <q-tooltip class="promotion-notice-tooltip" max-width="360px" :delay="400">
      <div class="text-weight-bold">{{ current.nombre }} — {{ percentage }}% de descuento</div>
      <div>{{ scope }}</div>
      <template v-if="current.alcance === 'PRODUCTOS'">
        <div v-for="(name, index) in current.productos" :key="index">• {{ name }}</div>
        <div v-if="current.cantidad_productos > 3">Y {{ current.cantidad_productos - 3 }} productos más.</div>
      </template>
      <div v-if="current.inicio_ms">Desde {{ date(current.inicio_ms, true) }}</div>
      <div>{{ current.fin_ms ? 'Hasta ' + date(current.fin_ms, true) : 'Permanente, sin fecha de fin' }}</div>
      <div>Canales: {{ channels }}</div>
      <div class="q-mt-xs">No incluye Medicamentos Controlados. Los descuentos no se acumulan.</div>
    </q-tooltip>
  </aside>
</template>

<script>
import { activeNotices, noticeScope, noticeDate, noticeRemaining } from 'src/utils/promotionNotice'

export default {
  name: 'PromotionNotice',
  props: { agenciaId: { type: Number, default: null } },
  data () {
    return { promotions: [], currentId: null, now: Date.now(), serverAnchor: null, clockAnchor: 0 }
  },
  computed: {
    active () { return activeNotices(this.promotions, this.now) },
    currentIndex () { return Math.max(0, this.active.findIndex(p => p.id === this.currentId)) },
    current () { return this.active[this.currentIndex] || null },
    percentage () { return Number(this.current?.porcentaje || 0).toLocaleString('es-BO', { maximumFractionDigits: 2 }) },
    scope () { return this.current ? noticeScope(this.current) : '' },
    period () {
      if (!this.current?.fin_ms) return ''
      const end = noticeDate(this.current.fin_ms)
      return this.current.inicio_ms ? `${noticeDate(this.current.inicio_ms)} – ${end}` : `Hasta ${end}`
    },
    remaining () { return noticeRemaining(this.current?.fin_ms, this.now) },
    channels () {
      if (!this.current) return ''
      return [this.current.canal_fisico && 'Tienda física', this.current.canal_web && 'Web', this.current.canal_app && 'App'].filter(Boolean).join(', ')
    }
  },
  watch: {
    current (promotion) {
      // If the hovered notice expires, leave rotation ready for future notices.
      if (!promotion) this.startRotation()
    },
    agenciaId () {
      this.promotions = []
      this.currentId = null
      this.refresh()
    }
  },
  mounted () {
    this.refresh()
    this._tick = setInterval(this.updateClock, 1000)
    this.startRotation()
    window.addEventListener('promotions-changed', this.refresh)
    document.addEventListener('visibilitychange', this.onVisibilityChange)
  },
  beforeUnmount () {
    this._disposed = true
    this._request?.abort()
    clearInterval(this._tick)
    clearInterval(this._rotation)
    window.removeEventListener('promotions-changed', this.refresh)
    document.removeEventListener('visibilitychange', this.onVisibilityChange)
  },
  methods: {
    date: noticeDate,
    pauseRotation () {
      clearInterval(this._rotation)
    },
    startRotation () {
      this.pauseRotation()
      if (!this._disposed) this._rotation = setInterval(this.nextPromotion, 12000)
    },
    updateClock () {
      this.now = this.serverAnchor === null ? Date.now() : this.serverAnchor + performance.now() - this.clockAnchor
    },
    nextPromotion () {
      this.updateClock()
      if (this.active.length > 1) this.currentId = this.active[(this.currentIndex + 1) % this.active.length].id
    },
    onVisibilityChange () {
      // Solo resincroniza el reloj local; las promociones se consultan al cargar (F5)
      if (!document.hidden) this.updateClock()
    },
    async refresh () {
      if (this._disposed) return
      this._request?.abort()
      const controller = new AbortController()
      this._request = controller
      const start = performance.now()
      try {
        const { data } = await this.$axios.get('promotions/announcements', {
          params: this.agenciaId ? { agencia_id: this.agenciaId } : {},
          signal: controller.signal,
          timeout: 60000
        })
        if (controller.signal.aborted || this._disposed) return
        if (!Array.isArray(data.promociones) || !Number.isFinite(data.server_time_ms)) return
        this.clockAnchor = performance.now()
        this.serverAnchor = data.server_time_ms + (this.clockAnchor - start) / 2
        this.updateClock()
        this.promotions = data.promociones
        this.currentId = this.current?.id || null
      } catch (error) {
        // Keep existing notices during outages; the local clock still removes expired ones.
        if (!controller.signal.aborted) console.warn('No se pudieron actualizar los avisos de promociones')
      } finally {
        if (this._request === controller) this._request = null
      }
    }
  }
}
</script>

<style scoped>
.promotion-notice {
  position: relative;
  display: flex;
  align-items: center;
  flex: 0 1 380px;
  min-width: 240px;
  max-width: 380px;
  height: 40px;
  box-sizing: border-box;
  margin: 0 10px;
  padding: 3px 9px;
  border: 1px solid rgba(94, 234, 212, .35);
  border-radius: 8px;
  background: #173b40;
  color: #f0fdfa;
}
.promotion-notice__content { display: flex; align-items: center; gap: 9px; min-width: 0; flex: 1; }
.promotion-notice__discount { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; min-width: 58px; padding-right: 9px; border-right: 1px solid rgba(94, 234, 212, .3); color: #99f6e4; font-size: 22px; line-height: 22px; font-weight: 800; }
.promotion-notice__discount small { font-size: 14px; margin-left: 1px; }
.promotion-notice__off { font-size: 10px; line-height: 10px; font-weight: 500; }
.promotion-notice__details { min-width: 0; flex: 1; }
.promotion-notice__scope { color: #fff; font-size: 13px; font-weight: 600; line-height: 17px; }
.promotion-notice__period { color: #d1e5e3; font-size: 11px; line-height: 15px; }
.promotion-notice__navigation { display: flex; align-items: center; flex-direction: column; margin-left: 4px; font-size: 10px; line-height: 12px; color: #99f6e4; }
.notice-swap-enter-active, .notice-swap-leave-active { transition: opacity .16s ease, transform .16s ease; }
.notice-swap-enter-from { opacity: 0; transform: translateY(5px); }
.notice-swap-leave-to { opacity: 0; transform: translateY(-5px); }
@media (prefers-reduced-motion: reduce) {
  .notice-swap-enter-active, .notice-swap-leave-active { transition: none; }
}
@media (max-width: 600px) {
  .promotion-notice { order: 1; flex-basis: 100%; max-width: none; min-width: 0; margin: 6px 0 2px; }
}
</style>
