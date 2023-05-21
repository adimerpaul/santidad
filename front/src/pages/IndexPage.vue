<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-6 col-md-3 bg-white">
        <q-input dense outlined v-model="dateIni" label="Fecha inicial" type="date" required />
      </div>
      <div class="col-6 col-md-3 bg-white">
        <q-input dense outlined v-model="dateFin" label="Fecha final" type="date" required />
      </div>
      <div class="col-6 col-md-3 bg-white">
        <q-input outlined v-model="search" label="Buscar concepto" dense clearable @update:model-value="productsGet" debounce="500">
          <template v-slot:prepend>
            <q-icon name="search" class="cursor-pointer" />
          </template>
        </q-input>
      </div>
      <div class="col-6 col-md-3 text-right">
        <q-btn color="black" no-caps flat icon="o_file_download" @click="downloadReport">
          <div class="q-page-xs subrayado"> Descargar reporte</div>
        </q-btn>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
        <q-card class="">
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_stacked_bar_chart" size="22px" color="grey-7" class="bg-grey-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Total referencia</q-item-label>
                <q-item-label class="text-bold text-h6">0</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
        <q-card class="">
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_local_atm" size="22px" color="green-7" class="bg-green-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Ventas totales</q-item-label>
                <q-item-label :class="`text-bold text-h6 text-green`">{{costoTotalProducts}} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
        <q-card class="">
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_local_atm" size="22px" color="red-7" class="bg-red-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Gastos totales</q-item-label>
                <q-item-label :class="`text-bold text-h6 text-red`">{{costoTotalProducts}} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
// import { date } from 'quasar'
import moment from 'moment'

export default {
  data () {
    return {
      search: '',
      dateIni: moment().startOf('isoweek').format('YYYY-MM-DD'),
      dateFin: moment().endOf('isoweek').format('YYYY-MM-DD'),
      loading: false,
      dateIniWeek: '',
      dateFinWeek: ''
    }
  },
  created () {
  },
  methods: {
  }
}
</script>
