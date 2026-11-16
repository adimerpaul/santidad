<template>
  <q-page class="support-page">
    <header class="support-heading">
      <div class="row items-center no-wrap q-gutter-sm">
        <q-avatar color="teal-1" text-color="teal-8" icon="forum" size="46px" />
        <div><h1>Atención al cliente</h1><p>Un WhatsApp para todas las sucursales. Cada consulta, con su equipo.</p></div>
      </div>
      <div v-if="!demo" class="row q-gutter-sm">
        <q-btn outline no-caps color="teal-8" icon="visibility" label="Probar demostración" @click="startDemo" />
        <q-btn unelevated no-caps color="teal-8" icon="add" label="Nuevo caso" :disable="!store.user?.id" @click="openCreate" />
      </div>
      <q-btn v-else outline no-caps color="teal-8" icon="close" label="Salir de demostración" @click="leaveDemo" />
    </header>

    <div class="connection-notice" role="status">
      <q-icon :name="demo ? 'science' : 'link_off'" size="22px" />
      <div><strong>{{ demo ? 'Demostración · datos ficticios' : 'WhatsApp pendiente de conexión' }}</strong>
        <span>{{ demo ? 'Las respuestas y cambios solo se simulan en esta pantalla. Se borran al salir.' : 'Los casos se guardan en Santidad. El envío de mensajes y el bot estarán disponibles al conectar WhatsApp.' }}</span>
      </div>
      <q-badge outline color="teal-8" class="scope-badge">{{ isAdmin ? 'Vista de administrador' : branchName }}</q-badge>
    </div>
    <q-banner v-if="error && !demo" class="bg-red-1 text-red-9 q-mb-md" rounded role="alert">{{ error }}</q-banner>

    <div class="support-workspace" :class="{ 'has-selection': !!selected }">
      <aside class="inbox-panel" aria-label="Bandeja de conversaciones">
        <div class="inbox-top">
          <div class="row items-center justify-between q-mb-md"><h2>Conversaciones</h2><q-badge color="grey-2" text-color="grey-8">{{ conversations.length }}</q-badge></div>
          <q-input v-model="search" :debounce="demo ? 0 : 300" maxlength="160" outlined dense clearable placeholder="Buscar cliente o pedido" aria-label="Buscar cliente o pedido"><template #prepend><q-icon name="search" /></template></q-input>
          <div class="inbox-scope"><q-icon name="storefront" /> {{ demo ? `Ejemplos · ${branchName}` : (isAdmin ? 'Todas las sucursales' : branchName) }}</div>
          <q-select v-if="!demo && isAdmin" v-model="branchFilter" :options="branchFilters" emit-value map-options outlined dense label="Filtrar por sucursal" class="q-mb-sm" />
          <q-btn v-if="!demo" flat dense no-caps icon="refresh" label="Actualizar" :loading="loading" @click="refresh" />
          <q-tabs v-model="filter" dense no-caps align="left" active-color="teal-8" indicator-color="teal" class="inbox-tabs">
            <q-tab name="all" label="Todos" /><q-tab name="pending" label="Pendientes" /><q-tab name="open" label="En atención" /><q-tab name="resolved" label="Resueltos" />
          </q-tabs>
        </div>
        <div class="conversation-list">
          <button v-for="chat in filtered" :key="chat.id" type="button" class="conversation-item" :class="{ active: selectedId === chat.id }" :aria-pressed="selectedId === chat.id" @click="selectChat(chat)">
            <q-avatar size="40px" color="teal-1" text-color="teal-9">{{ chat.initials }}</q-avatar>
            <div class="conversation-copy">
              <div class="row no-wrap justify-between items-center"><strong>{{ chat.name }}</strong><small>{{ time(lastMessage(chat)?.at || chat.at) }}</small></div>
              <p>{{ lastMessage(chat)?.text || chat.order?.number || 'Sin mensajes' }}</p>
              <div class="row items-center q-gutter-xs"><span class="source-label">{{ sources[chat.source].label }}</span><span class="state-label">{{ states[chat.status] }}</span><q-badge v-if="chat.unread" rounded color="teal">{{ chat.unread }}</q-badge></div>
            </div>
          </button>
          <q-linear-progress v-if="!demo && loading" indeterminate color="teal" />
          <div v-if="!filtered.length && !loading" class="inbox-empty"><q-icon name="inbox" size="34px" /><strong>{{ search || filter !== 'all' || branchFilter !== null ? 'Sin coincidencias' : 'Todavía no hay casos' }}</strong><span>{{ search || filter !== 'all' || branchFilter !== null ? 'Prueba otra búsqueda o filtro.' : 'Abre un caso desde un pedido para iniciar la atención.' }}</span></div>
        </div>
        <div class="inbox-footer"><q-icon name="lock_outline" /> Atención organizada por sucursal</div>
        <q-pagination v-if="!demo && lastPage > 1" v-model="page" :max="lastPage" :max-pages="5" direction-links size="sm" color="teal-8" class="q-pa-sm self-center" />
      </aside>

      <section v-if="selected" class="chat-panel" aria-label="Conversación seleccionada">
        <header class="chat-header">
          <q-btn flat round dense icon="arrow_back" class="mobile-back" aria-label="Volver a conversaciones" @click="clearSelection" />
          <q-avatar color="teal-1" text-color="teal-9" size="40px">{{ selected.initials }}</q-avatar>
          <div class="chat-person"><strong>{{ selected.name }}</strong><span>{{ selected.branch }} · {{ sources[selected.source].label }}</span></div>
          <q-btn flat round icon="info_outline" aria-label="Ver detalles del pedido" @click="detailsOpen = true"><q-tooltip>Detalles de atención</q-tooltip></q-btn>
          <q-btn v-if="selected.status !== 'resolved'" outline no-caps dense color="teal-8" icon="task_alt" label="Resolver" :disable="busy" @click="resolveChat" />
          <q-btn v-else outline no-caps dense color="teal-8" icon="refresh" label="Reabrir" :disable="busy" @click="reopenChat" />
        </header>

        <div class="mode-bar">
          <q-icon :name="selected.mode === 'bot' ? 'smart_toy' : 'support_agent'" size="21px" />
          <div><strong>{{ modeTitle }}</strong><span>{{ modeHint }}</span></div>
          <q-btn v-if="selected.status !== 'resolved' && (demo || selected.branch_id)" unelevated no-caps dense :disable="busy" :color="selected.mode === 'bot' ? 'teal-8' : 'grey-3'" :text-color="selected.mode === 'bot' ? 'white' : 'grey-9'" :label="selected.mode === 'bot' ? 'Atender yo' : (demo ? 'Activar bot' : 'Elegir modo bot')" @click="toggleMode" />
        </div>

        <button v-if="selected.order" type="button" class="order-strip" @click="detailsOpen = true"><q-icon :name="sources[selected.source].icon" size="18px" /><strong>{{ selected.order.number }}</strong><span>{{ selected.branch }}</span><q-icon name="chevron_right" /></button>

        <div ref="messageList" class="message-list" role="log" aria-live="polite" aria-label="Historial de mensajes">
          <div v-if="demo" class="demo-divider">CONVERSACIÓN DE EJEMPLO</div>
          <div v-if="selected.before" class="text-center"><q-btn flat no-caps label="Ver mensajes anteriores" :disable="busy" @click="loadOlder" /></div>
          <div v-if="!selected.messages.length" class="inbox-empty">Todavía no hay mensajes en este caso.</div>
          <template v-for="message in selected.messages" :key="message.id">
            <div v-if="message.author === 'system'" class="system-message">{{ message.text }} · {{ time(message.at) }}</div>
            <div v-else class="message-row" :class="{ outgoing: message.author !== 'customer' }">
              <div class="message-bubble" :class="{ 'bot-bubble': message.author === 'bot', 'note-bubble': message.author === 'note' }">
                <strong>{{ authors[message.author] }}</strong><p>{{ message.text }}</p><small>{{ time(message.at) }}{{ message.author === 'agent' ? (demo ? ' · Simulado, no enviado' : ' · Guardado en Santidad') : '' }}</small>
              </div>
            </div>
          </template>
        </div>

        <div class="composer">
          <div class="row items-center justify-between q-mb-sm">
            <q-btn-toggle v-model="composeKind" no-caps unelevated dense toggle-color="teal-1" toggle-text-color="teal-9" text-color="grey-7" :options="[{label: 'Respuesta', value: 'reply'}, {label: 'Nota interna', value: 'note'}]" />
            <q-btn flat dense no-caps color="teal-8" icon="bolt" label="Respuestas rápidas" :disable="!canCompose || composeKind === 'note'">
              <q-menu><q-list style="max-width: 340px"><q-item v-for="reply in quickReplies" :key="reply.title" v-close-popup clickable @click="insertReply(reply.text)"><q-item-section><q-item-label>{{ reply.title }}</q-item-label><q-item-label caption lines="2">{{ reply.text }}</q-item-label></q-item-section></q-item></q-list></q-menu>
            </q-btn>
          </div>
          <q-input v-model="draft" outlined autogrow :maxlength="2000" :disable="!canCompose" :placeholder="placeholder" :aria-label="composeKind === 'note' ? 'Escribir nota interna' : 'Escribir respuesta'" @keydown.ctrl.enter.prevent="submit" @keydown.meta.enter.prevent="submit" />
          <div class="composer-bottom"><span>{{ composeKind === 'note' ? 'Visible para el equipo asignado y el administrador.' : (demo ? 'Ctrl + Enter para simular.' : 'Se guarda en Santidad. WhatsApp está desconectado.') }}</span><q-btn unelevated no-caps color="teal-8" :icon="composeKind === 'note' ? 'note_add' : (demo ? 'send' : 'save')" :label="composeKind === 'note' ? 'Añadir nota' : (demo ? 'Simular respuesta' : 'Guardar respuesta')" :disable="!canCompose || !draft.trim()" @click="submit" /></div>
        </div>
      </section>

      <section v-else class="welcome-panel">
        <div class="welcome-icon"><q-icon name="forum" size="44px" /></div>
        <h2>{{ demo ? 'Elige una conversación' : 'Bandeja de atención' }}</h2>
        <p>{{ demo ? 'Prueba la atención manual, las notas internas y el cambio al bot con datos de ejemplo.' : 'Pedidos de la web y la aplicación, consultas y respuestas en un solo lugar.' }}</p>
        <div class="welcome-features"><div><q-icon name="storefront" /><strong>La sucursal ya viene en el pedido</strong><span>El equipo recibe el contexto del cliente.</span></div><div><q-icon name="support_agent" /><strong>Bot y atención manual</strong><span>Toma la conversación cuando te necesiten.</span></div><div><q-icon name="receipt_long" /><strong>Todo a mano</strong><span>Historial, pedido y notas del equipo.</span></div></div>
        <q-btn v-if="!demo" unelevated no-caps color="teal-8" label="Explorar con ejemplos" icon="play_arrow" @click="startDemo" />
      </section>
    </div>

    <q-dialog v-model="detailsOpen" position="right">
      <q-card v-if="selected" class="support-details">
        <q-card-section class="row items-center justify-between"><h2>Detalles de atención</h2><q-btn flat round dense icon="close" v-close-popup aria-label="Cerrar detalles" /></q-card-section>
        <q-separator /><q-card-section>
          <q-badge v-if="demo" color="amber-2" text-color="brown-9">Ejemplo · no es un pedido real</q-badge>
          <dl><dt>Cliente</dt><dd>{{ selected.name }}</dd><dt>Sucursal asignada</dt><dd>{{ selected.branch }}</dd><dt>Origen</dt><dd>{{ sources[selected.source].label }}</dd><dt>Estado</dt><dd>{{ states[selected.status] }}</dd><dt>Atención</dt><dd>{{ modeTitle }}</dd></dl>
          <div v-if="selected.order" class="detail-order"><h3>{{ selected.order.number }}</h3><p v-for="(item, index) in selected.order.items" :key="item.id ?? index">{{ item.text ?? item }}</p><strong>Total Bs {{ selected.order.total }}</strong></div>
          <p v-else>Consulta sin pedido asociado.</p>
          <form v-if="!demo && isAdmin" class="q-mt-md" @submit.prevent="assignBranch">
            <q-select v-model="assignedBranch" :options="branches" option-label="nombre" option-value="id" emit-value map-options outlined dense label="Sucursal de atención" />
            <q-btn class="q-mt-sm" type="submit" unelevated no-caps color="teal-8" label="Guardar asignación" :disable="busy || !assignedBranch || assignedBranch === selected.branch_id" />
            <p v-if="error" class="text-negative q-mt-sm" role="alert">{{ error }}</p>
          </form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="createOpen" :persistent="busy">
      <q-card class="support-details">
        <q-form @submit="createCase">
          <q-card-section class="row items-center justify-between"><h2>Nuevo caso</h2><q-btn flat round dense icon="close" :disable="busy" v-close-popup aria-label="Cerrar nuevo caso" /></q-card-section>
          <q-card-section class="q-gutter-md">
            <q-btn-toggle v-if="isAdmin" v-model="createKind" no-caps unelevated toggle-color="teal-8" :options="[{label: 'Pedido online', value: 'order'}, {label: 'Consulta directa', value: 'direct'}]" />
            <q-input v-if="createKind === 'order'" v-model.trim="orderNumber" outlined maxlength="120" label="Número de pedido web o app" :rules="[required]" />
            <template v-else>
              <q-input v-model.trim="customerName" outlined maxlength="160" label="Nombre del cliente" :rules="[required]" />
              <q-input v-model.trim="initialMessage" outlined autogrow maxlength="2000" label="Consulta del cliente (opcional)" />
              <p class="detail-hint">El caso quedará pendiente de asignación.</p>
            </template>
            <div v-if="error" class="text-negative" role="alert">{{ error }}</div>
          </q-card-section>
          <q-card-actions align="right"><q-btn type="submit" unelevated no-caps color="teal-8" label="Abrir caso" :loading="busy" /></q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, nextTick, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { onBeforeRouteLeave } from 'vue-router'
import { useCounterStore } from 'stores/example-store'
import { createDemoConversations, quickReplies } from 'src/features/customer-support/demo'

const store = useCounterStore()
const $q = useQuasar()
const api = getCurrentInstance().appContext.config.globalProperties.$axios
const demo = ref(false)
const conversations = ref([])
const selectedId = ref(null)
const detail = ref(null)
const search = ref('')
const filter = ref('all')
const branchFilter = ref(null)
const branches = ref([])
const assignedBranch = ref(null)
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const busy = ref(false)
const error = ref('')
const createOpen = ref(false)
const createKind = ref('order')
const orderNumber = ref('')
const customerName = ref('')
const initialMessage = ref('')
let listRequest = 0
let detailRequest = 0
let scopeVersion = 0
const drafts = ref({})
const composeKind = ref('reply')
const detailsOpen = ref(false)
const messageList = ref(null)
const isAdmin = computed(() => String(store.user?.id) === '1')
const branchName = computed(() => store.user?.agencia?.nombre || 'Sin sucursal asignada')
const branchFilters = computed(() => [
  { label: 'Todas las sucursales', value: null },
  { label: 'Sin asignar', value: 0 },
  ...branches.value.map(branch => ({ label: branch.nombre, value: branch.id }))
])
const sources = {
  web: { label: 'Página web', icon: 'language' },
  app: { label: 'Aplicación móvil', icon: 'smartphone' },
  direct: { label: 'Consulta directa', icon: 'chat' },
  whatsapp: { label: 'WhatsApp', icon: 'chat' }
}
const states = { pending: 'Pendiente', open: 'En atención', resolved: 'Resuelto' }
const authors = { customer: 'Cliente', bot: 'Asistente', agent: 'Respuesta manual', note: 'Nota interna' }
const selected = computed(() => demo.value ? conversations.value.find(chat => chat.id === selectedId.value) : detail.value)
const draftKey = computed(() => `${selectedId.value}:${composeKind.value}`)
const draft = computed({ get: () => drafts.value[draftKey.value] || '', set: value => { drafts.value[draftKey.value] = value } })
const canCompose = computed(() => !busy.value && selected.value && selected.value.status !== 'resolved' &&
  (composeKind.value === 'note' || (selected.value.mode === 'manual' && (demo.value || selected.value.branch_id))))
const modeTitle = computed(() => {
  if (selected.value?.status === 'resolved') return 'Atención resuelta'
  if (!demo.value && !selected.value?.branch_id) return 'Pendiente de asignación'
  if (selected.value?.mode === 'bot') return demo.value ? 'Modo bot · simulación' : 'Modo bot guardado'
  return 'Atención manual · bot pausado'
})
const modeHint = computed(() => {
  if (selected.value?.status === 'resolved') return 'Reabre el caso para continuar.'
  if (!demo.value && !selected.value?.branch_id) return 'El administrador puede asignar una sucursal desde Detalles.'
  if (selected.value?.mode === 'bot') return demo.value ? 'Toma la atención para escribir personalmente.' : 'WhatsApp sigue pendiente de conexión. Puedes pasar a atención manual.'
  return demo.value ? 'Puedes simular una respuesta.' : 'Puedes guardar respuestas y notas en el caso.'
})
const placeholder = computed(() => {
  if (selected.value?.status === 'resolved') return 'Reabre la atención para continuar.'
  if (composeKind.value === 'note') return 'Escribe una nota para tu equipo…'
  if (!demo.value && !selected.value?.branch_id) return 'Asigna una sucursal para responder.'
  return selected.value?.mode === 'bot' ? 'Pulsa «Atender yo» para responder.' : 'Escribe tu respuesta…'
})
const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
const filtered = computed(() => {
  if (!demo.value) return conversations.value
  return conversations.value.filter(chat => (filter.value === 'all' || chat.status === filter.value) &&
    normalize(`${chat.name} ${chat.order?.number || ''}`).includes(normalize(search.value)))
    .sort((a, b) => lastMessage(b).at - lastMessage(a).at)
})
const lastMessage = chat => chat.preview || chat.messages?.[chat.messages.length - 1]
const time = at => new Date(at).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
const required = value => !!value?.trim() || 'Completa este campo.'

function showError (failure) {
  const status = failure.response?.status
  if (status === 404) error.value = 'El caso o pedido no existe o ya no está disponible para tu sucursal.'
  else if (status === 403) error.value = 'Tu cuenta no tiene permiso para realizar esta acción.'
  else if (status === 422) error.value = failure.response.data.message || 'Revisa los datos del caso.'
  else error.value = 'No se pudo conectar con la bandeja. Intenta actualizar en unos momentos.'
}

async function loadCases () {
  if (!api || demo.value || !store.user?.id) return
  const request = ++listRequest
  loading.value = true
  try {
    const { data } = await api.get('support/cases', {
      params: {
        page: page.value,
        search: search.value || undefined,
        status: filter.value === 'all' ? undefined : filter.value,
        branch: branchFilter.value ?? undefined
      }
    })
    if (request !== listRequest) return
    conversations.value = data.data
    lastPage.value = data.last_page
    if (page.value > data.last_page) page.value = data.last_page
  } catch (failure) {
    if (request === listRequest) showError(failure)
  } finally {
    if (request === listRequest) loading.value = false
  }
}

async function loadBranches () {
  if (!api || !isAdmin.value) return
  const version = scopeVersion
  try {
    const { data } = await api.get('support/branches')
    if (version === scopeVersion) branches.value = data
  } catch (failure) {
    if (version === scopeVersion) showError(failure)
  }
}

function setDetail (data) {
  detail.value = data
  assignedBranch.value = data.branch_id
}

async function loadDetail (id) {
  const request = ++detailRequest
  try {
    const { data } = await api.get(`support/cases/${id}`)
    if (request === detailRequest && selectedId.value === id) setDetail(data)
  } catch (failure) {
    if (request !== detailRequest || selectedId.value !== id) return
    detail.value = null
    detailsOpen.value = false
    showError(failure)
  }
}

function clearSelection () {
  selectedId.value = null
  detail.value = null
  detailsOpen.value = false
}

function refresh () {
  if (busy.value) return
  error.value = ''
  loadCases()
  if (selectedId.value !== null) loadDetail(selectedId.value)
  if (isAdmin.value && !branches.value.length) loadBranches()
}

async function runAction (request, apply) {
  if (busy.value) return
  const version = scopeVersion
  detailRequest++
  busy.value = true
  error.value = ''
  try {
    const { data } = await request()
    if (version === scopeVersion) {
      apply(data)
      loadCases()
    }
  } catch (failure) {
    if (version !== scopeVersion) return
    if (failure.response?.status === 404 && !createOpen.value) {
      clearSelection()
      loadCases()
    }
    showError(failure)
  } finally {
    if (version === scopeVersion) busy.value = false
  }
}

function openCreate () {
  error.value = ''
  createKind.value = 'order'
  orderNumber.value = ''
  customerName.value = ''
  initialMessage.value = ''
  createOpen.value = true
}

function createCase () {
  const payload = createKind.value === 'order'
    ? { order_number: orderNumber.value }
    : { customer_name: customerName.value, message: initialMessage.value || null }
  runAction(() => api.post('support/cases', payload), data => {
    createOpen.value = false
    selectedId.value = data.id
    composeKind.value = 'reply'
    setDetail(data)
  })
}

function changeState (action, id = selectedId.value) {
  runAction(() => api.patch(`support/cases/${id}`, { action }), data => {
    if (selectedId.value === id) setDetail(data)
  })
}

function assignBranch () {
  const id = selectedId.value
  runAction(() => api.patch(`support/cases/${id}/branch`, { agencia_id: assignedBranch.value }), data => {
    if (selectedId.value === id) setDetail(data)
  })
}

function loadOlder () {
  const id = selectedId.value
  const before = detail.value?.before
  if (!before) return
  const top = messageList.value
  const offset = top?.scrollHeight - top?.scrollTop
  runAction(() => api.get(`support/cases/${id}`, { params: { before } }), async data => {
    if (selectedId.value !== id || !detail.value) return
    detail.value.messages = [...data.messages, ...detail.value.messages]
    detail.value.before = data.before
    await nextTick()
    if (messageList.value === top && top) top.scrollTop = top.scrollHeight - offset
  })
}

function reset () {
  scopeVersion++
  listRequest++
  detailRequest++
  demo.value = false
  conversations.value = []
  clearSelection()
  drafts.value = {}
  search.value = ''
  filter.value = 'all'
  page.value = 1
  lastPage.value = 1
  branchFilter.value = null
  loading.value = false
  busy.value = false
  createOpen.value = false
  error.value = ''
  composeKind.value = 'reply'
}
function startDemo () {
  reset()
  conversations.value = createDemoConversations(branchName.value)
  demo.value = true
}
function leaveDemo () {
  $q.dialog({ title: 'Salir de la demostración', message: 'Se borrarán las respuestas, notas y borradores de ejemplo.', cancel: { label: 'Seguir probando', flat: true }, ok: { label: 'Salir', color: 'teal-8' } }).onOk(() => {
    reset()
    refresh()
  })
}
function selectChat (chat) {
  if (busy.value) return
  error.value = ''
  selectedId.value = chat.id
  composeKind.value = 'reply'
  if (demo.value) {
    chat.unread = 0
  } else {
    detail.value = null
    loadDetail(chat.id)
  }
}
function append (chat, author, text) {
  chat.messages.push({ id: `${chat.id}-${chat.messages.length}-${Date.now()}`, author, text, at: Date.now() })
}
function toggleMode () {
  const chat = selected.value
  if (!chat || chat.status === 'resolved') return
  if (!demo.value) {
    if (chat.mode === 'bot') changeState('manual', chat.id)
    else {
      $q.dialog({ title: 'Elegir modo bot', message: 'Se guardará este modo. Las respuestas automáticas requieren conectar WhatsApp.', cancel: { label: 'Cancelar', flat: true }, ok: { label: 'Guardar modo', color: 'teal-8' } })
        .onOk(() => changeState('bot', chat.id))
    }
    return
  }
  if (chat.mode === 'bot') {
    chat.mode = 'manual'
    chat.status = 'open'
    append(chat, 'system', 'Tomaste la atención. Bot pausado (simulación)')
  } else {
    $q.dialog({ title: 'Activar bot en esta conversación', message: 'Dejarás la atención manual. En esta demostración no se generarán respuestas automáticas.', cancel: { label: 'Cancelar', flat: true }, ok: { label: 'Activar bot', color: 'teal-8' } }).onOk(() => {
      if (!demo.value || !conversations.value.includes(chat) || chat.status === 'resolved') return
      chat.mode = 'bot'
      append(chat, 'system', 'Modo bot activado (simulación)')
    })
  }
}
function resolveChat () {
  const chat = selected.value
  if (!chat) return
  $q.dialog({ title: 'Resolver atención', message: 'Podrás reabrir el caso para continuar.', cancel: { label: 'Cancelar', flat: true }, ok: { label: 'Resolver', color: 'teal-8' } }).onOk(() => {
    if (!demo.value) {
      changeState('resolve', chat.id)
      return
    }
    if (!demo.value || !conversations.value.includes(chat)) return
    chat.status = 'resolved'
    chat.mode = 'manual'
    append(chat, 'system', 'Atención resuelta (simulación)')
  })
}
function reopenChat () {
  if (!selected.value) return
  if (!demo.value) {
    changeState('reopen')
    return
  }
  selected.value.status = 'open'
  selected.value.mode = 'manual'
  append(selected.value, 'system', 'Atención reabierta en modo manual (simulación)')
}
function insertReply (text) {
  if (!canCompose.value || composeKind.value !== 'reply') return
  draft.value = `${draft.value}${draft.value ? '\n' : ''}${text}`.slice(0, 2000)
}
function submit () {
  if (!canCompose.value || !draft.value.trim()) return
  const id = selectedId.value
  const key = draftKey.value
  const author = composeKind.value === 'note' ? 'note' : 'agent'
  const text = draft.value.trim().slice(0, 2000)
  if (!demo.value) {
    runAction(() => api.post(`support/cases/${id}/messages`, { author, text }), message => {
      delete drafts.value[key]
      if (selectedId.value !== id || !detail.value) return
      detail.value.messages.push(message)
      if (author === 'agent' && detail.value.status === 'pending') detail.value.status = 'open'
    })
    return
  }
  append(selected.value, author, text)
  draft.value = ''
}
watch([selectedId, () => lastMessage({ messages: selected.value?.messages })?.id], async () => {
  await nextTick()
  if (messageList.value) messageList.value.scrollTop = messageList.value.scrollHeight
})
// Los ejemplos y borradores no sobreviven a cambios de usuario o sucursal.
watch(() => [store.user?.id, store.user?.agencia_id], () => {
  reset()
  branches.value = []
  refresh()
}, { immediate: true })
watch([search, filter, branchFilter], () => {
  if (page.value === 1) loadCases()
  else page.value = 1
})
watch(page, loadCases)
onBeforeRouteLeave(() => {
  if (!Object.values(drafts.value).some(value => value.trim())) return true
  return new Promise(resolve => {
    $q.dialog({ title: 'Hay borradores pendientes', message: 'Al salir se descartará el texto que aún no guardaste.', cancel: { label: 'Quedarme', flat: true }, ok: { label: 'Salir', color: 'teal-8' } }).onOk(() => resolve(true)).onCancel(() => resolve(false)).onDismiss(() => resolve(false))
  })
})
</script>

<style scoped>
.support-page { padding: 24px; background: #f3f6f9; color: #243747; }
.support-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
h1, h2, h3, p { margin: 0; }
h1 { font-size: 24px; line-height: 1.35; font-weight: 700; letter-spacing: -.5px; }
h2 { font-size: 17px; line-height: 1.4; font-weight: 600; }
h3 { font-size: 15px; line-height: 1.4; font-weight: 600; }
.support-heading p { color: #667b8b; font-size: 13px; margin-top: 4px; }
.connection-notice { display: flex; align-items: center; gap: 12px; padding: 13px 16px; background: #e8f3f2; border: 1px solid #cee5e1; border-radius: 12px; color: #285d58; margin-bottom: 18px; }
.connection-notice strong, .connection-notice span:not(.q-badge) { display: block; font-size: 12px; }
.connection-notice strong { font-size: 13px; margin-bottom: 3px; }
.scope-badge { margin-left: auto; flex-shrink: 0; max-width: 230px; white-space: normal; }
.support-workspace { display: flex; height: calc(100vh - 245px); min-height: 560px; max-height: 960px; border: 1px solid #dfe7ec; border-radius: 16px; background: white; overflow: hidden; box-shadow: 0 8px 30px #1a465508; }
.inbox-panel { display: flex; flex-direction: column; width: 330px; flex-shrink: 0; border-right: 1px solid #e4ebef; }
.inbox-top { padding: 20px 16px 0; }
.inbox-scope { font-size: 12px; color: #71818e; margin: 12px 0; }
.inbox-tabs { margin: 0 -12px; }
.inbox-tabs :deep(.q-tab) { padding: 0 8px; min-height: 40px; }
.inbox-tabs :deep(.q-tab__label) { font-size: 12px; }
.conversation-list { flex: 1; overflow-y: auto; }
.conversation-item { display: flex; gap: 10px; width: 100%; padding: 18px 14px; text-align: left; border: 0; border-bottom: 1px solid #edf1f4; border-left: 3px solid transparent; background: white; color: inherit; cursor: pointer; font: inherit; }
.conversation-item:hover { background: #f5faf9; }
.conversation-item.active { background: #edf7f5; border-left-color: #148979; }
.conversation-copy { flex: 1; min-width: 0; }
.conversation-copy strong { font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.conversation-copy small { font-size: 10px; color: #758692; margin-left: 6px; }
.conversation-copy p { font-size: 12px; color: #71818e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 6px 0 10px; }
.source-label, .state-label { font-size: 10px; padding: 2px 6px; border-radius: 5px; background: #f0f3f7; color: #526778; }
.state-label { background: #e5eeeb; color: #346454; }
.inbox-empty { display: flex; align-items: center; flex-direction: column; text-align: center; gap: 9px; padding: 60px 24px; color: #7a8b97; font-size: 12px; }
.inbox-empty strong { color: #425a6a; font-size: 14px; }
.inbox-footer { padding: 14px; border-top: 1px solid #edf1f4; color: #7a8b97; font-size: 11px; text-align: center; }
.welcome-panel { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px; text-align: center; background: radial-gradient(ellipse at top, #f2faf7, white 70%); }
.welcome-icon { display: grid; place-items: center; width: 88px; height: 88px; background: #e0f0ec; color: #258673; border-radius: 28px; margin-bottom: 24px; }
.welcome-panel h2 { font-size: 25px; letter-spacing: -.5px; }
.welcome-panel > p { font-size: 14px; color: #758692; max-width: 400px; margin: 12px 0 25px; }
.welcome-features { display: grid; gap: 18px; margin-bottom: 28px; text-align: left; }
.welcome-features > div { display: grid; grid-template-columns: 30px 1fr; align-items: center; column-gap: 12px; }
.welcome-features .q-icon { grid-row: span 2; font-size: 22px; color: #509387; }
.welcome-features strong { font-size: 13px; font-weight: 500; }
.welcome-features span { font-size: 12px; color: #7a8b97; }
.chat-panel { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.chat-header { display: flex; align-items: center; gap: 10px; padding: 16px; border-bottom: 1px solid #edf1f4; }
.chat-person { flex: 1; min-width: 0; }
.chat-person strong, .chat-person span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.chat-person strong { font-size: 14px; }
.chat-person span { font-size: 11px; color: #71818e; margin-top: 3px; }
.mode-bar { display: flex; align-items: center; gap: 10px; padding: 11px 16px; background: #f1f7f6; color: #366c62; }
.mode-bar > div { flex: 1; }
.mode-bar strong, .mode-bar span { display: block; font-size: 11px; }
.mode-bar strong { font-size: 12px; }
.order-strip { display: flex; gap: 8px; align-items: center; border: 0; border-bottom: 1px solid #e6edf3; background: #f6f8fc; padding: 10px 16px; color: #596f91; font-size: 11px; cursor: pointer; text-align: left; }
.order-strip span { flex: 1; }
.message-list { flex: 1; min-height: 150px; overflow-y: auto; padding: 20px; background: #f7f9fb; }
.demo-divider { font-size: 10px; letter-spacing: 1px; text-align: center; color: #95a1ac; margin-bottom: 22px; }
.message-row { display: flex; margin: 12px 0; }
.message-row.outgoing { justify-content: flex-end; }
.message-bubble { max-width: 82%; padding: 12px 15px; border: 1px solid #e4ebef; background: white; border-radius: 0 14px 14px; overflow-wrap: anywhere; }
.outgoing .message-bubble { background: #e7f4ef; border-color: #d8eae3; border-radius: 14px 0 14px 14px; }
.outgoing .bot-bubble { background: #eef0fa; border-color: #e1e5f4; }
.outgoing .note-bubble { background: #fff5d9; border-color: #f1e4bf; }
.message-bubble strong { font-size: 10px; color: #68807b; }
.message-bubble p { font-size: 13px; line-height: 1.6; white-space: pre-wrap; margin: 5px 0; }
.message-bubble small { display: block; text-align: right; font-size: 10px; color: #7a8b97; }
.system-message { text-align: center; font-size: 11px; color: #788996; margin: 18px 0; }
.composer { padding: 14px 16px; border-top: 1px solid #e4ebef; }
.composer :deep(textarea) { max-height: 120px; overflow-y: auto !important; }
.composer-bottom { display: flex; gap: 12px; justify-content: space-between; align-items: center; margin-top: 10px; }
.composer-bottom > span { font-size: 10px; color: #83929e; }
.support-details { width: 380px; max-width: 95vw; }
.support-details dl { margin: 22px 0; }
.support-details dt { font-size: 11px; color: #7a8b97; margin-top: 16px; }
.support-details dd { margin: 4px 0 0; font-size: 14px; }
.detail-order { border: 1px solid #dfe7ec; border-radius: 10px; padding: 16px; }
.detail-order p { margin: 12px 0; font-size: 13px; }
.detail-hint { font-size: 12px; color: #7a8b97; margin-top: 20px; }
.mobile-back { display: none; }
@media (max-width: 1100px) {
  .support-page { padding: 16px; }
  .inbox-panel { width: 280px; }
  .scope-badge { display: none; }
  .support-heading { flex-wrap: wrap; }
}
@media (max-width: 750px) {
  .support-page { padding: 12px; }
  h1 { font-size: 21px; }
  .support-workspace { height: 75vh; min-height: 580px; }
  .inbox-panel { width: 100%; border-right: 0; }
  .welcome-panel { display: none; }
  .has-selection .inbox-panel { display: none; }
  .mobile-back { display: inline-flex; }
  .chat-header { padding: 12px 8px; gap: 6px; }
  .chat-header > .q-avatar { display: none; }
  .chat-person strong { font-size: 12px; }
  .mode-bar { padding: 10px; }
  .mode-bar span { font-size: 10px; }
  .message-list { padding: 12px; }
  .message-bubble { max-width: 92%; }
  .composer { padding: 10px; }
  .composer-bottom > span { max-width: 130px; }
  .order-strip span { display: none; }
}
</style>
