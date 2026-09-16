<template>
  <q-page class="promotions-page q-pa-md">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm">
        <div class="text-overline text-primary text-weight-bold">GESTIÓN COMERCIAL</div>
        <div class="text-h5 text-weight-bold text-grey-9">Promociones y descuentos</div>
        <div class="text-grey-7">Programa descuentos sin modificar el precio normal de los productos.</div>
      </div>
      <div class="col-12 col-sm-auto">
        <q-btn
          color="primary"
          unelevated
          rounded
          no-caps
          icon="add"
          label="Nueva promoción"
          @click="abrirNueva"
        />
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-lg">
      <div v-for="stat in estadisticas" :key="stat.label" class="col-6 col-md-3">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar :color="stat.color + '-1'" :text-color="stat.color" :icon="stat.icon" size="46px" />
            <div class="q-ml-md">
              <div class="text-h5 text-weight-bold">{{ stat.value }}</div>
              <div class="text-caption text-grey-7">{{ stat.label }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="promotions-card">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-sm">
          <div class="text-subtitle1 text-weight-bold">Reglas configuradas</div>
          <div class="text-caption text-grey-7">Si coinciden varias, se aplica el mayor descuento; no se acumulan.</div>
        </div>
        <div class="col-12 col-sm-5 col-md-4">
          <q-input v-model="filtro" outlined dense clearable debounce="250" placeholder="Buscar promoción...">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn flat round color="grey-7" icon="refresh" :loading="cargando" @click="cargarPromociones">
            <q-tooltip>Actualizar</q-tooltip>
          </q-btn>
        </div>
      </q-card-section>

      <q-separator />

      <q-table
        flat
        :rows="promociones"
        :columns="columnas"
        row-key="id"
        :filter="filtro"
        :loading="cargando"
        :pagination="{ rowsPerPage: 15 }"
        no-data-label="Todavía no hay promociones"
      >
        <template #body-cell-nombre="props">
          <q-td :props="props">
            <div class="text-weight-bold text-grey-9">{{ props.row.nombre }}</div>
            <div v-if="props.row.descripcion" class="text-caption text-grey-6 ellipsis" style="max-width: 250px">
              {{ props.row.descripcion }}
            </div>
          </q-td>
        </template>

        <template #body-cell-descuento="props">
          <q-td :props="props">
            <q-chip color="red-1" text-color="red-8" icon="percent" class="text-weight-bold">
              {{ numero(props.row.porcentaje) }}% menos
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-alcance="props">
          <q-td :props="props">
            <div class="text-weight-medium">
              <q-icon :name="props.row.alcance === 'PRODUCTOS' ? 'inventory_2' : 'category'" color="primary" class="q-mr-xs" />
              {{ descripcionAlcance(props.row) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-vigencia="props">
          <q-td :props="props">
            <div v-if="props.row.permanente" class="text-weight-medium">
              <q-icon name="all_inclusive" color="purple" class="q-mr-xs" /> Permanente
            </div>
            <template v-else>
              <div>{{ fecha(props.row.fecha_inicio) }}</div>
              <div class="text-caption text-grey-7">hasta {{ fecha(props.row.fecha_fin) }}</div>
            </template>
          </q-td>
        </template>

        <template #body-cell-aplicacion="props">
          <q-td :props="props">
            <div class="q-mb-xs">
              <q-badge color="blue-grey-1" text-color="blue-grey-9">
                {{ descripcionAgencias(props.row) }}
              </q-badge>
            </div>
            <div class="row q-gutter-xs">
              <q-icon v-if="props.row.canal_fisico" name="storefront" color="teal" size="18px"><q-tooltip>Tienda física</q-tooltip></q-icon>
              <q-icon v-if="props.row.canal_web" name="language" color="blue" size="18px"><q-tooltip>Página web</q-tooltip></q-icon>
              <q-icon v-if="props.row.canal_app" name="smartphone" color="deep-purple" size="18px"><q-tooltip>Aplicación</q-tooltip></q-icon>
              <q-icon v-if="props.row.mostrar_en_ofertas" name="local_offer" color="pink-7" size="18px"><q-tooltip>Visible en la sección Ofertas</q-tooltip></q-icon>
            </div>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge rounded :color="colorEstado(props.row.estado)" class="q-pa-sm">
              {{ props.row.estado }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" class="no-wrap">
            <q-btn flat round dense icon="edit" color="primary" @click="abrirEditar(props.row)"><q-tooltip>Editar</q-tooltip></q-btn>
            <q-btn flat round dense icon="delete_outline" color="negative" @click="confirmarEliminar(props.row)"><q-tooltip>Eliminar</q-tooltip></q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="dialogo" persistent>
      <q-card class="promotion-dialog">
        <q-card-section class="dialog-header row items-center">
          <q-avatar color="white" text-color="primary" :icon="editandoId ? 'edit' : 'add'" size="40px" />
          <div class="q-ml-md">
            <div class="text-h6 text-weight-bold">{{ editandoId ? 'Editar promoción' : 'Nueva promoción' }}</div>
            <div class="text-caption opacity-80">La configuración se aplicará automáticamente.</div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" color="white" v-close-popup />
        </q-card-section>

        <q-form ref="formPromocion" @submit="guardar">
          <q-card-section class="q-pa-lg scroll" style="max-height: 72vh">
            <div class="section-title"><q-icon name="campaign" /> Información principal</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-8">
                <q-input
                  v-model.trim="form.nombre"
                  outlined
                  dense
                  label="Nombre de la promoción *"
                  maxlength="150"
                  :rules="[requerido]"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model.number="form.porcentaje"
                  outlined
                  dense
                  type="number"
                  step="0.01"
                  min="0.01"
                  max="100"
                  suffix="%"
                  label="Descuento *"
                  :rules="[porcentajeValido]"
                />
              </div>
              <div class="col-12">
                <q-input v-model.trim="form.descripcion" outlined dense type="textarea" autogrow label="Descripción opcional" maxlength="1000" />
              </div>
            </div>

            <div class="section-title q-mt-md"><q-icon name="filter_alt" /> ¿A qué se aplica?</div>
            <q-btn-toggle
              v-model="form.alcance"
              spread
              no-caps
              unelevated
              toggle-color="primary"
              color="grey-2"
              text-color="grey-8"
              :options="[
                { label: 'Todas las categorías', value: 'TODAS_CATEGORIAS', icon: 'apps' },
                { label: 'Una categoría', value: 'CATEGORIA', icon: 'category' },
                { label: 'Productos elegidos', value: 'PRODUCTOS', icon: 'inventory_2' }
              ]"
              class="q-mb-md"
            />

            <q-select
              v-if="form.alcance === 'CATEGORIA'"
              v-model="form.category_id"
              :options="categorias"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              label="Categoría *"
              :rules="[requerido]"
            />
            <q-select
              v-else-if="form.alcance === 'PRODUCTOS'"
              v-model="form.product_ids"
              :options="opcionesProductos"
              option-label="nombre"
              option-value="id"
              emit-value
              map-options
              multiple
              use-chips
              use-input
              input-debounce="350"
              outlined
              dense
              label="Productos *"
              :loading="cargandoProductos"
              :rules="[seleccionRequerida]"
              @filter="filtrarProductos"
            >
              <template #no-option><q-item><q-item-section class="text-grey">Escribe para buscar productos</q-item-section></q-item></template>
            </q-select>

            <div v-if="form.alcance === 'TODAS_CATEGORIAS'" class="text-grey-7 q-mb-sm">
              El descuento se aplica a todo el catálogo, incluidos los productos que agregues después.
            </div>

            <q-banner dense rounded class="bg-amber-1 text-amber-10 q-mt-sm">
              <template #avatar><q-icon name="health_and_safety" color="amber-9" /></template>
              La subcategoría <strong>Medicamentos Controlados</strong> queda excluida automáticamente de todas las promociones.
            </q-banner>

            <div class="section-title q-mt-md"><q-icon name="event" /> Vigencia</div>
            <q-toggle v-model="form.permanente" color="purple" icon="all_inclusive" label="Permanente, sin fecha de finalización" />
            <div v-if="!form.permanente" class="row q-col-gutter-md q-mt-xs">
              <div class="col-12 col-sm-6">
                <q-input v-model="form.fecha_inicio" outlined dense type="datetime-local" label="Comienza *" stack-label :rules="[requerido]" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="form.fecha_fin" outlined dense type="datetime-local" label="Finaliza *" stack-label :rules="[requerido]" />
              </div>
            </div>

            <div class="section-title q-mt-md"><q-icon name="store" /> Sucursales</div>
            <q-toggle v-model="form.todas_agencias" color="primary" label="Aplicar en todas las sucursales" />
            <q-select
              v-if="!form.todas_agencias"
              v-model="form.agencia_ids"
              :options="agencias"
              option-label="nombre"
              option-value="id"
              emit-value
              map-options
              multiple
              use-chips
              outlined
              dense
              class="q-mt-sm"
              label="Sucursales incluidas *"
              :rules="[seleccionRequerida]"
            />

            <div class="section-title q-mt-md"><q-icon name="devices" /> Canales de venta</div>
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4"><q-toggle v-model="form.canal_fisico" icon="storefront" color="teal" label="Tienda física" /></div>
              <div class="col-12 col-sm-4"><q-toggle v-model="form.canal_web" icon="language" color="blue" label="Página web" /></div>
              <div class="col-12 col-sm-4"><q-toggle v-model="form.canal_app" icon="smartphone" color="deep-purple" label="Aplicación" /></div>
            </div>
            <div v-if="!hayCanal" class="text-negative text-caption q-mt-xs">Selecciona al menos un canal.</div>

            <q-card flat bordered class="offer-setting q-mt-md">
              <q-item tag="label" class="q-pa-md">
                <q-item-section avatar>
                  <q-avatar color="pink-1" text-color="pink-7" icon="local_offer" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold">Mostrar todos en Ofertas</q-item-label>
                  <q-item-label caption>
                    Incluye automáticamente los productos alcanzados en la sección Ofertas de la página web y la app.
                  </q-item-label>
                  <q-item-label v-if="!form.canal_web && !form.canal_app" caption>
                    Para mostrarlos en Ofertas, activa el canal Página web o Aplicación.
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-toggle
                    v-model="form.mostrar_en_ofertas"
                    color="pink-7"
                    aria-label="Mostrar todos en Ofertas"
                  />
                </q-item-section>
              </q-item>
            </q-card>

            <q-separator class="q-my-md" />
            <q-toggle v-model="form.activo" color="positive" icon="power_settings_new" label="Promoción habilitada" />
            <q-banner rounded class="bg-blue-1 text-blue-9 q-mt-md">
              <template #avatar><q-icon name="info" color="blue" /></template>
              Al finalizar, el sistema deja de aplicar esta regla y conserva intacto el precio normal o descuento permanente anterior.
            </q-banner>
          </q-card-section>

          <q-separator />
          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat no-caps color="grey-7" label="Cancelar" v-close-popup />
            <q-btn unelevated no-caps color="primary" icon="save" label="Guardar promoción" type="submit" :loading="guardando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'PromotionsPage',
  data () {
    return {
      promociones: [],
      categorias: [],
      agencias: [],
      opcionesProductos: [],
      filtro: '',
      cargando: false,
      cargandoProductos: false,
      guardando: false,
      dialogo: false,
      editandoId: null,
      form: this.formVacio(),
      columnas: [
        { name: 'nombre', label: 'Promoción', field: 'nombre', align: 'left', sortable: true },
        { name: 'descuento', label: 'Descuento', field: 'porcentaje', align: 'left', sortable: true },
        { name: 'alcance', label: 'Productos', field: 'alcance', align: 'left' },
        { name: 'vigencia', label: 'Vigencia', field: 'fecha_inicio', align: 'left', sortable: true },
        { name: 'aplicacion', label: 'Dónde aplica', field: 'todas_agencias', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center', sortable: true },
        { name: 'acciones', label: '', field: 'id', align: 'right' }
      ]
    }
  },
  computed: {
    hayCanal () {
      return this.form.canal_fisico || this.form.canal_web || this.form.canal_app
    },
    estadisticas () {
      return [
        { label: 'Activas ahora', value: this.promociones.filter(p => p.estado === 'ACTIVA').length, icon: 'bolt', color: 'green' },
        { label: 'Programadas', value: this.promociones.filter(p => p.estado === 'PROGRAMADA').length, icon: 'schedule', color: 'blue' },
        { label: 'Permanentes', value: this.promociones.filter(p => p.permanente && p.activo).length, icon: 'all_inclusive', color: 'purple' },
        { label: 'Pausadas', value: this.promociones.filter(p => p.estado === 'PAUSADA').length, icon: 'pause_circle', color: 'orange' }
      ]
    }
  },
  created () {
    if (!this.$store.user || String(this.$store.user.id) !== '1') {
      this.$router.replace('/')
      return
    }
    this.cargarDatos()
  },
  methods: {
    formVacio () {
      return {
        nombre: '',
        descripcion: '',
        porcentaje: 15,
        alcance: 'CATEGORIA',
        category_id: null,
        product_ids: [],
        permanente: false,
        fecha_inicio: this.fechaLocal(new Date()),
        fecha_fin: this.fechaLocal(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)),
        activo: true,
        todas_agencias: true,
        agencia_ids: [],
        canal_fisico: true,
        canal_web: true,
        canal_app: true,
        mostrar_en_ofertas: true
      }
    },
    fechaLocal (date) {
      const pad = value => String(value).padStart(2, '0')
      return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
    },
    requerido (value) {
      return (value !== null && value !== undefined && value !== '') || 'Este campo es obligatorio'
    },
    seleccionRequerida (value) {
      return (Array.isArray(value) && value.length > 0) || 'Selecciona al menos una opción'
    },
    porcentajeValido (value) {
      return (Number(value) > 0 && Number(value) <= 100) || 'Ingresa un porcentaje entre 0,01 y 100'
    },
    async cargarDatos () {
      await Promise.all([this.cargarPromociones(), this.cargarCatalogos(), this.buscarProductos('')])
    },
    async cargarPromociones () {
      this.cargando = true
      try {
        const { data } = await this.$axios.get('promotions')
        this.promociones = data
      } catch (error) {
        this.notificarError(error)
      } finally {
        this.cargando = false
      }
    },
    async cargarCatalogos () {
      try {
        const [categorias, agencias] = await Promise.all([
          this.$axios.get('categories'),
          this.$axios.get('agencias')
        ])
        this.categorias = categorias.data
        this.agencias = agencias.data
      } catch (error) {
        this.notificarError(error)
      }
    },
    async buscarProductos (search) {
      this.cargandoProductos = true
      try {
        const { data } = await this.$axios.get('productsSale', { params: { search, paginate: 40, agencia: 0 } })
        const encontrados = data.products?.data || []
        const seleccionados = this.opcionesProductos.filter(p => this.form.product_ids.includes(p.id))
        this.opcionesProductos = [...seleccionados, ...encontrados]
          .filter((p, index, all) => all.findIndex(item => item.id === p.id) === index)
      } catch (error) {
        this.notificarError(error)
      } finally {
        this.cargandoProductos = false
      }
    },
    filtrarProductos (value, update) {
      update(() => {})
      this.buscarProductos(value || '')
    },
    abrirNueva () {
      this.editandoId = null
      this.form = this.formVacio()
      this.dialogo = true
    },
    abrirEditar (promocion) {
      this.editandoId = promocion.id
      const productos = promocion.products || []
      this.opcionesProductos = [...productos, ...this.opcionesProductos]
        .filter((p, index, all) => all.findIndex(item => item.id === p.id) === index)
      this.form = {
        nombre: promocion.nombre,
        descripcion: promocion.descripcion || '',
        porcentaje: Number(promocion.porcentaje),
        alcance: promocion.alcance,
        category_id: promocion.category_id,
        product_ids: productos.map(p => p.id),
        permanente: Boolean(promocion.permanente),
        fecha_inicio: promocion.fecha_inicio ? promocion.fecha_inicio.slice(0, 16).replace(' ', 'T') : '',
        fecha_fin: promocion.fecha_fin ? promocion.fecha_fin.slice(0, 16).replace(' ', 'T') : '',
        activo: Boolean(promocion.activo),
        todas_agencias: Boolean(promocion.todas_agencias),
        agencia_ids: (promocion.agencias || []).map(a => a.id),
        canal_fisico: Boolean(promocion.canal_fisico),
        canal_web: Boolean(promocion.canal_web),
        canal_app: Boolean(promocion.canal_app),
        mostrar_en_ofertas: Boolean(promocion.mostrar_en_ofertas)
      }
      this.dialogo = true
    },
    payload () {
      return {
        ...this.form,
        category_id: this.form.alcance === 'CATEGORIA' ? this.form.category_id : null,
        product_ids: this.form.alcance === 'PRODUCTOS' ? this.form.product_ids : [],
        fecha_inicio: this.form.permanente ? null : this.form.fecha_inicio.replace('T', ' '),
        fecha_fin: this.form.permanente ? null : this.form.fecha_fin.replace('T', ' '),
        agencia_ids: this.form.todas_agencias ? [] : this.form.agencia_ids
      }
    },
    async guardar () {
      const valido = await this.$refs.formPromocion.validate()
      if (!valido || !this.hayCanal) return
      if (!this.form.permanente && new Date(this.form.fecha_fin) <= new Date(this.form.fecha_inicio)) {
        this.$q.notify({ type: 'negative', message: 'La fecha de finalización debe ser posterior al inicio.' })
        return
      }

      this.guardando = true
      try {
        if (this.editandoId) {
          await this.$axios.put(`promotions/${this.editandoId}`, this.payload())
        } else {
          await this.$axios.post('promotions', this.payload())
        }
        this.$q.notify({ type: 'positive', icon: 'check_circle', message: 'Promoción guardada correctamente.' })
        window.dispatchEvent(new Event('promotions-changed'))
        this.dialogo = false
        await this.cargarPromociones()
      } catch (error) {
        this.notificarError(error)
      } finally {
        this.guardando = false
      }
    },
    confirmarEliminar (promocion) {
      this.$q.dialog({
        title: 'Eliminar promoción',
        message: `¿Eliminar “${promocion.nombre}”? Las ventas anteriores conservarán su información.`,
        persistent: true,
        ok: { label: 'Eliminar', color: 'negative', noCaps: true },
        cancel: { label: 'Cancelar', flat: true, noCaps: true }
      }).onOk(async () => {
        try {
          await this.$axios.delete(`promotions/${promocion.id}`)
          window.dispatchEvent(new Event('promotions-changed'))
          this.$q.notify({ type: 'positive', message: 'Promoción eliminada.' })
          await this.cargarPromociones()
        } catch (error) {
          this.notificarError(error)
        }
      })
    },
    descripcionAlcance (promocion) {
      if (promocion.alcance === 'TODAS_CATEGORIAS') return 'Todas las categorías'
      if (promocion.alcance === 'CATEGORIA') return promocion.category?.name || 'Categoría'
      const cantidad = promocion.products?.length || 0
      return `${cantidad} producto${cantidad === 1 ? '' : 's'}`
    },
    descripcionAgencias (promocion) {
      if (promocion.todas_agencias) return 'Todas las sucursales'
      const cantidad = promocion.agencias?.length || 0
      return `${cantidad} sucursal${cantidad === 1 ? '' : 'es'}`
    },
    colorEstado (estado) {
      return { ACTIVA: 'positive', PROGRAMADA: 'primary', FINALIZADA: 'grey-7', PAUSADA: 'orange-8' }[estado] || 'grey'
    },
    fecha (value) {
      if (!value) return '—'
      return new Intl.DateTimeFormat('es-BO', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')))
    },
    numero (value) {
      return Number(value).toLocaleString('es-BO', { maximumFractionDigits: 2 })
    },
    notificarError (error) {
      const errors = error.response?.data?.errors
      const message = errors ? Object.values(errors).flat()[0] : error.response?.data?.message
      this.$q.notify({ type: 'negative', icon: 'error', message: message || 'No se pudo completar la operación.' })
    }
  }
}
</script>

<style scoped>
.promotions-page {
  min-height: 100%;
  background: linear-gradient(145deg, #f5f8fc 0%, #eef3f8 100%);
}

.stat-card,
.promotions-card {
  border-radius: 16px;
  border-color: #e2e8f0;
  box-shadow: 0 8px 24px rgba(30, 64, 175, 0.04);
}

.stat-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(30, 64, 175, 0.09);
}

.promotion-dialog {
  width: 820px;
  max-width: 96vw;
  border-radius: 18px;
  overflow: hidden;
}

.dialog-header {
  color: white;
  background: linear-gradient(125deg, #1565c0, #3949ab);
}

.opacity-80 {
  opacity: 0.8;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 12px;
  color: #334155;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.2px;
}

.offer-setting {
  border-color: #fbcfe8;
  border-radius: 14px;
  background: linear-gradient(135deg, #fff7fb, #ffffff);
}
</style>
