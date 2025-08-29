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
        <q-icon name="policy" size="48px" class="pp-hero__icon" />
        <h1 class="pp-hero__title">Términos y Condiciones &amp; Política de Reembolso</h1>
        <p class="pp-hero__subtitle">
          Lee detenidamente tus derechos y obligaciones al usar Santidad Divina Ecommerce.
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

        <!-- Secciones -->
        <article class="pp-content">
          <!-- POLÍTICA DE REEMBOLSO -->
          <q-card flat bordered class="pp-section" id="reembolso-intro">
            <div class="pp-section__header">
              <q-icon name="assignment_return" />
              <h2>Política de Reembolso</h2>
            </div>
            <p>
              En Santidad Divina, nuestra principal preocupación es tu bienestar y garantizar tu satisfacción como cliente.
              En este sentido, Santidad Divina realiza cambios y devoluciones bajo las siguientes condiciones:
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="cambios">
            <div class="pp-section__header">
              <q-icon name="sync_alt" />
              <h2>Cambios</h2>
            </div>
            <p>
              Un cambio se refiere a la solicitud de reemplazo de la mercadería, no motivada por fallas o defectos de
              fabricación, clasificación, empaque, o por discrepancia entre lo adquirido y lo recibido.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="devoluciones">
            <div class="pp-section__header">
              <q-icon name="undo" />
              <h2>Devoluciones</h2>
            </div>
            <p>
              Una devolución implica la solicitud de reemplazo de la mercadería debido a fallas o defectos de fabricación,
              clasificación, empaque, transporte, o por discrepancia entre lo adquirido y lo recibido.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="situaciones">
            <div class="pp-section__header">
              <q-icon name="checklist" />
              <h2>Situaciones que aplican a cambios y/o reemplazos</h2>
            </div>
            <p class="q-mb-sm text-weight-medium">Medicamentos y/o Productos:</p>
            <p class="q-ml-sm">
              • Que presenten defectos en su empaque o contenido al momento de la compra.<br>
              • Con fecha de caducidad vencida al ser recibidos por el cliente.<br>
              • Sujetos a alerta sanitaria por parte de la autoridad competente (Agemed).<br>
              • Dispensados de manera diferente a lo prescrito en la receta o solicitado en la farmacia.
            </p>
            <p class="q-mt-md q-mb-sm text-weight-medium">Otros Productos de Consumo:</p>
            <p class="q-ml-sm">
              • Productos Dermocosméticos, Cuidado Personal, Dispositivos Médicos que conserven sus sellos originales
              y no hayan sido utilizadas.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="excepciones">
            <div class="pp-section__header">
              <q-icon name="block" />
              <h2>Excepciones</h2>
            </div>
            <p>
              NO se aceptan cambios ni devoluciones de Medicamentos y/o Productos en los siguientes casos:
            </p>
            <p class="q-ml-sm">
              • Medicamentos controlados y/o refrigerados.<br>
              • Medicamentos no utilizables debido a un cambio en la prescripción médica.<br>
              • Adquisición de una cantidad mayor a la necesaria.<br>
              • Productos sin defectos de fabricación.<br>
              • Daños ocasionados por el consumidor.<br>
              • Reacciones adversas a medicamentos según lo indicado en la posología.<br>
              • Errores de despacho, salvo que sea un error por parte de Santidad Divina.<br>
              • Productos parcialmente consumidos.
            </p>
            <p>
              Santidad Divina se reserva el derecho de rechazar solicitudes de cambio o devolución si el producto no
              cumple con las condiciones mencionadas.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="procedimiento">
            <div class="pp-section__header">
              <q-icon name="receipt_long" />
              <h2>Procedimiento para gestionar cambios o devoluciones</h2>
            </div>
            <p>
              El cliente tiene un plazo máximo de 24 horas (1 día) para realizar cambios, contados desde la entrega del producto.
              La mercadería debe estar en perfectas condiciones, sin uso ni daños, conservando etiquetas y empaques originales.
              Para solicitar una devolución, el cliente debe apersonarse a la sucursal donde hizo la compra (recomendado en el turno
              correspondiente), indicando el motivo y presentando la factura de venta y el producto dentro del plazo establecido.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="opciones">
            <div class="pp-section__header">
              <q-icon name="list_alt" />
              <h2>Opciones de gestión</h2>
            </div>
            <p>
              Santidad Divina ofrece tres opciones para cambios o devoluciones, a elección del cliente:
            </p>
            <p class="q-ml-sm">
              • Reemplazo por el mismo producto, respetando la receta médica.<br>
              • Bonificación del valor en otro producto.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="ley453">
            <div class="pp-section__header">
              <q-icon name="gavel" />
              <h2>Cumplimiento de garantía legal (Ley N° 453)</h2>
            </div>
            <p>
              Santidad Divina S.A. cumple íntegramente con la garantía legal establecida en la Ley N° 453 del 4 de diciembre
              de 2013 "Ley General de los Derechos de las Usuarias y los Usuarios y de las Consumidoras y los Consumidores".
              Cualquier otro beneficio voluntario otorgado por Santidad Divina S.A. en relación con la política de devoluciones
              puede ser modificado o anulado por la compañía en cualquier momento.
            </p>
          </q-card>

          <!-- TÉRMINOS DEL SERVICIO -->
          <q-card flat bordered class="pp-section" id="terminos-intro">
            <div class="pp-section__header">
              <q-icon name="policy" />
              <h2>Términos del Servicio</h2>
            </div>
            <p>
              ¡Bienvenido a Santidad Divina Ecommerce! Aquí están los términos y condiciones que rigen el uso de nuestros
              servicios en el sitio web de Santidad Divina (farmaciasantidaddivina.com). Estas condiciones establecen los
              derechos y responsabilidades tanto de los Usuarios como de los clientes.
            </p>
            <p>
              Al acceder y/o utilizar el Sitio o los servicios, cualquier persona acepta estos Términos y Condiciones Generales,
              así como todas las políticas y principios que rigen Santidad Divina Ecommerce, incorporados por referencia.
            </p>
            <p>
              Todas las visitas, contratos y transacciones realizadas en este Sitio, así como sus efectos legales, estarán sujetos
              a estas reglas y a la legislación aplicable en el Estado Plurinacional de Bolivia.
            </p>
            <p>
              Los términos y condiciones aquí contenidos se aplicarán a todos los actos y contratos realizados mediante los sistemas
              de oferta y comercialización disponibles en este Sitio entre los Usuarios y/o clientes de Santidad Divina Digital.
            </p>
            <p>
              Antes de realizar cualquier contratación, el Usuario debe leer, comprender y aceptar todas las condiciones establecidas
              en los Términos y Condiciones Generales, así como en las Políticas del sitio.
            </p>
            <p>
              Al utilizar Santidad Divina Ecommerce, el Usuario acepta plenamente estos términos y condiciones, así como las Políticas
              de Santidad Divina Digital. Se compromete a cumplir con estos términos de manera expresa.
            </p>
            <p>
              Las Condiciones de Uso, y sus modificaciones, son vigentes inmediatamente después de su publicación en el Sitio.
              El uso del Sitio implica la aceptación de estos términos, que son obligatorios y vinculantes. Si no se acepta algún
              cambio en las Condiciones de Uso, se debe cesar inmediatamente en el uso del Sitio y/o los Servicios.
            </p>
            <p>
              La información proporcionada al registrarse como Usuario y/o cliente debe ser precisa y veraz. Proporcionar información
              inexacta o falsa constituye una violación de estos términos.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="registro">
            <div class="pp-section__header">
              <q-icon name="person_add" />
              <h2>Registro</h2>
            </div>
            <p>
              Es obligatorio completar todos los campos del formulario de registro con datos válidos para adquirir productos y servicios.
              El Usuario registrado debe proporcionar información personal exacta, precisa y verdadera ("Datos Personales"), y se compromete
              a actualizarla según sea necesario.
            </p>
            <p>
              Santidad Divina puede utilizar diversos medios para identificar a sus Usuarios, pero no se responsabiliza de la certeza de los
              Datos Personales proporcionados. Los Usuarios garantizan la veracidad, exactitud, vigencia y autenticidad de los Datos Personales
              ingresados.
            </p>
            <p>
              Santidad Divina se reserva el derecho de solicitar comprobantes adicionales para corroborar los Datos Personales y de suspender a
              aquellos Usuarios cuyos datos no puedan ser confirmados.
            </p>
            <p>
              El Usuario debe mantener la confidencialidad de su contraseña de acceso y asume total responsabilidad por su uso. La entrega de la
              contraseña a terceros no involucra responsabilidad de Santidad Divina S.A.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="pedido">
            <div class="pp-section__header">
              <q-icon name="shopping_cart" />
              <h2>Pedido</h2>
            </div>
            <p>
              El Usuario puede navegar por las categorías, seleccionar productos y agregarlos al carrito de compra. Puede modificar o revisar la
              orden antes de confirmarla. La disponibilidad de productos está sujeta al stock en el momento de la solicitud. El producto seleccionado
              puede no estar disponible al momento de preparar el pedido.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="terceros">
            <div class="pp-section__header">
              <q-icon name="link" />
              <h2>Referencias a Terceros</h2>
            </div>
            <p>
              Las referencias a nombres, marcas, productos o servicios de terceros en el Sitio se proporcionan como una comodidad y no implican
              respaldo por parte de Santidad Divina. No se hace responsable de las prácticas o políticas de terceros.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="contratos">
            <div class="pp-section__header">
              <q-icon name="handshake" />
              <h2>Contratos</h2>
            </div>
            <p>
              Santidad Divina realizará ofertas de productos, que podrán ser aceptadas electrónicamente o por teléfono. La empresa oferente debe
              validar la transacción antes de aceptarla.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="pagos">
            <div class="pp-section__header">
              <q-icon name="payments" />
              <h2>Medios de Pago</h2>
            </div>
            <p>
              El Usuario puede pagar en efectivo contra entrega, con transferencia bancaria (QR).
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="entrega">
            <div class="pp-section__header">
              <q-icon name="local_shipping" />
              <h2>Entrega de Pedidos</h2>
            </div>
            <p>
              Santidad Divina utilizará su servicio de entrega propia o empresas terceras para entregar los pedidos.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="imposibilidad">
            <div class="pp-section__header">
              <q-icon name="report_gmailerrorred" />
              <h2>Imposibilidad de Entrega</h2>
            </div>
            <p>
              Si no hay nadie en el domicilio indicado, Santidad Divina dejará una nota de aviso. Se realizarán dos intentos de entrega.
              Si no es posible entregar el pedido, se guardará en la sucursal del operador logístico más cercana.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="precios">
            <div class="pp-section__header">
              <q-icon name="price_change" />
              <h2>Precio y Ofertas</h2>
            </div>
            <p>
              Los precios publicados en el Sitio son válidos solo para este canal de venta. Santidad Divina se reserva el derecho de modificar la
              información, incluidos precios y existencias, en cualquier momento y sin previo aviso.
            </p>
          </q-card>

          <q-card flat bordered class="pp-section" id="garantia">
            <div class="pp-section__header">
              <q-icon name="verified_user" />
              <h2>Garantía</h2>
            </div>
            <p>
              Los productos tienen garantía limitada por defectos de diseño y fabricación. No cubre daños por uso inapropiado.
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
  name: 'TermsPage', // o 'TermsAndRefund' si prefieres
  data () {
    return {
      // 🔎 buscador
      search: '',
      drawer: false,
      loading: false,

      items: [
        // Reembolso
        { id: 'reembolso-intro', label: 'Política de Reembolso', icon: 'assignment_return' },
        { id: 'cambios', label: 'Cambios', icon: 'sync_alt' },
        { id: 'devoluciones', label: 'Devoluciones', icon: 'undo' },
        { id: 'situaciones', label: 'Situaciones que aplican', icon: 'checklist' },
        { id: 'excepciones', label: 'Excepciones', icon: 'block' },
        { id: 'procedimiento', label: 'Procedimiento', icon: 'receipt_long' },
        { id: 'opciones', label: 'Opciones de gestión', icon: 'list_alt' },
        { id: 'ley453', label: 'Ley N° 453', icon: 'gavel' },
        // Términos
        { id: 'terminos-intro', label: 'Términos del Servicio', icon: 'policy' },
        { id: 'registro', label: 'Registro', icon: 'person_add' },
        { id: 'pedido', label: 'Pedido', icon: 'shopping_cart' },
        { id: 'terceros', label: 'Referencias a Terceros', icon: 'link' },
        { id: 'contratos', label: 'Contratos', icon: 'handshake' },
        { id: 'pagos', label: 'Medios de Pago', icon: 'payments' },
        { id: 'entrega', label: 'Entrega', icon: 'local_shipping' },
        { id: 'imposibilidad', label: 'Imposibilidad de Entrega', icon: 'report_gmailerrorred' },
        { id: 'precios', label: 'Precio y Ofertas', icon: 'price_change' },
        { id: 'garantia', label: 'Garantía', icon: 'verified_user' }
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
      // Integra búsqueda real si gustas:
      // this.$router.push({ path: '/', query: { q: this.search } })
      setTimeout(() => {
        this.loading = false
        this.$q.notify({ type: 'info', message: `Buscando: ${this.search}` })
      }, 600)
    },

    // Índice / navegación interna
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
