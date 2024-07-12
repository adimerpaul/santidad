<template>
  <q-page>
    <q-carousel
      animated
      v-model="slide"
      arrows
      navigation
      navigation-icon="radio_button_unchecked"
      control-text-color="primary"
      autoplay
      infinite
    >
      <q-carousel-slide :name="i++" v-for="(c,i) in carousels" :key="i++" class="cursor-pointer q-pa-xs"
                        :img-src="$q.screen.lt.md ?`${$url}../images/${c.imageResponsive}`:`${$url}../images/${c.image}`"
      />
    </q-carousel>
    <div class="row q-pa-xs">
      <div class="col-12">
        <div class="text-h6 text-center text-bold no-select flex flex-center">
          <q-input
            v-model="search"
            dense
            style="width: 300px;min-width: 300px;"
            outlined
            bg-color="grey-4"
            rounded
            placeholder="Buscar producto"
            class="q-ml-sm">
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
          <q-btn
            rounded
            @click="buscar"
            color="primary"
            label="Buscar"
            :loading="loading"
            no-caps/>
        </div>
      </div>
      <div class="col-12 flex flex-center" v-if="totalPages > 1" >
        <q-pagination
          rounded
          v-if="totalPages > 1"
          v-model="currentPage"
          :max="totalPages"
          :max-pages="6"
          @update:model-value="buscar"
          boundary-numbers
          ellipses
        />
      </div>
      <div class="col-12">
<!--        <q-card>-->
<!--          <q-card-section class="q-pa-xs">-->
          <div class="row cursor-pointer" v-if="products.length>0">
            <div class="col-4 col-md-2 q-px-md q-py-xs" v-for="p in products" :key="p.id">
              <q-card @click="clickDetalleProducto(p)" flat bordered style="border: 2px solid #00BD73">
                <q-img :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`" width="100%" height="100px">
<!--                  <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">-->
<!--                    {{p.nombre}}-->
<!--                  </div>-->
                </q-img>
                <div class="text-center text-bold" style="line-height: 1;font-size: 12px;height: 40px">
                  {{p.nombre}}
                </div>
                <q-card-section class="q-pa-none q-ma-none">
                  <q-btn
                    @click="clickDetalleProducto(p)"
                    label="Añadir al carrito"
                    icon="add_shopping_cart"
                    class="full-width"
                    color="green"
                    size="10px"
                    no-caps
                    dense/>
                </q-card-section>
              </q-card>
            </div>
          </div>
        <div class="text-center" v-else>
          <q-icon name="sentiment_very_dissatisfied" size="100px" color="grey-5" />
          <div class="text-h6">No hay productos</div>
        </div>
<!--          </q-card-section>-->
<!--        </q-card>-->
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'IndexPage',
  data () {
    return {
      slide: 1,
      carousels: [],
      products: [],
      search: '',
      loading: false,
      currentPage: 1,
      totalPages: 1
    }
  },
  created () {
    this.carouselsGet()
    this.buscar()
  },
  methods: {
    buscar () {
      this.loading = true
      this.$axios.get('productos', {
        params: {
          search: this.search,
          page: this.currentPage
        }
      }).then(response => {
        this.products = response.data.data
        this.totalPages = response.data.last_page
      }).finally(() => {
        this.loading = false
      })
    },
    carouselsGet () {
      // this.loading = true
      this.$axios.get('carousels').then(response => {
        this.carousels = response.data
      }).finally(() => {
        // this.loading = false
      })
    }
  }
}
</script>
