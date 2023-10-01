<template>
  <q-page>
    <q-carousel
      animated
      v-model="slide"
      arrows
      :height="$q.screen.lt.md ? '200px' : '500px'"
      width="80%"
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
        <div class="text-h6 text-center text-bold no-select">
          PRODUCTOS DESTACADOS
        </div>
      </div>
      <div class="col-12">
        <q-card>
          <q-card-section class="q-pa-xs">
            <div class="row cursor-pointer" v-if="products.length>0">
              <div class="col-4 col-md-2 q-pa-md" v-for="p in products" :key="p.id">
                <q-card @click="clickDetalleProducto(p)">
                  <q-img :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`" width="100%" height="100px">
                    <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">
                      {{p.nombre}}
                    </div>
                  </q-img>
                  <q-card-section class="q-pa-none q-ma-none">
                    <div class="text-center text-subtitle2">{{ p.precio }} Bs</div>
                    <div :class="`text-center text-bold text-${p.cantidad<=10?'red':p.cantidad<=20?'yellow-9':'black'}`">{{ p.cantidad }} {{ $q.screen.lt.md?'Dis':'Disponible' }}</div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
            <q-card v-else>
              <q-card-section>
                <div class="row">
                  <div class="col-12 flex flex-center">
                    <q-avatar size="150px" font-size="150px" color="white" text-color="grey" icon="view_in_ar" />
                  </div>
                  <div class="col-12">
                    <div class="text-bold text-grey text-center">No encontramos productos para tu búsqueda.</div>
                    <div class="text-bold text-grey text-center">Intenta con otra palabra o agrega productos a tu Inventario.</div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </q-card-section>
        </q-card>
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
      products: []
    }
  },
  created () {
    // for (let i = 0; i < 100; i++) {
    //   this.heavyList.push({
    //     label: 'Option ' + (i + 1),
    //     class: i % 2 === 0 ? 'q-pa-md self-center bg-grey-2 text-black' : 'q-pa-lg bg-black text-white'
    //   })
    // }
    // this.programacionGet()
    this.carouselsGet()
    this.productsGet()
  },
  methods: {
    productsGet () {
      this.loading = true
      this.$axios.get('productos').then(res => {
        this.loading = false
        this.products = res.data
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    },
    carouselsGet () {
      this.$axios.get('carousels').then(response => {
        this.carousels = response.data
      })
    }
  }
}
</script>
