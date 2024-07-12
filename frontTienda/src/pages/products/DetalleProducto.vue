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
    <div :class="`col-12 col-md-6 ${$q.screen.lt.md?'text-center':'text-right'}`">
      <q-img
        :src="product?.imagen.includes('http')?product?.imagen:`${$url}../images/${product?.imagen}`"
        spinner-color="white"
        v-if="product?.imagen"
        style="border: 2px solid #496aec;border-radius: 5px"
        width="250px"
        spinner-size="40px"></q-img>
    </div>
<!--    {-->
<!--    "id": 8,-->
<!--    "nombre": "HUGGIES ONE DONE REFRESHING TOALLITAS HUMEDAS X 48 UNIDADES",-->
<!--    "barra": null,-->
<!--    "cantidad": 0,-->
<!--    "cantidadAlmacen": 0,-->
<!--    "cantidadSucursal1": 0,-->
<!--    "cantidadSucursal2": 0,-->
<!--    "cantidadSucursal3": 0,-->
<!--    "cantidadSucursal4": 0,-->
<!--    "costo": 15.38,-->
<!--    "precioAntes": 29.5,-->
<!--    "precio": 20,-->
<!--    "activo": "ACTIVO",-->
<!--    "unidad": "PAQUETE",-->
<!--    "registroSanitario": null,-->
<!--    "paisOrigen": "",-->
<!--    "nombreComun": null,-->
<!--    "composicion": null,-->
<!--    "marca": "",-->
<!--    "distribuidora": "",-->
<!--    "imagen": "7702425800700_a34898b6-0419-4af4-ac82-6dcdfea028f7_543x543.webp",-->
<!--    "descripcion": "Las toallitas humedas Huggies naturally refreshing baby tienen un aroma a pepino fresco y te verde con una textura similar a una toallita. Estas toallitas no contienen alcohol y contienen ingredientes suaves como el aloe por lo que puede usar estas toallitas para limpiar mas que solo las nalgas de su bebe.",-->
<!--    "category_id": 6,-->
<!--    "agencia_id": 1,-->
<!--    "created_at": "2023-08-18T10:47:56.000000Z",-->
<!--    "updated_at": "2024-05-31T09:32:35.000000Z",-->
<!--    "subcategory_id": 24,-->
<!--    "cantidadSucursal5": 0,-->
<!--    "cantidadSucursal6": 0,-->
<!--    "cantidadSucursal7": 0,-->
<!--    "cantidadSucursal8": 0,-->
<!--    "cantidadSucursal9": 0,-->
<!--    "cantidadSucursal10": 0-->
<!--    }-->
    <div class="col-12 col-md-6">
      <div class="text-left q-px-md">
        <div class="text-h6">{{product?.nombre}}</div>
        <table>
          <tr>
            <td class="text-bold">Precio:</td>
            <td>Bs. {{product?.precio}}</td>
          </tr>
          <tr>
            <td class="text-bold">Unidad:</td>
            <td>{{product?.unidad}}</td>
          </tr>
          <tr>
            <td class="text-bold">Stock:</td>
            <td>{{product?.cantidad}}</td>
          </tr>
          <tr>
            <td class="text-bold">Principio activo:</td>
            <td>{{product?.composicion}}</td>
          </tr>
          <tr>
            <td class="text-bold">Distribuidora:</td>
            <td>{{product?.distribuidora}}</td>
          </tr>
          <tr>
            <td class="text-bold">Laboratorio:</td>
            <td>{{product?.marca}}</td>
          </tr>
          <tr>
            <td class="text-bold">Pais origen:</td>
            <td>{{product?.paisOrigen}}</td>
          </tr>
          <tr>
            <td class="text-bold">Registro sanitario:</td>
            <td>{{product?.registroSanitario}}</td>
          </tr>
        </table>
      </div>
      <div class="row">
        <div class="col-9 flex flex-center">
          <span class="text-bold">Cantidad:</span>
          <q-btn
            @click="cantidad = cantidad - 1"
            icon="remove"
            dense
            round
            color="primary"
            :disable="cantidad<=1"
          />
          <q-input v-model="cantidad" type="number"  min="1" class="text-center text-bold"  outlined dense style="text-align: center;width: 100px"/>
          <q-btn
            @click="cantidad = cantidad + 1"
            icon="add"
            dense
            round
            color="primary"
            :disable="cantidad>=product?.cantidad"
          />
          <span class="text-bold">{{product?.unidad}}</span>
        </div>
        <div class="col-3 bg-grey-4 text-right">
          <label class="text-bold ">Total</label>
          <div class="text-bold justify-between">
            Bs.
            <span>{{(product?.precio * cantidad).toFixed(2)}}</span>
          </div>
        </div>
        <div class="col-6">
          <q-btn
            @click="addCart(product, cantidad)"
            label="Añadir al carrito"
            icon="add_shopping_cart"
            class="full-width"
            color="blue"
            no-caps
            dense/>
        </div>
        <div class="col-6">
          <q-btn
            @click="addCart(product, cantidad, true)"
            label="Comprar ahora"
            icon="shopping_cart"
            class="full-width"
            color="red"
            no-caps
            dense/>
        </div>
      </div>
    </div>
    <div class="col-12">
      <label class="text-bold text-h6">Descripción:</label>
      <div class="text-justify q-px-md" v-html="product?.descripcion"></div>
    </div>
    <div class="col-12">
      <label class="text-bold text-h6">Productos relacionados:</label>
    </div>
  </div>
<!--  <pre>{{product}}</pre>-->
</q-page>
</template>
<script>
export default {
  name: 'DetalleProducto',
  data () {
    return {
      id: this.$route.params.id,
      product: {},
      loading: true,
      cantidad: 1
    }
  },
  mounted () {
    this.getProduct()
  },
  methods: {
    async getProduct () {
      this.loading = true
      this.$q.loading.show()
      const response = await this.$axios.get(`productos/${this.id}`)
      this.product = response.data
      this.loading = false
      this.$q.loading.hide()
    },
    addCart (product, cantidad, comprar = false) {
      const text = `Deseo comprar ${cantidad} ${product.nombre} a Bs. ${product.precio} c/u. Total Bs. ${(product.precio * cantidad).toFixed(2)}`
      window.open(`https://wa.me/59172319869?text=${text}`)
    }
  }
}
</script>
