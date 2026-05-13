<template>
  <v-container fluid class="budget-view">
    <section class="budget-header">
      <div>
        <p class="budget-kicker">Suivi budgétaire</p>
        <h1>Budget et investissements</h1>
        <p>Contrôle des prévisions, réalisations et indicateurs d'investissement.</p>
      </div>
      <div class="header-actions">
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

    <section class="metric-grid">
      <article class="metric-card">
        <div class="metric-top">
          <span>Budget prévu</span>
          <v-icon>mdi-wallet-outline</v-icon>
        </div>
        <strong>{{ formatMoney(totalPrevu) }}</strong>
        <p>Prévisions annuelles enregistrées.</p>
      </article>
      <article class="metric-card metric-realise">
        <div class="metric-top">
          <span>Réalisé</span>
          <v-icon>mdi-cash-check</v-icon>
        </div>
        <strong>{{ formatMoney(totalRealise) }}</strong>
        <p>Dépenses réalisées sur l'année.</p>
      </article>
      <article class="metric-card" :class="ecartGlobal >= 0 ? 'metric-ok' : 'metric-alert'">
        <div class="metric-top">
          <span>Écart prévu - réalisé</span>
          <v-icon>mdi-chart-timeline-variant</v-icon>
        </div>
        <strong>{{ formatMoney(ecartGlobal) }}</strong>
        <p>{{ ecartGlobal >= 0 ? 'Marge disponible' : 'Dépassement global' }}.</p>
      </article>
      <article class="metric-card metric-rate">
        <div class="metric-top">
          <span>Taux d'exécution</span>
          <v-icon>mdi-percent-outline</v-icon>
        </div>
        <strong>{{ tauxExecution }}%</strong>
        <p>Réalisé rapporté au prévu.</p>
      </article>
    </section>

    <v-card class="budget-card rounded-lg" elevation="2">
      <v-tabs v-model="activeTab" color="primary" density="comfortable">
        <v-tab value="suivi">Suivi</v-tab>
        <v-tab value="prevision">Prévision</v-tab>
        <v-tab value="realisation">Réalisation</v-tab>
        <v-tab value="investissements">Investissements</v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <v-window-item value="suivi">
          <v-card-title class="section-title">
            <div>
              <h2>Suivi par service et compte</h2>
              <p>{{ budgetRows.length }} ligne(s) consolidée(s) pour {{ annee }}.</p>
            </div>
          </v-card-title>
          <v-data-table
            :headers="budgetHeaders"
            :items="budgetRows"
            :loading="loading"
            :items-per-page="10"
            class="elevation-0"
            hover
            no-data-text="Aucune donnée budgétaire pour cette année"
          >
            <template #item.service="{ item }">
              <strong>{{ item.service?.code || 'N/A' }}</strong>
              <div class="muted">{{ item.service?.intitule || '' }}</div>
            </template>
            <template #item.compte="{ item }">
              <strong>{{ item.compte?.numero || 'N/A' }}</strong>
              <div class="muted">{{ item.compte?.intitule || '' }}</div>
            </template>
            <template #item.montant_prevu="{ item }">
              {{ formatMoney(item.montant_prevu) }}
            </template>
            <template #item.montant_realise="{ item }">
              {{ formatMoney(item.montant_realise) }}
            </template>
            <template #item.ecart="{ item }">
              <v-chip :color="item.ecart >= 0 ? 'success' : 'error'" size="small" variant="tonal">
                {{ formatMoney(item.ecart) }}
              </v-chip>
            </template>
            <template #item.taux_execution="{ item }">
              <div class="rate-cell">
                <span>{{ item.taux_execution }}%</span>
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
              <h2>Nouvelle prévision</h2>
              <p>Renseigne le budget annuel prévu pour un service et un compte.</p>
            </div>
            <v-form ref="previsionFormRef" @submit.prevent="submitPrevision">
              <v-row>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="previsionForm.service_id"
                    :items="services"
                    :item-title="serviceTitle"
                    item-value="id"
                    label="Service"
                    variant="outlined"
                    density="compact"
                    :loading="loadingRefs"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="previsionForm.compte_id"
                    :items="comptes"
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

        <v-window-item value="realisation">
          <div class="form-panel">
            <div class="form-copy">
              <h2>Nouvelle réalisation</h2>
              <p>Ajoute une dépense mensuelle constatée pour suivre l'exécution.</p>
            </div>
            <v-form ref="realisationFormRef" @submit.prevent="submitRealisation">
              <v-row>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="realisationForm.service_id"
                    :items="services"
                    :item-title="serviceTitle"
                    item-value="id"
                    label="Service"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="realisationForm.compte_id"
                    :items="comptes"
                    :item-title="compteTitle"
                    item-value="id"
                    label="Compte"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select
                    v-model.number="realisationForm.mois"
                    :items="monthOptions"
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
                    v-model.number="realisationForm.annee"
                    type="number"
                    label="Année"
                    variant="outlined"
                    density="compact"
                    :rules="[requiredRule]"
                  />
                </v-col>
                <v-col cols="12" md="2">
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
                    label="Observation"
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
                <div class="form-actions">
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
  </v-container>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useBudgetStore } from '@/stores/budget'

const budgetStore = useBudgetStore()
const {
  annee,
  previsions,
  services,
  comptes,
  investissements,
  calculation,
  loading,
  loadingRefs,
  loadingInvestments,
  saving,
  calculating,
  error,
  totalPrevu,
  totalRealise,
  ecartGlobal,
  tauxExecution,
  budgetRows
} = storeToRefs(budgetStore)

const activeTab = ref('suivi')
const selectedYear = ref(annee.value)
const previsionFormRef = ref(null)
const realisationFormRef = ref(null)
const investmentFormRef = ref(null)

const previsionForm = reactive({
  service_id: null,
  compte_id: null,
  montant_prevu: null,
  annee: selectedYear.value
})

const realisationForm = reactive({
  service_id: null,
  compte_id: null,
  montant_realise: null,
  mois: new Date().getMonth() + 1,
  annee: selectedYear.value,
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
  { title: 'Service', key: 'service' },
  { title: 'Compte', key: 'compte' },
  { title: 'Prévu', key: 'montant_prevu', align: 'end' },
  { title: 'Réalisé', key: 'montant_realise', align: 'end' },
  { title: 'Écart', key: 'ecart', align: 'end' },
  { title: 'Exécution', key: 'taux_execution', sortable: false }
]

const investmentHeaders = [
  { title: 'Montant', key: 'montant_initial' },
  { title: 'Taux', key: 'taux_actualisation' },
  { title: 'VAN', key: 'van' },
  { title: 'TRI', key: 'tri' },
  { title: 'DRCI', key: 'drci' }
]

const monthOptions = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
].map((label, index) => ({ label, value: index + 1 }))

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

const serviceTitle = (item) => item ? `${item.code} - ${item.intitule}` : ''
const compteTitle = (item) => item ? `${item.numero} - ${item.intitule}` : ''

const getRateColor = (value) => {
  const rate = Number(value || 0)
  if (rate > 100) return 'error'
  if (rate >= 85) return 'warning'
  return 'success'
}

const resetPrevisionForm = () => {
  previsionForm.service_id = null
  previsionForm.compte_id = null
  previsionForm.montant_prevu = null
  previsionForm.annee = selectedYear.value
}

const resetRealisationForm = () => {
  realisationForm.service_id = null
  realisationForm.compte_id = null
  realisationForm.montant_realise = null
  realisationForm.mois = new Date().getMonth() + 1
  realisationForm.annee = selectedYear.value
  realisationForm.observation = ''
}

const handleYearChange = async () => {
  await budgetStore.setAnnee(selectedYear.value)
}

const submitPrevision = async () => {
  const result = await previsionFormRef.value?.validate()
  if (!result?.valid) return

  await budgetStore.createPrevision({ ...previsionForm })
  resetPrevisionForm()
  activeTab.value = 'suivi'
}

const submitRealisation = async () => {
  const result = await realisationFormRef.value?.validate()
  if (!result?.valid) return

  await budgetStore.createRealisation({ ...realisationForm })
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
  realisationForm.annee = value
})

onMounted(async () => {
  await Promise.all([
    budgetStore.fetchReferentiels(),
    budgetStore.fetchBudget(),
    budgetStore.fetchInvestissements()
  ])
})
</script>

<style scoped>
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

.year-field {
  width: 132px;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.metric-card {
  min-height: 132px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-left: 4px solid #008a9b;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}

.metric-realise {
  border-left-color: #2563eb;
}

.metric-alert {
  border-left-color: #dc2626;
}

.metric-ok {
  border-left-color: #16a34a;
}

.metric-rate {
  border-left-color: #f59e0b;
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
  font-size: 1.75rem;
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

.muted {
  color: #64748b;
  font-size: 0.82rem;
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

@media (max-width: 1100px) {
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

  .year-field {
    width: 100%;
  }

  .flux-row > span {
    width: auto;
  }
}
</style>
