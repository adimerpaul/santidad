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
    <q-page-sticky
      position="bottom-right"
      class="q-ma-md"
      :offset="[18, 18]"
    >
      <q-btn
        color="green"
        fab
        icon="fa-solid fa-cart-shopping"
        @click="clickCarrito"
      >
<!--        <pre>-->
<!--          {{ $store?.carrito }}-->
<!--        </pre>-->
        <q-badge
          v-if="$store?.carrito.length"
          color="red"
          floating
          :label="$store?.carrito.length"
        />
      </q-btn>
    </q-page-sticky>
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
    <q-dialog v-model="carritoDialog" maximized position="right">
      <q-card style="width: 350px; max-width: 80vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-bold text-h6 row items-center">
              Carrito de compras
          </div>
          <q-space />
          <q-btn flat icon="close" @click="clickCarrito" />
        </q-card-section>
        <q-card-section>
          <q-item
            v-for="(item,index) in $store?.carrito"
            :key="item.id"
            clickable
            @click="clickCarrito"
          >
            <q-item-section avatar>
              <q-avatar>
                <q-img :src="item.imagen.includes('http')?item.imagen:`${$url}../images/${item.imagen}`" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ item.nombre }}
              </q-item-label>
              <q-item-label caption>
                {{ item.cantidad }} x {{ item.precio }}
              </q-item-label>
            </q-item-section>
            <q-item-section side top>
              <q-btn flat icon="delete" @click="removeCarrito(index)" />
            </q-item-section>
          </q-item>
<!--          boton pedir por whatasapp-->
          <q-btn
            icon="fa-brands fa-whatsapp"
            label="Pedir por WhatsApp"
            color="green"
            no-caps
            class="full-width"
            @click="pedirCarritoWhatsApp"
          />
<!--          <pre>{{ $store?.carrito }}</pre>-->
<!--          [-->
<!--          {-->
<!--          "id": 34,-->
<!--          "nombre": "AZIMAX  200 MG SUSP. X 30 ML",-->
<!--          "barra": null,-->
<!--          "cantidad": 7,-->
<!--          "cantidadAlmacen": 0,-->
<!--          "cantidadSucursal1": 0,-->
<!--          "cantidadSucursal2": 0,-->
<!--          "cantidadSucursal3": 1,-->
<!--          "cantidadSucursal4": 2,-->
<!--          "costo": 79.09,-->
<!--          "precioAntes": null,-->
<!--          "precio": "94.59",-->
<!--          "porcentaje": 8,-->
<!--          "activo": "ACTIVO",-->
<!--          "unidad": "FRASCOS",-->
<!--          "registroSanitario": "NN-64180/2023",-->
<!--          "paisOrigen": "BOLIVIA",-->
<!--          "nombreComun": "Azitrimicina",-->
<!--          "composicion": "Azitrimicina 200 mg/5ml",-->
<!--          "marca": "BRESKOT",-->
<!--          "distribuidora": "COFAR",-->
<!--          "imagen": "1718814998fsfd.jpg",-->
<!--          "descripcion": "Antibiótico macrólido de amplio espectro.",-->
<!--          "category_id": 3,-->
<!--          "agencia_id": null,-->
<!--          "created_at": "2024-01-09T15:56:12.000000Z",-->
<!--          "updated_at": "2024-10-03T19:22:19.000000Z",-->
<!--          "subcategory_id": 13,-->
<!--          "cantidadSucursal5": 0,-->
<!--          "cantidadSucursal6": 2,-->
<!--          "cantidadSucursal7": 2,-->
<!--          "cantidadSucursal8": 0,-->
<!--          "cantidadSucursal9": 0,-->
<!--          "cantidadSucursal10": 0,-->
<!--          "image": "productDefault.jpg",-->
<!--          "precioNormal": 102.82-->
<!--          },-->
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
        // { name: 'Oferas', label: 'Ofertas', to: '/ofertas' },
        { name: 'Sucursales', label: 'Sucursales', to: '/sucursales' }
        // { name: 'Contactos', label: 'Contactos', to: '/contactos' }
      ],
      leftDrawerOpen: false,
      carritoDialog: false,
      search: ''
    }
  },
  methods: {
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
