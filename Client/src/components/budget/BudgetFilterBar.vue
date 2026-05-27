<template>
  <section class="filter-bar">
    <v-select
      v-model="localFilters.compteId"
      :items="compteOptions"
      item-title="label"
      item-value="value"
      label="Compte"
      variant="outlined"
      density="compact"
      hide-details
      clearable
      class="filter-field filter-field-lg"
      @update:model-value="emit('update:filters', { ...localFilters })"
    />
    <v-select
      v-model="localFilters.mois"
      :items="monthOptions"
      item-title="label"
      item-value="value"
      label="Mois"
      variant="outlined"
      density="compact"
      hide-details
      clearable
      class="filter-field"
      @update:model-value="emit('update:filters', { ...localFilters })"
    />
    <v-btn
      variant="text"
      size="small"
      prepend-icon="mdi-filter-off-outline"
      :disabled="!hasActiveFilter"
      @click="resetFilters"
    >
      Réinitialiser
    </v-btn>
  </section>
</template>

<script setup>
import { computed, reactive } from 'vue'

const props = defineProps({

  comptes: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:filters'])

const localFilters = reactive({

  compteId: null,
  mois: null
})

const compteOptions = computed(() =>
  props.comptes.map((c) => ({
    label: `${c.numero} — ${c.intitule}${Number(c.enfants_count || 0) > 0 ? ' (regroupement)' : ''}`,
    value: c.id
  }))
)

const monthOptions = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
].map((label, index) => ({ label, value: index + 1 }))

const hasActiveFilter = computed(() =>
  localFilters.compteId != null ||
  localFilters.mois != null
)

const resetFilters = () => {

  localFilters.compteId = null
  localFilters.mois = null
  emit('update:filters', { ...localFilters })
}
</script>

<style scoped>
.filter-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}

.filter-field {
  min-width: 160px;
  max-width: 220px;
  flex: 1 1 160px;
}

.filter-field-lg {
  max-width: 300px;
  flex: 1.5 1 200px;
}

@media (max-width: 760px) {
  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-field,
  .filter-field-lg {
    max-width: 100%;
  }
}
</style>
