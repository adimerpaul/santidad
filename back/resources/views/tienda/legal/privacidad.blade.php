@extends('tienda.layout')

@section('title', 'Política de Privacidad · Farmacias Santidad Divina')
@section('description', 'Cómo Santidad-Divina S.R.L. recopila, usa y protege tu información personal, conforme a la legislación del Estado Plurinacional de Bolivia.')
@section('canonical', route('tienda.privacidad'))

@section('content')
@include('tienda.partials.legal', [
  'titulo' => 'Política de Privacidad',
  'subtitulo' => 'En Santidad Divina valoramos profundamente tu derecho a la privacidad.',
  'icono' => 'shield-check',
  'chips' => ['Estado Plurinacional de Bolivia'],
  'secciones' => [
    ['id' => 'intro', 'titulo' => 'Introducción y alcance', 'icono' => 'shield', 'html' => '
      <p>En Santidad-Divina S.R.L., reconocemos y valoramos profundamente tu derecho a la privacidad. Esta política de privacidad establece de manera concisa cómo recopilamos y utilizamos la información de identificación personal, destacando los aspectos más relevantes relacionados con tu privacidad.</p>'],
    ['id' => 'cumplimiento', 'titulo' => 'Cumplimiento Normativo', 'icono' => 'gavel', 'html' => '
      <p>Santidad-Divina S.R.L. se compromete a cumplir con todas las leyes de privacidad y protección de datos aplicables en el Estado Plurinacional de Bolivia. Este compromiso refleja nuestra dedicación para ganar y mantener la confianza de nuestros clientes, socios comerciales y todas las personas que comparten su información personal con nosotros.</p>'],
    ['id' => 'recopilacion', 'titulo' => 'Recopilación y Uso de Información Personal', 'icono' => 'fingerprint', 'html' => '
      <p>Para acceder y utilizar los servicios ofrecidos por Santidad-Divina S.R.L., los clientes deben proporcionar ciertos datos de carácter personal. Esta información se procesa y almacena con altos estándares de seguridad y protección tanto física como tecnológica.</p>'],
    ['id' => 'info-requerida', 'titulo' => 'Información Requerida del Usuario', 'icono' => 'clipboard-text', 'html' => '
      <p>Al completar el formulario de registro en nuestro sitio web, solicitamos a los usuarios datos como nombre, dirección de correo electrónico, dirección física y número de teléfono. Esta información se utiliza para responder consultas sobre productos y servicios, procesar pedidos y pagos, así como para mantener una comunicación efectiva con nuestros clientes.</p>'],
    ['id' => 'consentimiento', 'titulo' => 'Consentimiento del Usuario', 'icono' => 'check-circle', 'html' => '
      <p>Al utilizar Santidad-Divina S.R.L. los usuarios aceptan los términos de esta Política de Privacidad. El envío de información a través de nuestro sitio implica el consentimiento para la recopilación, transferencia, uso y divulgación de dicha información, según lo descrito en esta política y en las Declaraciones de Privacidad pertinentes.</p>'],
    ['id' => 'finalidad', 'titulo' => 'Uso y Finalidad de la Información', 'icono' => 'target', 'html' => '
      <p>Santidad-Divina S.R.L. recopilará información personal únicamente para fines específicos y limitados, detallados en este documento. Procesaremos esta información de manera coherente con los propósitos para los cuales fue recopilada originalmente o para los cuales el usuario otorgó su consentimiento.</p>'],
    ['id' => 'seguridad', 'titulo' => 'Seguridad de la Información Personal', 'icono' => 'lock-key', 'html' => '
      <p>La información personal se almacena en bases de datos con medidas preventivas razonables para garantizar su seguridad, confidencialidad e integridad. Se aplican protocolos de seguridad tanto físicos como tecnológicos para proteger los datos del acceso no autorizado.</p>'],
    ['id' => 'cambios', 'titulo' => 'Cambios en la Política de Privacidad', 'icono' => 'arrows-clockwise', 'html' => '
      <p>Los cambios en esta Política de Privacidad se publicarán en nuestro sitio web. Santidad-Divina S.R.L. se reserva el derecho de actualizar o modificar esta política en cualquier momento y sin previo aviso. Las modificaciones se aplicarán únicamente a la información recopilada después de la fecha de publicación.</p>'],
    ['id' => 'gestion', 'titulo' => 'Gestión de la Información Personal', 'icono' => 'user-gear', 'html' => '
      <p>Santidad-Divina S.R.L. es responsable de la base de datos y está comprometida a cumplir con los derechos de los usuarios en virtud de la legislación aplicable. Los usuarios tienen derecho a acceder, actualizar y eliminar sus datos personales en cualquier momento, así como a oponerse al tratamiento de los mismos.</p>
      <p>Para realizar modificaciones o solicitar la eliminación de datos personales, los usuarios pueden comunicarse a través de nuestros canales de atención al cliente. Una vez solicitada la eliminación de datos, Santidad-Divina S.R.L. procederá en un plazo razonable y notificará al usuario sobre el resultado del proceso. Una vez completada la eliminación, la información personal será eliminada de nuestras bases de datos, quedando solo registros anónimos para fines estadísticos y de prevención de fraude.</p>'],
  ],
])
@endsection
