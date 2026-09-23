<template>
  <div
    v-if="visible"
    class="promotion-ticket"
    :class="{ 'promotion-ticket--overlay': overlay }"
    :aria-label="`Promoción: ${name}`"
  >
    <q-icon name="sell" size="15px" class="promotion-ticket__icon" />
    <strong class="promotion-ticket__name">{{ name }}</strong>
    <q-tooltip>Promoción: {{ name }}</q-tooltip>
  </div>
</template>

<script>
export default {
  name: 'PromotionTicket',
  props: {
    name: {
      type: String,
      default: ''
    },
    promotionId: {
      type: [Number, String],
      default: null
    },
    overlay: Boolean
  },
  computed: {
    visible () {
      return this.promotionId !== null && this.promotionId !== undefined && String(this.name).trim() !== ''
    }
  }
}
</script>

<style scoped>
.promotion-ticket {
  position: relative;
  z-index: 3;
  display: inline-flex;
  align-items: flex-start;
  gap: 6px;
  max-width: 100%;
  min-height: 27px;
  padding: 6px 9px;
  color: #334155;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(148, 163, 184, 0.42);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
  line-height: 1.2;
  white-space: normal;
  backdrop-filter: blur(8px);
}

.promotion-ticket--overlay {
  position: absolute;
  bottom: 8px;
  left: 8px;
  width: calc(100% - 16px);
}

.promotion-ticket__icon {
  flex: 0 0 auto;
  margin-top: 1px;
  color: #64748b;
}

.promotion-ticket__name {
  min-width: 0;
  color: #334155;
  font-size: 10.5px;
  font-weight: 650;
  line-height: 1.25;
  overflow-wrap: anywhere;
}
</style>
