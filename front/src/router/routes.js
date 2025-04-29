import LoginPage from 'pages/LoginPage.vue'
import MainLayout from 'layouts/MainLayout.vue'
import IndexPage from 'pages/IndexPage.vue'
import ProductosPage from 'pages/Productos/ProductosPage.vue'
import SalePage from 'pages/SalePage.vue'
import ProductosPorVencerPage from 'pages/ProductosPorVencerPage.vue'
import UserPage from 'pages/UserPage.vue'
import ClientesPage from 'pages/ClientesPage.vue'
import ProvedoresPage from 'pages/ProvedoresPage.vue'
import ReportPage from 'pages/ReportPage.vue'
import Unidades from 'pages/Unidades.vue'
import ComprasPage from 'pages/Compras/ComprasPage.vue'
import AgenciaPage from 'pages/AgenciaPage.vue'
import ProductosVencidosPage from 'pages/ProductosVencidosPage.vue'
import ProductosRetiradosPage from 'pages/ProductosRetiradosPage.vue'
import FacturasPage from 'src/pages/FacturasPage.vue'
import TransferenciasPage from 'src/pages/Compras/TransferenciasPage.vue'

const routes = [
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', component: IndexPage, meta: { requiresAuth: true } },
      { path: 'productos', component: ProductosPage, meta: { requiresAuth: true } },
      { path: 'sale', component: SalePage, meta: { requiresAuth: true } },
      { path: 'compras', component: ComprasPage, meta: { requiresAuth: true } },
      { path: 'users', component: UserPage, meta: { requiresAuth: true } },
      { path: 'reportes', component: ReportPage, meta: { requiresAuth: true } },
      { path: 'facturas', component: FacturasPage, meta: { requiresAuth: true } },
      { path: 'transferencias', component: TransferenciasPage, meta: { requiresAuth: true } },
      { path: 'clientes', component: ClientesPage, meta: { requiresAuth: true } },
      { path: 'proveedores', component: ProvedoresPage, meta: { requiresAuth: true } },
      { path: 'unidades', component: Unidades, meta: { requiresAuth: true } },
      { path: 'productosPorVencer', component: ProductosPorVencerPage, meta: { requiresAuth: true } },
      { path: 'productosVencidos', component: ProductosVencidosPage, meta: { requiresAuth: true } },
      { path: 'agencias', component: AgenciaPage, meta: { requiresAuth: true } },
      { path: 'productosRetirados', component: ProductosRetiradosPage, meta: { requiresAuth: true } },
      { path: 'subcategorias', component: () => import('pages/SubcategoryPage.vue'), meta: { requiresAuth: true } },
      { path: 'carousel', component: () => import('pages/Carousel/CarouselPage.vue'), meta: { requiresAuth: true } }
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
