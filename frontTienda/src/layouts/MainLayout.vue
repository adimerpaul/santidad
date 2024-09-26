<template>
  <q-layout view="lHh Lpr lff">
<!--    <q-header>-->
<!--      <q-toolbar>-->
<!--&lt;!&ndash;        <q-btn&ndash;&gt;-->
<!--&lt;!&ndash;          flat&ndash;&gt;-->
<!--&lt;!&ndash;          dense&ndash;&gt;-->
<!--&lt;!&ndash;          round&ndash;&gt;-->
<!--&lt;!&ndash;          icon="menu"&ndash;&gt;-->
<!--&lt;!&ndash;          aria-label="Menu"&ndash;&gt;-->
<!--&lt;!&ndash;          @click="toggleLeftDrawer"&ndash;&gt;-->
<!--&lt;!&ndash;        />&ndash;&gt;-->
<!--        <q-toolbar-title class="text-bold cursor-pointer" @click="$router.push('/')">-->
<!--&lt;!&ndash;          <q-avatar rounded size="50px" color="orange">&ndash;&gt;-->
<!--            <q-img src="/logo.png" width="150px" class="bg-white"  v-if="!$q.screen.lt.md" />-->
<!--&lt;!&ndash;          </q-avatar>&ndash;&gt;-->
<!--&lt;!&ndash;          <q-avatar size="50px">&ndash;&gt;-->
<!--&lt;!&ndash;            <q-img src="logo.png"  />&ndash;&gt;-->
<!--&lt;!&ndash;          </q-avatar>&ndash;&gt;-->
<!--        </q-toolbar-title>-->
<!--        <q-space />-->
<!--        <q-tabs-->
<!--          v-model="tab"-->
<!--          align="left"-->
<!--          class="hidden-sm-and-down"-->
<!--          active-color="orange"-->
<!--          indicator-color="orange"-->
<!--          no-caps-->
<!--        >-->
<!--          <q-route-tab-->
<!--            v-for="tab in tabs"-->
<!--            :key="tab.name"-->
<!--            v-bind="tab"-->
<!--            :to="tab.to"-->
<!--          />-->
<!--        </q-tabs>-->
<!--        <q-space />-->
<!--        <q-btn-->
<!--          flat-->
<!--          dense-->
<!--          round-->
<!--          icon="search"-->
<!--          aria-label="Search"-->
<!--        />-->
<!--        <q-btn-->
<!--          flat-->
<!--          dense-->
<!--          round-->
<!--          icon="shopping_cart"-->
<!--          aria-label="Cart"-->
<!--        />-->
<!--        <q-btn-->
<!--          flat-->
<!--          dense-->
<!--          round-->
<!--          icon="menu"-->
<!--          aria-label="Menu"-->
<!--          />-->
<!--&lt;!&ndash;        <q-separator dark vertical inset />&ndash;&gt;-->
<!--&lt;!&ndash;        <q-input&ndash;&gt;-->
<!--&lt;!&ndash;          v-model="search"&ndash;&gt;-->
<!--&lt;!&ndash;          dense&ndash;&gt;-->
<!--&lt;!&ndash;          style="width: 650px;"&ndash;&gt;-->
<!--&lt;!&ndash;          outlined&ndash;&gt;-->
<!--&lt;!&ndash;          bg-color="white"&ndash;&gt;-->
<!--&lt;!&ndash;          rounded&ndash;&gt;-->
<!--&lt;!&ndash;          placeholder="Buscar producto"&ndash;&gt;-->
<!--&lt;!&ndash;          class="q-ml-sm"/>&ndash;&gt;-->
<!--&lt;!&ndash;        <q-space />&ndash;&gt;-->
<!--&lt;!&ndash;        <q-btn&ndash;&gt;-->
<!--&lt;!&ndash;          flat&ndash;&gt;-->
<!--&lt;!&ndash;          dense&ndash;&gt;-->
<!--&lt;!&ndash;          round&ndash;&gt;-->
<!--&lt;!&ndash;          icon="shopping_cart"&ndash;&gt;-->
<!--&lt;!&ndash;          aria-label="Menu"&ndash;&gt;-->
<!--&lt;!&ndash;        />&ndash;&gt;-->
<!--      </q-toolbar>-->
<!--    </q-header>-->

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
