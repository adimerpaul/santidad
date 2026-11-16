// Entorno aislado: no usa pedidos reales, almacenamiento ni transporte WhatsApp.
export function createDemoConversations (branch) {
  const now = Date.now()
  const message = (id, author, text, minutes) => ({ id, author, text, at: now - minutes * 60000 })
  return [
    {
      id: 'demo-web',
      name: 'Cliente de ejemplo · Ana',
      initials: 'AC',
      branch,
      source: 'web',
      status: 'pending',
      mode: 'bot',
      unread: 1,
      order: { number: 'EJEMPLO-WEB-01', total: '25.00', items: ['1 × Producto de ejemplo · Bs 25.00'] },
      messages: [
        message('w1', 'customer', 'Hola, hice este pedido en la página web. ¿Puedo pasar a recogerlo?', 8),
        message('w2', 'bot', 'Tu pedido ya tiene una sucursal seleccionada. El equipo de esa sucursal te atenderá por aquí.', 7),
        message('w3', 'customer', 'Quisiera confirmar el horario de recojo con una persona.', 5)
      ]
    },
    {
      id: 'demo-app',
      name: 'Cliente de ejemplo · Luis',
      initials: 'LM',
      branch,
      source: 'app',
      status: 'open',
      mode: 'manual',
      unread: 0,
      order: { number: 'EJEMPLO-APP-02', total: '18.00', items: ['2 × Producto de ejemplo · Bs 9.00'] },
      messages: [
        message('a1', 'customer', 'Les escribo por mi pedido de la aplicación.', 20),
        message('a2', 'agent', 'Hola, revisaré tu pedido para ayudarte.', 18)
      ]
    },
    {
      id: 'demo-chat',
      name: 'Cliente de ejemplo · María',
      initials: 'MR',
      branch,
      source: 'whatsapp',
      status: 'resolved',
      mode: 'manual',
      unread: 0,
      order: null,
      messages: [
        message('c1', 'customer', '¿Puedo consultar la disponibilidad de un producto?', 35),
        message('c2', 'agent', 'Sí, indícanos el nombre y la presentación para revisarlo.', 34),
        message('c3', 'customer', 'Gracias, pasaré por la sucursal.', 30)
      ]
    }
  ]
}

export const quickReplies = [
  { title: 'Saludo', text: 'Hola, gracias por escribir a Santidad Divina. Soy parte del equipo de atención. ¿En qué puedo ayudarte?' },
  { title: 'Revisar pedido', text: 'Voy a revisar los detalles de tu pedido. Te confirmaré la disponibilidad y los siguientes pasos por este chat.' },
  { title: 'Consultar producto', text: '¿Puedes indicarme el nombre del producto, su presentación y la cantidad que necesitas? Así revisamos la disponibilidad en tu sucursal.' },
  { title: 'Despedida', text: 'Gracias por comunicarte con Santidad Divina. Si necesitas más ayuda, puedes escribirnos por aquí.' }
]
