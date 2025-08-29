<template>
  <q-layout view="lHh Lpr lff">
    <q-page-container>
      <router-view />
    </q-page-container>

    <!-- FAB Carrito -->
    <q-page-sticky position="bottom-right" class="q-ma-md" :offset="[18, 18]">
      <q-btn color="green" fab icon="fa-solid fa-cart-shopping" @click="clickCarrito">
        <q-badge
          v-if="$store?.carrito.length"
          color="red"
          floating
          :label="$store?.carrito.length"
        />
      </q-btn>
    </q-page-sticky>

    <!-- ===== Footer Moderno ===== -->
    <q-footer class="footer-modern no-border">
      <div class="footer-top q-pa-lg">
        <div class="container grid">
          <!-- Col 1: Marca / Slogan -->
          <div class="col">
            <div class="brand row items-center">
              <img src="/images/logo.png" alt="Logo" class="logo" />
              <div class="q-ml-sm">
                <div class="text-h6 text-bold">Farmacia Santidad Divina</div>
                <div class="text-subtitle2 opacity-70">Precio Solidario</div>
              </div>
            </div>
            <div class="q-mt-md text-body2 opacity-80">
              Atendemos con ética y responsabilidad. Encuentra medicamentos,
              dermocosmética y cuidado personal a precios justos.
            </div>

            <!-- Social -->
            <div class="row q-gutter-sm q-mt-md">
              <q-btn round dense flat class="social" icon="fa-brands fa-facebook" href="https://www.facebook.com/profile.php?id=61562087074524" target="_blank" />
              <q-btn round dense flat class="social" icon="fa-brands fa-instagram" href=" https://www.instagram.com/farmacias_santidad_divina/?igsh=emI5NGl6ODNvejJu/" target="_blank" />
              <q-btn round dense flat class="social" icon="fa-brands fa-tiktok" href="https://www.tiktok.com/@santidad_divina/" target="_blank" />
              <q-btn round dense flat class="social" icon="fa-brands fa-whatsapp" href="https://wa.me/59172319869" target="_blank" />
            </div>
          </div>

          <!-- Col 2: Navegación -->
          <div class="col">
            <div class="col-title">Navegación</div>
            <div class="links">
              <router-link class="link" to="/">Inicio</router-link>
              <router-link class="link" to="/sucursales">Sucursales</router-link>
              <a class="link" href="tel:+59172319869">Llámanos</a>
              <a class="link" href="mailto:contacto@santidad.com">farmaciasantidaddivinacentral@gmail.com</a>
            </div>
          </div>

          <!-- Col 3: Legales -->
          <div class="col">
            <div class="col-title">Legales</div>
            <div class="links">
              <router-link class="link" to="/privacidad">Políticas de Privacidad</router-link>
              <router-link class="link" to="/envio">Política de Envío</router-link>
              <router-link class="link" to="/terminos">Términos y Condiciones</router-link>
              <router-link class="link" to="/quienes-somos">Quiénes Somos</router-link>
            </div>
          </div>

          <!-- Col 4: Contacto rápido -->
          <div class="col">
            <div class="col-title">Contacto</div>
            <div class="text-body2 opacity-80">
              <div class="row items-center q-gutter-x-sm q-mb-xs">
                <q-icon name="place" size="20px" /><span>Oruro, Bolivia</span>
              </div>
              <div class="row items-center q-gutter-x-sm q-mb-xs">
                <q-icon name="schedule" size="20px" /><span>Lun-Dom: 08:00–10:00</span>
              </div>
              <div class="row items-center q-gutter-x-sm">
                <q-icon name="phone" size="20px" /><a class="link" href="tel:+59172319869">+591 72319869</a>
              </div>
            </div>
            <!-- Newsletter (opcional) -->
            <div class="q-mt-md">
              <q-input dense standout v-model="newsletter" placeholder="Tu correo electrónico" type="email">
                <template #append>
                  <q-btn dense flat icon="send" @click="suscribir" />
                </template>
              </q-input>
              <div v-if="newsletterOk" class="text-positive text-caption q-mt-xs">¡Gracias por suscribirte!</div>
            </div>
          </div>
        </div>
      </div>

      <q-separator dark class="separator" />

      <div class="footer-bottom q-py-sm">
        <div class="container row items-center justify-between">
          <div class="text-caption opacity-70">
            © {{ new Date().getFullYear() }} Farmacia Santidad Divina. Todos los derechos reservados.
          </div>
          <div class="row q-gutter-sm">
            <router-link class="link tiny" to="/privacidad">Privacidad</router-link>
            <router-link class="link tiny" to="/terminos">Términos</router-link>
            <a class="link tiny" href="mailto:legal@santidad.com">farmaciasantidaddivinacentral@gmail.com</a>
          </div>
        </div>
      </div>
    </q-footer>

    <!-- Carrito -->
    <q-dialog v-model="carritoDialog" maximized position="right">
      <q-card style="width: 350px; max-width: 80vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-bold text-h6 row items-center">Carrito de compras</div>
          <q-space />
          <q-btn flat icon="close" @click="clickCarrito" />
        </q-card-section>
        <q-card-section>
          <q-item
            v-for="(item,index) in $store?.carrito"
            :key="item.id"
            clickable
          >
            <q-item-section avatar>
              <q-avatar>
                <q-img :src="item.imagen.includes('http')?item.imagen:`${$url}../images/${item.imagen}`" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ item.nombre }}</q-item-label>
              <q-item-label caption>{{ item.cantidad }} x {{ item.precio }}</q-item-label>
            </q-item-section>
            <q-item-section side top>
              <q-btn flat icon="delete" @click="removeCarrito(index)" />
            </q-item-section>
          </q-item>

          <q-btn
            icon="fa-brands fa-whatsapp"
            label="Pedir por WhatsApp"
            color="green"
            no-caps
            class="full-width q-mt-md"
            @click="pedirCarritoWhatsApp"
          />
        </q-card-section>
      </q-card>
    </q-dialog>
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
        { name: 'Sucursales', label: 'Sucursales', to: '/sucursales' }
      ],
      leftDrawerOpen: false,
      carritoDialog: false,
      search: '',
      newsletter: '',
      newsletterOk: false
    }
  },
  methods: {
    suscribir () {
      if (!this.newsletter) return
      // Aquí puedes integrar tu endpoint de suscripción (Laravel)
      this.newsletterOk = true
      setTimeout(() => { this.newsletter = ''; this.newsletterOk = false }, 2000)
    },
    pedirCarritoWhatsApp () {
      const carrito = this.$store.carrito
      const mensaje = carrito.reduce((acc, item) => {
        return `${acc}${item.nombre} x ${item.cantidad}\n`
      }, 'Hola, me gustaría pedir los siguientes productos:\n')
      const url = `https://wa.me/59172319869?text=${encodeURIComponent(mensaje)}`
      window.open(url, '_blank')
    },
    clickCarrito () {
      this.carritoDialog = !this.carritoDialog
    },
    removeCarrito (index) {
      this.$store.carrito.splice(index, 1)
    }
  }
})
</script>

<style scoped>
.footer-modern {
  background: linear-gradient(165deg, #0b132b 0%, #1c2541 60%, #3a506b 100%);
  color: #fff;
}
.footer-top .container,
.footer-bottom .container {
  max-width: 1200px;
  margin: 0 auto;
}
.grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 24px;
}
.col { grid-column: span 12; }
@media (min-width: 600px) {
  .col { grid-column: span 6; }
}
@media (min-width: 1024px) {
  .col { grid-column: span 3; }
}
.brand .logo {
  width: 42px; height: 42px; object-fit: contain; border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,.3);
}
.col-title {
  font-weight: 700;
  margin-bottom: 8px;
  letter-spacing: 0.3px;
}
.links { display: flex; flex-direction: column; gap: 6px; }
.link {
  color: #ffffff;
  text-decoration: none;
  opacity: .9;
  transition: opacity .2s, transform .2s;
}
.link:hover { opacity: 1; transform: translateX(2px); }
.link.tiny { font-size: 12px; opacity: .75; }
.opacity-70 { opacity: .7; }
.opacity-80 { opacity: .8; }

.social {
  background: rgba(255,255,255,.08);
  border-radius: 50%;
  transition: transform .15s ease;
}
.social:hover { transform: translateY(-2px); }

.separator { opacity: .15; }

.footer-bottom {
  background: rgba(0,0,0,.18);
  backdrop-filter: saturate(120%) blur(2px);
  padding: 8px 0;
}
</style>
