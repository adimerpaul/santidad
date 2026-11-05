<template>
  <q-card flat bordered class="report-notes q-mb-md">
    <div class="notes-heading">
      <q-icon name="notes" color="primary" size="22px" />
      <div><h2>Observaciones generales del informe</h2><p>Detalles del conteo, incidencias y conclusiones que corresponden a todo el informe.</p></div>
      <q-space />
      <span v-if="canEdit && dirty" class="draft-label">Cambios sin guardar</span>
      <span v-else-if="savedMessage" class="saved-label"><q-icon name="check_circle" /> Guardado</span>
    </div>
    <template v-if="canEdit">
      <q-input v-model="draft" outlined dense autogrow maxlength="5000" counter :disable="saving"
        label="Detalles y observaciones generales" placeholder="Ej.: fecha del conteo, responsables, diferencias detectadas y acciones pendientes."
        :input-style="{ minHeight: '52px' }" />
      <div v-if="errorMessage" class="notes-error" role="alert">
        {{ errorMessage }}
        <q-btn v-if="conflict" flat dense no-caps label="Cargar versión guardada" @click="discardChanges" />
      </div>
      <div class="notes-actions">
        <span>Se guardan en este informe y se incluyen en su PDF.</span>
        <q-space />
        <q-btn v-if="dirty && !conflict" flat dense no-caps label="Deshacer" :disable="saving" @click="discardChanges" />
        <q-btn unelevated dense no-caps color="primary" icon="save" label="Guardar observaciones" :loading="saving"
          :disable="!dirty || conflict" @click="save" />
      </div>
    </template>
    <template v-else>
      <div class="stored-notes">{{ report.observaciones || 'Sin observaciones generales.' }}</div>
      <p class="readonly-note">{{ report.estado === 'REVISADO' ? 'Informe revisado: observaciones de solo lectura.' : 'Observaciones de solo lectura en este estado del informe.' }}</p>
    </template>
  </q-card>
</template>

<script>
export default {
  name: 'WithdrawalReportNotes',
  props: { report: { type: Object, required: true } },
  emits: ['notes-updated'],
  data () {
    return {
      draft: '',
      baseline: '',
      saving: false,
      savedMessage: false,
      errorMessage: '',
      conflict: false,
      conflictValue: '',
      requestAbort: null,
      disposed: false
    }
  },
  computed: {
    dirty () { return this.draft !== this.baseline },
    identityKey () { return String(this.$store.user?.id || '') + ':' + String(this.$store.user?.agencia_id || '') },
    canEdit () {
      const user = this.$store.user
      if (!user?.id) return false
      const admin = String(user.id) === '1'
      if (!admin && (!user.agencia_id || String(user.agencia_id) !== String(this.report.agencia_id))) return false
      return (admin ? ['ABIERTO', 'OBSERVADO', 'PENDIENTE'] : ['ABIERTO', 'OBSERVADO']).includes(this.report.estado)
    }
  },
  watch: {
    report: {
      immediate: true,
      handler (report, previous) {
        const value = report.observaciones || ''
        if (!previous || String(report.id) !== String(previous.id)) {
          this.requestAbort?.abort()
          this.saving = false
          this.draft = value
          this.baseline = value
          this.conflict = false
          this.errorMessage = ''
          this.savedMessage = false
        } else if (!this.dirty && !this.saving) {
          this.draft = value
          this.baseline = value
        } else if (!this.saving && value !== this.baseline) {
          this.conflict = true
          this.conflictValue = value
          this.errorMessage = 'Las observaciones cambiaron mientras editabas. Tu texto sigue en el borrador.'
        }
      }
    },
    identityKey () {
      this.requestAbort?.abort()
      this.saving = false
      this.draft = this.report.observaciones || ''
      this.baseline = this.draft
      this.errorMessage = ''
      this.conflict = false
      this.savedMessage = false
    },
    draft () { this.savedMessage = false }
  },
  beforeUnmount () {
    this.disposed = true
    this.requestAbort?.abort()
  },
  methods: {
    discardChanges () {
      if (this.saving) return
      const value = this.conflict ? this.conflictValue : this.baseline
      this.draft = value
      this.baseline = value
      if (this.conflict) this.$emit('notes-updated', { id: this.report.id, observaciones: value })
      this.conflict = false
      this.errorMessage = ''
    },
    async save () {
      if (!this.canEdit || !this.dirty || this.saving || this.conflict || this.disposed) return
      if (this.draft.length > 5000) {
        this.errorMessage = 'Las observaciones admiten hasta 5000 caracteres.'
        return
      }
      const id = this.report.id
      const text = this.draft
      const controller = new AbortController()
      this.requestAbort?.abort()
      this.requestAbort = controller
      this.saving = true
      this.errorMessage = ''
      try {
        const { data } = await this.$axios.put('withdrawal-reports/' + id, {
          observaciones: text, observaciones_originales: this.baseline
        }, { signal: controller.signal })
        if (this.disposed || controller.signal.aborted || this.requestAbort !== controller || String(this.report.id) !== String(id)) return
        this.baseline = data.observaciones || ''
        this.draft = this.baseline
        this.$emit('notes-updated', data)
        this.$nextTick(() => {
          if (!this.disposed && this.requestAbort === controller && String(this.report.id) === String(id)) this.savedMessage = true
        })
      } catch (error) {
        if (this.disposed || controller.signal.aborted || this.requestAbort !== controller) return
        this.errorMessage = error.response?.data?.message || 'No se pudieron guardar las observaciones. Tu texto se conserva para reintentar.'
        if (error.response?.status === 409) {
          this.conflict = true
          this.conflictValue = error.response.data.observaciones || ''
        }
      } finally {
        if (this.requestAbort === controller && !this.disposed) this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.report-notes { border-color: #e0e7ef; border-radius: 10px; padding: 14px 16px; color: #304158; }
.notes-heading { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.notes-heading h2 { font-size: 14px; line-height: 1.4; font-weight: 600; margin: 0; }
.notes-heading p { font-size: 11px; color: #8490a1; margin: 3px 0 0; }
.notes-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.notes-actions > span { font-size: 10px; color: #8592a5; }
.notes-actions .q-btn { border-radius: 7px; padding: 5px 12px; }
.draft-label, .saved-label { font-size: 10px; white-space: nowrap; }
.draft-label { color: #a87c21; }
.saved-label { color: #2b8659; }
.notes-error { font-size: 12px; color: #b9444c; background: #fff3f3; border-radius: 7px; padding: 8px 10px; margin-top: 8px; }
.stored-notes { white-space: pre-wrap; overflow-wrap: anywhere; font-size: 12px; line-height: 1.6; padding: 8px 10px; background: #f7f9fc; border-radius: 7px; }
.readonly-note { font-size: 10px; color: #8592a5; margin: 6px 0 0; }
@media(max-width:599px) { .report-notes { padding: 12px; } .notes-heading { flex-wrap: wrap; } .notes-actions > span { width: 100%; } }
</style>
