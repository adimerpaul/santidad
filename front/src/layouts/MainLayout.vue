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
      :width="200"
    >
<!--      :breakpoint="400"-->
      <q-layout>
        <q-header class="bg-white">
          <q-list bordered padding class="text-black">
            <q-item-label header class="text-bold">
              Menu principal
            </q-item-label>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/">
              <q-item-section avatar><q-icon name="o_store" /></q-item-section>
              <q-item-section>
                <q-item-label>
                  Movimientos
                  <q-tooltip anchor="top middle" self="bottom middle">
                    Movimientos de caja
                  </q-tooltip>
                </q-item-label>
              </q-item-section>
            </q-item>
<!--            <q-expansion-item expand-separator icon="o_engineering" label="Siat" v-if="$store.user.id=='1'">-->
<!--              <q-expansion-item dense exact :header-inset-level="0.3" expand-separator icon="o_psychology" label="Cuis" default-opened to="/cuis" hide-expand-icon  />-->
<!--              <q-expansion-item dense exact :header-inset-level="0.3" expand-separator icon="o_business_center" label="sincronizacion" default-opened to="/sincronizacion" hide-expand-icon  />-->
<!--              <q-expansion-item dense exact :header-inset-level="0.3" expand-separator icon="link" label="Cufd" default-opened to="/cufd" hide-expand-icon  />-->
<!--              <q-expansion-item dense exact :header-inset-level="0.3" expand-separator icon="list" label="Evento significativo" default-opened to="/eventoSignificativo" hide-expand-icon  />-->
<!--            </q-expansion-item>-->
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/sale">
              <q-item-section avatar><q-icon name="o_shopping_cart" /></q-item-section>
              <q-item-section>
                <q-item-label>
                  Venta
                  <q-tooltip anchor="top middle" self="bottom middle">
                    Venta de productos
                  </q-tooltip>
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/productos" >
              <q-item-section avatar><q-icon name="o_local_mall" /></q-item-section>
              <q-item-section>
                <q-item-label>Productos</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar productos
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/clientes" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_face" /></q-item-section>
              <q-item-section>
                <q-item-label>Clientes</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar clientes
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/proveedores" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_assignment_ind" /></q-item-section>
              <q-item-section>
                <q-item-label>Proveedores</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar proveedores
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/reportes" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_print" /></q-item-section>
              <q-item-section>
                <q-item-label>Reportes</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Consultar reportes
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/users" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_manage_accounts" /></q-item-section>
              <q-item-section>
                <q-item-label>Usuarios</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar usuarios
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/unidades" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_vaccines" /></q-item-section>
              <q-item-section>
                <q-item-label>Unidades</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar unidades
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/productosPorVencer">
              <q-item-section avatar><q-icon name="o_warning" /></q-item-section>
              <q-item-section>
                <q-item-label>Productos por vencer</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Productos por vencer
                </q-tooltip>
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
          localStorage.removeItem('agencia_id')
          this.$store.isLoggedIn = false
          this.$q.loading.hide()
          this.$router.push('/login')
        })
      })
    }
  }
}
</script>
