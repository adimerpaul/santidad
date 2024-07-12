<template>
  <q-layout view="lHh Lpr lFf">
    <q-header>
      <q-toolbar>
<!--        <q-btn-->
<!--          flat-->
<!--          dense-->
<!--          round-->
<!--          icon="menu"-->
<!--          aria-label="Menu"-->
<!--          @click="toggleLeftDrawer"-->
<!--        />-->
        <q-toolbar-title class="text-bold">
<!--          <q-avatar rounded size="50px" color="orange">-->
            <q-img src="logo.png" width="150px" class="bg-white"  />
<!--          </q-avatar>-->
<!--          <q-avatar size="50px">-->
<!--            <q-img src="logo.png"  />-->
<!--          </q-avatar>-->
        </q-toolbar-title>
        <q-space />
        <q-tabs
          v-model="tab"
          align="left"
          class="hidden-sm-and-down"
          active-color="orange"
          indicator-color="orange"
          no-caps
        >
          <q-tab
            v-for="tab in tabs"
            :key="tab.name"
            v-bind="tab"
          />
        </q-tabs>
        <q-space />
        <q-btn
          flat
          dense
          round
          icon="search"
          aria-label="Search"
        />
        <q-btn
          flat
          dense
          round
          icon="shopping_cart"
          aria-label="Cart"
        />
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          />
<!--        <q-separator dark vertical inset />-->
<!--        <q-input-->
<!--          v-model="search"-->
<!--          dense-->
<!--          style="width: 650px;"-->
<!--          outlined-->
<!--          bg-color="white"-->
<!--          rounded-->
<!--          placeholder="Buscar producto"-->
<!--          class="q-ml-sm"/>-->
<!--        <q-space />-->
<!--        <q-btn-->
<!--          flat-->
<!--          dense-->
<!--          round-->
<!--          icon="shopping_cart"-->
<!--          aria-label="Menu"-->
<!--        />-->
      </q-toolbar>
    </q-header>

<!--    <q-drawer-->
<!--      v-model="leftDrawerOpen"-->
<!--      show-if-above-->
<!--      bordered-->
<!--    >-->
<!--      <q-list>-->
<!--        <q-item-label-->
<!--          header-->
<!--        >-->
<!--          Essential Links-->
<!--        </q-item-label>-->

<!--        <EssentialLink-->
<!--          v-for="link in essentialLinks"-->
<!--          :key="link.title"-->
<!--          v-bind="link"-->
<!--        />-->
<!--      </q-list>-->
<!--    </q-drawer>-->

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'MainLayout',
  data () {
    return {
      tab: 'Inicio',
      tabs: [
        { name: 'Inicio', label: 'Inicio', to: '/' },
        { name: 'Oferas', label: 'Ofertas', to: '/ofertas' },
        { name: 'Sucursales', label: 'Sucursales', to: '/sucursales' },
        { name: 'Contactos', label: 'Contactos', to: '/contactos' }
      ],
      leftDrawerOpen: false,
      search: ''
    }
  }
})
</script>
