<template>
  <v-container fluid class="statistiques-view">
    <section class="stats-header">
      <div>
        <p class="eyebrow">Analyse RH</p>
        <h1>Statistiques du personnel</h1>
        <p class="intro">
          Vue consolidée des effectifs, équilibres H/F, départs retraite et alertes de plafonnement.
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
          <span class="metric-label">Effectif total</span>
          <strong>{{ repartitionHF.total }}</strong>
          <span class="metric-note">Agents recensés</span>
        </article>
        <article class="metric-card metric-women">
          <span class="metric-label">Femmes</span>
          <strong>{{ repartitionHF.femmes.nombre }}</strong>
          <span class="metric-note">{{ repartitionHF.femmes.pourcentage }}% de l'effectif</span>
        </article>
        <article class="metric-card metric-men">
          <span class="metric-label">Hommes</span>
          <strong>{{ repartitionHF.hommes.nombre }}</strong>
          <span class="metric-note">{{ repartitionHF.hommes.pourcentage }}% de l'effectif</span>
        </article>
        <article class="metric-card metric-risk">
          <span class="metric-label">Départs sous 5 ans</span>
          <strong>{{ retirementsWithinFiveYears }}</strong>
          <span class="metric-note">{{ retirementRate }}% de l'effectif</span>
        </article>
        <!-- <article class="metric-card metric-alert">
          <span class="metric-label">Anomalies tube</span>
          <strong>{{ plafonnementAnomalies.length }}</strong>
          <span class="metric-note">GF hors plage poste</span>
        </article> -->
        <article class="metric-card metric-blocked">
          <span class="metric-label">Agents bloqués</span>
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
              <h2>Pyramide des âges</h2>
              <p>Répartition par tranche et par sexe.</p>
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
              <h2>Services</h2>
              <p>Effectifs et équilibre H/F par unité.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="serviceCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>
      </section>

      <section class="tables-grid">
        <v-card class="rounded-lg" elevation="2">
          <v-card-title class="table-title">
            <div>
              <h2>Départs retraite prioritaires</h2>
              <p>Agents à surveiller dans les 12 prochains mois.</p>
            </div>
            <v-chip color="warning" variant="tonal" size="small">
              {{ urgentRetirements.length }}
            </v-chip>
          </v-card-title>
          <v-data-table
            :headers="retirementHeaders"
            :items="urgentRetirements"
            :items-per-page="5"
            density="comfortable"
            no-data-text="Aucun départ retraite à moins d'un an"
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
          </v-data-table>
        </v-card>

        <v-card class="rounded-lg service-table" elevation="2">
          <v-card-title class="table-title">
            <div>
              <h2>Répartition par service</h2>
              <p>Détail des volumes et pourcentages H/F.</p>
            </div>
          </v-card-title>
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
        </v-card>

        <v-card class="rounded-lg" elevation="2">
          <v-card-title class="table-title">
            <div>
              <h2>Agents bloqués</h2>
              <p>Au plafond sans promotion depuis au moins 3 ans.</p>
            </div>
            <v-chip color="secondary" variant="tonal" size="small">
              {{ agentsBloques.length }}
            </v-chip>
          </v-card-title>
          <v-data-table
            :headers="blockedHeaders"
            :items="agentsBloques"
            :items-per-page="5"
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

let genderChart = null
let ageChart = null
let retirementChart = null
let serviceChart = null

const retirementHeaders = [
  { title: 'Matricule', key: 'matricule' },
  { title: 'Agent', key: 'nom_complet' },
  { title: 'Poste', key: 'poste' },
  { title: 'Age', key: 'age' }
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

const urgentRetirements = computed(() => departsRetraite.value.liste?.moins_1_an || [])

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
        padding: 14
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
    ageChart = new Chart(ageCanvas.value, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Hommes',
            data: labels.map(label => pyramideAges.value[label]?.hommes || 0),
            backgroundColor: '#008a9b'
          },
          {
            label: 'Femmes',
            data: labels.map(label => pyramideAges.value[label]?.femmes || 0),
            backgroundColor: '#f59e0b'
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
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
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
  min-height: 126px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
  border-radius: 8px;
  background: #ffffff;
  border-top: 4px solid #008a9b;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

.metric-card strong {
  color: #111827;
  font-size: 2rem;
  line-height: 1;
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
  border-top-color: #f59e0b;
}

.metric-men {
  border-top-color: #008a9b;
}

.metric-risk {
  border-top-color: #2563eb;
}

.metric-alert {
  border-top-color: #dc2626;
}

.metric-blocked {
  border-top-color: #6d28d9;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
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
  font-size: 1rem;
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

.tables-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.service-table {
  grid-column: span 2;
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
  .charts-grid,
  .tables-grid {
    grid-template-columns: 1fr;
  }

  .stats-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .service-table {
    grid-column: span 1;
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
