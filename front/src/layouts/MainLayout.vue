<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-white text-black">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />
        <q-toolbar-title style="line-height: 0.7;padding: 0;margin: 0">
          <div class="text-bold" style="line-height: 0.7;padding: 0;margin: 0">
            {{ $store.user.name }} <br>
            <q-chip
              dense
              size="10px"
              v-if="$store.user.agencia"
              class="bg-primary text-white text-subtitle2 text-bold"
            >
            {{$store.user.agencia.nombre}}
          </q-chip>
            <span class="text-caption">{{ $version }}</span>
          </div>
        </q-toolbar-title>
        <div>
          <q-btn flat dense icon="notifications" color="primary">
            <q-badge v-if="notificaciones.some(n => !n.leida)" color="red" floating>
            {{ notificaciones.filter(n => !n.leida).length }}
          </q-badge>

            <!-- Agregar ref="menuNotificaciones" aquí -->
            <q-menu ref="menuNotificaciones">
              <q-list style="min-width: 250px">
                <q-item-label header>Notificaciones</q-item-label>
                <q-item
                  v-for="(notif, index) in notificaciones"
                  :key="index"
                  clickable
               @click="abrirNotificacion(notif)"
                >
                  <!-- Chip "Nuevo" si no está leído -->
                  <q-item-section avatar v-if="!notif.leida" style="min-width: 50px;">
                    <q-chip dense color="green" text-color="white" label>
                      Nuevo
                    </q-chip>
                  </q-item-section>

                  <!-- Mensaje y fecha -->
                  <q-item-section>
                    <div>{{ notif.mensaje }}</div>
                    <div style="font-size: 11px; color: gray; margin-top: 2px;">
                      {{ formatDate(notif.created_at) }}
                    </div>
                  </q-item-section>
                </q-item>
                <q-item v-if="notificaciones.length === 0">
                  <q-item-section class="text-grey">
                    Sin notificaciones nuevas
                  </q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>

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
          <q-list bordered padding class="text-black" dense>
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
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/compras">
              <q-item-section avatar><q-icon name="o_storefront" /></q-item-section>
              <q-item-section>
                <q-item-label>
                  Compras
                  <q-tooltip anchor="top middle" self="bottom middle">
                    Compra de productos
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
            <q-item to="/transferencias" clickable v-ripple>
            <q-item-section avatar>
              <q-icon name="swap_horiz" />
            </q-item-section>
            <q-item-section>Transferencias</q-item-section>
          </q-item>
            <!-- NUEVO: Opción Facturas -->
          <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/facturas">
            <q-item-section avatar><q-icon name="receipt_long" /></q-item-section>
            <q-item-section>
              <q-item-label>Facturas</q-item-label>
              <q-tooltip anchor="top middle" self="bottom middle">
                Control de facturas
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
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/agencias" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_apartment" /></q-item-section>
              <q-item-section>
                <q-item-label>Agencias</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar agencias
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/subcategorias" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_category" /></q-item-section>
              <q-item-section>
                <q-item-label>Subcategorias</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar subcategorias
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
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/productosVencidos" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="priority_high" /></q-item-section>
              <q-item-section>
                <q-item-label>Productos vencidos</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Productos vencidos
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/productosRetirados" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_delete_sweep" /></q-item-section>
              <q-item-section>
                <q-item-label>Productos retirados</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Productos retirados
                </q-tooltip>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/carousel" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="o_image" /></q-item-section>
              <q-item-section>
                <q-item-label>Carousel</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar carousel
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
    <!-- Div para impresión -->
    <div id="myElement" style="display: none;"></div>
  </q-layout>
</template>

<script>
export default {
  name: 'MainLayout',
  components: {},
  data () {
    return {
      leftDrawerOpen: false,
      notificaciones: [],
      prevNotificaciones: [],
      notificacionActiva: null,
      dialogoNotificacion: false,
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
  mounted () {
    this.getNotificaciones()
    setInterval(this.getNotificaciones, 200000)
  },
  methods: {
    logout () {
      this.$q.dialog({
        message: '¿Quieres cerrar sesión?',
        title: 'Salir',
        ok: { push: true },
        cancel: { push: true, color: 'negative' }
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
    },
    getNotificaciones () {
      const agencia = this.$store.agencia_id
      this.$axios.get(`/notificaciones/${agencia}`)
        .then(res => {
          const nuevasNotificaciones = res.data

          // Normalizar leida y eliminar duplicados por ID
          const mapNotifs = new Map()
          nuevasNotificaciones.forEach(n => {
            n.leida = n.leida === 1 || n.leida === true
            mapNotifs.set(n.id, n)
          })
          const notifsArray = Array.from(mapNotifs.values())

          // Extraer IDs de notificaciones nuevas que no están en prevNotificaciones
          const prevIds = new Set(this.prevNotificaciones.map(n => n.id))
          const newNotifs = notifsArray.filter(n => !prevIds.has(n.id) && !n.leida)

          // Solo mostrar alerta si hay nuevas notificaciones no leídas no notificadas antes
          if (newNotifs.length > 0) {
            this.$q.notify({
              type: 'info',
              color: 'primary',
              icon: 'info',
              message: '📦 ¡Tienes una nueva transferencia de productos!',
              position: 'top-right',
              timeout: 6000,
              actions: [
                {
                  label: 'VER',
                  color: 'white',
                  handler: () => {
                    if (this.$refs.menuNotificaciones) {
                      this.$refs.menuNotificaciones.show()
                    }
                  }
                }
              ]
            })
          }

          // Actualizar los arrays de notificaciones y prevNotificaciones con los nuevos datos
          this.notificaciones = notifsArray
          this.prevNotificaciones = notifsArray
        })
        .catch(() => {
          this.notificaciones = []
          this.prevNotificaciones = []
        })
    },

    abrirNotificacion (notif) {
      this.notificacionActiva = notif

      // Formateo con fecha y hora: "5 de mayo del 2025, 14:30"
      const formatDate = iso => {
        const d = new Date(iso)
        const day = d.getDate()
        const month = d.toLocaleDateString('es-ES', { month: 'long' })
        const year = d.getFullYear()
        const hours = String(d.getHours()).padStart(2, '0')
        const mins = String(d.getMinutes()).padStart(2, '0')
        return `${day} de ${month} del ${year}, ${hours}:${mins}`
      }

      let mensaje = `
        <div style="font-size:16px;margin-bottom:8px;">
          <b>${notif.mensaje}</b>
        </div>
        <div style="color:#777;font-size:12px;margin-bottom:12px;">
          Fecha: ${formatDate(notif.created_at)}
        </div>
      `
      try {
        const productos = JSON.parse(notif.detalle)

        if (productos.length > 0) {
          // Encabezado de productos
          mensaje += '<div style="margin-bottom: 10px;"><b>Productos recibidos:</b></div>'

          // Tabla para alinear la información
          mensaje += '<div style="max-height: 300px; overflow-y: auto;"><table style="width: 100%; border-collapse: collapse;">'

          productos.forEach(p => {
            mensaje += `
              <tr style="border-bottom: 1px solid #eaeaea;">
                <td style="padding: 8px 0;">
                  <span style="display: flex; align-items: start;">
                    <span style="margin-right: 8px;">•</span>
                    <span>
                      <b>${p.nombre}</b> – ${p.cantidad} unidad${p.cantidad > 1 ? 'es' : ''}
                      ${p.fechaVencimiento
                        ? `<div style="color: #777; font-size: 12px; margin-top: 2px;">(Vence: ${p.fechaVencimiento})</div>`
                        : ''}
                    </span>
                  </span>
                </td>
              </tr>
            `
          })

          mensaje += '</table></div>'
        } else {
          mensaje += '<div style="color: #777;">Sin detalle de productos.</div>'
        }
      } catch (e) {
        mensaje += '<div style="color: #c00;">Error al mostrar detalle.</div>'
      }

      this.$q.dialog({
        html: true,
        title: '📦 Transferencia recibida',
        message: mensaje,
        ok: {
          label: 'Cerrar',
          color: 'primary'
        }
      }).onOk(() => {
        this.$axios.put(`/notificaciones/${notif.id}/leer`)
          .then(() => {
            const index = this.notificaciones.findIndex(n => n.id === notif.id)
            if (index !== -1) {
              this.notificaciones[index].leida = 1
              this.notificaciones = [...this.notificaciones]
            }
          })
      })
    },

    // Nueva función para formatear fecha, para usar en template (lista)
    formatDate (iso) {
      const d = new Date(iso)
      const day = d.getDate()
      const month = d.toLocaleDateString('es-ES', { month: 'long' })
      const year = d.getFullYear()
      const hours = String(d.getHours()).padStart(2, '0')
      const mins = String(d.getMinutes()).padStart(2, '0')
      return `${day} de ${month} del ${year}, ${hours}:${mins}`
    }
  }
}
</script>
