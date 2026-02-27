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

            <q-badge v-if="conteoNoLeidas > 0" color="red" floating>
              {{ conteoNoLeidas }}
            </q-badge>

            <q-menu ref="menuNotificaciones" @show="getNotificacionesEnviadas(1)">
              <q-list style="min-width: 340px; max-width: 340px">

                <q-item-label header class="q-pa-sm bg-grey-1">
                  <q-btn-toggle
                    v-model="tabNotificaciones"
                    spread no-caps dense
                    toggle-color="primary"
                    color="white" text-color="grey-8"
                    :options="[
                      {label: 'Recibidas', value: 'recibidas'},
                      {label: 'Enviadas', value: 'enviadas'}
                    ]"
                  />
                </q-item-label>
                <q-separator />

                <div v-if="tabNotificaciones === 'recibidas'">
                  <div v-if="notificaciones.length > 0">
                    <q-item
                      v-for="(notif, i) in notificaciones" :key="'in-'+i"
                      clickable v-ripple
                      @click="abrirNotificacion(notif, 'recibida')"
                      class="q-py-sm"
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
                      v-for="(notif, i) in notificacionesEnviadas" :key="'out-'+i"
                      clickable v-ripple
                      @click="abrirNotificacion(notif, 'enviada')"
                      class="q-py-sm"
                    >
                      <q-item-section avatar style="min-width: 20px; padding-right: 10px;">
                        <q-icon name="arrow_outward" color="blue-grey" size="16px" />
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
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/pedidos">
              <q-item-section avatar>
                <q-icon name="o_assignment" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  Pedidos
                  <q-tooltip anchor="top middle" self="bottom middle">
                    Realizar pedido a central
                  </q-tooltip>
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item clickable v-ripple to="/historial-pedidos">
            <q-item-section avatar>
              <q-icon name="history" />
            </q-item-section>
            <q-item-section>
              Historial de Pedidos
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
            <q-item clickable v-ripple exact active-class="bg-primary text-white" to="/vendedores" v-if="$store.user.id=='1'">
              <q-item-section avatar><q-icon name="badge" /></q-item-section>
              <q-item-section>
                <q-item-label>Vendedores</q-item-label>
                <q-tooltip anchor="top middle" self="bottom middle">
                  Administrar vendedores
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
      tabNotificaciones: 'recibidas', // Para controlar la pestaña activa
      notificacionesEnviadas: [], // Array para las enviadas
      paginationEnviadas: { // Paginación independiente para enviadas
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
      prevNotificaciones: [],
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
    this.getNotificaciones(1)
    // Revisar nuevas notificaciones cada 60 seg (solo buscando alertas nuevas)
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
    // --- PEGAR ESTO EN METHODS ---

    // Función optimizada para cargar por páginas
    // Función optimizada para cargar por páginas y notificar novedades
    // Función optimizada para cargar por páginas y alertar en tiempo real
    getNotificaciones (page = 1, isBackgroundCheck = false) {
      const agencia = this.$store.agencia_id
      const paginaSolicitada = isBackgroundCheck ? 1 : page

      // 🕒 NUEVO: Generamos una marca de tiempo única (Cache Buster)
      const timeStamp = new Date().getTime()

      // 🔥 NUEVO: Añadimos &_t=12345678... a la URL para obligar a descargar los datos frescos siempre
      this.$axios.get(`/notificaciones/${agencia}?page=${paginaSolicitada}&_t=${timeStamp}`)
        .then(res => {
          const data = res.data
          const nuevosItems = data.listado.data

          // 1. Siempre actualizamos el número rojo EXACTO
          this.conteoNoLeidas = data.total_no_leidas

          // 2. Si pedimos la página 1, SIEMPRE actualizamos la lista visible
          // (incluso si es en segundo plano). Así, al abrir la campana, los datos están listos sin F5.
          if (paginaSolicitada === 1) {
            this.notificaciones = nuevosItems
            this.pagination = {
              current_page: data.listado.current_page,
              last_page: data.listado.last_page,
              total: data.listado.total
            }

            // 3. DETECTOR DE ALERTAS: Solo lanza el aviso visual si ya cargó antes (size > 0)
            if (isBackgroundCheck && this.prevNotificacionesIds.size > 0) {
              // Filtramos las que NO estén en nuestra memoria Y que NO estén leídas
              const alertasNuevas = nuevosItems.filter(n => !this.prevNotificacionesIds.has(n.id) && (n.leida === 0 || n.leida === false))

              if (alertasNuevas.length > 0) {
                this.$q.notify({
                  type: 'info',
                  color: 'primary',
                  icon: 'notifications_active',
                  message: `📦 ¡Tienes ${alertasNuevas.length} transferencia(s) nueva(s)!`,
                  position: 'top-right'
                })
              }
            }

            // 4. Guardamos los IDs actuales en la memoria para compararlos en los próximos 10 segundos
            nuevosItems.forEach(n => {
              this.prevNotificacionesIds.add(n.id)
            })
          } else {
            // Si el usuario navegó a la pág 2 o 3 manualmente
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

    // Función para los botones Anterior/Siguiente
    cambiarPagina (page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.getNotificaciones(page)
      }
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
    },
    // ... tus otros métodos ...

    // NUEVO MÉTODO PARA OBTENER ENVIADAS
    // PEGA ESTAS FUNCIONES DENTRO DE methods: { ... }

    // 1. Obtener lista de ENVIADAS
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

    // 2. Función inteligente para mostrar detalle (Corrige el texto "Recibido")
    abrirNotificacion (notif, tipo = 'recibida') {
      this.notificacionActiva = notif

      // Título y Texto dinámicos
      const titulo = tipo === 'recibida' ? '📦 Transferencia recibida' : '📤 Transferencia enviada'
      let textoPrincipal = ''
      if (tipo === 'recibida') {
        textoPrincipal = `<b>${notif.mensaje}</b>` // Muestra el mensaje original de la BD
      } else {
        // Si es ENVIADA, creamos nuestro propio mensaje usando la relación 'agencia'
        const destinoName = notif.agencia ? notif.agencia.nombre : 'Destino desconocido'
        textoPrincipal = `Has enviado productos a: <br><b style="font-size: 14px; color: #1976D2">${destinoName}</b>`
      }

      const formatDate = iso => {
        const d = new Date(iso)
        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
      }

      let mensaje = `
        <div style="font-size:15px;margin-bottom:8px;">${textoPrincipal}</div>
        <div style="color:#777;font-size:12px;margin-bottom:12px;">Fecha: ${formatDate(notif.created_at)}</div>
      `

      // Tabla de productos
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
      } catch (e) { mensaje += '<div class="text-red">Error cargando detalle</div>' }

      this.$q.dialog({
        html: true,
        title: titulo,
        message: mensaje,
        ok: { label: 'Cerrar', flat: true, color: 'primary' }
      }).onOk(() => {
        // Solo marcamos como leída si es recibida
        if (tipo === 'recibida' && !notif.leida) {
          this.$axios.put(`/notificaciones/${notif.id}/leer`).then(() => {
            const idx = this.notificaciones.findIndex(n => n.id === notif.id)
            if (idx !== -1) this.notificaciones[idx].leida = 1
            // --- NUEVO: Reducir el contador inmediatamente ---
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
