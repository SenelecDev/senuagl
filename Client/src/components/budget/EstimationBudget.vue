<template>
  <v-card class="estimation-card rounded-lg mt-6" elevation="1">
    <v-card-title class="d-flex align-center justify-space-between pa-4">
      <div>
        <h2 class="text-h6 font-weight-bold">📊 Estimation budgétaire {{ annee + 1 }}</h2>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Projection du budget à partir des dépenses cumulées (engagements + réalisations).
        </p>
      </div>
      <div class="d-flex align-center ga-3">
        <v-btn-toggle
          v-model="selectedQuarter"
          color="primary"
          variant="outlined"
          density="compact"
          mandatory
        >
          <v-btn :value="1">Q1</v-btn>
          <v-btn :value="2">Q2</v-btn>
          <v-btn :value="3" class="q3-btn">Q3</v-btn>
          <v-btn :value="4">Q4</v-btn>
        </v-btn-toggle>
        <v-btn
          variant="flat"
          color="primary"
          size="small"
          prepend-icon="mdi-refresh"
          :loading="budgetStore.loadingEstimation"
          @click="refresh"
        >
          Actualiser
        </v-btn>
      </div>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-0">
      <v-data-table
        :headers="headers"
        :items="filteredRows"
        :loading="budgetStore.loadingEstimation"
        density="comfortable"
        :items-per-page="-1"
        hide-default-footer
        class="estimation-table"
        no-data-text="Aucune donnée pour cette année"
      >
        <template #item.intitule="{ item }">
          <strong>{{ formatSectionName(item.intitule) }}</strong>
        </template>
        <template #item.cumul="{ item }">
          {{ formatMoney(item[`cumul_q${selectedQuarter}`]) }}
        </template>
        <template #item.estimation="{ item }">
          <strong class="text-primary">{{ formatMoney(item[`estimation_q${selectedQuarter}`]) }}</strong>
        </template>

        <template #body.append>
          <tr class="total-row">
            <td><strong>TOTAL</strong></td>
            <td class="text-end"><strong>{{ formatMoney(totalCumul) }}</strong></td>
            <td class="text-end"><strong class="text-primary text-h6">{{ formatMoney(totalEstimation) }}</strong></td>
          </tr>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Info trimestre -->
    <v-card-text class="pt-0">
      <v-alert
        :type="selectedQuarter === 3 ? 'success' : 'info'"
        variant="tonal"
        density="compact"
      >
        <template v-if="selectedQuarter === 3">
          <strong>Trimestre 3 (jan–sep)</strong> — Extrapolation la plus fiable : 9 mois de données réelles, coefficient ×1,33.
        </template>
        <template v-else-if="selectedQuarter === 1">
          <strong>Trimestre 1 (jan–mar)</strong> — Extrapolation indicative : seulement 3 mois de données, coefficient ×4.
        </template>
        <template v-else-if="selectedQuarter === 2">
          <strong>Trimestre 2 (jan–jun)</strong> — Extrapolation modérée : 6 mois de données, coefficient ×2.
        </template>
        <template v-else>
          <strong>Trimestre 4 (jan–déc)</strong> — Données réelles complètes, pas d'extrapolation.
        </template>
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useBudgetStore } from '@/stores/budget'

const props = defineProps({
  annee: { type: Number, required: true },
  typeBudget: { type: String, default: 'exploitation' }
})

const budgetStore = useBudgetStore()
const selectedQuarter = ref(3) // Q3 par défaut

const headers = [
  { title: 'Section', key: 'intitule' },
  { title: 'Cumul réel', key: 'cumul', align: 'end' },
  { title: 'Estimation annuelle', key: 'estimation', align: 'end' },
]

const isExploitationSection = (numero) => {
  return ['SECTION-AUTRES-ACHATS', 'SECTION-TRANSPORTS', 'SECTION-SERVICES-EXTERIEURS', 'SECTION-CHARGES-PERSONNEL'].includes(numero)
}

const isInvestissementSection = (numero) => {
  return numero === 'SECTION-INVESTISSEMENT-SERVICE'
}

const filteredRows = computed(() => {
  if (!budgetStore.estimation?.sections) return []
  return budgetStore.estimation.sections.filter(s => {
    if (props.typeBudget === 'exploitation') return isExploitationSection(s.numero)
    return isInvestissementSection(s.numero)
  })
})

const totalCumul = computed(() =>
  filteredRows.value.reduce((sum, row) => sum + (row[`cumul_q${selectedQuarter.value}`] || 0), 0)
)

const totalEstimation = computed(() =>
  filteredRows.value.reduce((sum, row) => sum + (row[`estimation_q${selectedQuarter.value}`] || 0), 0)
)

const formatMoney = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}

const formatSectionName = (intitule) => {
  return intitule?.replace('SECTION-', '').replace(/-/g, ' ') || ''
}

const refresh = () => {
  budgetStore.fetchEstimation(props.annee)
}

onMounted(() => {
  budgetStore.fetchEstimation(props.annee)
})

watch(() => props.annee, (newAnnee) => {
  budgetStore.fetchEstimation(newAnnee)
})
</script>

<style scoped>
.estimation-card {
  border-left: 4px solid rgb(var(--v-theme-primary));
}

.q3-btn {
  font-weight: 700 !important;
}

.estimation-table :deep(th) {
  font-weight: 600 !important;
  text-transform: uppercase;
  font-size: 0.75rem !important;
  letter-spacing: 0.05em;
}

.total-row {
  background: rgba(var(--v-theme-primary), 0.04);
}

.total-label {
  font-size: 1rem;
}
</style>
