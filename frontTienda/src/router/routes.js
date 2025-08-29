const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: 'detalle-producto/:id/:name', component: () => import('pages/products/DetalleProducto.vue') },
      { path: 'sucursales', component: () => import('pages/sucursales/Sucursales.vue') },
      { path: '/categoria/:id', component: () => import('pages/CategoriaPage.vue'), props: true },
      { path: 'privacidad', component: () => import('pages/politicas/PrivacyPolicy.vue') },
      { path: 'envio', component: () => import('pages/politicas/ShippingPolicy.vue') },
      { path: 'terminos', component: () => import('src/pages/politicas/TermsPage.vue') },
      { path: 'quienes-somos', component: () => import('pages/politicas/AboutUs.vue') },
      { path: 'buscar', name: 'buscar', component: () => import('pages/products/BuscarResultados.vue') }
    ]
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
