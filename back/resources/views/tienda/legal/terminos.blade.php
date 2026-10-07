@extends('tienda.layout')

@section('title', 'Términos y Condiciones · Farmacias Santidad Divina')
@section('description', 'Términos y condiciones de uso, política de reembolso, cambios y devoluciones de Farmacias Santidad Divina, conforme a la Ley N° 453.')
@section('canonical', route('tienda.terminos'))

@section('content')
@include('tienda.partials.legal', [
  'titulo' => 'Términos y Condiciones & Política de Reembolso',
  'subtitulo' => 'Lee detenidamente tus derechos y obligaciones al usar Santidad Divina Ecommerce.',
  'icono' => 'scroll',
  'chips' => ['Estado Plurinacional de Bolivia'],
  'secciones' => [
    // Política de reembolso
    ['id' => 'reembolso-intro', 'titulo' => 'Política de Reembolso', 'icono' => 'arrow-u-up-left', 'html' => '
      <p>En Santidad Divina, nuestra principal preocupación es tu bienestar y garantizar tu satisfacción como cliente. En este sentido, Santidad Divina realiza cambios y devoluciones bajo las siguientes condiciones:</p>'],
    ['id' => 'cambios', 'titulo' => 'Cambios', 'icono' => 'arrows-left-right', 'html' => '
      <p>Un cambio se refiere a la solicitud de reemplazo de la mercadería, no motivada por fallas o defectos de fabricación, clasificación, empaque, o por discrepancia entre lo adquirido y lo recibido.</p>'],
    ['id' => 'devoluciones', 'titulo' => 'Devoluciones', 'icono' => 'arrow-counter-clockwise', 'html' => '
      <p>Una devolución implica la solicitud de reemplazo de la mercadería debido a fallas o defectos de fabricación, clasificación, empaque, transporte, o por discrepancia entre lo adquirido y lo recibido.</p>'],
    ['id' => 'situaciones', 'titulo' => 'Situaciones que aplican a cambios y/o reemplazos', 'icono' => 'list-checks', 'html' => '
      <p class="lg-sub">Medicamentos y/o Productos:</p>
      <ul>
        <li>Que presenten defectos en su empaque o contenido al momento de la compra.</li>
        <li>Con fecha de caducidad vencida al ser recibidos por el cliente.</li>
        <li>Sujetos a alerta sanitaria por parte de la autoridad competente (Agemed).</li>
        <li>Dispensados de manera diferente a lo prescrito en la receta o solicitado en la farmacia.</li>
      </ul>
      <p class="lg-sub">Otros Productos de Consumo:</p>
      <ul>
        <li>Productos Dermocosméticos, Cuidado Personal, Dispositivos Médicos que conserven sus sellos originales y no hayan sido utilizadas.</li>
      </ul>'],
    ['id' => 'excepciones', 'titulo' => 'Excepciones', 'icono' => 'prohibit', 'html' => '
      <p>NO se aceptan cambios ni devoluciones de Medicamentos y/o Productos en los siguientes casos:</p>
      <ul>
        <li>Medicamentos controlados y/o refrigerados.</li>
        <li>Medicamentos no utilizables debido a un cambio en la prescripción médica.</li>
        <li>Adquisición de una cantidad mayor a la necesaria.</li>
        <li>Productos sin defectos de fabricación.</li>
        <li>Daños ocasionados por el consumidor.</li>
        <li>Reacciones adversas a medicamentos según lo indicado en la posología.</li>
        <li>Errores de despacho, salvo que sea un error por parte de Santidad Divina.</li>
        <li>Productos parcialmente consumidos.</li>
      </ul>
      <p>Santidad Divina se reserva el derecho de rechazar solicitudes de cambio o devolución si el producto no cumple con las condiciones mencionadas.</p>'],
    ['id' => 'procedimiento', 'titulo' => 'Procedimiento para gestionar cambios o devoluciones', 'icono' => 'receipt', 'html' => '
      <p>El cliente tiene un plazo máximo de 24 horas (1 día) para realizar cambios, contados desde la entrega del producto. La mercadería debe estar en perfectas condiciones, sin uso ni daños, conservando etiquetas y empaques originales. Para solicitar una devolución, el cliente debe apersonarse a la sucursal donde hizo la compra (recomendado en el turno correspondiente), indicando el motivo y presentando la factura de venta y el producto dentro del plazo establecido.</p>'],
    ['id' => 'opciones', 'titulo' => 'Opciones de gestión', 'icono' => 'list-bullets', 'html' => '
      <p>Santidad Divina ofrece tres opciones para cambios o devoluciones, a elección del cliente:</p>
      <ul>
        <li>Reemplazo por el mismo producto, respetando la receta médica.</li>
        <li>Bonificación del valor en otro producto.</li>
      </ul>'],
    ['id' => 'ley453', 'titulo' => 'Cumplimiento de garantía legal (Ley N° 453)', 'icono' => 'gavel', 'html' => '
      <p>Santidad Divina S.A. cumple íntegramente con la garantía legal establecida en la Ley N° 453 del 4 de diciembre de 2013 "Ley General de los Derechos de las Usuarias y los Usuarios y de las Consumidoras y los Consumidores". Cualquier otro beneficio voluntario otorgado por Santidad Divina S.A. en relación con la política de devoluciones puede ser modificado o anulado por la compañía en cualquier momento.</p>'],
    // Términos del servicio
    ['id' => 'terminos-intro', 'titulo' => 'Términos del Servicio', 'icono' => 'scroll', 'html' => '
      <p>¡Bienvenido a Santidad Divina Ecommerce! Aquí están los términos y condiciones que rigen el uso de nuestros servicios en el sitio web de Santidad Divina (farmaciasantidaddivina.com). Estas condiciones establecen los derechos y responsabilidades tanto de los Usuarios como de los clientes.</p>
      <p>Al acceder y/o utilizar el Sitio o los servicios, cualquier persona acepta estos Términos y Condiciones Generales, así como todas las políticas y principios que rigen Santidad Divina Ecommerce, incorporados por referencia.</p>
      <p>Todas las visitas, contratos y transacciones realizadas en este Sitio, así como sus efectos legales, estarán sujetos a estas reglas y a la legislación aplicable en el Estado Plurinacional de Bolivia.</p>
      <p>Los términos y condiciones aquí contenidos se aplicarán a todos los actos y contratos realizados mediante los sistemas de oferta y comercialización disponibles en este Sitio entre los Usuarios y/o clientes de Santidad Divina Digital.</p>
      <p>Antes de realizar cualquier contratación, el Usuario debe leer, comprender y aceptar todas las condiciones establecidas en los Términos y Condiciones Generales, así como en las Políticas del sitio.</p>
      <p>Al utilizar Santidad Divina Ecommerce, el Usuario acepta plenamente estos términos y condiciones, así como las Políticas de Santidad Divina Digital. Se compromete a cumplir con estos términos de manera expresa.</p>
      <p>Las Condiciones de Uso, y sus modificaciones, son vigentes inmediatamente después de su publicación en el Sitio. El uso del Sitio implica la aceptación de estos términos, que son obligatorios y vinculantes. Si no se acepta algún cambio en las Condiciones de Uso, se debe cesar inmediatamente en el uso del Sitio y/o los Servicios.</p>
      <p>La información proporcionada al registrarse como Usuario y/o cliente debe ser precisa y veraz. Proporcionar información inexacta o falsa constituye una violación de estos términos.</p>'],
    ['id' => 'registro', 'titulo' => 'Registro', 'icono' => 'user-plus', 'html' => '
      <p>Es obligatorio completar todos los campos del formulario de registro con datos válidos para adquirir productos y servicios. El Usuario registrado debe proporcionar información personal exacta, precisa y verdadera ("Datos Personales"), y se compromete a actualizarla según sea necesario.</p>
      <p>Santidad Divina puede utilizar diversos medios para identificar a sus Usuarios, pero no se responsabiliza de la certeza de los Datos Personales proporcionados. Los Usuarios garantizan la veracidad, exactitud, vigencia y autenticidad de los Datos Personales ingresados.</p>
      <p>Santidad Divina se reserva el derecho de solicitar comprobantes adicionales para corroborar los Datos Personales y de suspender a aquellos Usuarios cuyos datos no puedan ser confirmados.</p>
      <p>El Usuario debe mantener la confidencialidad de su contraseña de acceso y asume total responsabilidad por su uso. La entrega de la contraseña a terceros no involucra responsabilidad de Santidad Divina S.A.</p>'],
    ['id' => 'pedido', 'titulo' => 'Pedido', 'icono' => 'shopping-cart', 'html' => '
      <p>El Usuario puede navegar por las categorías, seleccionar productos y agregarlos al carrito de compra. Puede modificar o revisar la orden antes de confirmarla. La disponibilidad de productos está sujeta al stock en el momento de la solicitud. El producto seleccionado puede no estar disponible al momento de preparar el pedido.</p>'],
    ['id' => 'terceros', 'titulo' => 'Referencias a Terceros', 'icono' => 'link', 'html' => '
      <p>Las referencias a nombres, marcas, productos o servicios de terceros en el Sitio se proporcionan como una comodidad y no implican respaldo por parte de Santidad Divina. No se hace responsable de las prácticas o políticas de terceros.</p>'],
    ['id' => 'contratos', 'titulo' => 'Contratos', 'icono' => 'handshake', 'html' => '
      <p>Santidad Divina realizará ofertas de productos, que podrán ser aceptadas electrónicamente o por teléfono. La empresa oferente debe validar la transacción antes de aceptarla.</p>'],
    ['id' => 'pagos', 'titulo' => 'Medios de Pago', 'icono' => 'money', 'html' => '
      <p>El Usuario puede pagar en efectivo contra entrega, con transferencia bancaria (QR).</p>'],
    ['id' => 'entrega', 'titulo' => 'Entrega de Pedidos', 'icono' => 'truck', 'html' => '
      <p>Santidad Divina utilizará su servicio de entrega propia o empresas terceras para entregar los pedidos.</p>'],
    ['id' => 'imposibilidad', 'titulo' => 'Imposibilidad de Entrega', 'icono' => 'warning-circle', 'html' => '
      <p>Si en caso de que no haya nadie en el domicilio indicado, Santidad-Divina S.R.L. dejará una nota de aviso. Si no es posible realizar la entrega y el pago ha sido efectuado de manera anticipada, el pedido se guardará en la sucursal del operador logístico más cercano. Sin embargo, si la hora de entrega supera las 10:00 p.m., el pedido será retornado a la sucursal de origen y se considerará anulado, a menos que se haya realizado una cancelación previa.</p>'],
    ['id' => 'precios', 'titulo' => 'Precio y Ofertas', 'icono' => 'tag', 'html' => '
      <p>Los precios establecidos en la página web y en las sucursales son los mismos. Santidad-Divina S.R.L. se reserva el derecho de modificar la información, incluidos los precios y la disponibilidad de productos, en cualquier momento y sin previo aviso.</p>'],
    ['id' => 'garantia', 'titulo' => 'Garantía', 'icono' => 'seal-check', 'html' => '
      <p>Los productos tienen garantía limitada por defectos de diseño y fabricación. No cubre daños por uso inapropiado.</p>'],
  ],
])
@endsection
