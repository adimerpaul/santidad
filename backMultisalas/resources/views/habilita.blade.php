<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
Una factura suya fue habilitada con codigo de autorizacion N: {{ $cuf }} <br>
Pueve ver el estado en <a href="{{env('URL_SIAT2')}}consulta/QR?nit=329448023&cuf={{$cuf}}&numero={{$numeroFactura}}&t=2">AQUI</a>
</body>
</html>
