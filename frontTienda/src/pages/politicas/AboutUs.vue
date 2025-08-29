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
      <div class="menu-item" @click="navigateTo('/envio')">
        <q-icon name="local_shipping" class="q-mr-sm" />
        Envío
      </div>
      <div class="menu-item" @click="navigateTo('/terminos')">
        <q-icon name="policy" class="q-mr-sm" />
        Términos
      </div>
    </div>

    <!-- Hero -->
    <section class="pp-hero pp-offset">
      <div class="pp-hero__inner">
        <q-icon name="diversity_3" size="48px" class="pp-hero__icon" />
        <h1 class="pp-hero__title">Quiénes Somos</h1>
        <p class="pp-hero__subtitle">
          Farmacia Santidad Divina —  compromiso con la salud, calidez y servicio.
        </p>

        <div class="pp-meta">
          <q-chip color="primary" text-color="white" outline>
            Desde 2010
          </q-chip>
          <q-chip color="primary" text-color="white" outline>
            Oruro, Bolivia
          </q-chip>
          <q-chip color="primary" text-color="white" outline>
            6 sucursales
          </q-chip>
        </div>
      </div>
    </section>

    <!-- Contenido -->
    <section class="pp-container">
      <!-- HISTORIA -->
      <q-card flat bordered class="pp-section">
        <div class="pp-section__header">
          <q-icon name="history" />
          <h2>Nuestra Historia</h2>
        </div>
        <div class="pp-columns">
          <div class="pp-col">
            <p>
              Farmacia Santidad Divina inicia este proyecto un <strong>12 de julio de 2010</strong>. Nuestra
              transformación está constantemente vinculada al proceso de modernización, priorizando la expansión de
              nuestros servicios, pensando en la población orureña y muy pronto a nivel nacional.
            </p>
            <p>
              En nuestro vigésimo cuarto aniversario, mantenemos un arduo trabajo con prioridad en la mejora continua y
              la excelencia, enfocándonos en nuestra clientela para ofrecerles un servicio de calidad. Actualmente
              contamos con <strong>6 sucursales</strong> en la ciudad de Oruro.
            </p>
          </div>
          <div class="pp-col">
            <div class="gallery">
              <q-card flat bordered class="gallery-card">
                <q-img src="/images/santidad1.png"  :img-style="{ objectFit: 'cover' }" />
                <div class="gallery-caption">
                  <q-icon name="photo_camera" class="q-mr-xs" />
                  <span>Frente antiguo — primeros años</span>
                </div>
              </q-card>
              <q-card flat bordered class="gallery-card">
                <q-img src="/images/santidad2.png"  :img-style="{ objectFit: 'cover' }" />
                <div class="gallery-caption">
                  <q-icon name="photo_camera" class="q-mr-xs" />
                  <span>Frente actual — renovación y crecimiento</span>
                </div>
              </q-card>
            </div>
          </div>
        </div>
      </q-card>

      <!-- MISIÓN / VISIÓN -->
      <div class="pp-grid-2">
        <q-card flat bordered class="pp-section">
          <div class="pp-section__header">
            <q-icon name="flag" />
            <h2>Misión</h2>
          </div>
          <p>
            Brindar productos farmacéuticos y servicio de calidad, accesibles en todo momento y lugar para el
            bienestar de la salud de la población orureña. A través de nuestra red de farmacias, ofrecemos
            asesoramiento profesional, una experiencia excepcional y soluciones integrales, guiados por valores de
            honestidad, transparencia, igualdad y ética.
          </p>
        </q-card>

        <q-card flat bordered class="pp-section">
          <div class="pp-section__header">
            <q-icon name="emoji_events" />
            <h2>Visión</h2>
          </div>
          <p>
            Ser la cadena de farmacias líder con mayor participación en el mercado, reconocida por nuestra excelencia
            en el servicio al cliente, la calidad de nuestros productos y nuestra contribución al cuidado de la salud.
            Nos esforzamos por ser la primera opción para cubrir necesidades de salud y bienestar, manteniendo los más
            altos estándares éticos con responsabilidad social.
          </p>
        </q-card>
      </div>

      <!-- DATOS DESTACADOS -->
      <div class="kpis">
        <q-card flat bordered class="kpi">
          <div class="kpi-num">2010</div>
          <div class="kpi-label">Año de inicio</div>
        </q-card>
        <q-card flat bordered class="kpi">
          <div class="kpi-num">6</div>
          <div class="kpi-label">Sucursales en Oruro</div>
        </q-card>
        <q-card flat bordered class="kpi">
          <div class="kpi-num">24+</div>
          <div class="kpi-label">Años de servicio</div>
        </q-card>
      </div>

      <!-- CTA -->
      <q-card flat bordered class="pp-section">
        <div class="pp-section__header">
          <q-icon name="support_agent" />
          <h2>¿Quieres saber más?</h2>
        </div>
        <div class="pp-actions">
          <q-btn color="primary" no-caps icon="store" label="Ver Sucursales" @click="navigateTo('/sucursales')" />
          <q-btn outline color="primary" no-caps icon="chat" label="Escríbenos por WhatsApp"
                 type="a" href="https://wa.me/59172319869" target="_blank" />
        </div>
      </q-card>
    </section>
  </q-page>
</template>

<script>
export default {
  name: 'AboutUsPage',
  data () {
    return {
      // 🔎 buscador
      search: '',
      drawer: false,
      loading: false
    }
  },
  methods: {
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
      // Integra búsqueda real si te conviene:
      // this.$router.push({ path: '/', query: { q: this.search } })
      setTimeout(() => {
        this.loading = false
        this.$q.notify({ type: 'info', message: `Buscando: ${this.search}` })
      }, 600)
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

/* ===== Contenedor ===== */
.pp-container { max-width: 1100px; margin: 0 auto; padding: 18px 16px 48px; }

/* ===== Secciones ===== */
.pp-section { margin-bottom: 18px; border-radius: 14px; }
.pp-section__header {
  display: grid; grid-template-columns: 26px 1fr; align-items: center;
  gap: 10px; padding: 14px 16px 0; color: #0F172A;
}
.pp-section p { padding: 8px 16px 16px; color: #334155; line-height: 1.6; margin: 0; }
.pp-actions { display: flex; gap: 10px; padding: 6px 16px 16px; flex-wrap: wrap; }

/* ===== Columnas historia + galería ===== */
.pp-columns {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 16px;
  padding: 0 8px 14px;
}
.pp-col { min-width: 0; }
.gallery { display: grid; grid-template-columns: 1fr; gap: 12px; }
.gallery-card { border-radius: 12px; overflow: hidden; }
.gallery-caption {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 12px; font-size: 13px; color: #334155; background: #fafafa;
  border-top: 1px solid rgba(15,23,42,.06);
}

/* ===== KPIs ===== */
.kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin: 8px 0 18px;
}
.kpi { text-align: center; padding: 14px 10px; border-radius: 12px; }
.kpi-num { font-size: 24px; font-weight: 800; color: #0F172A; line-height: 1; }
.kpi-label { font-size: 12px; color: #64748B; margin-top: 4px; }

/* ===== Grid misión/visión ===== */
.pp-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 12px 0 18px;
}

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

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .pp-columns { grid-template-columns: 1fr; }
  .pp-grid-2 { grid-template-columns: 1fr; }
  .kpis { grid-template-columns: 1fr 1fr 1fr; }
}
@media (max-width: 640px) {
  .kpis { grid-template-columns: 1fr 1fr; }
}
</style>
