# Preparar el PDF de productos retirados

En Informes de bajas > Productos retirados, **Preparar PDF** abre un borrador a partir de todos los resultados del filtro actual, no únicamente de la página visible.

El borrador permite excluir y volver a incluir productos, modificar cantidades enteras (incluidos los ajustes negativos de inventario), lote, número de factura, motivo, precio facturado de referencia, descripción, observaciones por producto, título y observaciones generales. Los productos conservan su identificación, sucursal, origen e informe de procedencia. Cada fila ajustada se marca y puede restaurarse individualmente. La factura editada solo se usa en el PDF preparado; no modifica la compra ni su factura registrada. Se conservan los ceros iniciales y, si se deja vacía, el PDF muestra «Sin factura».

La factura se muestra en la tabla de Productos retirados, el editor, el CSV y ambos PDF. Se usa el número de la factura asociada a la compra; para compras antiguas se toma el campo de factura del lote. Sin número registrado se indica «Sin factura». Los ceros iniciales se conservan. El endpoint carga las facturas asociadas en conjunto para evitar una consulta adicional por producto.

El precio de referencia usa la misma base de la tabla existente (precio normal / 1,30) y se redondea a dos decimales. En el borrador, los totales se calculan con ese precio visible y la cantidad, usando centavos. Por ello, respecto al PDF anterior, puede existir una diferencia de centavos cuando antes el total utilizaba el precio sin redondear. El total es firmado, como las cantidades del informe.

La búsqueda dentro del editor sirve para revisar filas; no excluye productos del PDF. Solo se omiten las filas retiradas explícitamente del borrador. El PDF contiene todos los incluidos, las descripciones y observaciones, el contexto del filtro, totales y numeración de páginas. Los textos largos continúan en las páginas siguientes.

## Alcance de los cambios

Los ajustes son temporales y solo afectan el documento descargado. No se modifica stock, compras, bajas, lotes reales ni informes originales. Cerrar un borrador modificado pide confirmar que se descarten sus ajustes. Al reabrir se consultan de nuevo los datos originales.

Se utiliza el GET autenticado existente `withdrawal-reports/all-items` con `rowsPerPage=0`, que ya devuelve todos los registros autorizados del filtro. El filtro se copia al abrir. Una carga parcial o fallida no habilita la generación. Cerrar el editor, cambiar de usuario/sucursal asignada o desmontarlo cancela la consulta y descarta respuestas tardías. No se hacen consultas al escribir o editar campos.

La exportación rápida de PDF y la de CSV existentes conservan su alcance de página actual. El nuevo flujo no requiere migraciones ni variables de entorno; se publica el backend junto con el frontend para incluir los números de facturas asociadas.

## Verificación

Desde `front`: `node --test tests/withdrawal-pdf.test.cjs`. Comprueba aislamiento de los datos originales, totales en centavos, validaciones, filtros congelados, carga completa, cancelación, exclusión/restauración y búsqueda sin exclusión implícita.

También se verificó la interacción real del componente y la página con Vue/Quasar en navegador y respuestas simuladas, y se generó y renderizó un PDF de varias páginas con observaciones extensas.
