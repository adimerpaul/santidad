<template>
  <q-page class="q-pa-none">
    <!-- 🔎 BARRA SUPERIOR + BUSCADOR -->
    <div class="barra-superior">
      <q-btn
        flat
        round
        dense
        icon="menu"
        @click="toggleDrawer"
        size="md"
      />
      <div class="search-container">
        <q-input
          v-model="search"
          dense
          outlined
          rounded
          @keyup.enter="buscar"
          placeholder="Buscar Producto / Palabra Clave"
          class="search-input"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
        <q-btn
          label="Buscar"
          rounded
          :loading="loading"
          @click="buscar"
          class="search-btn"
          no-caps
        />
      </div>
    </div>

    <!-- 📌 MENÚ FLOTANTE -->
    <div v-if="drawer" class="menu-navegacion">
      <div class="menu-item" @click="navigateTo('/')">
        <q-icon name="home" class="q-mr-sm" />
        Inicio
      </div>
      <div class="menu-item" @click="navigateTo('/sucursales')">
        <q-icon name="store" class="q-mr-sm" />
        Sucursales
      </div>
    </div>

    <!-- Hero -->
    <section class="pp-hero pp-offset">
      <div class="pp-hero__inner">
        <q-icon name="local_shipping" size="48px" class="pp-hero__icon" />
        <h1 class="pp-hero__title">Política de Envío</h1>
        <p class="pp-hero__subtitle">
          En Santidad Divina garantizamos entregas rápidas, seguras y confiables.
        </p>

        <div class="pp-meta">
          <q-chip color="primary" text-color="white" outline>
            Última actualización: {{ fechaBonita }}
          </q-chip>
          <q-chip color="primary" text-color="white" outline>
            Estado Plurinacional de Bolivia
          </q-chip>
        </div>
      </div>
    </section>

    <!-- Contenido + Índice -->
    <section class="pp-container">
      <div class="pp-grid">
        <!-- Índice lateral -->
        <nav class="pp-toc">
          <div class="pp-toc__card">
            <div class="pp-toc__title">
              <q-icon name="menu_book" class="q-mr-sm" />
              Contenido
            </div>
            <q-separator spaced />
            <ul>
              <li v-for="i in items" :key="i.id">
                <a href="" @click.prevent="go(i.id)">
                  <q-icon :name="i.icon" size="18px" class="q-mr-sm" />
                  {{ i.label }}
                </a>
              </li>
            </ul>
          </div>
        </nav>

        <!-- Secciones con TU TEXTO -->
        <article class="pp-content">
          <q-card flat bordered class="pp-section" id="proceso-pedido">
            <div class="pp-section__header">
              <q-icon name="assignment_turned_in" />
              <h2>Proceso tras confirmar el pedido</h2>
            </div>
            <p>
              Una vez confirmado su pedido y aprobado el importe de la factura, procederemos al despacho y envío de su compra,
              a menos que surjan inconvenientes excepcionales para la aceptación del pedido por parte nuestra, en cuyo caso
              se le informará de inmediato.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="servicio-domicilio">
            <div class="pp-section__header">
              <q-icon name="delivery_dining" />
              <h2>Servicio de envío a domicilio</h2>
            </div>
            <p>
              Santidad Divina ofrece a sus clientes servicios de envío a domicilio a través de su Web Ecommerce.
              Este servicio de entrega tiene un costo, claramente especificado al finalizar la compra. Al confirmar el pedido
              y aprobar la factura, el cliente acepta el costo del servicio de entrega a domicilio y el total correspondiente.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="costos">
            <div class="pp-section__header">
              <q-icon name="attach_money" />
              <h2>Costos de Envío</h2>
            </div>
            <p>
              Los gastos de envío varían según la distancia y el método de despacho seleccionado, así como el lugar de origen
              del pedido. Estos costos están incluidos en el total de la compra y se detallan en la factura correspondiente.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="fechas-horarios">
            <div class="pp-section__header">
              <q-icon name="schedule" />
              <h2>Fechas y Horarios de Entrega</h2>
            </div>
            <p>
              Las fechas y horarios de entrega son efectivos una vez confirmada la compra y el stock. Si la confirmación tarda
              más de lo habitual, se reprogramará el despacho para la fecha más cercana posible.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="responsabilidad">
            <div class="pp-section__header">
              <q-icon name="verified_user" />
              <h2>Responsabilidad</h2>
            </div>
            <p>
              Santidad Divina se compromete a entregar los pedidos en buen estado según lo ofrecido, siempre que la entrega sea
              responsabilidad de la Farmacia. En caso de envío a terminal, Santidad Divina solo es responsable hasta la entrega
              de los productos a un tercero para su envío a otro destino. En caso de pérdida, robo o daño por parte de una empresa
              ajena a Santidad Divina, el cliente debe contactar directamente a la empresa transportadora.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="forma-entrega">
            <div class="pp-section__header">
              <q-icon name="home" />
              <h2>Forma de Entrega</h2>
            </div>
            <p>
              Utilizaremos nuestro servicio interno de entrega Ecommerce para realizar la entrega en el domicilio registrado por
              el cliente. El plazo de entrega se indica al aceptar el pedido, y varía según los días hábiles informados al finalizar
              la transacción.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="compra-minima">
            <div class="pp-section__header">
              <q-icon name="production_quantity_limits" />
              <h2>Compra mínima y cobertura</h2>
            </div>
            <p>
              La compra mínima en línea es de Bs. 30. Realizamos entregas en toda la ciudad de Oruro, con costos de envío variables
              según la distancia y zona de entrega.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="horarios-entrega">
            <div class="pp-section__header">
              <q-icon name="event_available" />
              <h2>Horarios de Entrega</h2>
            </div>
            <p>
              Las entregas se realizan de lunes a domingo de 08:00 A.M. a 22:30 P.M., salvo en casos de emergencia, que se atienden
              las 24 horas del día.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="excepciones">
            <div class="pp-section__header">
              <q-icon name="warning_amber" />
              <h2>Excepciones y condiciones especiales</h2>
            </div>
            <p>
              Los plazos estimados de entrega no aplican para pedidos durante promociones especiales o condiciones climáticas
              adversas que afecten la operación normal de entrega.
            </p>
          </q-card>

          <div class="pp-top">
            <q-btn round color="primary" icon="keyboard_arrow_up" @click="goTop" />
          </div>
        </article>
      </div>
    </section>
  </q-page>
</template>

<script>
export default {
  name: 'ShippingPolicy', // o 'PoliticaEnvio' si tu ruta importa así
  data () {
    return {
      // 🔎 buscador
      search: '',
      drawer: false,
      loading: false,

      items: [
        { id: 'proceso-pedido', label: 'Proceso tras confirmar el pedido', icon: 'assignment_turned_in' },
        { id: 'servicio-domicilio', label: 'Servicio de envío a domicilio', icon: 'delivery_dining' },
        { id: 'costos', label: 'Costos de Envío', icon: 'attach_money' },
        { id: 'fechas-horarios', label: 'Fechas y Horarios', icon: 'schedule' },
        { id: 'responsabilidad', label: 'Responsabilidad', icon: 'verified_user' },
        { id: 'forma-entrega', label: 'Forma de Entrega', icon: 'home' },
        { id: 'compra-minima', label: 'Compra mínima y cobertura', icon: 'production_quantity_limits' },
        { id: 'horarios-entrega', label: 'Horarios de Entrega', icon: 'event_available' },
        { id: 'excepciones', label: 'Excepciones', icon: 'warning_amber' }
      ]
    }
  },
  computed: {
    fechaBonita () {
      const d = new Date()
      const fmt = new Intl.DateTimeFormat('es-BO', {
        day: '2-digit', month: 'long', year: 'numeric'
      })
      return fmt.format(d)
    }
  },
  methods: {
    // 🔎 buscador / menú
    toggleDrawer () {
      this.drawer = !this.drawer
    },
    navigateTo (ruta) {
      this.$router.push(ruta)
      this.drawer = false
    },
    buscar () {
      if (!this.search) return
      this.loading = true
      // Integra tu búsqueda real aquí si quieres:
      // this.$router.push({ path: '/', query: { q: this.search } })
      setTimeout(() => {
        this.loading = false
        this.$q.notify({ type: 'info', message: `Buscando: ${this.search}` })
      }, 600)
    },

    // índice / navegación de secciones
    go (id) {
      const el = document.getElementById(id)
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    },
    goTop () {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }
}
</script>

<style scoped>
/* ===== Hero ===== */
.pp-hero {
  background:
    radial-gradient(1200px 400px at 50% -200px, rgba(45,156,219,.25), transparent),
    linear-gradient(180deg, #f6fbff 0%, #ffffff 40%);
  border-bottom: 1px solid rgba(45,156,219,.15);
}
.pp-hero__inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 36px 20px 28px;
  text-align: center;
}
.pp-hero__icon { color: #2D9CDB; filter: drop-shadow(0 2px 6px rgba(45,156,219,.25)); }
.pp-hero__title { margin: 8px 0 6px; font-size: 32px; font-weight: 800; color: #0F172A; }
.pp-hero__subtitle { margin: 0; color: #475569; }
.pp-meta { margin-top: 14px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
.pp-offset { margin-top: 80px; }

/* ===== Contenido ===== */
.pp-container { max-width: 1100px; margin: 0 auto; padding: 18px 16px 48px; }
.pp-grid { display: grid; grid-template-columns: 280px 1fr; gap: 18px; }

.pp-toc__card {
  position: sticky; top: 90px;
  background: #fff; border: 1px solid rgba(15,23,42,.08);
  border-radius: 12px; padding: 14px 14px 10px;
  box-shadow: 0 6px 18px rgba(0,0,0,.04);
}
.pp-toc__title { font-weight: 700; color: #0F172A; display:flex; align-items:center; }
.pp-toc ul { list-style: none; padding: 0; margin: 8px 0 0; }
.pp-toc li + li { margin-top: 6px; }
.pp-toc a {
  display: inline-flex; align-items: center;
  color: #0F172A; text-decoration: none; padding: 8px 10px; border-radius: 8px;
}
.pp-toc a:hover { background: rgba(45,156,219,.08); color: #0B5EA5; }

.pp-section { margin-bottom: 18px; border-radius: 14px; }
.pp-section__header {
  display: grid; grid-template-columns: 26px 1fr; align-items: center;
  gap: 10px; padding: 14px 16px 0; color: #0F172A;
}
.pp-section p { padding: 8px 16px 16px; color: #334155; line-height: 1.6; margin: 0; }

.pp-top { position: sticky; bottom: 18px; display: flex; justify-content: flex-end; padding: 8px 0; }

/* ===== Buscador ===== */
.barra-superior {
  position: fixed;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 90vw;
  max-width: 1100px;
  z-index: 999;
  background: #fff;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 12px;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
}
.search-container { display: flex; flex: 1; gap: 8px; align-items: center; }
.search-input { flex: 1; min-width: 120px; }
.search-btn { width: 90px; min-width: 60px; }
.search-container button {
  margin-left: 10px;
  width: 10%;
  min-width: 50px;
  background: linear-gradient(135deg, #2D9CDB, #2D9CDB);
  color: white; border: none; border-radius: 10px;
  box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
  font-weight: 600; font-size: 14px; cursor: pointer;
  transition: all 0.3s ease; padding: 8px 0;
}

/* ===== Menú flotante ===== */
.menu-navegacion {
  position: fixed; top: 65px; left: 26%; transform: translateX(-50%);
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  padding: 15px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;
}
.menu-item {
  padding: 10px; font-size: 18px; font-weight: bold; color: #333;
  display: flex; align-items: center; gap: 10px; cursor: pointer;
  transition: background-color 0.3s ease; border-radius: 6px;
}
.menu-item:hover { background: #f0f0f0; }
.menu-item .q-icon { color: #2D9CDB; font-size: 20px; }

/* Responsive */
@media (max-width: 1024px) {
  .pp-grid { grid-template-columns: 1fr; }
  .pp-toc__card { position: static; }
}
</style>
