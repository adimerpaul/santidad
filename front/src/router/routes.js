import LoginPage from 'pages/LoginPage.vue'
import MainLayout from 'layouts/MainLayout.vue'
import IndexPage from 'pages/IndexPage.vue'
import ProductosPage from 'pages/ProductosPage.vue'
import SalePage from 'pages/SalePage.vue'

const routes = [
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', component: IndexPage, meta: { requiresAuth: true } },
      { path: 'productos', component: ProductosPage, meta: { requiresAuth: true } },
      { path: 'sale', component: SalePage, meta: { requiresAuth: true } }
    ]
  },
  {
    path: '/login',
    component: LoginPage
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
