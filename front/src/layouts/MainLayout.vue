<template>
  <q-layout view="lHh Lpr lFf" class="bg-grey-2">
    <q-header class="bg-blue-grey-10 text-grey-2">
      <q-toolbar class="q-px-sm q-py-xs">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />

        <q-toolbar-title class="q-ml-sm">
          <div class="row items-center no-wrap q-col-gutter-sm">
            <div class="col-auto">
              <q-avatar size="38px" color="blue-grey-8" text-color="white" icon="local_pharmacy" />
            </div>
            <div class="col">
              <div class="text-weight-bold text-body2 ellipsis">
                {{ $store.user.name || 'Panel administrativo' }}
              </div>
              <div class="row items-center q-gutter-xs">
                <q-chip
                  v-if="$store.user.agencia"
                  dense
                  square
                  size="11px"
                  color="blue-grey-7"
                  text-color="white"
                  icon="apartment"
                >
                  {{ $store.user.agencia.nombre }}
                </q-chip>
                <span class="text-caption text-grey-5">v{{ $version }}</span>
              </div>
            </div>
          </div>
        </q-toolbar-title>

        <q-btn flat dense round icon="notifications_none" color="grey-3">
          <q-badge v-if="conteoNoLeidas > 0" color="red" floating>
            {{ conteoNoLeidas }}
          </q-badge>

          <q-menu ref="menuNotificaciones" @show="getNotificacionesEnviadas(1)">
            <q-list style="min-width: 340px; max-width: 340px">
              <q-item-label header class="q-pa-sm bg-grey-1">
                <q-btn-toggle
                  v-model="tabNotificaciones"
                  spread
                  no-caps
                  dense
                  toggle-color="primary"
                  color="white"
                  text-color="grey-8"
                  :options="[
                    { label: 'Recibidas', value: 'recibidas' },
                    { label: 'Enviadas', value: 'enviadas' }
                  ]"
                />
              </q-item-label>
              <q-separator />

              <div v-if="tabNotificaciones === 'recibidas'">
                <div v-if="notificaciones.length > 0">
                  <q-item
                    v-for="(notif, i) in notificaciones"
                    :key="'in-' + i"
                    clickable
                    v-ripple
                    class="q-py-sm"
                    @click="abrirNotificacion(notif, 'recibida')"
                  >
                    <q-item-section avatar style="min-width: 20px; padding-right: 10px;">
                      <q-icon name="circle" :color="!notif.leida ? 'green' : 'grey-3'" size="10px" />
                    </q-item-section>
                    <q-item-section>
                      <div style="font-size: 13px; line-height: 1.3;">{{ notif.mensaje }}</div>
                      <div class="text-caption text-grey-6">{{ formatDate(notif.created_at) }}</div>
                    </q-item-section>
                  </q-item>
                </div>
                <div v-else class="q-pa-md text-center text-grey">Sin notificaciones recibidas</div>
                <div class="row justify-between q-pa-sm bg-grey-1">
                  <q-btn flat dense size="sm" icon="chevron_left" :disable="pagination.current_page <= 1" @click.stop="cambiarPagina(pagination.current_page - 1)" />
                  <span class="text-caption q-pt-xs">Pág {{ pagination.current_page }}</span>
                  <q-btn flat dense size="sm" icon="chevron_right" :disable="pagination.current_page >= pagination.last_page" @click.stop="cambiarPagina(pagination.current_page + 1)" />
                </div>
              </div>

              <div v-else>
                <div v-if="notificacionesEnviadas.length > 0">
                  <q-item
                    v-for="(notif, i) in notificacionesEnviadas"
                    :key="'out-' + i"
                    clickable
                    v-ripple
                    class="q-py-sm"
                    @click="abrirNotificacion(notif, 'enviada')"
                  >
                    <q-item-section avatar style="min-width: 20px; padding-right: 10px;">
                      <q-icon name="north_east" color="blue-grey" size="16px" />
                    </q-item-section>
                    <q-item-section>
                      <div style="font-size: 13px; line-height: 1.3;">
                        <span class="text-grey-7">Para: </span>
                        <b>{{ notif.agencia ? notif.agencia.nombre : 'Destino desconocido' }}</b>
                      </div>
                      <div class="text-caption text-grey-6">{{ formatDate(notif.created_at) }}</div>
                    </q-item-section>
                  </q-item>
                </div>
                <div v-else class="q-pa-md text-center text-grey">No has enviado nada aún</div>

                <div class="row justify-between q-pa-sm bg-grey-1">
                  <q-btn flat dense size="sm" icon="chevron_left" :disable="paginationEnviadas.current_page <= 1" @click.stop="getNotificacionesEnviadas(paginationEnviadas.current_page - 1)" />
                  <span class="text-caption q-pt-xs">Pág {{ paginationEnviadas.current_page }}</span>
                  <q-btn flat dense size="sm" icon="chevron_right" :disable="paginationEnviadas.current_page >= paginationEnviadas.last_page" @click.stop="getNotificacionesEnviadas(paginationEnviadas.current_page + 1)" />
                </div>
              </div>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :width="228"
      :breakpoint="500"
      class="drawer-shell text-white"
    >
      <div class="drawer-content">
        <div class="drawer-profile">
          <div class="drawer-profile__avatar">
            <q-icon name="medication" size="26px" />
          </div>
          <div class="drawer-profile__info">
            <div class="drawer-profile__name">{{ $store.user.name || 'Usuario' }}</div>
            <div class="drawer-profile__sub">{{ $store.user.agencia ? $store.user.agencia.nombre : 'Santidad Divina' }}</div>
          </div>
        </div>

        <div class="drawer-section-label">Navegación</div>

        <q-list dense class="drawer-list">
          <template v-for="section in menuSections" :key="section.title">
            <q-expansion-item
              dense
              dense-toggle
              expand-separator
              default-opened
              :icon="section.icon"
              :label="section.title"
              :header-class="sectionIsActive(section) ? 'drawer-group drawer-group--active' : 'drawer-group'"
            >
              <q-list dense class="q-px-xs q-pb-xs">
                <q-item
                  v-for="item in section.items"
                  :key="item.to"
                  clickable
                  :to="item.to"
                  exact
                  class="drawer-link"
                  :active="linkIsActive(item)"
                  active-class="drawer-link--active"
                >
                  <q-item-section avatar class="drawer-link__avatar">
                    <q-icon
                      :name="item.icon"
                      size="17px"
                      :class="linkIsActive(item) ? 'text-white' : 'text-blue-grey-2'"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="drawer-link__label" :class="linkIsActive(item) ? 'text-white text-weight-bold' : 'text-blue-grey-1'">
                      {{ item.label }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-expansion-item>
          </template>
        </q-list>

        <q-item clickable class="drawer-logout" @click="logout">
          <q-item-section avatar>
            <q-icon name="exit_to_app" size="18px" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="drawer-link__label">Salir</q-item-label>
          </q-item-section>
        </q-item>
      </div>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>

    <div id="myElement" style="display: none;" />
  </q-layout>
</template>

<script>
export default {
  name: 'MainLayout',
  data () {
    return {
      leftDrawerOpen: false,
      notificaciones: [],
      tabNotificaciones: 'recibidas',
      notificacionesEnviadas: [],
      paginationEnviadas: {
        current_page: 1,
        last_page: 1
      },
      conteoNoLeidas: 0,
      prevNotificacionesIds: new Set(),
      pagination: {
        current_page: 1,
        last_page: 1
      },
      notificacionActiva: null,
      prevNotificaciones: []
    }
  },
  computed: {
    isAdmin () {
      return String(this.$store.user.id) === '1'
    },
    menuSections () {
      const operacion = [
        { to: '/', label: 'Movimientos', icon: 'space_dashboard' },
        { to: '/sale', label: 'Venta', icon: 'point_of_sale' },
        { to: '/compras', label: 'Compras', icon: 'shopping_bag' },
        { to: '/pedidos', label: 'Pedidos', icon: 'assignment' },
        { to: '/historial-pedidos', label: 'Historial de pedidos', icon: 'history' },
        { to: '/transferencias', label: 'Transferencias', icon: 'swap_horiz' },
        { to: '/facturas', label: 'Facturas', icon: 'receipt_long' }
      ]

      const inventario = [
        { to: '/productos', label: 'Productos', icon: 'inventory_2' },
        { to: '/subcategorias', label: 'Subcategorías', icon: 'category' },
        { to: '/unidades', label: 'Unidades', icon: 'straighten' },
        { to: '/productosPorVencer', label: 'Por vencer', icon: 'warning_amber' },
        { to: '/productosVencidos', label: 'Vencidos', icon: 'report_gmailerrorred' },
        { to: '/productosRetirados', label: 'Retirados', icon: 'remove_shopping_cart' }
      ]

      const gestion = [
        { to: '/clientes', label: 'Clientes', icon: 'groups' },
        { to: '/proveedores', label: 'Proveedores', icon: 'local_shipping' },
        { to: '/vendedores', label: 'Vendedores', icon: 'badge' },
        { to: '/users', label: 'Usuarios', icon: 'manage_accounts' },
        { to: '/agencias', label: 'Agencias', icon: 'apartment' },
        { to: '/reportes', label: 'Reportes', icon: 'insert_chart' },
        { to: '/siat', label: 'SIAT', icon: 'verified' },
        { to: '/carousel', label: 'Carousel', icon: 'view_carousel' }
      ]

      const sections = [
        { title: 'Operación', icon: 'point_of_sale', items: operacion },
        { title: 'Inventario', icon: 'inventory_2', items: this.isAdmin ? inventario : inventario.filter(item => item.to === '/productos' || item.to === '/productosPorVencer') }
      ]

      if (this.isAdmin) {
        sections.push({ title: 'Gestión', icon: 'manage_accounts', items: gestion })
      }

      return sections
    }
  },
  mounted () {
    this.getNotificaciones(1)
    setInterval(() => this.getNotificaciones(1, true), 120000)
  },
  methods: {
    linkIsActive (item) {
      return this.$route.path === item.to
    },
    sectionIsActive (section) {
      return section.items.some(item => this.linkIsActive(item))
    },
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
    getNotificaciones (page = 1, isBackgroundCheck = false) {
      const agencia = this.$store.agencia_id
      const paginaSolicitada = isBackgroundCheck ? 1 : page
      const timeStamp = new Date().getTime()

      this.$axios.get(`/notificaciones/${agencia}?page=${paginaSolicitada}&_t=${timeStamp}`)
        .then(res => {
          const data = res.data
          const nuevosItems = data.listado.data

          this.conteoNoLeidas = data.total_no_leidas

          if (paginaSolicitada === 1) {
            this.notificaciones = nuevosItems
            this.pagination = {
              current_page: data.listado.current_page,
              last_page: data.listado.last_page,
              total: data.listado.total
            }

            if (isBackgroundCheck && this.prevNotificacionesIds.size > 0) {
              const alertasNuevas = nuevosItems.filter(n => !this.prevNotificacionesIds.has(n.id) && (n.leida === 0 || n.leida === false))

              if (alertasNuevas.length > 0) {
                this.$q.notify({
                  type: 'info',
                  color: 'primary',
                  icon: 'notifications_active',
                  message: `Tienes ${alertasNuevas.length} transferencia(s) nueva(s).`,
                  position: 'top-right'
                })
              }
            }

            nuevosItems.forEach(n => {
              this.prevNotificacionesIds.add(n.id)
            })
          } else {
            this.notificaciones = nuevosItems
            this.pagination = {
              current_page: data.listado.current_page,
              last_page: data.listado.last_page,
              total: data.listado.total
            }
          }
        })
        .catch(err => {
          console.error('Error notificaciones', err)
        })
    },
    cambiarPagina (page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.getNotificaciones(page)
      }
    },
    formatDate (iso) {
      const d = new Date(iso)
      const day = d.getDate()
      const month = d.toLocaleDateString('es-ES', { month: 'long' })
      const year = d.getFullYear()
      const hours = String(d.getHours()).padStart(2, '0')
      const mins = String(d.getMinutes()).padStart(2, '0')
      return `${day} de ${month} del ${year}, ${hours}:${mins}`
    },
    getNotificacionesEnviadas (page = 1) {
      const agencia = this.$store.agencia_id
      this.$axios.get(`/notificaciones-enviadas/${agencia}?page=${page}`)
        .then(res => {
          const data = res.data
          this.notificacionesEnviadas = data.listado.data
          this.paginationEnviadas = {
            current_page: data.listado.current_page,
            last_page: data.listado.last_page,
            total: data.listado.total
          }
        })
        .catch(err => console.error('Error enviadas', err))
    },
    abrirNotificacion (notif, tipo = 'recibida') {
      this.notificacionActiva = notif

      const titulo = tipo === 'recibida' ? 'Transferencia recibida' : 'Transferencia enviada'
      let textoPrincipal = ''

      if (tipo === 'recibida') {
        textoPrincipal = `<b>${notif.mensaje}</b>`
      } else {
        const destinoName = notif.agencia ? notif.agencia.nombre : 'Destino desconocido'
        textoPrincipal = `Has enviado productos a: <br><b style="font-size: 14px; color: #1976D2">${destinoName}</b>`
      }

      const formatDialogDate = iso => {
        const d = new Date(iso)
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
      }

      let mensaje = `
        <div style="font-size:15px;margin-bottom:8px;">${textoPrincipal}</div>
        <div style="color:#777;font-size:12px;margin-bottom:12px;">Fecha: ${formatDialogDate(notif.created_at)}</div>
      `

      try {
        const productos = JSON.parse(notif.detalle)
        if (productos.length > 0) {
          mensaje += '<div style="margin-bottom: 5px;"><b>Detalle:</b></div>'
          mensaje += '<div style="max-height: 250px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px;">'
          mensaje += '<table style="width: 100%; border-collapse: collapse;">'
          productos.forEach(p => {
            mensaje += `
              <tr style="border-bottom: 1px solid #f0f0f0;">
                <td style="padding: 8px;">
                  <b>${p.nombre}</b> <br>
                  <span style="color: #555; font-size: 12px;">${p.cantidad} unid. ${p.fechaVencimiento ? `(Vence: ${p.fechaVencimiento})` : ''}</span>
                </td>
              </tr>`
          })
          mensaje += '</table></div>'
        }
      } catch (e) {
        mensaje += '<div class="text-red">Error cargando detalle</div>'
      }

      this.$q.dialog({
        html: true,
        title: titulo,
        message: mensaje,
        ok: { label: 'Cerrar', flat: true, color: 'primary' }
      }).onOk(() => {
        if (tipo === 'recibida' && !notif.leida) {
          this.$axios.put(`/notificaciones/${notif.id}/leer`).then(() => {
            const idx = this.notificaciones.findIndex(n => n.id === notif.id)
            if (idx !== -1) this.notificaciones[idx].leida = 1
            if (this.conteoNoLeidas > 0) {
              this.conteoNoLeidas--
            }
          })
        }
      })
    }
  }
}
</script>

<style>
.drawer-shell {
  background: linear-gradient(180deg, #0f4c81 0%, #0a3558 100%) !important;
}

.drawer-content {
  min-height: 100%;
  padding: 12px 10px 16px;
  display: flex;
  flex-direction: column;
}

.drawer-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  margin-bottom: 10px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.13);
}

.drawer-profile__avatar {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.18);
  flex-shrink: 0;
}

.drawer-profile__info {
  min-width: 0;
}

.drawer-profile__name {
  font-weight: 700;
  font-size: 13px;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.drawer-profile__sub {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.65);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.drawer-section-label {
  padding: 0 8px 6px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.55);
}

.drawer-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* q-expansion-item header: se aplica via :header-class, necesita !important */
.drawer-group {
  border-radius: 12px !important;
  background: rgba(255, 255, 255, 0.07) !important;
  color: white !important;
  font-weight: 600 !important;
  font-size: 12.5px !important;
  min-height: 36px !important;
}

.drawer-group--active {
  background: rgba(255, 255, 255, 0.16) !important;
}

/* separador de q-expansion-item */
.drawer-group + .q-expansion-item__content {
  padding: 0;
}

.drawer-link {
  min-height: 34px !important;
  border-radius: 9px !important;
  margin: 1px 0;
  color: rgba(176, 210, 255, 0.85) !important;
}

.drawer-link__avatar {
  min-width: 30px;
}

.drawer-link__label {
  font-size: 12.5px;
  line-height: 1.2;
}

/* active-class aplicado por Vue Router */
.drawer-link--active {
  background-color: #1565C0 !important;
  border-radius: 9px !important;
  color: white !important;
}

.drawer-link--active .q-icon {
  color: white !important;
}

.drawer-logout {
  margin-top: auto;
  padding-top: 10px;
  border-radius: 12px !important;
  background: rgba(244, 67, 54, 0.18) !important;
  color: #ffd5d2 !important;
  min-height: 38px !important;
}
</style>
