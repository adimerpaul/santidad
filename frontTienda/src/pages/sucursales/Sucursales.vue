<template>
  <q-page class="flex flex-center">
    <div class="row">
      <div class="col-12">
        <label class="text-bold text-h6">Sucursales:</label>
      </div>
      <div class="col-12">
        <q-card>
          <q-card-section>
            <q-list>
              <q-item
                v-for="sucursal in sucursales"
                :key="sucursal.id"
                clickable
                @click="clickDetalleSucursal(sucursal)"
              >
                <q-item-section>
                  <q-item-label>
                    <div class="text-h6">{{sucursal.nombre}}</div>
                    <div class="text-caption">{{sucursal.direccion}}</div>
                  </q-item-label>
                </q-item-section>
                <q-item-section side top>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>
<script>
export default {
  name: 'SucursalesPage',
  data () {
    return {
      sucursales: []
    }
  },
  created () {
    this.sucursalesGet()
  },
  methods: {
    clickDetalleSucursal (sucursal) {
      this.$router.push('/detalle-sucursal/' + sucursal.id)
    },
    sucursalesGet () {
      this.$axios.get('sucursales').then(response => {
        this.sucursales = response.data
      })
    }
  }
}
</script>
