<template>
  <q-page class="q-pa-none">
    <div class="carousel-container">
      <q-carousel
          animated
          v-model="slide"
          arrows
          navigation
          infinite
          height="auto"
          :autoplay="5000"
      >
        <q-carousel-slide :name="1">
          <img src="images/1.png" alt="Imagen 1" />
        </q-carousel-slide>
      </q-carousel>
    </div>
    <div class="row bg-grey-3">
<!--      <div class="col-12 col-md-2"></div>-->
<!--      <div class="col-12" >-->
<!--        <q-carousel-->
<!--          animated-->
<!--          v-model="slide"-->
<!--          arrows-->
<!--          navigation-->
<!--          navigation-icon="radio_button_unchecked"-->
<!--          control-text-color="primary"-->
<!--          autoplay-->
<!--          infinite-->
<!--          height="auto"-->
<!--        >-->
<!--&lt;!&ndash;          <q-carousel-slide :name="i++" v-for="(c,i) in carousels" :key="i++" class="cursor-pointer q-pa-xs"&ndash;&gt;-->
<!--&lt;!&ndash;                            :img-src="$q.screen.lt.md ?`${$url}../images/${c.imageResponsive}`:`${$url}../images/${c.image}`"&ndash;&gt;-->
<!--&lt;!&ndash;          />&ndash;&gt;-->
<!--          <q-carousel-slide :name="1">-->
<!--            <img src="images/1.png" alt="Imagen 1" />-->
<!--          </q-carousel-slide>-->
<!--          <q-carousel-slide :name="2">-->
<!--            <img src="images/1.png" alt="Imagen 2" />-->
<!--          </q-carousel-slide>-->
<!--          <q-carousel-slide :name="3">-->
<!--            <img src="images/1.png" alt="Imagen 3" />-->
<!--          </q-carousel-slide>-->
<!--          <q-carousel-slide :name="4">-->
<!--            <img src="images/1.png" alt="Imagen 4" />-->
<!--          </q-carousel-slide>-->
<!--        </q-carousel>-->
<!--&lt;!&ndash;        <pre>{{slide}}</pre>&ndash;&gt;-->
<!--&lt;!&ndash;        <div v-for="(c,i) in carousels" :key="i">&ndash;&gt;-->
<!--&lt;!&ndash;&lt;!&ndash;          <pre>{{ i }}</pre>&ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;          <q-img :src="`${$url}../images/${c.image}`" height="10%" :style="`display: ${slide === i ? '' : 'none'}`">&ndash;&gt;-->
<!--&lt;!&ndash;&lt;!&ndash;            colocar 2 botones a la izuiqerda ya ala derecha para&ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;            <div class="absolute-top-left q-ma-xs">&ndash;&gt;-->
<!--&lt;!&ndash;              <q-btn&ndash;&gt;-->
<!--&lt;!&ndash;                flat&ndash;&gt;-->
<!--&lt;!&ndash;                dense&ndash;&gt;-->
<!--&lt;!&ndash;                round&ndash;&gt;-->
<!--&lt;!&ndash;                icon="chevron_left"&ndash;&gt;-->
<!--&lt;!&ndash;                aria-label="Previous"&ndash;&gt;-->
<!--&lt;!&ndash;                color="white"&ndash;&gt;-->
<!--&lt;!&ndash;                @click="slide&#45;&#45;"&ndash;&gt;-->
<!--&lt;!&ndash;                v-if="i>0"&ndash;&gt;-->
<!--&lt;!&ndash;              />&ndash;&gt;-->
<!--&lt;!&ndash;            </div>&ndash;&gt;-->
<!--&lt;!&ndash;            <div class="absolute-top-right q-ma-xs">&ndash;&gt;-->
<!--&lt;!&ndash;              <q-btn&ndash;&gt;-->
<!--&lt;!&ndash;                flat&ndash;&gt;-->
<!--&lt;!&ndash;                dense&ndash;&gt;-->
<!--&lt;!&ndash;                round&ndash;&gt;-->
<!--&lt;!&ndash;                icon="chevron_right"&ndash;&gt;-->
<!--&lt;!&ndash;                aria-label="Next"&ndash;&gt;-->
<!--&lt;!&ndash;                color="white"&ndash;&gt;-->
<!--&lt;!&ndash;                @click="slide++"&ndash;&gt;-->
<!--&lt;!&ndash;                v-if="i<carousels.length-1"&ndash;&gt;-->
<!--&lt;!&ndash;              />&ndash;&gt;-->
<!--&lt;!&ndash;            </div>&ndash;&gt;-->
<!--&lt;!&ndash;          </q-img>&ndash;&gt;-->
<!--&lt;!&ndash;&lt;!&ndash;          <pre>{{c}}</pre>&ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;        </div>&ndash;&gt;-->
<!--      </div>-->
<!--      <div class="col-12 col-md-2"></div>-->
    </div>
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
            @keyup.enter="buscar"
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
          <div class="row cursor-pointer" v-if="$store.products.length>0">
            <div class="col-6 col-md-3 q-pa-md" v-for="p in $store.products" :key="p.id">
              <q-card @click="clickDetalleProducto(p)" flat bordered style="border: 2px solid #00BD73">
                <q-img :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`" width="100%" height="130px">
<!--                  <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">-->
<!--                    {{p.nombre}}-->
<!--                  </div>-->
                  <q-badge color="red" floating style="padding: 10px 10px 5px 5px;margin: 0px" v-if="p.porcentaje">
                    {{p.porcentaje}}%
                  </q-badge>
                </q-img>
                <div class="text-center text-bold" style="line-height: 1;font-size: 14px;height: 30px">
                  {{p.nombre}}
                </div>
                <q-card-section class="q-pa-none q-ma-none">
                  <div class="text-left bg-white" style="font-size: 13px">Bs. {{p.precio}}</div>
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
      slide: 0,
      carousels: [],
      // products: [],
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
    clickDetalleProducto (p) {
      this.$router.push('/detalle-producto/' + p.id + '/' + this.espacioCambioGuion(p.nombre))
    },
    espacioCambioGuion (text) {
      return text.replace(/ |\/+/g, '-')
    },
    buscar () {
      this.loading = true
      this.$axios.get('productos', {
        params: {
          search: this.search,
          page: this.currentPage
        }
      }).then(response => {
        this.$store.products = []
        // this.products = response.data.data
        // this.$store.products = response.data.data
        this.totalPages = response.data.last_page
        response.data.data.forEach(p => {
          const esPorcentaje = p.porcentaje > 0
          if (esPorcentaje) {
            const precio = p.precio - (p.precio * p.porcentaje / 100)
            p.precio = precio.toFixed(2)
          }
          this.$store.products.push(p)
        })
      }).finally(() => {
        this.loading = false
      })
    },
    carouselsGet () {
      // this.loading = true
      this.$axios.get('carouselsPage').then(response => {
        this.carousels = response.data
      }).finally(() => {
        // this.loading = false
      })
    }
  }
}
</script>
<style scoped>
.carousel-container {
  margin: 0;
  padding: 0;
  width: 100%; /* Asegura que el contenedor ocupe todo el ancho de la ventana */
}

img {
  width: 100%;
  height: auto;
  object-fit: cover; /* Asegura que las imágenes llenen el contenedor sin distorsionarse */
}

.q-carousel-slide {
  margin: 0;
  padding: 0;
  display: flex;
  justify-content: center;
  align-items: center;
}
.q-carousel__slide, .q-carousel .q-carousel--padding  {
  margin: 0;
  padding: 0;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
