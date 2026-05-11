<template>
  <v-container fluid class="statistiques-view">
    <section class="stats-header">
      <div>
        <p class="eyebrow">Analyse RH</p>
        <h1>Statistiques du personnel</h1>
        <p class="intro">
          Lecture synthétique des effectifs, de la mixité et des mouvements prévisionnels.
        </p>
      </div>
      <v-btn
        color="primary"
        variant="flat"
        prepend-icon="mdi-refresh"
        :loading="loading"
        @click="fetchAll"
      >
        Actualiser
      </v-btn>
    </section>

    <v-alert v-if="error" type="error" variant="tonal">
      {{ error }}
    </v-alert>

    <div v-if="loading" class="loading-panel">
      <v-progress-circular indeterminate color="primary" size="44" />
      <span>Chargement des statistiques...</span>
    </div>

    <template v-else>
      <section class="kpi-grid">
        <article class="metric-card metric-total">
          <div class="metric-heading">
            <span class="metric-label">Effectif total</span>
            <v-icon>mdi-account-group-outline</v-icon>
          </div>
          <strong>{{ repartitionHF.total }}</strong>
          <span class="metric-note">Agents recensés</span>
        </article>
        <article class="metric-card metric-women">
          <div class="metric-heading">
            <span class="metric-label">Femmes</span>
            <v-icon>mdi-human-female</v-icon>
          </div>
          <strong>{{ repartitionHF.femmes.nombre }}</strong>
          <span class="metric-note">{{ repartitionHF.femmes.pourcentage }}% de l'effectif</span>
        </article>
        <article class="metric-card metric-men">
          <div class="metric-heading">
            <span class="metric-label">Hommes</span>
            <v-icon>mdi-human-male</v-icon>
          </div>
          <strong>{{ repartitionHF.hommes.nombre }}</strong>
          <span class="metric-note">{{ repartitionHF.hommes.pourcentage }}% de l'effectif</span>
        </article>
        <article class="metric-card metric-risk">
          <div class="metric-heading">
            <span class="metric-label">Départs sous 5 ans</span>
            <v-icon>mdi-calendar-clock-outline</v-icon>
          </div>
          <strong>{{ retirementsWithinFiveYears }}</strong>
          <span class="metric-note">{{ retirementRate }}% de l'effectif</span>
        </article>
        <article class="metric-card metric-blocked">
          <div class="metric-heading">
            <span class="metric-label">Agents bloqués</span>
            <v-icon>mdi-lock-clock-outline</v-icon>
          </div>
          <strong>{{ agentsBloques.length }}</strong>
          <span class="metric-note">Au plafond depuis au moins 3 ans</span>
        </article>
      </section>

      <section class="charts-grid">
        <v-card class="rounded-lg chart-card" elevation="2">
          <v-card-title class="chart-title">
            <div>
              <h2>Répartition H/F</h2>
              <p>Structure globale de l'effectif.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="genderCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="rounded-lg chart-card" elevation="2">
          <v-card-title class="chart-title">
            <div>
              <h2>Pyramide des âges H/F</h2>
              <p>Hommes à gauche, femmes à droite par tranche d'âge.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="ageCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="rounded-lg chart-card" elevation="2">
          <v-card-title class="chart-title">
            <div>
              <h2>Départs retraite</h2>
              <p>Vision prévisionnelle à 5 ans.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="retirementCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="rounded-lg chart-card" elevation="2">
          <v-card-title class="chart-title">
            <div>
              <h2>Top 8 services</h2>
              <p>Effectifs et équilibre H/F dans les plus grandes unités.</p>
            </div>
            <v-chip color="primary" variant="tonal" size="small">Top 8</v-chip>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="serviceCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>
      </section>

      <section>
        <v-card class="rounded-lg insight-card" elevation="2">
          <v-card-title class="table-title">
            <div>
              <h2>Détails opérationnels</h2>
              <p>Listes de suivi pour transformer les indicateurs en actions.</p>
            </div>
            <v-chip color="primary" variant="tonal" size="small">
              {{ repartitionHFParService.length }} services
            </v-chip>
          </v-card-title>

          <v-tabs v-model="activeTable" color="primary" density="comfortable">
            <v-tab value="retirements">
              Retraites
              <v-chip class="ml-2" color="warning" variant="tonal" size="x-small">
                {{ retirementsWithinFiveYears }}
              </v-chip>
            </v-tab>
            <v-tab value="services">Services</v-tab>
            <v-tab value="blocked">
              Agents bloqués
              <v-chip class="ml-2" color="secondary" variant="tonal" size="x-small">
                {{ agentsBloques.length }}
              </v-chip>
            </v-tab>
          </v-tabs>

          <v-window v-model="activeTable">
            <v-window-item value="retirements">
              <div class="retirement-toolbar">
                <v-btn-toggle
                  v-model="activeRetirementHorizon"
                  color="primary"
                  density="comfortable"
                  mandatory
                  variant="outlined"
                >
                  <v-btn
                    v-for="option in retirementHorizonOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                    <v-chip class="ml-2" size="x-small" variant="tonal">
                      {{ option.count }}
                    </v-chip>
                  </v-btn>
                </v-btn-toggle>
              </div>

              <v-data-table
                :headers="retirementHeaders"
                :items="selectedRetirements"
                :items-per-page="6"
                density="comfortable"
                :no-data-text="selectedRetirementEmptyText"
              >
                <template #item.nom_complet="{ item }">
                  {{ item.prenom }} {{ item.nom }}
                </template>
                <template #item.poste="{ item }">
                  {{ item.poste?.intitule || 'N/A' }}
                </template>
                <template #item.age="{ item }">
                  {{ item.age ?? getAge(item.date_naissance) }} ans
                </template>
                <template #item.date_retraite="{ item }">
                  {{ formatDate(item.date_retraite) }}
                </template>
                <template #item.mois_avant_retraite="{ item }">
                  {{ formatRetirementDelay(item.mois_avant_retraite) }}
                </template>
              </v-data-table>
            </v-window-item>

            <v-window-item value="services">
              <v-data-table
                :headers="serviceHeaders"
                :items="repartitionHFParService"
                :items-per-page="8"
                density="comfortable"
                no-data-text="Aucune donnée par service"
              >
                <template #item.mixite="{ item }">
                  <div class="mix-cell">
                    <span>H {{ item.pourcentage_hommes }}%</span>
                    <v-progress-linear
                      :model-value="Number(item.pourcentage_hommes || 0)"
                      color="primary"
                      height="8"
                      rounded
                    />
                    <span>F {{ item.pourcentage_femmes }}%</span>
                  </div>
                </template>
              </v-data-table>
            </v-window-item>

            <v-window-item value="blocked">
              <v-data-table
                :headers="blockedHeaders"
                :items="agentsBloques"
                :items-per-page="6"
                density="comfortable"
                no-data-text="Aucun agent bloqué détecté"
              >
                <template #item.nom_complet="{ item }">
                  {{ item.prenom }} {{ item.nom }}
                </template>
                <template #item.gf="{ item }">
                  {{ item.gf_actuel?.ordre || 'N/A' }}
                </template>
                <template #item.poste="{ item }">
                  {{ item.poste?.intitule || 'N/A' }}
                </template>
              </v-data-table>
            </v-window-item>
          </v-window>
        </v-card>
      </section>
    </template>
  </v-container>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import Chart from 'chart.js/auto'
import { useStatistiqueStore } from '@/stores/statistique'

const statistiqueStore = useStatistiqueStore()
const {
  pyramideAges,
  repartitionHF,
  repartitionHFParService,
  departsRetraite,
  agentsBloques,
  loading,
  error
} = storeToRefs(statistiqueStore)
const { fetchAll } = statistiqueStore

const genderCanvas = ref(null)
const ageCanvas = ref(null)
const retirementCanvas = ref(null)
const serviceCanvas = ref(null)
const activeTable = ref('retirements')
const activeRetirementHorizon = ref('entre_1_et_2_ans')

let genderChart = null
let ageChart = null
let retirementChart = null
let serviceChart = null

const retirementHeaders = [
  { title: 'Matricule', key: 'matricule' },
  { title: 'Agent', key: 'nom_complet' },
  { title: 'Poste', key: 'poste' },
  { title: 'Age', key: 'age' },
  { title: 'Date retraite', key: 'date_retraite' },
  { title: 'Échéance', key: 'mois_avant_retraite' }
]

const serviceHeaders = [
  { title: 'Service', key: 'service' },
  { title: 'Total', key: 'total' },
  { title: 'Hommes', key: 'hommes' },
  { title: 'Femmes', key: 'femmes' },
  { title: 'Mixité', key: 'mixite', sortable: false }
]

const blockedHeaders = [
  { title: 'Matricule', key: 'matricule' },
  { title: 'Agent', key: 'nom_complet' },
  { title: 'Poste', key: 'poste' },
  { title: 'GF', key: 'gf' },
  { title: 'Années sans promotion', key: 'annees_sans_promotion' }
]

const retirementHorizonOptions = computed(() => {
  const comptage = departsRetraite.value.comptage || {}

  return [
    { value: 'moins_1_an', label: 'Sous 1 an', count: Number(comptage.moins_1_an || 0) },
    { value: 'entre_1_et_2_ans', label: 'Dans 2 ans', count: Number(comptage.entre_1_et_2_ans || 0) },
    { value: 'entre_2_et_3_ans', label: '2 à 3 ans', count: Number(comptage.entre_2_et_3_ans || 0) },
    { value: 'entre_3_et_5_ans', label: '3 à 5 ans', count: Number(comptage.entre_3_et_5_ans || 0) }
  ]
})

const selectedRetirements = computed(() => {
  return departsRetraite.value.liste?.[activeRetirementHorizon.value] || []
})

const selectedRetirementEmptyText = computed(() => {
  const selected = retirementHorizonOptions.value.find(option => option.value === activeRetirementHorizon.value)
  return `Aucun agent à afficher pour l'horizon "${selected?.label || 'sélectionné'}"`
})

const retirementsWithinFiveYears = computed(() => {
  const comptage = departsRetraite.value.comptage || {}
  return Number(comptage.moins_1_an || 0)
    + Number(comptage.entre_1_et_3_ans || 0)
    + Number(comptage.entre_3_et_5_ans || 0)
})

const retirementRate = computed(() => {
  const total = Number(repartitionHF.value.total || 0)
  if (!total) return 0
  return Math.round((retirementsWithinFiveYears.value / total) * 1000) / 10
})

const getAge = (date) => {
  if (!date) return 'N/A'
  const birthDate = new Date(date)
  const now = new Date()
  let age = now.getFullYear() - birthDate.getFullYear()
  const monthDiff = now.getMonth() - birthDate.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birthDate.getDate())) {
    age -= 1
  }
  return age
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('fr-FR').format(new Date(date))
}

const formatRetirementDelay = (months) => {
  const value = Number(months)
  if (!Number.isFinite(value)) return 'N/A'
  if (value <= 0) return 'Échu'
  if (value < 12) return `${value} mois`

  const years = Math.floor(value / 12)
  const remainingMonths = value % 12

  if (!remainingMonths) return `${years} an${years > 1 ? 's' : ''}`
  return `${years} an${years > 1 ? 's' : ''} ${remainingMonths} mois`
}

const destroyCharts = () => {
  genderChart?.destroy()
  ageChart?.destroy()
  retirementChart?.destroy()
  serviceChart?.destroy()
  genderChart = null
  ageChart = null
  retirementChart = null
  serviceChart = null
}

const baseChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        boxWidth: 12,
        padding: 14,
        usePointStyle: true
      }
    }
  }
}

const buildCharts = async () => {
  await nextTick()
  destroyCharts()

  if (genderCanvas.value) {
    genderChart = new Chart(genderCanvas.value, {
      type: 'doughnut',
      data: {
        labels: ['Hommes', 'Femmes'],
        datasets: [
          {
            data: [repartitionHF.value.hommes.nombre, repartitionHF.value.femmes.nombre],
            backgroundColor: ['#008a9b', '#f59e0b'],
            borderWidth: 0,
            hoverOffset: 6
          }
        ]
      },
      options: {
        ...baseChartOptions,
        cutout: '64%'
      }
    })
  }

  if (ageCanvas.value) {
    const labels = Object.keys(pyramideAges.value || {})
    const hommes = labels.map(label => Number(pyramideAges.value[label]?.hommes || 0))
    const femmes = labels.map(label => Number(pyramideAges.value[label]?.femmes || 0))
    const maxAgeValue = Math.max(...hommes, ...femmes, 1)

    ageChart = new Chart(ageCanvas.value, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Hommes',
            data: hommes.map(value => -value),
            backgroundColor: '#008a9b',
            borderRadius: 6
          },
          {
            label: 'Femmes',
            data: femmes,
            backgroundColor: '#f59e0b',
            borderRadius: 6
          }
        ]
      },
      options: {
        ...baseChartOptions,
        indexAxis: 'y',
        scales: {
          x: {
            min: -maxAgeValue,
            max: maxAgeValue,
            ticks: {
              precision: 0,
              callback: value => Math.abs(value)
            },
            grid: {
              color: context => context.tick.value === 0 ? '#94a3b8' : '#e5e7eb'
            }
          },
          y: { grid: { display: false } }
        },
        plugins: {
          ...baseChartOptions.plugins,
          tooltip: {
            callbacks: {
              label: context => `${context.dataset.label}: ${Math.abs(context.parsed.x)}`
            }
          }
        }
      }
    })
  }

  if (retirementCanvas.value) {
    const comptage = departsRetraite.value.comptage || {}
    retirementChart = new Chart(retirementCanvas.value, {
      type: 'bar',
      data: {
        labels: ['< 1 an', '1 à 3 ans', '3 à 5 ans', '> 5 ans'],
        datasets: [
          {
            label: 'Agents',
            data: [
              comptage.moins_1_an || 0,
              comptage.entre_1_et_3_ans || 0,
              comptage.entre_3_et_5_ans || 0,
              comptage.plus_5_ans || 0
            ],
            backgroundColor: ['#dc2626', '#f59e0b', '#2563eb', '#16a34a'],
            borderRadius: 6
          }
        ]
      },
      options: {
        ...baseChartOptions,
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    })
  }

  if (serviceCanvas.value) {
    const sortedServices = [...repartitionHFParService.value]
      .sort((a, b) => Number(b.total || 0) - Number(a.total || 0))
      .slice(0, 8)

    serviceChart = new Chart(serviceCanvas.value, {
      type: 'bar',
      data: {
        labels: sortedServices.map(item => item.service),
        datasets: [
          {
            label: 'Hommes',
            data: sortedServices.map(item => item.hommes),
            backgroundColor: '#008a9b'
          },
          {
            label: 'Femmes',
            data: sortedServices.map(item => item.femmes),
            backgroundColor: '#f59e0b'
          }
        ]
      },
      options: {
        ...baseChartOptions,
        indexAxis: 'y',
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 } },
          y: { grid: { display: false } }
        }
      }
    })
  }
}

watch(loading, (isLoading) => {
  if (!isLoading && !error.value) {
    buildCharts()
  }
})

onMounted(async () => {
  await fetchAll()
  if (!error.value) {
    buildCharts()
  }
})

onUnmounted(() => {
  destroyCharts()
})
</script>

<style scoped>
.statistiques-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #111827;
}

.stats-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  padding: 1.5rem;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.eyebrow {
  margin: 0 0 0.35rem;
  color: #008a9b;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
}

.stats-header h1 {
  margin: 0;
  color: #111827;
  font-size: 1.85rem;
}

.intro {
  margin: 0.35rem 0 0;
  color: #64748b;
}

.loading-panel {
  min-height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  color: #475569;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 1rem;
}

.metric-card {
  min-height: 132px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem 1.1rem;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-left: 4px solid #008a9b;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}

.metric-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.metric-heading .v-icon {
  color: #94a3b8;
  font-size: 1.25rem;
}

.metric-card strong {
  color: #111827;
  font-size: 2.15rem;
  line-height: 1;
  letter-spacing: 0;
}

.metric-label {
  color: #475569;
  font-size: 0.85rem;
  font-weight: 700;
}

.metric-note {
  color: #64748b;
  font-size: 0.82rem;
}

.metric-women {
  border-left-color: #f59e0b;
}

.metric-men {
  border-left-color: #008a9b;
}

.metric-risk {
  border-left-color: #2563eb;
}

.metric-blocked {
  border-left-color: #6d28d9;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.chart-card,
.insight-card {
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05) !important;
}

.chart-title,
.table-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.chart-title h2,
.table-title h2 {
  margin: 0;
  color: #111827;
  font-size: 1.05rem;
  font-weight: 700;
}

.chart-title p,
.table-title p {
  margin: 0.2rem 0 0;
  color: #64748b;
  font-size: 0.85rem;
}

.chart-box {
  height: 320px;
  position: relative;
}

.retirement-toolbar {
  display: flex;
  padding: 0 1rem 1rem;
  overflow-x: auto;
}

.retirement-toolbar .v-btn-toggle {
  flex-wrap: wrap;
  gap: 0.5rem;
}

.mix-cell {
  display: grid;
  grid-template-columns: 58px minmax(90px, 1fr) 58px;
  align-items: center;
  gap: 0.65rem;
  min-width: 220px;
  color: #475569;
  font-size: 0.82rem;
}

@media (max-width: 1200px) {
  .kpi-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .stats-header,
  .charts-grid {
    grid-template-columns: 1fr;
  }

  .stats-header {
    align-items: flex-start;
    flex-direction: column;
  }
}

@media (max-width: 640px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }

  .chart-box {
    height: 280px;
  }

  .mix-cell {
    grid-template-columns: 1fr;
  }
}
</style>
