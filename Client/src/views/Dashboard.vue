<!-- src/views/Dashboard.vue -->
<template>
  <div class="dashboard-page">
    <section class="dashboard-header">
      <div>
        <p class="dashboard-kicker">Synthèse RH</p>
        <h1>Tableau de bord</h1>
        <p>Vue d'ensemble des effectifs, postes à pourvoir et mouvements prévisionnels.</p>
      </div>
      <v-btn
        color="primary"
        variant="flat"
        prepend-icon="mdi-refresh"
        :loading="loading"
        @click="fetchKPI"
      >
        Actualiser
      </v-btn>
    </section>

    <div v-if="loading" class="state-card">
      <v-progress-circular indeterminate color="primary" size="42" />
      <span>Chargement des indicateurs...</span>
    </div>

    <v-alert v-else-if="error" type="error" variant="tonal">
      {{ error }}
    </v-alert>

    <template v-else>
      <section class="metrics-grid">
        <article class="metric-card">
          <div class="metric-top">
            <span>Effectif total</span>
            <v-icon color="primary">mdi-account-group-outline</v-icon>
          </div>
          <strong>{{ kpi.total_agents }}</strong>
          <p>Agents actifs dans le périmètre RH.</p>
        </article>

        <article class="metric-card">
          <div class="metric-top">
            <span>Postes vacants</span>
            <v-icon color="warning">mdi-briefcase-outline</v-icon>
          </div>
          <strong>{{ kpi.postes_vacants }}</strong>
          <p>Taux de vacance : {{ kpi.taux_vacants }}%.</p>
        </article>

        <article class="metric-card">
          <div class="metric-top">
            <span>Départs retraite</span>
            <v-icon color="secondary">mdi-calendar-clock-outline</v-icon>
          </div>
          <strong>{{ kpi.departs_retraite_5ans }}</strong>
          <p>Agents concernés dans les 5 prochaines années.</p>
        </article>
      </section>

      <section class="dashboard-grid">
        <v-card class="rounded-lg chart-card main-chart" elevation="2">
          <v-card-title class="panel-title">
            <div>
              <h2>Projection des effectifs</h2>
              <p>Tendance indicative basée sur l'effectif actuel.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="trendCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="rounded-lg chart-card" elevation="2">
          <v-card-title class="panel-title">
            <div>
              <h2>Capacité des postes</h2>
              <p>Postes occupés estimés et postes vacants.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="chart-box">
              <canvas ref="capacityCanvas"></canvas>
            </div>
          </v-card-text>
        </v-card>
      </section>

      <section class="operations-grid">
        <v-card class="rounded-lg" elevation="2">
          <v-card-title class="panel-title">
            <div>
              <h2>Suivi opérationnel</h2>
              <p>Indicateurs à garder sous surveillance.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="operation-list">
              <div class="operation-row">
                <span>Postes à pourvoir</span>
                <strong>{{ kpi.postes_vacants }}</strong>
              </div>
              <div class="operation-row">
                <span>Départs retraite sous 12 mois</span>
                <strong>{{ kpi.departs_retraite_12mois }}</strong>
              </div>
              <div class="operation-row">
                <span>Taux de vacance</span>
                <strong>{{ kpi.taux_vacants }}%</strong>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="rounded-lg" elevation="2">
          <v-card-title class="panel-title">
            <div>
              <h2>Accès rapides</h2>
              <p>Raccourcis vers les vues de pilotage.</p>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="quick-links">
              <router-link to="/agents" class="quick-link">
                <v-icon>mdi-account-multiple-outline</v-icon>
                <span>Agents</span>
              </router-link>
              <router-link to="/postes" class="quick-link">
                <v-icon>mdi-briefcase-outline</v-icon>
                <span>Postes</span>
              </router-link>
              <router-link to="/postes-vacants" class="quick-link">
                <v-icon>mdi-briefcase-search-outline</v-icon>
                <span>Postes vacants</span>
              </router-link>
              <router-link to="/statistiques" class="quick-link">
                <v-icon>mdi-chart-box-outline</v-icon>
                <span>Statistiques</span>
              </router-link>
            </div>
          </v-card-text>
        </v-card>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, watch, ref } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { storeToRefs } from 'pinia'
import Chart from 'chart.js/auto'

const dashboardStore = useDashboardStore()
const { kpi, loading, error } = storeToRefs(dashboardStore)
const { fetchKPI } = dashboardStore

const trendCanvas = ref(null)
const capacityCanvas = ref(null)
let trendChart = null
let capacityChart = null

const occupiedPosts = computed(() => Math.max(Number(kpi.value.total_agents || 0) - Number(kpi.value.postes_vacants || 0), 0))

const trendData = computed(() => {
  const total = Number(kpi.value.total_agents || 0)
  const retirements = Number(kpi.value.departs_retraite_5ans || 0)
  const annualImpact = Math.ceil(retirements / 5)

  return {
    labels: ['Actuel', '+1 an', '+2 ans', '+3 ans', '+4 ans', '+5 ans'],
    datasets: [
      {
        label: 'Effectif projeté',
        data: [
          total,
          Math.max(total - annualImpact, 0),
          Math.max(total - annualImpact * 2, 0),
          Math.max(total - annualImpact * 3, 0),
          Math.max(total - annualImpact * 4, 0),
          Math.max(total - annualImpact * 5, 0)
        ],
        borderColor: '#008a9b',
        backgroundColor: 'rgba(0, 138, 155, 0.14)',
        fill: true,
        tension: 0.3,
        pointRadius: 4,
        pointBackgroundColor: '#008a9b'
      }
    ]
  }
})

const capacityData = computed(() => ({
  labels: ['Postes occupés', 'Postes vacants'],
  datasets: [
    {
      data: [occupiedPosts.value, Number(kpi.value.postes_vacants || 0)],
      backgroundColor: ['#008a9b', '#f59e0b'],
      borderWidth: 0,
      hoverOffset: 6
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        boxWidth: 12,
        padding: 12
      }
    }
  }
}

const destroyCharts = () => {
  trendChart?.destroy()
  capacityChart?.destroy()
  trendChart = null
  capacityChart = null
}

const buildCharts = async () => {
  await nextTick()
  destroyCharts()

  if (trendCanvas.value) {
    trendChart = new Chart(trendCanvas.value, {
      type: 'line',
      data: trendData.value,
      options: {
        ...chartOptions,
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    })
  }

  if (capacityCanvas.value) {
    capacityChart = new Chart(capacityCanvas.value, {
      type: 'doughnut',
      data: capacityData.value,
      options: {
        ...chartOptions,
        cutout: '68%'
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
  await fetchKPI()
  if (!error.value) {
    buildCharts()
  }
})

onUnmounted(() => {
  destroyCharts()
})
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 1.35rem 1.5rem;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

.dashboard-kicker {
  margin: 0 0 0.35rem;
  color: #008a9b;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
}

.dashboard-header h1 {
  margin: 0;
  color: #111827;
  font-size: 1.75rem;
}

.dashboard-header p:last-child {
  margin: 0.35rem 0 0;
  color: #64748b;
}

.state-card {
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

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.metric-card {
  min-height: 150px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.15rem;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-top: 4px solid #008a9b;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

.metric-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  color: #475569;
  font-weight: 700;
}

.metric-card strong {
  color: #111827;
  font-size: 2.25rem;
  line-height: 1;
}

.metric-card p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(320px, 0.85fr);
  gap: 1rem;
}

.chart-card {
  min-height: 390px;
}

.panel-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-title h2 {
  margin: 0;
  color: #111827;
  font-size: 1rem;
}

.panel-title p {
  margin: 0.2rem 0 0;
  color: #64748b;
  font-size: 0.86rem;
}

.chart-box {
  height: 290px;
  position: relative;
}

.operations-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 1rem;
}

.operation-list {
  display: grid;
  gap: 0.75rem;
}

.operation-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 0;
  border-bottom: 1px solid #eef2f7;
}

.operation-row:last-child {
  border-bottom: 0;
}

.operation-row span {
  color: #475569;
}

.operation-row strong {
  color: #111827;
  font-size: 1.1rem;
}

.quick-links {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.quick-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-height: 52px;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  color: #111827;
  font-weight: 600;
  text-decoration: none;
  background: #ffffff;
}

.quick-link:hover {
  border-color: #008a9b;
  color: #008a9b;
  text-decoration: none;
}

@media (max-width: 1100px) {
  .dashboard-grid,
  .operations-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .dashboard-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .metrics-grid,
  .quick-links {
    grid-template-columns: 1fr;
  }

  .chart-box {
    height: 260px;
  }
}
</style>
