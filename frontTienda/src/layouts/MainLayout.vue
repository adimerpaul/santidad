<template>
  <q-layout view="lHh Lpr lff">
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
        <q-toolbar-title class="text-bold cursor-pointer" @click="$router.push('/')">
<!--          <q-avatar rounded size="50px" color="orange">-->
            <q-img src="/logo.png" width="150px" class="bg-white"  v-if="!$q.screen.lt.md" />
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
          <q-route-tab
            v-for="tab in tabs"
            :key="tab.name"
            v-bind="tab"
            :to="tab.to"
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
    <q-footer>
<!--      iconos faceoob instagram ticktick whtsapp-->
      <div class="text-center bg-white">
        <q-btn class="q-ma-xs" dense color="primary" icon="fa-brands fa-facebook" href="https://www.facebook.com/"></q-btn>
        <q-btn class="q-ma-xs" dense color="red" icon="fa-brands fa-instagram" href="https://www.instagram.com/"></q-btn>
        <q-btn class="q-ma-xs" dense color="green" icon="fa-brands fa-whatsapp" href="https://wa.me/"></q-btn>
        <q-btn class="q-ma-xs" dense color="black" icon="fa-brands fa-tiktok" href="https://www.tiktok.com/"></q-btn>
      </div>
      <q-toolbar>
        <div class="text-h5">Farmacia Santidad Divina</div>
        <q-space />
        <div class="text-caption">© 2024</div>
      </q-toolbar>
    </q-footer>
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
        // { name: 'Oferas', label: 'Ofertas', to: '/ofertas' },
        { name: 'Sucursales', label: 'Sucursales', to: '/sucursales' }
        // { name: 'Contactos', label: 'Contactos', to: '/contactos' }
      ],
      leftDrawerOpen: false,
      search: ''
    }
  }
})
</script>
