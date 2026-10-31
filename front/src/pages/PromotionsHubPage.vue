<template>
  <q-page class="bg-grey-1">
    <q-banner v-if="!isAdmin" class="bg-red-1 text-negative q-ma-md">
      {{ $store.loading ? 'Cargando sesión…' : 'Solo el administrador puede gestionar promociones y TRAE.' }}
    </q-banner>
    <template v-else>
      <q-tabs v-model="channel" align="left" active-color="deep-purple" indicator-color="deep-purple" class="bg-white text-grey-7 q-px-md shadow-1">
        <q-tab name="general" icon="sell" label="Promociones web, APK y sucursal" no-caps />
        <q-tab name="trae" icon="delivery_dining" label="TRAE · Ventas y descuentos" no-caps />
      </q-tabs>
      <TraePage v-if="channel === 'trae'" />
      <PromotionsPage v-else />
    </template>
  </q-page>
</template>

<script>
import PromotionsPage from './PromotionsPage.vue'
import TraePage from './TraePage.vue'

export default {
  name: 'PromotionsHubPage',
  components: { PromotionsPage, TraePage },
  computed: {
    isAdmin () { return Number(this.$store.user?.id) === 1 },
    channel: {
      get () { return this.$route.query.canal === 'trae' ? 'trae' : 'general' },
      set (value) { this.$router.replace({ path: '/promociones', query: value === 'trae' ? { canal: 'trae' } : {} }) }
    }
  }
}
</script>
