<template>
  <q-page class="q-pa-none">

    <!-- Hero -->
    <section class="pp-hero pp-offset">
      <div class="pp-hero__inner">
        <q-icon name="privacy_tip" size="48px" class="pp-hero__icon" />
        <h1 class="pp-hero__title">Política de Privacidad</h1>
        <p class="pp-hero__subtitle">
          En Santidad Divina valoramos profundamente tu derecho a la privacidad.
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
        <!-- Índice lateral (sticky en desktop, arriba en móvil) -->
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

        <!-- Secciones -->
        <article class="pp-content">
          <q-card flat bordered class="pp-section" id="intro">
            <div class="pp-section__header">
              <q-icon name="shield" />
              <h2>Introducción y alcance</h2>
            </div>
            <p>
              En Santidad Divina, reconocemos y valoramos profundamente tu derecho a la
              privacidad. Esta política establece de manera concisa cómo recopilamos y utilizamos
              la información de identificación personal, destacando los aspectos más relevantes
              relacionados con tu privacidad.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="cumplimiento">
            <div class="pp-section__header">
              <q-icon name="gavel" />
              <h2>Cumplimiento Normativo</h2>
            </div>
            <p>
              Santidad Divina se compromete a cumplir con las leyes de privacidad y protección de
              datos aplicables en el Estado Plurinacional de Bolivia. Este compromiso refleja
              nuestra dedicación para ganar y mantener la confianza de nuestros clientes, socios
              comerciales y todas las personas que comparten su información personal con nosotros.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="recopilacion">
            <div class="pp-section__header">
              <q-icon name="fingerprint" />
              <h2>Recopilación y Uso de Información Personal</h2>
            </div>
            <p>
              Para acceder y utilizar los servicios ofrecidos por Santidad Divina, nuestros
              clientes deben proporcionar ciertos datos personales. Esta información se procesa y
              almacena con altos estándares de seguridad y protección tanto física como
              tecnológica.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="info-requerida">
            <div class="pp-section__header">
              <q-icon name="assignment" />
              <h2>Información Requerida del Usuario</h2>
            </div>
            <p>
              Al completar el formulario de registro solicitamos datos como nombre, correo
              electrónico, dirección y número de teléfono. Esta información se utiliza para
              responder consultas sobre productos y servicios, procesar pedidos y pagos, así como
              para mantener una comunicación efectiva con nuestros clientes.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="consentimiento">
            <div class="pp-section__header">
              <q-icon name="check_circle" />
              <h2>Consentimiento del Usuario</h2>
            </div>
            <p>
              Al utilizar Santidad Divina Digital, los usuarios aceptan esta Política de
              Privacidad. El envío de información a través del sitio implica el consentimiento para
              la recopilación, transferencia, uso y divulgación de dicha información, según se
              describe en este documento.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="finalidad">
            <div class="pp-section__header">
              <q-icon name="task" />
              <h2>Uso y Finalidad de la Información</h2>
            </div>
            <p>
              Santidad Divina recopilará información personal únicamente para fines específicos y
              limitados. Procesaremos esta información de manera coherente con los propósitos para
              los cuales fue recopilada originalmente o para los cuales el usuario otorgó su
              consentimiento.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="seguridad">
            <div class="pp-section__header">
              <q-icon name="enhanced_encryption" />
              <h2>Seguridad de la Información Personal</h2>
            </div>
            <p>
              La información personal se almacena en bases de datos con medidas preventivas
              razonables para garantizar su seguridad, confidencialidad e integridad. Aplicamos
              protocolos de seguridad físicos y tecnológicos para proteger los datos de accesos no
              autorizados.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="cambios">
            <div class="pp-section__header">
              <q-icon name="update" />
              <h2>Cambios en la Política de Privacidad</h2>
            </div>
            <p>
              Los cambios en esta política se publicarán en nuestro sitio web. Santidad Divina se
              reserva el derecho de actualizarla o modificarla en cualquier momento y sin previo
              aviso. Las modificaciones aplicarán únicamente a la información recopilada después
              de la fecha de publicación.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="gestion">
            <div class="pp-section__header">
              <q-icon name="manage_accounts" />
              <h2>Gestión de la Información Personal</h2>
            </div>
            <p>
              Santidad Divina es responsable de la base de datos y cumple con los derechos de los
              usuarios conforme a la legislación aplicable. Los usuarios pueden acceder, actualizar
              y eliminar sus datos personales en cualquier momento, así como oponerse a su
              tratamiento.
            </p>
            <p>
              Para ejercer estos derechos o solicitar la eliminación de datos, contáctanos por
              nuestros canales de atención. Una vez solicitada la eliminación, procederemos en un
              plazo razonable y te notificaremos el resultado. Tras completarse, la información será
              suprimida de nuestras bases de datos, conservando únicamente registros anónimos con
              fines estadísticos y de prevención de fraude.
            </p>

            <div class="pp-actions">
              <q-btn color="primary" no-caps icon="support_agent" label="Atención al cliente"
                     @click="$router.push('/contacto')" />
              <q-btn outline color="primary" no-caps icon="mail"
                     label="Escribir por correo"
                     type="a" href="mailto:soporte@santidaddivina.bo" />
            </div>
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
  name: 'PoliticaPrivacidad',
  data () {
    return {

      items: [
        { id: 'intro', label: 'Introducción y alcance', icon: 'shield' },
        { id: 'cumplimiento', label: 'Cumplimiento Normativo', icon: 'gavel' },
        { id: 'recopilacion', label: 'Recopilación y uso de datos', icon: 'fingerprint' },
        { id: 'info-requerida', label: 'Información requerida del usuario', icon: 'assignment' },
        { id: 'consentimiento', label: 'Consentimiento del usuario', icon: 'check_circle' },
        { id: 'finalidad', label: 'Uso y finalidad', icon: 'task' },
        { id: 'seguridad', label: 'Seguridad de la información', icon: 'enhanced_encryption' },
        { id: 'cambios', label: 'Cambios en la política', icon: 'update' },
        { id: 'gestion', label: 'Gestión de la información personal', icon: 'manage_accounts' }
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

    // existentes
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
/* ======= TUS ESTILOS ORIGINALES ======= */
/* Contenedores */
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

.pp-container { max-width: 1100px; margin: 0 auto; padding: 18px 16px 48px; }
.pp-grid { display: grid; grid-template-columns: 280px 1fr; gap: 18px; }

/* Índice */
.pp-toc { position: relative; }
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

/* Secciones */
.pp-content { min-width: 0; }
.pp-section { margin-bottom: 18px; border-radius: 14px; }
.pp-section__header {
  display: grid; grid-template-columns: 26px 1fr; align-items: center;
  gap: 10px; padding: 14px 16px 0; color: #0F172A;
}
.pp-section p { padding: 8px 16px 16px; color: #334155; line-height: 1.6; margin: 0; }

/* Acciones */
.pp-actions { display: flex; gap: 10px; padding: 6px 16px 16px; flex-wrap: wrap; }

/* Botón “ir arriba” */
.pp-top {
  position: sticky; bottom: 18px; display: flex; justify-content: flex-end; padding: 8px 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .pp-grid { grid-template-columns: 1fr; }
  .pp-toc__card { position: static; }
}

/* ======= ESTILOS DEL BUSCADOR (añadidos) ======= */
/* Despegar el hero para que no lo tape la barra fija */
.pp-offset {
  margin-top: 80px;
}

</style>
