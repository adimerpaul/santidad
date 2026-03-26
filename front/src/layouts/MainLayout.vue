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
      bordered
      :width="228"
      class="bg-grey-3 text-grey-10"
    >
      <div class="column fit no-wrap">
        <div class="q-pa-sm bg-grey-4">
          <div class="row items-center no-wrap q-col-gutter-sm">
            <div class="col-auto">
              <q-avatar size="38px" color="primary" text-color="white" icon="medication" />
            </div>
            <div class="col">
              <div class="text-body2 text-weight-bold ellipsis">Farmacia Santidad Divina</div>
              <div class="text-caption text-grey-7 ellipsis">
                {{ $store.user.agencia ? $store.user.agencia.nombre : 'Sin agencia' }}
              </div>
            </div>
          </div>
        </div>

        <q-scroll-area class="col">
          <div class="q-pa-xs">
            <div
              v-for="section in menuSections"
              :key="section.title"
              class="q-mb-xs"
            >
              <div class="text-caption text-uppercase text-grey-7 text-weight-bold q-px-sm q-py-xs menu-section-title">
                {{ section.title }}
              </div>
              <q-list dense class="rounded-borders bg-grey-2 q-pa-xs">
                <q-item
                  v-for="item in section.items"
                  :key="item.to"
                  clickable
                  v-ripple
                  :to="item.to"
                  exact
                  active-class="menu-item-active"
                  class="menu-item q-my-xs"
                >
                  <q-item-section avatar class="menu-avatar-section">
                    <q-icon :name="item.icon" size="18px" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-medium menu-label">{{ item.label }}</q-item-label>
                    <q-item-label caption class="text-grey-7 menu-caption">{{ item.caption }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </q-scroll-area>

        <div class="q-pa-xs bg-grey-4">
          <q-list dense class="rounded-borders bg-grey-2 q-pa-xs">
            <q-item clickable v-ripple class="menu-item" @click="logout()">
              <q-item-section avatar class="menu-avatar-section">
                <q-icon name="logout" color="negative" size="18px" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium menu-label">Cerrar sesión</q-item-label>
                <q-item-label caption class="text-grey-7 menu-caption">Salir del sistema</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </div>
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
        { to: '/', label: 'Movimientos', caption: 'Resumen operativo y caja', icon: 'space_dashboard' },
        { to: '/sale', label: 'Venta', caption: 'Registro de ventas', icon: 'point_of_sale' },
        { to: '/compras', label: 'Compras', caption: 'Ingreso de mercadería', icon: 'shopping_bag' },
        { to: '/pedidos', label: 'Pedidos', caption: 'Solicitudes a central', icon: 'assignment' },
        { to: '/historial-pedidos', label: 'Historial de pedidos', caption: 'Seguimiento de pedidos', icon: 'history' },
        { to: '/transferencias', label: 'Transferencias', caption: 'Movimientos entre agencias', icon: 'swap_horiz' },
        { to: '/facturas', label: 'Facturas', caption: 'Control de facturación', icon: 'receipt_long' }
      ]

      const inventario = [
        { to: '/productos', label: 'Productos', caption: 'Catálogo principal', icon: 'inventory_2' },
        { to: '/subcategorias', label: 'Subcategorías', caption: 'Clasificación de productos', icon: 'category' },
        { to: '/unidades', label: 'Unidades', caption: 'Presentaciones y medidas', icon: 'straighten' },
        { to: '/productosPorVencer', label: 'Por vencer', caption: 'Alertas de vencimiento', icon: 'warning_amber' },
        { to: '/productosVencidos', label: 'Vencidos', caption: 'Control de bajas', icon: 'report_gmailerrorred' },
        { to: '/productosRetirados', label: 'Retirados', caption: 'Productos fuera de circulación', icon: 'remove_shopping_cart' }
      ]

      const gestion = [
        { to: '/clientes', label: 'Clientes', caption: 'Base de clientes', icon: 'groups' },
        { to: '/proveedores', label: 'Proveedores', caption: 'Relación de compras', icon: 'local_shipping' },
        { to: '/vendedores', label: 'Vendedores', caption: 'Equipo comercial', icon: 'badge' },
        { to: '/users', label: 'Usuarios', caption: 'Accesos del sistema', icon: 'manage_accounts' },
        { to: '/agencias', label: 'Agencias', caption: 'Sucursales y sedes', icon: 'apartment' },
        { to: '/reportes', label: 'Reportes', caption: 'Consultas y análisis', icon: 'insert_chart' },
        { to: '/carousel', label: 'Carousel', caption: 'Contenido visual', icon: 'view_carousel' }
      ]

      const sections = [
        { title: 'Operación', items: operacion },
        { title: 'Inventario', items: this.isAdmin ? inventario : inventario.filter(item => item.to === '/productos' || item.to === '/productosPorVencer') }
      ]

      if (this.isAdmin) {
        sections.push({ title: 'Gestión', items: gestion })
      }

      return sections
    }
  },
  mounted () {
    this.getNotificaciones(1)
    setInterval(() => this.getNotificaciones(1, true), 120000)
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

<style scoped>
.menu-item {
  border-radius: 10px;
  min-height: 42px;
  padding: 6px 8px;
}

.menu-item-active {
  background: var(--q-primary);
  color: white;
}

.menu-item-active .text-grey-7 {
  color: rgba(255, 255, 255, 0.82) !important;
}

.menu-avatar-section {
  min-width: 30px;
}

.menu-label {
  font-size: 12.5px;
  line-height: 1.1;
}

.menu-caption {
  font-size: 10.5px;
  line-height: 1.1;
}

.menu-section-title {
  font-size: 10px;
  letter-spacing: 0.08em;
}
</style>
