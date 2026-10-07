@extends('tienda.layout')

@section('title', 'Política de Envío · Farmacias Santidad Divina')
@section('description', 'Costos, horarios, compra mínima y condiciones de entrega a domicilio de Farmacias Santidad Divina en la ciudad de Oruro.')
@section('canonical', route('tienda.envio'))

@section('content')
@include('tienda.partials.legal', [
  'titulo' => 'Política de Envío',
  'subtitulo' => 'En Santidad Divina garantizamos entregas rápidas, seguras y confiables.',
  'icono' => 'truck',
  'chips' => ['Estado Plurinacional de Bolivia'],
  'secciones' => [
    ['id' => 'proceso-pedido', 'titulo' => 'Proceso tras confirmar el pedido', 'icono' => 'clipboard-text', 'html' => '
      <p>Una vez confirmado su pedido y aprobado el importe de la factura, procederemos al despacho y envío de su compra, a menos que surjan inconvenientes excepcionales para la aceptación del pedido por parte nuestra, en cuyo caso se le informará de inmediato.</p>'],
    ['id' => 'servicio-domicilio', 'titulo' => 'Servicio de envío a domicilio', 'icono' => 'moped', 'html' => '
      <p>Santidad-Divina S.R.L. ofrece a sus clientes servicios de envío a domicilio a través de su Página Web. Este servicio de entrega tiene un costo, claramente especificado al finalizar la compra. Al confirmar el pedido y aprobar la factura, el cliente acepta el costo del servicio de entrega a domicilio y el total correspondiente.</p>'],
    ['id' => 'costos', 'titulo' => 'Costos de Envío', 'icono' => 'currency-circle-dollar', 'html' => '
      <p>Los gastos de envío varían según la distancia y el método de despacho seleccionado, así como el lugar de origen del pedido. Estos costos están incluidos en el total de la compra y se detallan en la factura correspondiente.</p>'],
    ['id' => 'fechas-horarios', 'titulo' => 'Fechas y Horarios de Entrega', 'icono' => 'clock', 'html' => '
      <p>Las fechas y horarios de entrega son efectivos una vez confirmada la compra y el stock. Si la confirmación tarda más de lo habitual, se reprogramará el despacho para la fecha más cercana posible.</p>'],
    ['id' => 'responsabilidad', 'titulo' => 'Responsabilidad', 'icono' => 'shield-check', 'html' => '
      <p>Santidad-Divina S.R.L. se compromete a entregar los pedidos en buen estado según lo ofrecido, siempre que la entrega sea responsabilidad de la Farmacia. En caso de envío a terminal, Santidad-Divina S.R.L. solo es responsable hasta la entrega de los productos a un tercero para su envío a otro destino. En caso de pérdida, robo o daño por parte de una empresa ajena a Santidad-Divina S.R.L., el cliente debe contactar directamente a la empresa transportadora.</p>'],
    ['id' => 'forma-entrega', 'titulo' => 'Forma de Entrega', 'icono' => 'house', 'html' => '
      <p>Utilizaremos nuestro servicio interno de entrega para llevar el pedido al domicilio registrado por el cliente. El plazo de entrega se especificará al confirmar el pedido y variará según los días hábiles indicados al finalizar la transacción.</p>'],
    ['id' => 'compra-minima', 'titulo' => 'Compra mínima y Entrega', 'icono' => 'shopping-bag', 'html' => '
      <p>La compra mínima en línea es de Bs. 50. Realizamos entregas en toda la ciudad de Oruro, con costos de envío variables según la distancia y zona de entrega.</p>'],
    ['id' => 'horarios-entrega', 'titulo' => 'Horarios de Entrega', 'icono' => 'calendar-check', 'html' => '
      <p>Las entregas se realizan de lunes a domingo, entre las 08:00 A.M. y las 10:00 P.M. En los turnos de 24 horas de la farmacia, se brindará atención mediante delivery o vehículos móviles particulares. Los plazos estimados de entrega no aplican para pedidos durante condiciones climáticas adversas que afecten la operación normal de entrega.</p>'],
    ['id' => 'excepciones', 'titulo' => 'Excepciones y condiciones especiales', 'icono' => 'warning', 'html' => '
      <p>Los plazos estimados de entrega no aplican para pedidos durante promociones especiales o condiciones climáticas adversas que afecten la operación normal de entrega.</p>'],
  ],
])
@endsection
