<template>
  <div class="analytique">

    <!-- ══════════ EN-TÊTE ══════════ -->
    <div class="ana-header">
      <div class="ana-header-left">
        <h1 class="ana-title">
          <i class="fas fa-chart-line"></i>
          Tableau de Bord Analytique
        </h1>
        <p class="ana-subtitle">Analyse des congés et absences · {{ annee }}</p>
      </div>
      <div class="ana-controls">
        <select v-if="peutFiltrerDept" v-model="filtreDepId" class="ana-select" @change="charger">
          <option value="">Tous les départements</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <select v-model="annee" class="ana-select" @change="charger">
          <option v-for="a in annees" :key="a" :value="a">{{ a }}</option>
        </select>
        <button class="btn-export" @click="exporterPDF">
          <i class="fas fa-file-pdf"></i> Exporter PDF
        </button>
      </div>
    </div>

    <!-- ══════════ CHARGEMENT ══════════ -->
    <div v-if="loading" class="ana-loading">
      <div class="spinner"></div>
      <p>Chargement des données…</p>
    </div>

    <div v-else-if="erreur" class="ana-error">
      <i class="fas fa-exclamation-triangle"></i>
      <p>{{ erreur }}</p>
      <button @click="charger" class="btn-retry">Réessayer</button>
    </div>

    <template v-else>

      <!-- ══════════ KPIs ══════════ -->
      <div class="kpi-grid">
        <div class="kpi-card" v-for="kpi in kpiCards" :key="kpi.label">
          <div class="kpi-icon" :style="{ background: kpi.gradient }">
            <i :class="['fas', kpi.icon]"></i>
          </div>
          <div class="kpi-body">
            <span class="kpi-value">{{ kpi.value }}</span>
            <span class="kpi-label">{{ kpi.label }}</span>
          </div>
          <div class="kpi-badge" :style="{ color: kpi.color }">{{ kpi.badge }}</div>
        </div>
      </div>

      <!-- ══════════ GRAPHIQUES LIGNE 1 ══════════ -->
      <div class="charts-row">

        <!-- Barres : demandes par mois -->
        <div class="chart-card chart-large">
          <div class="chart-header">
            <h3><i class="fas fa-chart-bar"></i> Demandes par mois</h3>
            <span class="chart-year">{{ annee }}</span>
          </div>
          <div class="chart-wrap">
            <canvas ref="barChart"></canvas>
          </div>
        </div>

        <!-- Camembert : par type -->
        <div class="chart-card chart-small">
          <div class="chart-header">
            <h3><i class="fas fa-chart-pie"></i> Par type de congé</h3>
          </div>
          <div class="chart-wrap chart-wrap-pie">
            <canvas ref="pieChart"></canvas>
          </div>
        </div>
      </div>

      <!-- ══════════ GRAPHIQUES LIGNE 2 ══════════ -->
      <div class="charts-row">

        <!-- Courbe : tendance 6 mois -->
        <div class="chart-card chart-medium">
          <div class="chart-header">
            <h3><i class="fas fa-chart-line"></i> Tendance approbations (6 mois)</h3>
          </div>
          <div class="chart-wrap">
            <canvas ref="lineChart"></canvas>
          </div>
        </div>

        <!-- Barres horizontales : par département -->
        <div class="chart-card chart-medium">
          <div class="chart-header">
            <h3><i class="fas fa-building"></i> Jours moyens par département</h3>
          </div>
          <div class="chart-wrap">
            <canvas ref="deptChart"></canvas>
          </div>
        </div>
      </div>

      <!-- ══════════ TABLEAUX ══════════ -->
      <div class="tables-row">

        <!-- Top employés -->
        <div class="table-card">
          <div class="table-header">
            <h3><i class="fas fa-trophy"></i> Top 5 — Jours de congés pris</h3>
          </div>
          <table class="ana-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Employé</th>
                <th>Département</th>
                <th>Demandes</th>
                <th>Jours pris</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(emp, i) in topEmployes" :key="i">
                <td>
                  <span class="rank" :class="'rank-' + (i + 1)">{{ i + 1 }}</span>
                </td>
                <td class="emp-name">{{ emp.nom }}</td>
                <td><span class="dept-chip">{{ emp.departement }}</span></td>
                <td class="center">{{ emp.nb_demandes }}</td>
                <td>
                  <div class="jours-bar-wrap">
                    <div class="jours-bar" :style="{ width: barPct(emp.total_jours) + '%' }"></div>
                    <span class="jours-val">{{ emp.total_jours }}j</span>
                  </div>
                </td>
              </tr>
              <tr v-if="topEmployes.length === 0">
                <td colspan="5" class="empty-row">Aucune donnée</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Stats par département -->
        <div class="table-card">
          <div class="table-header">
            <h3><i class="fas fa-sitemap"></i> Synthèse par département</h3>
          </div>
          <table class="ana-table">
            <thead>
              <tr>
                <th>Département</th>
                <th>Employés</th>
                <th>Demandes</th>
                <th>Approuvées</th>
                <th>Moy. jours</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dept in parDept" :key="dept.department">
                <td class="emp-name">{{ dept.department }}</td>
                <td class="center">{{ dept.nb_employes }}</td>
                <td class="center">{{ dept.total }}</td>
                <td class="center">
                  <span class="badge-appro">{{ dept.approuves }}</span>
                </td>
                <td class="center">
                  <span class="moy-chip">{{ dept.moy_jours }}j</span>
                </td>
              </tr>
              <tr v-if="parDept.length === 0">
                <td colspan="5" class="empty-row">Aucune donnée</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>
  </div>
</template>

<script>
import { departmentsApi } from '@/services/api';
import apiClient from '@/services/api';
import {
  Chart,
  BarController, BarElement,
  LineController, LineElement, PointElement,
  PieController, ArcElement,
  CategoryScale, LinearScale,
  Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(
  BarController, BarElement,
  LineController, LineElement, PointElement,
  PieController, ArcElement,
  CategoryScale, LinearScale,
  Tooltip, Legend, Filler
);

const PRIMARY   = '#008a9b';
const ACCENT    = '#261555';
const COLORS    = ['#008a9b','#261555','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
const PIE_COLORS= ['#dcfce7','#fee2e2','#fce7f3','#ede9fe','#fff7ed','#e0f2fe','#faf5ff'];
const PIE_BORDER= ['#16a34a','#dc2626','#db2777','#7c3aed','#ea580c','#0284c7','#9333ea'];

export default {
  name: 'AnalytiqueView',

  data() {
    const currentYear = new Date().getFullYear();
    return {
      annee: currentYear,
      annees: [currentYear, currentYear - 1, currentYear - 2],
      filtreDepId: '',
      departments: [],
      loading: false,
      erreur: null,

      // données
      kpis: {},
      parMois: [],
      parType: [],
      parDept: [],
      topEmployes: [],
      tendance: [],

      // instances Chart.js
      _barChart:  null,
      _pieChart:  null,
      _lineChart: null,
      _deptChart: null,
    };
  },

  computed: {
    peutFiltrerDept() {
      const p = this.$route.path;
      return p.startsWith('/directeur-rh') || p.startsWith('/admin');
    },

    kpiCards() {
      return [
        {
          label: 'Total demandes',
          value: this.kpis.total ?? 0,
          badge: this.annee,
          icon: 'fa-file-alt',
          gradient: 'linear-gradient(135deg,#008a9b,#006d7a)',
          color: '#008a9b',
        },
        {
          label: 'Approuvées',
          value: this.kpis.approuves ?? 0,
          badge: (this.kpis.tauxAppro ?? 0) + '%',
          icon: 'fa-check-circle',
          gradient: 'linear-gradient(135deg,#10b981,#059669)',
          color: '#10b981',
        },
        {
          label: 'En attente',
          value: this.kpis.enAttente ?? 0,
          badge: 'à traiter',
          icon: 'fa-clock',
          gradient: 'linear-gradient(135deg,#f59e0b,#d97706)',
          color: '#f59e0b',
        },
        {
          label: 'Jours consommés',
          value: this.kpis.joursPris ?? 0,
          badge: 'jours',
          icon: 'fa-calendar-check',
          gradient: 'linear-gradient(135deg,#261555,#4c1d95)',
          color: '#261555',
        },
        {
          label: 'Rejetées',
          value: this.kpis.rejetes ?? 0,
          badge: 'demandes',
          icon: 'fa-times-circle',
          gradient: 'linear-gradient(135deg,#ef4444,#dc2626)',
          color: '#ef4444',
        },
      ];
    },
  },

  async mounted() {
    if (this.peutFiltrerDept) {
      const res = await departmentsApi.list();
      if (res.data.success) this.departments = res.data.data;
    }
    await this.charger();
  },

  beforeUnmount() {
    this.detruireCharts();
  },

  methods: {
    async charger() {
      this.loading = true;
      this.erreur  = null;
      this.detruireCharts();
      try {
        const params = { annee: this.annee };
        if (this.filtreDepId) params.department_id = this.filtreDepId;

        const res = await apiClient.get('/analytique', { params });
        if (res.data.success) {
          const d = res.data.data;
          this.kpis        = d.kpis;
          this.parMois     = d.par_mois;
          this.parType     = d.par_type;
          this.parDept     = d.par_dept;
          this.topEmployes = d.top_employes;
          this.tendance    = d.tendance;
        }
      } catch (e) {
        this.erreur = 'Impossible de charger les données analytiques.';
        console.error(e);
      } finally {
        this.loading = false;
        this.$nextTick(this.construireCharts);
      }
    },

    construireCharts() {
      this.buildBarChart();
      this.buildPieChart();
      this.buildLineChart();
      this.buildDeptChart();
    },

    detruireCharts() {
      ['_barChart','_pieChart','_lineChart','_deptChart'].forEach(k => {
        if (this[k]) { this[k].destroy(); this[k] = null; }
      });
    },

    buildBarChart() {
      const ctx = this.$refs.barChart;
      if (!ctx) return;
      this._barChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: this.parMois.map(m => m.mois),
          datasets: [
            {
              label: 'Total',
              data: this.parMois.map(m => m.total),
              backgroundColor: 'rgba(0,138,155,0.15)',
              borderColor: PRIMARY,
              borderWidth: 2,
              borderRadius: 6,
            },
            {
              label: 'Approuvées',
              data: this.parMois.map(m => m.approuves),
              backgroundColor: 'rgba(16,185,129,0.7)',
              borderColor: '#059669',
              borderWidth: 2,
              borderRadius: 6,
            },
            {
              label: 'Rejetées',
              data: this.parMois.map(m => m.rejetes),
              backgroundColor: 'rgba(239,68,68,0.7)',
              borderColor: '#dc2626',
              borderWidth: 2,
              borderRadius: 6,
            },
          ],
        },
        options: this.baseOptions('Nombre de demandes'),
      });
    },

    buildPieChart() {
      const ctx = this.$refs.pieChart;
      if (!ctx || this.parType.length === 0) return;
      this._pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: this.parType.map(t => t.label),
          datasets: [{
            data: this.parType.map(t => t.total),
            backgroundColor: PIE_COLORS.slice(0, this.parType.length),
            borderColor:     PIE_BORDER.slice(0, this.parType.length),
            borderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } },
            tooltip: { callbacks: {
              label: ctx => ` ${ctx.label} : ${ctx.parsed} demande(s)`,
            }},
          },
        },
      });
    },

    buildLineChart() {
      const ctx = this.$refs.lineChart;
      if (!ctx) return;
      this._lineChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: this.tendance.map(t => t.mois),
          datasets: [{
            label: 'Congés approuvés',
            data: this.tendance.map(t => t.total),
            borderColor: PRIMARY,
            backgroundColor: 'rgba(0,138,155,0.1)',
            borderWidth: 3,
            pointBackgroundColor: PRIMARY,
            pointRadius: 5,
            tension: 0.4,
            fill: true,
          }],
        },
        options: this.baseOptions('Congés approuvés'),
      });
    },

    buildDeptChart() {
      const ctx = this.$refs.deptChart;
      if (!ctx || this.parDept.length === 0) return;
      this._deptChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: this.parDept.map(d => d.department),
          datasets: [{
            label: 'Jours moyens',
            data: this.parDept.map(d => d.moy_jours),
            backgroundColor: COLORS.slice(0, this.parDept.length).map(c => c + 'cc'),
            borderColor:     COLORS.slice(0, this.parDept.length),
            borderWidth: 2,
            borderRadius: 6,
          }],
        },
        options: {
          ...this.baseOptions('Jours moyens'),
          indexAxis: 'y',
        },
      });
    },

    baseOptions(yLabel) {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { labels: { font: { size: 12 } } },
          tooltip: { mode: 'index', intersect: false },
        },
        scales: {
          x: { grid: { color: 'rgba(0,0,0,0.05)' } },
          y: {
            grid: { color: 'rgba(0,0,0,0.05)' },
            title: { display: false },
            beginAtZero: true,
          },
        },
      };
    },

    barPct(val) {
      const max = Math.max(...this.topEmployes.map(e => e.total_jours), 1);
      return Math.round((val / max) * 100);
    },

    exporterPDF() {
      window.print();
    },
  },
};
</script>

<style scoped>
/* ══════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════ */
.analytique {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
  font-family: 'Inter', 'Segoe UI', sans-serif;
  color: #1e293b;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  min-height: 100vh;
}

/* ══════════════════════════════════════════
   EN-TÊTE
══════════════════════════════════════════ */
.ana-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  background: white;
  border-radius: 20px;
  padding: 1.5rem 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.08);
  border-left: 5px solid #008a9b;
}

.ana-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #261555;
  margin: 0 0 .25rem;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.ana-title i { color: #008a9b; }
.ana-subtitle { color: #64748b; font-size: .95rem; margin: 0; }

.ana-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.ana-select {
  padding: .5rem 1rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: .875rem;
  color: #1e293b;
  background: white;
  cursor: pointer;
}
.ana-select:focus { outline: none; border-color: #008a9b; }

.btn-export {
  padding: .5rem 1.2rem;
  background: linear-gradient(135deg, #261555, #4c1d95);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: .5rem;
  transition: all .2s;
}
.btn-export:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(38,21,85,.3); }

/* ══════════════════════════════════════════
   KPIs
══════════════════════════════════════════ */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.kpi-card {
  background: white;
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0,0,0,.07);
  border: 1px solid #f1f5f9;
  transition: transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.1); }

.kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.kpi-body {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.kpi-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}
.kpi-label {
  font-size: .8rem;
  color: #64748b;
  margin-top: .25rem;
}
.kpi-badge {
  font-size: .75rem;
  font-weight: 700;
  background: #f8fafc;
  padding: .2rem .5rem;
  border-radius: 6px;
  white-space: nowrap;
}

/* ══════════════════════════════════════════
   GRAPHIQUES
══════════════════════════════════════════ */
.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.charts-row:last-of-type {
  grid-template-columns: 1fr 1fr;
}

.chart-card {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.08);
  border: 1px solid #f1f5f9;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.chart-header h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #261555;
  margin: 0;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.chart-header h3 i { color: #008a9b; }
.chart-year {
  font-size: .8rem;
  color: #94a3b8;
  background: #f8fafc;
  padding: .2rem .6rem;
  border-radius: 6px;
}

.chart-wrap { height: 260px; position: relative; }
.chart-wrap-pie { height: 240px; }

/* ══════════════════════════════════════════
   TABLEAUX
══════════════════════════════════════════ */
.tables-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.table-card {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.08);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.table-header {
  margin-bottom: 1.25rem;
}
.table-header h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #261555;
  margin: 0;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.table-header h3 i { color: #008a9b; }

.ana-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .875rem;
}
.ana-table th {
  text-align: left;
  padding: .6rem .75rem;
  background: #f8fafc;
  color: #475569;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  border-bottom: 2px solid #e2e8f0;
}
.ana-table td {
  padding: .75rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.ana-table tr:last-child td { border-bottom: none; }
.ana-table tr:hover td { background: #f8fafc; }

.center { text-align: center; }
.emp-name { font-weight: 600; color: #1e293b; }

.rank {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  font-weight: 700;
  font-size: .8rem;
  background: #f1f5f9;
  color: #64748b;
}
.rank-1 { background: #fef9c3; color: #ca8a04; }
.rank-2 { background: #f1f5f9; color: #475569; }
.rank-3 { background: #fff7ed; color: #ea580c; }

.dept-chip {
  background: rgba(0,138,155,.1);
  color: #008a9b;
  padding: .2rem .6rem;
  border-radius: 6px;
  font-size: .78rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge-appro {
  background: #dcfce7;
  color: #16a34a;
  padding: .2rem .6rem;
  border-radius: 6px;
  font-weight: 700;
  font-size: .8rem;
}

.moy-chip {
  background: rgba(38,21,85,.08);
  color: #261555;
  padding: .2rem .6rem;
  border-radius: 6px;
  font-weight: 700;
  font-size: .8rem;
}

.jours-bar-wrap {
  display: flex;
  align-items: center;
  gap: .5rem;
}
.jours-bar {
  height: 8px;
  background: linear-gradient(90deg, #008a9b, #261555);
  border-radius: 4px;
  flex: 1;
  max-width: 120px;
  transition: width .3s;
}
.jours-val {
  font-weight: 700;
  color: #008a9b;
  font-size: .85rem;
  white-space: nowrap;
}

.empty-row {
  text-align: center;
  color: #94a3b8;
  padding: 2rem !important;
  font-style: italic;
}

/* ══════════════════════════════════════════
   CHARGEMENT / ERREUR
══════════════════════════════════════════ */
.ana-loading, .ana-error {
  text-align: center;
  padding: 4rem;
  color: #64748b;
}
.spinner {
  width: 48px; height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #008a9b;
  border-radius: 50%;
  animation: spin .8s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }
.ana-error i { font-size: 2.5rem; color: #ef4444; display: block; margin-bottom: .75rem; }
.btn-retry {
  margin-top: 1rem; padding: .5rem 1.5rem;
  background: #008a9b; color: white;
  border: none; border-radius: 8px; cursor: pointer;
}

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media (max-width: 1024px) {
  .charts-row,
  .charts-row:last-of-type,
  .tables-row { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
  .analytique { padding: 1rem; }
  .ana-header { flex-direction: column; align-items: flex-start; }
  .kpi-grid { grid-template-columns: 1fr 1fr; }
}

/* ══════════════════════════════════════════
   IMPRESSION PDF
══════════════════════════════════════════ */
@media print {
  .ana-controls, .btn-export { display: none; }
  .analytique { padding: 0; background: white; }
  .chart-card, .table-card, .kpi-card { box-shadow: none; border: 1px solid #e2e8f0; }
  .charts-row, .tables-row { grid-template-columns: 1fr 1fr; }
}
</style>