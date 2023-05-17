<template>
  <q-layout view="lHh Lpr lFf">
    <q-header
      class="bg-white text-black"
    >
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />
        <q-toolbar-title>
          <span class="text-bold">{{ $store.user.name }}</span>
          <q-chip dense v-if="$store.user.agencia"
                  class="bg-primary text-white text-subtitle2 text-bold">{{$store.user.agencia.nombre}}</q-chip>
        </q-toolbar-title>
        <div>
          <q-btn
            dense
            round
            icon="logout"
            color="red"
            aria-label="Logout"
            @click="logout()"
          />
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
    >
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
<!--        <pre>{{essentialLinks}}</pre>-->
<!--      </q-list>-->
      <q-layout>
        <q-header class="bg-white">
<!--          <q-toolbar>-->
<!--            <q-toolbar-title>-->
<!--              <span class="text-bold text-grey">Menú</span>-->
<!--            </q-toolbar-title>-->
<!--          </q-toolbar>-->
          <q-list bordered padding class="text-black">
            <q-item-label header class="text-bold">
              Menú
            </q-item-label>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/">
              <q-item-section avatar>
                <q-icon name="o_store" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Venta</q-item-label>
                <q-item-label caption class="text-grey">Venta de productos</q-item-label>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/productos">
              <q-item-section avatar>
                <q-icon name="o_local_mall" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Productos</q-item-label>
                <q-item-label caption class="text-grey">Productos disponibles</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-header>
        <q-footer class="bg-white">
          <q-list bordered padding dense class="rounded-borders text-red">
            <q-item clickable v-ripple @click="logout()">
              <q-item-section avatar>
                <q-icon name="o_logout" />
              </q-item-section>
              <q-item-section> Cerrar sesión</q-item-section>
            </q-item>
          </q-list>
        </q-footer>
      </q-layout>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
// import EssentialLink from 'components/EssentialLink.vue'

export default {
  name: 'MainLayout',
  components: {
    // EssentialLink: EssentialLink
  },
  data () {
    return {
      leftDrawerOpen: false,
      essentialLinks: [
        {
          title: 'Home',
          icon: 'home',
          to: '/'
        },
        {
          title: 'About',
          icon: 'info',
          to: '/about'
        },
        {
          title: 'Contact',
          icon: 'phone',
          to: '/contact'
        }
      ]
    }
  },
  methods: {
    logout () {
      this.$q.dialog({
        message: '¿Quieres cerrar sesión?',
        title: 'Salir',
        ok: {
          push: true
        },
        cancel: {
          push: true,
          color: 'negative'
        }
      }).onOk(() => {
        this.$q.loading.show()
        this.$axios.post('logout').then(() => {
          this.$axios.defaults.headers.common.Authorization = ''
          this.$store.user = {}
          localStorage.removeItem('tokenSantidad')
          this.$store.isLoggedIn = false
          this.$q.loading.hide()
          this.$router.push('/login')
        })
      })
    }
  }
}
</script>
