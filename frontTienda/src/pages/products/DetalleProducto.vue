<template>
  <q-page class="bg-grey-3 q-pa-md">
    <div class="row">
      <div class="col-12">
        <q-breadcrumbs>
          <q-breadcrumbs-el label="Inicio" to="/" />
          <q-breadcrumbs-el label="Productos" to="/" />
          <q-breadcrumbs-el :label="product?.nombre" />
        </q-breadcrumbs>
      </div>

      <div :class="`col-12 col-md-6 ${$q.screen.lt.md ? 'text-center' : 'text-right'}`">
        <q-img
          :src="product?.imagen.includes('http') ? product?.imagen : `${$url}../images/${product?.imagen}`"
          spinner-color="white"
          v-if="product?.imagen"
          style="border: 2px solid #496aec; border-radius: 5px"
          width="250px"
          spinner-size="40px"
        />
      </div>

      <div class="col-12 col-md-6">
        <div class="text-left q-px-md">
          <h1 class="text-h5 q-mb-md">{{ product?.nombre }}</h1>

          <h2 class="text-h6 q-mt-sm">Precio y características</h2>
          <table>
            <tr>
              <td class="text-bold">Precio:</td>
              <td>
                Bs. {{ product?.precio }}
                <q-badge v-if="es_porcentaje" color="red">
                  Descuento {{ product?.porcentaje }}% - Bs. {{ (product?.precio * product?.porcentaje / 100).toFixed(2) }}
                </q-badge>
                <q-badge color="green" v-if="es_porcentaje">
                  Bs. {{ product?.precioNormal }}
                </q-badge>
              </td>
            </tr>
            <tr><td class="text-bold">Unidad:</td><td>{{ product?.unidad }}</td></tr>
            <tr><td class="text-bold">Stock:</td><td>{{ product?.cantidad > 100 ? '100' : product?.cantidad }}</td></tr>
            <tr><td class="text-bold">Principio activo:</td><td>{{ product?.composicion }}</td></tr>
            <tr><td class="text-bold">Distribuidora:</td><td>{{ product?.distribuidora }}</td></tr>
            <tr><td class="text-bold">Laboratorio:</td><td>{{ product?.marca }}</td></tr>
            <tr><td class="text-bold">País origen:</td><td>{{ product?.paisOrigen }}</td></tr>
            <tr><td class="text-bold">Registro sanitario:</td><td>{{ product?.registroSanitario }}</td></tr>
          </table>
        </div>

        <div class="row">
          <div class="col-9 flex flex-center">
            <span class="text-bold">Cantidad:</span>
            <q-btn @click="cantidad = cantidad - 1" icon="remove" dense round color="primary" :disable="cantidad <= 1" />
            <q-input v-model="cantidad" type="number" min="1" class="text-center text-bold" outlined dense style="width: 100px" />
            <q-btn @click="cantidad = cantidad + 1" icon="add" dense round color="primary" :disable="cantidad >= product?.cantidad" />
            <span class="text-bold">{{ product?.unidad }}</span>
          </div>

          <div class="col-3 bg-grey-4 text-right">
            <label class="text-bold">Total</label>
            <div class="text-bold">Bs. <span>{{ (product?.precio * cantidad).toFixed(2) }}</span></div>
          </div>

          <div class="col-4 q-pa-xs">
            <q-btn @click="addCarrito(product, cantidad)" label="Añadir al carrito" icon="add_shopping_cart" class="full-width" color="blue" no-caps dense />
          </div>
          <div class="col-4 q-pa-xs">
            <q-btn @click="addCart(product, cantidad, true)" label="Comprar ahora" icon="shopping_cart" class="full-width" color="red" no-caps dense />
          </div>
          <div class="col-4 q-pa-xs">
            <q-btn @click="share" label="Compartir" icon="share" class="full-width" color="green" no-caps dense />
          </div>
        </div>

        <div class="q-pt-xs">
          <h3 class="text-h6 text-blue">Disponible en las siguientes sucursales</h3>
          <q-list dense>
            <template v-for="sucursal in sucursales" :key="sucursal.id">
              <q-item v-if="sucursal.cantidad > 0">
                <q-item-section avatar>
                  <q-avatar><q-icon name="store" /></q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ sucursal.nombre }}</q-item-label>
                  <q-item-label caption>{{ sucursal.direccion }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge color="green" :label="'Disponible'" />
                </q-item-section>
              </q-item>
            </template>
          </q-list>
        </div>
      </div>

      <div class="col-12">
        <h2 class="text-h6">Descripción</h2>
        <div class="text-justify q-px-md" v-html="product?.descripcion"></div>
      </div>

      <div class="col-12">
        <h3 class="text-h6">Productos relacionados</h3>
      </div>
    </div>
  </q-page>
</template>

<script>
import { useMeta } from 'quasar'

export default {
  name: 'DetalleProducto',
  data () {
    return {
      id: this.$route.params.id,
      product: {},
      loading: true,
      cantidad: 1,
      es_porcentaje: false,
      sucursales: []
    }
  },
  async mounted () {
    await this.getSucursales()
    await this.getProduct()
    // const nombre = this.product.nombre || 'Detalle del Producto'
    // const descripcion = this.product?.descripcion || 'Explora nuestros productos disponibles en farmacia online.'
    // const imagen = this.product.imagen?.includes('http')
    //   ? this.product.imagen
    //   : `${window.location.origin}/images/${this.product.imagen}`
    //
    // useMeta({
    //   title: nombre,
    //   titleTemplate: title => `${title} - Santidad Divina`,
    //   description: descripcion,
    //   meta: {
    //     description: { name: 'description', content: 'Page 1' },
    //     keywords: { name: 'keywords', content: 'Quasar website' },
    //     equiv: { 'http-equiv': 'Content-Type', content: 'text/html; charset=UTF-8' },
    //     'og:title': { property: 'og:title', content: nombre },
    //     'og:description': { property: 'og:description', content: descripcion },
    //     'og:image': { property: 'og:image', content: imagen },
    //     'og:type': { property: 'og:type', content: 'product' },
    //     'og:url': { property: 'og:url', content: window.location.href }
    //   },
    //   link: {
    //     canonical: { rel: 'canonical', href: window.location.href }
    //   }
    // })
  },
  methods: {
    async getSucursales () {
      const response = await this.$axios.get('sucursales')
      this.sucursales = response.data
    },
    async getProduct () {
      this.loading = true
      this.$q.loading.show()
      const response = await this.$axios.get(`productos/${this.id}`)
      this.product = response.data
      if (this.product.porcentaje > 0) {
        this.es_porcentaje = true
        this.product.precioNormal = this.product.precio
        const precio = this.product.precio - (this.product.precio * this.product.porcentaje / 100)
        this.product.precio = precio.toFixed(2)
      }
      this.sucursales.forEach(sucursal => {
        sucursal.cantidad = this.product[`cantidadSucursal${sucursal.id}`]
      })
      this.loading = false
      this.$q.loading.hide()
      console.log('descripcion', this.product?.descripcion)
      const descripcionSeo = this.product?.descripcion + ' Producto disponible en Santidad Divina. Encuentra este y otros productos médicos en nuestra farmacia digital, con stock actualizado y atención inmediata.' || 'Producto disponible en Santidad Divina.'
      useMeta({
        title: this.product.nombre,
        description: descripcionSeo,
        meta: {
          description: { name: 'description', content: descripcionSeo },
          keywords: {
            name: 'keywords',
            content: `medicamento, salud, ${this.product.nombre}, ${this.product.marca}, ${this.product?.composicion}`
          },
          robots: { name: 'robots', content: 'index, follow' },
          'og:title': { property: 'og:title', content: this.product.nombre },
          'og:description': { property: 'og:description', content: descripcionSeo },
          'og:image': { property: 'og:image', content: this.$url + '../images/' + this.product.imagen },
          'og:type': { property: 'og:type', content: 'product' },
          'og:url': { property: 'og:url', content: window.location.href }
        },
        link: {
          canonical: { rel: 'canonical', href: window.location.href }
        }
      })
    },
    addCarrito (product, cantidad) {
      product.cantidad = cantidad
      this.$store.addCarrito(product)
      this.$alert.success('Producto añadido al carrito')
    },
    addCart (product, cantidad, comprar = false) {
      const text = `Deseo comprar ${cantidad} ${product.nombre} a Bs. ${product.precio} c/u. Total Bs. ${(product.precio * cantidad).toFixed(2)}`
      window.open(`https://wa.me/59172319869?text=${text}`)
    },
    share () {
      const shareData = {
        title: this.product.nombre,
        text: this.product.descripcion,
        url: window.location.href
      }
      navigator.share(shareData)
        .then(() => console.log('Successful share'))
        .catch((error) => console.log('Error sharing', error))
    }
  }
}
</script>
