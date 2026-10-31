<template>
  <div class="validity-editor">
    <div class="validity-label"><q-icon name="event_available" size="17px" />Vigencia del descuento</div>
    <q-btn-toggle
      :model-value="modelValue.permanente"
      :options="[{ label: 'Indefinida', value: true, icon: 'all_inclusive' }, { label: 'Por fechas', value: false, icon: 'date_range' }]"
      spread no-caps unelevated toggle-color="deep-purple" color="grey-2" text-color="grey-7" :disable="disabled"
      @update:model-value="update('permanente', $event)"
    />
    <div v-if="!modelValue.permanente" class="validity-dates">
      <q-input :model-value="modelValue.fecha_inicio" type="datetime-local" outlined dense stack-label label="Inicio" :disable="disabled" :rules="[required]" @update:model-value="update('fecha_inicio', $event)" />
      <q-input :model-value="modelValue.fecha_fin" type="datetime-local" outlined dense stack-label label="Fin" :min="modelValue.fecha_inicio || undefined" :disable="disabled" :rules="[required, endAfterStart]" @update:model-value="update('fecha_fin', $event)" />
      <div class="validity-note"><q-icon name="schedule" />Hora de Bolivia. Fuera de este plazo se aplica la siguiente regla vigente.</div>
    </div>
    <div v-else class="validity-note">Sin fecha de fin, hasta que la cambies o pauses.</div>
  </div>
</template>

<script>
export default {
  name: 'TraeValidity',
  props: { modelValue: { type: Object, required: true }, disabled: Boolean },
  emits: ['update:modelValue'],
  methods: {
    update (field, value) { this.$emit('update:modelValue', { ...this.modelValue, [field]: value }) },
    required (value) { return !!value || 'Selecciona fecha y hora' },
    endAfterStart (value) { return (!value || !this.modelValue.fecha_inicio || value > this.modelValue.fecha_inicio) || 'El fin debe ser posterior al inicio' }
  }
}
</script>

<style scoped>
.validity-editor { padding: 14px 0; }
.validity-label { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #686077; margin-bottom: 10px; }
.validity-editor :deep(.q-btn-toggle) { border-radius: 8px; overflow: hidden; }
.validity-editor :deep(.q-btn) { font-size: 11px; min-height: 37px; }
.validity-editor :deep(.q-btn .q-icon) { font-size: 16px; }
.validity-dates { display: grid; gap: 4px; margin-top: 16px; }
.validity-dates :deep(.q-field__native) { font-size: 12px; }
.validity-note { font-size: 10px; line-height: 1.7; color: #867b95; margin-top: 9px; }
.validity-note .q-icon { margin-right: 4px; }
</style>
