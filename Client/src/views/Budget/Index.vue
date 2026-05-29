<template>
  <v-container fluid class="budget-view">
    <section class="budget-header">
      <div>
        <p class="budget-kicker">Suivi budgétaire</p>
        <h1>Budget et investissements</h1>
        <p>Contrôle des prévisions, engagements et réalisations.</p>
      </div>
      <div class="header-actions">
        <v-btn-toggle v-model="typeBudget" color="primary" variant="outlined" density="compact" class="mr-2" mandatory>
          <v-btn value="exploitation">Exploitation</v-btn>
          <v-btn value="investissement_service">Investissement Service</v-btn>
        </v-btn-toggle>
        <v-select
          v-model="periodeType"
          :items="periodeTypes"
          item-title="label"
          item-value="value"
          label="Périodicité"
          variant="outlined"
          density="compact"
          hide-details
          class="periode-field"
        />
        <v-select
          v-if="periodeType !== 'annee'"
          v-model="periodeValue"
          :items="periodeOptions"
          item-title="label"
          item-value="value"
          label="Période"
          variant="outlined"
          density="compact"
          hide-details
          class="periode-field"
        />
        <v-text-field
          v-model.number="selectedYear"
          type="number"
          label="Année"
          variant="outlined"
          density="compact"
          hide-details
          min="2000"
          max="2100"
          class="year-field"
          @keyup.enter="handleYearChange"
        />
        <v-btn color="primary" variant="flat" prepend-icon="mdi-refresh" :loading="loading" @click="handleYearChange">
          Actualiser
        </v-btn>
      </div>
    </section>

    <v-alert v-if="error" type="error" variant="tonal">
      {{ error }}
    </v-alert>

    <BudgetFilterBar
      :comptes="comptes"
      @update:filters="onFiltersChange"
    />

    <section class="metric-grid">
      <article class="metric-card metric-prevu">
        <div class="metric-top">
          <span>Budget prévu</span>
          <v-icon>mdi-wallet-outline</v-icon>
        </div>
        <strong>{{ formatMoney(animatedMetrics.prevu) }}</strong>
        <p>Prévisions{{ hasActiveFilter ? ' (filtré)' : '' }}.</p>
      </article>
      <article class="metric-card metric-engage">
        <div class="metric-top">
          <span>Engagements</span>
          <v-icon>mdi-file-document-edit</v-icon>
        </div>
        <strong>{{ formatMoney(animatedMetrics.engage) }}</strong>
        <p>Commandes passées.</p>
      </article>
      <article class="metric-card metric-realise">
        <div class="metric-top">
          <span>Réalisations</span>
          <v-icon>mdi-cash-check</v-icon>
        </div>
        <strong>{{ formatMoney(animatedMetrics.realise) }}</strong>
        <p>Factures réglées.</p>
      </article>
      <article class="metric-card" :class="filteredDisponible >= 0 ? 'metric-ok' : 'metric-alert'">
        <div class="metric-top">
          <span>Disponible</span>
          <v-icon>mdi-scale-balance</v-icon>
        </div>
        <strong>{{ formatMoney(animatedMetrics.dispo) }}</strong>
        <p>{{ filteredDisponible >= 0 ? 'Marge restante' : 'Dépassement' }}.</p>
      </article>
      <article class="metric-card metric-rate">
        <div class="metric-top">
          <span>Exécution</span>
          <v-icon>mdi-percent-outline</v-icon>
        </div>
        <strong>{{ animatedMetrics.taux }}%</strong>
        <p>Taux d'exécution global.</p>
      </article>
    </section>

    <v-card class="budget-card rounded-lg" elevation="2">
      <v-tabs v-model="activeTab" color="primary" density="comfortable" class="modern-tabs">
        <v-tab value="suivi">Suivi</v-tab>
        <v-tab value="prevision">{{ typeBudget === 'exploitation' ? 'Nouvelle Prévision' : 'Nouveau Budget Alloué' }}</v-tab>
        <v-tab value="engagement">Nouvel Engagement</v-tab>
        <v-tab value="realisation">Nouvelle Réalisation</v-tab>
        <v-tab value="projets_travaux">Projets Travaux</v-tab>
        <v-tab value="investissements">Analyse Investissements</v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <v-window-item value="suivi">
          <v-card-title class="section-title">
            <div>
              <h2>Suivi par section et compte</h2>
              <p>Période : {{ currentPeriodLabel }} {{ annee }}{{ hasActiveFilter ? ' (filtré par compte)' : '' }}.</p>
            </div>
          </v-card-title>
          <v-data-table
            :headers="budgetHeaders"
            :items="filteredBudgetRows"
            :loading="loading"
            :items-per-page="50"
            class="elevation-0 budget-table"
            hover
            no-data-text="Aucune donnée budgétaire pour cette période"
            @click:row="openRowDetails"
          >
            <template #item.compte="{ item }">
              <div class="account-cell" :class="{ 'is-child': item.niveau > 0, 'is-section': item.is_regroupement }">
                <strong :class="{'section-text': item.is_regroupement}">
                  {{ item.is_regroupement ? (item.compte?.intitule || item.compte?.numero || 'N/A').replace('SECTION-', '').replace(/-/g, ' ').toUpperCase() : (item.compte?.numero || 'N/A') }}
                </strong>
                <v-chip v-if="item.is_regroupement" size="x-small" color="primary" variant="tonal" class="ml-2">
                  Total
                </v-chip>
              </div>
              <div v-if="!item.is_regroupement" class="muted">{{ item.compte?.intitule || '' }}</div>
            </template>
            <template #item.montant_prevu="{ item }">
              <span :class="{'section-text': item.is_regroupement}">{{ formatMoney(item.montant_prevu) }}</span>
            </template>
            <template #item.montant_engage="{ item }">
              <span :class="{'section-text': item.is_regroupement}">{{ formatMoney(item.montant_engage) }}</span>
            </template>
            <template #item.montant_realise="{ item }">
              <span :class="{'section-text': item.is_regroupement}">{{ formatMoney(item.montant_realise) }}</span>
            </template>
            <template #item.disponible="{ item }">
              <v-chip :color="item.disponible >= 0 ? 'success' : 'error'" size="small" variant="tonal" class="font-weight-bold">
                {{ formatMoney(item.disponible) }}
              </v-chip>
            </template>
            <template #item.taux_execution="{ item }">
              <div class="rate-cell">
                <span :class="{'section-text': item.is_regroupement}">{{ item.taux_execution }}%</span>
                <v-progress-linear
                  :model-value="Math.min(Number(item.taux_execution || 0), 130)"
                  :color="getRateColor(item.taux_execution)"
                  height="7"
                  rounded
                />
              </div>
            </template>
          </v-data-table>
        </v-window-item>

        <v-window-item value="prevision">
          <div class="form-panel">
            <div class="form-copy">
              <h2>{{ typeBudget === 'exploitation' ? 'Prévision mensuelle' : 'Nouveau Budget Investissement Service' }}</h2>
              <p>Une seule prévision par compte et par mois. Si elle existe déjà, le montant sera mis à jour.</p>
            </div>
            <v-form ref="previsionFormRef" @submit.prevent="submitPrevision">
              <v-row>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="previsionForm.compte_id"
                    :items="filteredComptesSaisissables"
                    :item-title="compteTitle"
                    item-value="id"
                    label="Compte"
                    variant="outlined"
                    density="compact"
                    :loading="loadingRefs"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select
                    v-model.number="previsionForm.mois"
                    :items="monthOptionsList"
                    item-title="label"
                    item-value="value"
                    label="Mois"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field
                    v-model.number="previsionForm.annee"
                    type="number"
                    label="Année"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field
                    v-model.number="previsionForm.montant_prevu"
                    type="number"
                    label="Montant"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule, positiveRule]"
                  />
                </v-col>
              </v-row>
              <div class="form-actions">
                <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save-outline" @click="submitPrevision">
                  Enregistrer
                </v-btn>
              </div>
            </v-form>
          </div>
        </v-window-item>

        <v-window-item value="engagement">
          <div class="form-panel">
            <div class="form-copy">
              <h2>Nouvel Engagement</h2>
              <p>Ajoute une dépense engagée (bon de commande) à une date précise.</p>
            </div>
            <v-form ref="engagementFormRef" @submit.prevent="submitEngagement">
              <v-row>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="engagementForm.compte_id"
                    :items="filteredComptesSaisissables"
                    :item-title="compteTitle"
                    item-value="id"
                    label="Compte"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="engagementForm.date_engagement"
                    type="date"
                    label="Date d'engagement"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model.number="engagementForm.montant_engage"
                    type="number"
                    label="Montant"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule, positiveRule]"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="engagementForm.observation"
                    label="Observation / Numéro de BC"
                    variant="outlined"
                    density="compact"
                    rows="2"
                  />
                </v-col>
              </v-row>
              <div class="form-actions">
                <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save-outline" @click="submitEngagement">
                  Enregistrer
                </v-btn>
              </div>
            </v-form>
          </div>
        </v-window-item>

        <v-window-item value="realisation">
          <div class="form-panel">
            <div class="form-copy">
              <h2>Nouvelle Réalisation</h2>
              <p>Ajoute une dépense constatée (facture réglée) à une date précise.</p>
            </div>
            <v-form ref="realisationFormRef" @submit.prevent="submitRealisation">
              <v-row>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="realisationForm.compte_id"
                    :items="filteredComptesSaisissables"
                    :item-title="compteTitle"
                    item-value="id"
                    label="Compte"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="realisationForm.date_realisation"
                    type="date"
                    label="Date de réalisation"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model.number="realisationForm.montant_realise"
                    type="number"
                    label="Montant"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule, positiveRule]"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="realisationForm.observation"
                    label="Observation / Référence Facture"
                    variant="outlined"
                    density="compact"
                    rows="2"
                  />
                </v-col>
              </v-row>
              <div class="form-actions">
                <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save-outline" @click="submitRealisation">
                  Enregistrer
                </v-btn>
              </div>
            </v-form>
          </div>
        </v-window-item>

        <v-window-item value="projets_travaux">
          <ProjetInvestissementTable :annee="selectedYear" />
        </v-window-item>

        <v-window-item value="investissements">
          <div class="investment-grid">
            <div class="form-panel">
              <div class="form-copy">
                <h2>Calcul investissement</h2>
                <p>Saisis les flux annuels pour calculer VAN, TRI et DRCI.</p>
              </div>
              <v-form ref="investmentFormRef" @submit.prevent="calculateInvestment">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="investmentForm.montant_initial"
                      type="number"
                      label="Montant initial"
                      variant="outlined"
                      density="compact"
                      :rules="[requiredRule, positiveRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="investmentForm.taux_actualisation"
                      type="number"
                      step="0.01"
                      label="Taux actualisation"
                      hint="Exemple : 0.1 pour 10%"
                      persistent-hint
                      variant="outlined"
                      density="compact"
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <div class="flux-header">
                      <strong>Flux prévisionnels</strong>
                      <v-btn size="small" variant="outlined" prepend-icon="mdi-plus" @click="addFluxYear">
                        Ajouter une année
                      </v-btn>
                    </div>
                  </v-col>
                  <v-col v-for="(flux, index) in investmentForm.flux" :key="index" cols="12">
                    <div class="flux-row">
                      <span>Année {{ index + 1 }}</span>
                      <v-text-field
                        v-model.number="flux.recette"
                        type="number"
                        label="Recettes"
                        variant="outlined"
                        density="compact"
                        hide-details
                      />
                      <v-text-field
                        v-model.number="flux.charge"
                        type="number"
                        label="Charges"
                        variant="outlined"
                        density="compact"
                        hide-details
                      />
                      <v-btn
                        icon="mdi-delete-outline"
                        variant="text"
                        color="error"
                        :disabled="investmentForm.flux.length <= 1"
                        @click="removeFluxYear(index)"
                      />
                    </div>
                  </v-col>
                </v-row>
                <div class="form-actions mt-4">
                  <v-btn color="primary" :loading="calculating" prepend-icon="mdi-calculator-variant-outline" @click="calculateInvestment">
                    Calculer
                  </v-btn>
                  <v-btn variant="outlined" :disabled="!calculation" :loading="saving" @click="saveInvestment">
                    Enregistrer le résultat
                  </v-btn>
                </div>
              </v-form>
            </div>

            <div class="result-panel">
              <h2>Résultat</h2>
              <div v-if="calculation" class="result-grid">
                <div>
                  <span>VAN</span>
                  <strong>{{ formatMoney(calculation.van) }}</strong>
                </div>
                <div>
                  <span>TRI</span>
                  <strong>{{ formatTri(calculation.tri) }}</strong>
                </div>
                <div>
                  <span>DRCI</span>
                  <strong>{{ formatDrci(calculation.drci, calculation.drci_libelle) }}</strong>
                </div>
              </div>
              <div v-if="calculation" class="result-notes">
                <v-alert v-if="calculation.tri === null" type="warning" variant="tonal" density="compact">
                  TRI non calculable : les flux fournis ne permettent pas de trouver un taux qui annule la VAN.
                </v-alert>
                <v-alert v-if="calculation.drci === null" type="warning" variant="tonal" density="compact">
                  DRCI non calculable : le cumul des flux actualisés ne récupère pas le montant initial.
                </v-alert>
              </div>
              <v-alert v-else type="info" variant="tonal">
                Aucun calcul lancé.
              </v-alert>

              <v-divider class="my-4" />

              <h2>Historique</h2>
              <v-data-table
                :headers="investmentHeaders"
                :items="investissements"
                :loading="loadingInvestments"
                density="comfortable"
                :items-per-page="5"
                no-data-text="Aucun investissement enregistré"
              >
                <template #item.montant_initial="{ item }">
                  {{ formatMoney(item.montant_initial) }}
                </template>
                <template #item.taux_actualisation="{ item }">
                  {{ formatRate(item.taux_actualisation) }}
                </template>
                <template #item.van="{ item }">
                  {{ item.van === null ? 'N/A' : formatMoney(item.van) }}
                </template>
                <template #item.tri="{ item }">
                  {{ formatTri(item.tri) }}
                </template>
                <template #item.drci="{ item }">
                  {{ formatDrci(item.drci) }}
                </template>
              </v-data-table>
            </div>
          </div>
        </v-window-item>
      </v-window>
    </v-card>

    <!-- Historique Ligne Modal -->
    <v-dialog v-model="showRowDetailsModal" max-width="800px">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4 bg-primary text-white">
          <span class="text-h6 font-weight-bold">
            Historique : {{ selectedRowDetails?.compte?.numero }} - {{ selectedRowDetails?.compte?.intitule }}
          </span>
          <v-btn icon="mdi-close" variant="text" color="white" @click="showRowDetailsModal = false"></v-btn>
        </v-card-title>
        <v-card-text class="pa-4">
          <v-data-table
            :headers="historyHeaders"
            :items="selectedRowDetails?.history || []"
            :items-per-page="10"
            class="elevation-1"
            density="compact"
            no-data-text="Aucun historique pour ce compte"
          >
            <template #item.type="{ item }">
              <v-chip :color="getHistoryTypeColor(item.type)" size="small" label>
                {{ item.type }}
              </v-chip>
            </template>
            <template #item.date="{ item }">
              {{ formatDate(item.date) }}
            </template>
            <template #item.montant="{ item }">
              <strong>{{ formatMoney(item.montant) }}</strong>
            </template>
            <template #item.observation="{ item }">
              <span class="text-medium-emphasis">{{ item.observation }}</span>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useBudgetStore } from '@/stores/budget'
import { matchesCompteFilter, rowsForKpi, buildCompteIndex, collectDescendantIds } from '@/utils/budgetHierarchy'
import BudgetFilterBar from '@/components/budget/BudgetFilterBar.vue'
import ProjetInvestissementTable from '@/components/budget/ProjetInvestissementTable.vue'

const budgetStore = useBudgetStore()
const {
  annee,
  comptes,
  comptesSaisissables,
  investissements,
  calculation,
  loading,
  loadingRefs,
  loadingInvestments,
  saving,
  calculating,
  error,
  budgetRows,
} = storeToRefs(budgetStore)

// --- Filtres Périodes ---
const periodeType = ref('annee')
const periodeValue = ref(null)
const selectedYear = ref(annee.value)

const periodeTypes = [
  { label: 'Année entière', value: 'annee' },
  { label: 'Par Trimestre', value: 'trimestre' },
  { label: 'Par Mois', value: 'mois' }
]

const monthOptionsList = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
].map((label, index) => ({ label, value: index + 1 }))

const quarterOptionsList = [
  { label: 'Trimestre 1', value: 1 },
  { label: 'Trimestre 2', value: 2 },
  { label: 'Trimestre 3', value: 3 },
  { label: 'Trimestre 4', value: 4 }
]

const periodeOptions = computed(() => {
  if (periodeType.value === 'trimestre') return quarterOptionsList
  if (periodeType.value === 'mois') return monthOptionsList
  return []
})

const currentPeriodLabel = computed(() => {
  if (periodeType.value === 'annee') return 'Année globale'
  if (periodeType.value === 'trimestre') return `Trimestre ${periodeValue.value}`
  if (periodeType.value === 'mois') {
    return monthOptionsList.find(m => m.value === periodeValue.value)?.label || 'Mois'
  }
  return ''
})

watch([periodeType, periodeValue], ([type, value]) => {
  if (type !== 'annee' && value === null) {
    periodeValue.value = 1
  }
  budgetStore.setPeriode(type, periodeValue.value)
})

// --- Filtres Comptes ---
const filters = reactive({
  compteId: null
})

const onFiltersChange = (f) => {
  filters.compteId = f.compteId
}

const hasActiveFilter = computed(() => filters.compteId != null)

const applyServiceCompteFilter = (rows) => {
  return rows.filter((row) => {
    if (!matchesCompteFilter(row, filters.compteId, comptes.value)) {
      return false
    }
    return true
  })
}

// --- Filtres Type de Budget ---
const typeBudget = ref('exploitation')

const isCompteType = (compte, type) => {
  if (!compte || !compte.numero) return false
  if (type === 'exploitation') {
    return compte.numero.startsWith('6') || 
           ['SECTION-AUTRES-ACHATS', 'SECTION-TRANSPORTS', 'SECTION-SERVICES-EXTERIEURS', 'SECTION-CHARGES-PERSONNEL'].includes(compte.numero)
  } else {
    return compte.numero.startsWith('2') || 
           compte.numero === 'SECTION-INVESTISSEMENT-SERVICE'
  }
}

const applyTypeFilter = (rows) => {
  return rows.filter((row) => isCompteType(row.compte, typeBudget.value))
}

const filteredComptesSaisissables = computed(() => {
  return comptesSaisissables.value.filter(c => isCompteType(c, typeBudget.value))
})

// Suivi table filtré
const filteredBudgetRows = computed(() => {
  let rows = budgetRows.value
  rows = applyTypeFilter(rows)
  return applyServiceCompteFilter(rows)
})

const kpiSuiviRows = computed(() =>
  rowsForKpi(filteredBudgetRows.value, filters.compteId, comptes.value)
)

// KPIs filtrés.
const filteredTotalPrevu = computed(() =>
  kpiSuiviRows.value.reduce((sum, row) => sum + (row.montant_prevu ?? 0), 0)
)
const filteredTotalEngage = computed(() =>
  kpiSuiviRows.value.reduce((sum, row) => sum + (row.montant_engage ?? 0), 0)
)
const filteredTotalRealise = computed(() =>
  kpiSuiviRows.value.reduce((sum, row) => sum + (row.montant_realise ?? 0), 0)
)
const filteredDisponible = computed(() => filteredTotalPrevu.value - filteredTotalEngage.value - filteredTotalRealise.value)

const filteredTaux = computed(() => {
  if (!filteredTotalPrevu.value) return 0
  return Math.round(((filteredTotalEngage.value + filteredTotalRealise.value) / filteredTotalPrevu.value) * 1000) / 10
})

// --- Animations CountUp ---
const animatedMetrics = reactive({
  prevu: 0,
  engage: 0,
  realise: 0,
  dispo: 0,
  taux: 0
})

const animateValue = (key, endValue, duration = 1000) => {
  const startValue = animatedMetrics[key]
  const startTime = performance.now()

  const step = (currentTime) => {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / duration, 1)
    const easeProgress = 1 - Math.pow(1 - progress, 4)
    
    // For rate (taux), keep 1 decimal
    if (key === 'taux') {
      animatedMetrics[key] = Math.round((startValue + (endValue - startValue) * easeProgress) * 10) / 10
    } else {
      animatedMetrics[key] = Math.round(startValue + (endValue - startValue) * easeProgress)
    }
    
    if (progress < 1) {
      requestAnimationFrame(step)
    } else {
      animatedMetrics[key] = endValue
    }
  }
  
  requestAnimationFrame(step)
}

watch([filteredTotalPrevu, filteredTotalEngage, filteredTotalRealise, filteredDisponible, filteredTaux], () => {
  animateValue('prevu', filteredTotalPrevu.value)
  animateValue('engage', filteredTotalEngage.value)
  animateValue('realise', filteredTotalRealise.value)
  animateValue('dispo', filteredDisponible.value)
  animateValue('taux', filteredTaux.value)
}, { immediate: true })


// --- Forms & Tabs ---
const activeTab = ref('suivi')
const previsionFormRef = ref(null)
const engagementFormRef = ref(null)
const realisationFormRef = ref(null)
const investmentFormRef = ref(null)

const getTodayDate = () => new Date().toISOString().split('T')[0]

const previsionForm = reactive({
  compte_id: null,
  montant_prevu: null,
  mois: new Date().getMonth() + 1,
  annee: selectedYear.value
})

const engagementForm = reactive({
  compte_id: null,
  montant_engage: null,
  date_engagement: getTodayDate(),
  observation: ''
})

const realisationForm = reactive({
  compte_id: null,
  montant_realise: null,
  date_realisation: getTodayDate(),
  observation: ''
})

const investmentForm = reactive({
  montant_initial: null,
  taux_actualisation: 0.1,
  flux: [
    { recette: 0, charge: 0 },
    { recette: 0, charge: 0 },
    { recette: 0, charge: 0 }
  ]
})

const budgetHeaders = [
  { title: 'Compte', key: 'compte' },
  { title: 'Prévu', key: 'montant_prevu', align: 'end' },
  { title: 'Engagements', key: 'montant_engage', align: 'end' },
  { title: 'Réalisé', key: 'montant_realise', align: 'end' },
  { title: 'Disponible', key: 'disponible', align: 'end' },
  { title: 'Exécution', key: 'taux_execution', sortable: false }
]

const historyHeaders = [
  { title: 'Type', key: 'type' },
  { title: 'Date', key: 'date' },
  { title: 'Montant', key: 'montant', align: 'end' },
  { title: 'Observation', key: 'observation' }
]

const investmentHeaders = [
  { title: 'Montant', key: 'montant_initial' },
  { title: 'Taux', key: 'taux_actualisation' },
  { title: 'VAN', key: 'van' },
  { title: 'TRI', key: 'tri' },
  { title: 'DRCI', key: 'drci' }
]

const requiredRule = value => Boolean(value) || 'Champ requis'
const positiveRule = value => Number(value) >= 0 || 'Montant invalide'

const formatMoney = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}

const formatRate = (value) => {
  if (value === null || value === undefined) return 'N/A'
  return `${Math.round(Number(value) * 10000) / 100}%`
}

const formatTri = (value) => {
  if (value === null || value === undefined) return 'Non calculable'
  return formatRate(value)
}

const formatDrci = (value, label = null) => {
  if (value === null || value === undefined) return 'Capital non récupéré'
  return label || `${Math.round(Number(value) * 100) / 100} ans`
}

const compteTitle = (item) => item ? `${item.numero} - ${item.intitule}` : ''

const getRateColor = (value) => {
  const rate = Number(value || 0)
  if (rate > 100) return 'error'
  if (rate >= 85) return 'warning'
  return 'success'
}

const resetPrevisionForm = () => {
  previsionForm.compte_id = null
  previsionForm.montant_prevu = null
  previsionForm.mois = new Date().getMonth() + 1
  previsionForm.annee = selectedYear.value
}

const resetEngagementForm = () => {
  engagementForm.compte_id = null
  engagementForm.montant_engage = null
  engagementForm.date_engagement = getTodayDate()
  engagementForm.observation = ''
}

const resetRealisationForm = () => {
  realisationForm.compte_id = null
  realisationForm.montant_realise = null
  realisationForm.date_realisation = getTodayDate()
  realisationForm.observation = ''
}

const handleYearChange = async () => {
  await budgetStore.setAnnee(selectedYear.value)
}

const submitPrevision = async () => {
  const result = await previsionFormRef.value?.validate()
  if (!result?.valid) return
  await budgetStore.createEntry({ ...previsionForm }, 'prevision')
  resetPrevisionForm()
  activeTab.value = 'suivi'
}

const submitEngagement = async () => {
  const result = await engagementFormRef.value?.validate()
  if (!result?.valid) return
  await budgetStore.createEntry({ ...engagementForm }, 'engagement')
  resetEngagementForm()
  activeTab.value = 'suivi'
}

const submitRealisation = async () => {
  const result = await realisationFormRef.value?.validate()
  if (!result?.valid) return
  await budgetStore.createEntry({ ...realisationForm }, 'realisation')
  resetRealisationForm()
  activeTab.value = 'suivi'
}

const addFluxYear = () => {
  investmentForm.flux.push({ recette: 0, charge: 0 })
}

const removeFluxYear = (index) => {
  investmentForm.flux.splice(index, 1)
}

const calculateInvestment = async () => {
  const result = await investmentFormRef.value?.validate()
  if (!result?.valid) return
  await budgetStore.calculateInvestment({
    montant_initial: investmentForm.montant_initial,
    taux_actualisation: investmentForm.taux_actualisation,
    recettes: investmentForm.flux.map(item => Number(item.recette || 0)),
    charges: investmentForm.flux.map(item => Number(item.charge || 0))
  })
}

const saveInvestment = async () => {
  if (!calculation.value) return
  await budgetStore.createInvestment({
    montant_initial: investmentForm.montant_initial,
    taux_actualisation: investmentForm.taux_actualisation,
    van: calculation.value.van,
    tri: calculation.value.tri,
    drci: calculation.value.drci
  })
}

watch(selectedYear, (value) => {
  previsionForm.annee = value
})

const showRowDetailsModal = ref(false)
const selectedRowDetails = ref(null)

const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR')

const getHistoryTypeColor = (type) => {
  if (type === 'Prévision') return 'primary'
  if (type === 'Engagement') return 'warning'
  if (type === 'Réalisation') return 'success'
  return 'grey'
}

const openRowDetails = (event, { item }) => {
  const { childrenByParentId } = buildCompteIndex(budgetStore.comptes)
  let compteIds = [Number(item.compte_id)]
  if (item.is_regroupement) {
    compteIds = Array.from(collectDescendantIds(item.compte_id, childrenByParentId))
  }

  const previsions = budgetStore.filteredPrevisions.filter(p => compteIds.includes(Number(p.compte_id)))
  const engagements = budgetStore.filteredEngagements.filter(e => compteIds.includes(Number(e.compte_id)))
  const realisations = budgetStore.filteredRealisations.filter(r => compteIds.includes(Number(r.compte_id)))

  const history = [
    ...previsions.map(p => ({
      type: 'Prévision',
      date: `${p.annee}-${String(p.mois).padStart(2, '0')}-01`,
      montant: p.montant_prevu,
      observation: p.observation || '-'
    })),
    ...engagements.map(e => ({
      type: 'Engagement',
      date: e.date_engagement,
      montant: e.montant_engage,
      observation: e.observation || '-'
    })),
    ...realisations.map(r => ({
      type: 'Réalisation',
      date: r.date_realisation,
      montant: r.montant_realise,
      observation: r.observation || '-'
    }))
  ]

  history.sort((a, b) => new Date(b.date) - new Date(a.date))
  
  selectedRowDetails.value = {
    compte: item.compte,
    history
  }
  showRowDetailsModal.value = true
}

onMounted(async () => {
  await budgetStore.fetchReferentiels()
  await Promise.all([
    budgetStore.fetchBudget(),
    budgetStore.fetchInvestissements()
  ])
})
</script>

<style scoped>
.budget-table {
  cursor: pointer;
}
.budget-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.04) !important;
}

.budget-view {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.budget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}

.budget-kicker {
  margin: 0 0 0.35rem;
  color: #008a9b;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.budget-header h1,
.section-title h2,
.form-copy h2,
.result-panel h2 {
  margin: 0;
  color: #111827;
}

.budget-header p,
.section-title p,
.form-copy p {
  margin: 0.35rem 0 0;
  color: #64748b;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.periode-field {
  width: 160px;
}
.year-field {
  width: 110px;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 1rem;
}

.metric-card {
  min-height: 132px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: linear-gradient(145deg, #ffffff, #f8fafc);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
  border-left: 4px solid #008a9b;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
}

.metric-prevu {
  border-left-color: #0ea5e9;
}
.metric-engage {
  border-left-color: #f59e0b;
}
.metric-realise {
  border-left-color: #8b5cf6;
}
.metric-alert {
  border-left-color: #dc2626;
}
.metric-ok {
  border-left-color: #10b981;
}
.metric-rate {
  border-left-color: #008a9b;
}

.metric-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  color: #475569;
  font-weight: 700;
}

.metric-top .v-icon {
  color: #94a3b8;
}

.metric-card strong {
  color: #111827;
  font-size: 1.65rem;
  line-height: 1.1;
}

.metric-card p {
  margin: 0;
  color: #64748b;
  font-size: 0.85rem;
}

.budget-card {
  border: 1px solid #e5e7eb;
}

.section-title {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
}

.modern-tabs {
  margin-bottom: 0.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.muted {
  color: #64748b;
  font-size: 0.82rem;
}

.account-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.account-cell.is-child {
  padding-left: 1rem;
}

/* Section styling for hierarchy regroupement */
.section-text {
  color: #0f172a;
  font-weight: 800;
  font-size: 1.05em;
}

.rate-cell {
  display: grid;
  grid-template-columns: 56px minmax(100px, 1fr);
  align-items: center;
  gap: 0.75rem;
  min-width: 180px;
}

.form-panel,
.result-panel {
  padding: 1rem;
}

.form-copy {
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.75rem;
}

.investment-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(360px, 0.75fr);
  gap: 1rem;
}

.flux-header,
.flux-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.flux-header {
  justify-content: space-between;
}
.flux-row > span {
  width: 72px;
  color: #475569;
  font-weight: 700;
}

.result-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}
.result-notes {
  display: grid;
  gap: 0.75rem;
  margin-top: 0.9rem;
}
.result-grid div {
  padding: 0.9rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
}
.result-grid span {
  display: block;
  color: #64748b;
  font-size: 0.8rem;
}
.result-grid strong {
  display: block;
  margin-top: 0.35rem;
  color: #111827;
  font-size: 1.1rem;
}

@media (max-width: 1200px) {
  .metric-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .metric-grid,
  .investment-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 760px) {
  .budget-header,
  .header-actions,
  .flux-row {
    align-items: stretch;
    flex-direction: column;
  }
  .metric-grid,
  .investment-grid,
  .result-grid {
    grid-template-columns: 1fr;
  }
  .periode-field,
  .year-field {
    width: 100%;
  }
  .flux-row > span {
    width: auto;
  }
}
</style>
