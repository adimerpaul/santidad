<template>
  <div>
    <div class="flex flex-center">
      <q-img :src="product.imagen.includes('http')?product.imagen:`${$url}../images/${product.imagen}`" width="200px">
        <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;">
          {{product.nombre}}
        </div>
      </q-img>
    </div>
    <q-card-section class="q-pa-none q-ma-none">
      <div class="text-center text-subtitle2">{{ product.precio }} Bs</div>
      <div :class="product.cantidad<=0?'text-center text-bold text-red':' text-center text-bold'">{{ product.cantidad }} Disponible</div>
      <div :class="product.cantidadAlmacen<=0?'text-center text-bold text-red':' text-center text-bold'">{{ product.cantidadAlmacen }} Almacen</div>
    </q-card-section>
    <q-card flat bordered class="bg-grey-1">
      <q-card-section>
        <div class="row">
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_qr_code_2" class="text-grey" size="20px" />
            Codigo de barras
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right">{{ product.barra }}</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_paid" class="text-grey" size="20px" />
            Precio
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right">{{ product.precio }} Bs</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-red">
            <q-icon name="o_paid" class="text-red" size="20px" />
            Precio antes
          </div>
          <div class="col-12 col-md-6">
            <div class="text-red text-caption text-right">{{ product.precioAntes }} Bs</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_shopping_bag" class="text-grey" size="20px" />
            Costo
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right">{{ product.costo }} Bs</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_local_offer" class="text-grey" size="20px" />
            Activo
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right">{{ product.activo }}</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_shopping_cart" class="text-grey" size="20px" />
            Unidad
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right">{{ product.unidad }}</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_local_shipping" class="text-grey" size="20px" />
            Agencia
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right" v-if="product.agencia">{{ product.agencia.nombre }}</div>
          </div>
          <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
            <q-icon name="o_shopping_cart" class="text-grey" size="20px" />
            Categoria
          </div>
          <div class="col-12 col-md-6">
            <div class="text-grey text-caption text-right" v-if="product.category">{{ product.category.name }}</div>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>
<script>
export default {
  name: 'DetailProductsPage',
  props: {
    product: {
      type: Object,
      default: () => {
        return {}
      }
    }
  }
}
</script>
