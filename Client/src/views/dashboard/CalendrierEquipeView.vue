<template>
  <div class="calendrier-equipe">

    <!-- ═══════════════════ EN-TÊTE ═══════════════════ -->
    <div class="cal-header">
      <div class="cal-header-left">
        <h1 class="cal-title">
          <i class="fas fa-calendar-alt"></i>
          Calendrier des Congés
        </h1>
        <p class="cal-subtitle">Vue mensuelle par équipe et département</p>
      </div>

      <!-- Navigation mois + filtres -->
      <div class="cal-controls">
        <!-- Filtre département (Directeur RH / Admin uniquement) -->
        <select
          v-if="peutFiltrerDept"
          v-model="filtreDepId"
          class="cal-select"
          @change="charger"
        >
          <option value="">Tous les départements</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>

        <!-- Filtre type de congé -->
        <select v-model="filtreType" class="cal-select" @change="appliquerFiltres">
          <option value="">Tous les types</option>
          <option value="conge_annuel">Congé annuel</option>
          <option value="conge_maladie">Congé maladie</option>
          <option value="conge_maternite">Congé maternité</option>
          <option value="conge_paternite">Congé paternité</option>
          <option value="conge_sans_solde">Congé sans solde</option>
          <option value="absence_exceptionnelle">Absence exceptionnelle</option>
          <option value="report_conge">Report de congé</option>
        </select>

        <!-- Navigation mois -->
        <div class="cal-nav">
          <button class="nav-btn" @click="changerMois(-1)">
            <i class="fas fa-chevron-left"></i>
          </button>
          <span class="nav-label">{{ meta.nom_mois }}</span>
          <button class="nav-btn" @click="changerMois(1)">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>

        <!-- Bouton aujourd'hui -->
        <button class="btn-today" @click="allerAujourdhui">
          Aujourd'hui
        </button>
      </div>
    </div>

    <!-- ═══════════════════ LÉGENDE ═══════════════════ -->
    <div class="cal-legend">
      <span v-for="(color, type) in typeColors" :key="type" class="legend-item">
        <span class="legend-dot" :style="{ background: color.bg, border: '2px solid ' + color.border }"></span>
        {{ typesLabels[type] }}
      </span>
      <span class="legend-item">
        <span class="legend-dot" style="background:#fef9c3;border:2px solid #ca8a04;"></span>
        En attente
      </span>
    </div>

    <!-- ═══════════════════ CHARGEMENT ═══════════════════ -->
    <div v-if="loading" class="cal-loading">
      <div class="spinner"></div>
      <p>Chargement du calendrier…</p>
    </div>

    <!-- ═══════════════════ ERREUR ═══════════════════ -->
    <div v-else-if="erreur" class="cal-error">
      <i class="fas fa-exclamation-triangle"></i>
      <p>{{ erreur }}</p>
      <button class="btn-retry" @click="charger">Réessayer</button>
    </div>

    <!-- ═══════════════════ CONTENU ═══════════════════ -->
    <div v-else>

      <!-- Résumé rapide -->
      <div class="cal-summary">
        <div class="summary-card">
          <span class="summary-value">{{ totalDemandes }}</span>
          <span class="summary-label">Demandes ce mois</span>
        </div>
        <div class="summary-card">
          <span class="summary-value">{{ totalApprouves }}</span>
          <span class="summary-label">Approuvées</span>
        </div>
        <div class="summary-card">
          <span class="summary-value">{{ totalAttente }}</span>
          <span class="summary-label">En attente</span>
        </div>
        <div class="summary-card">
          <span class="summary-value">{{ nbDepartements }}</span>
          <span class="summary-label">Département(s)</span>
        </div>
      </div>

      <!-- ─── Vue par département ─── -->
      <div
        v-for="dept in parDepartementFiltre"
        :key="dept.department_id"
        class="dept-block"
      >
        <!-- En-tête département -->
        <div class="dept-header" @click="toggleDept(dept.department_id)">
          <div class="dept-header-left">
            <div class="dept-icon">
              <i class="fas fa-building"></i>
            </div>
            <div>
              <h2 class="dept-name">{{ dept.department_name }}</h2>
              <span class="dept-count">
                {{ dept.employes.length }} employé(s) ·
                {{ dept.demandeCount }} demande(s)
              </span>
            </div>
          </div>
          <i :class="['fas', deptOuvert[dept.department_id] ? 'fa-chevron-up' : 'fa-chevron-down', 'dept-toggle']"></i>
        </div>

        <!-- Calendrier Gantt du département -->
        <div v-if="deptOuvert[dept.department_id]" class="gantt-wrapper">

          <!-- En-tête des jours -->
          <div class="gantt-grid" :style="gridStyle">

            <!-- Colonne nom fixe -->
            <div class="gantt-name-header">Employé</div>

            <!-- Numéros de jours -->
            <div
              v-for="jour in meta.nb_jours"
              :key="jour"
              class="gantt-day-header"
              :class="{
                'gantt-day-weekend': isWeekend(jour),
                'gantt-day-today': isToday(jour),
              }"
            >
              {{ jour }}
            </div>

            <!-- ─── Ligne par employé ─── -->
            <template v-for="employe in dept.employes" :key="employe.user_id">

              <!-- Nom de l'employé -->
              <div class="gantt-name-cell">
                <div class="employe-avatar">{{ initiales(employe.full_name) }}</div>
                <div class="employe-info">
                  <span class="employe-nom">{{ employe.full_name }}</span>
                  <span class="employe-role">{{ employe.role }}</span>
                </div>
              </div>

              <!-- Cases de jours -->
              <div
                v-for="jour in meta.nb_jours"
                :key="jour"
                class="gantt-cell"
                :class="{
                  'gantt-cell-weekend': isWeekend(jour),
                  'gantt-cell-today': isToday(jour),
                }"
              >
                <!-- Segments de congé pour ce jour -->
                <template v-for="conge in congesDuJour(employe, jour)" :key="conge.id">
                  <div
                    class="conge-segment"
                    :class="[
                      'conge-' + conge.type,
                      conge.statut === 'en_attente' ? 'conge-attente' : '',
                      conge.startsByDay[jour] ? 'conge-debut' : '',
                      conge.endsByDay[jour] ? 'conge-fin' : '',
                    ]"
                    :style="segmentStyle(conge)"
                    :title="tooltipConge(conge)"
                    @click="ouvrirDetail(conge, employe)"
                  ></div>
                </template>
              </div>

            </template>
          </div>
        </div>
      </div>

      <!-- État vide -->
      <div v-if="parDepartementFiltre.length === 0" class="cal-empty">
        <div class="empty-icon-wrap">
          <i class="fas fa-calendar-times"></i>
        </div>
        <h3>Aucun congé ce mois-ci</h3>
        <p>Aucune demande approuvée ou en attente pour {{ meta.nom_mois }}</p>
      </div>
    </div>

    <!-- ═══════════════════ MODAL DÉTAIL ═══════════════════ -->
    <transition name="modal-fade">
      <div v-if="detailVisible" class="modal-overlay" @click.self="fermerDetail">
        <div class="modal-card">
          <div class="modal-header" :style="{ background: typeColors[detail.conge?.type]?.bg || '#f1f5f9' }">
            <h3 class="modal-title">
              <i class="fas fa-info-circle"></i>
              {{ detail.conge?.type_label }}
            </h3>
            <button class="modal-close" @click="fermerDetail">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body" v-if="detail.conge">
            <div class="modal-row">
              <i class="fas fa-user"></i>
              <span><strong>Employé :</strong> {{ detail.employe?.full_name }}</span>
            </div>
            <div class="modal-row">
              <i class="fas fa-calendar-check"></i>
              <span><strong>Du :</strong> {{ formatDate(detail.conge.date_debut) }}</span>
            </div>
            <div class="modal-row">
              <i class="fas fa-calendar-times"></i>
              <span><strong>Au :</strong> {{ formatDate(detail.conge.date_fin) }}</span>
            </div>
            <div class="modal-row">
              <i class="fas fa-clock"></i>
              <span><strong>Durée :</strong> {{ detail.conge.duree_jours }} jour(s)</span>
            </div>
            <div class="modal-row">
              <i class="fas fa-tag"></i>
              <span>
                <strong>Statut :</strong>
                <span class="modal-badge" :class="'badge-' + detail.conge.statut">
                  {{ detail.conge.statut_label }}
                </span>
              </span>
            </div>
            <div class="modal-row" v-if="detail.conge.motif">
              <i class="fas fa-comment"></i>
              <span><strong>Motif :</strong> {{ detail.conge.motif }}</span>
            </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script>
import { departmentsApi } from '@/services/api';
import apiClient from '@/services/api';

export default {
  name: 'CalendrierEquipeView',

  data() {
    const today = new Date();
    return {
      annee: today.getFullYear(),
      mois: today.getMonth() + 1,
      meta: { nb_jours: 30, nom_mois: '', premier_jour: 1 },
      parDepartement: [],
      totalDemandes: 0,
      weekendDays: [],
      todayDay: null,
      departments: [],
      filtreDepId: '',
      filtreType: '',
      loading: false,
      erreur: null,
      deptOuvert: {},
      detailVisible: false,
      detail: { conge: null, employe: null },

      typesLabels: {
        conge_annuel:           'Congé annuel',
        conge_maladie:          'Congé maladie',
        conge_maternite:        'Congé maternité',
        conge_paternite:        'Congé paternité',
        conge_sans_solde:       'Congé sans solde',
        absence_exceptionnelle: 'Absence exceptionnelle',
        report_conge:           'Report de congé',
      },
      typeColors: {
        conge_annuel:           { bg: '#dcfce7', border: '#16a34a', text: '#166534' },
        conge_maladie:          { bg: '#fee2e2', border: '#dc2626', text: '#991b1b' },
        conge_maternite:        { bg: '#fce7f3', border: '#db2777', text: '#9d174d' },
        conge_paternite:        { bg: '#ede9fe', border: '#7c3aed', text: '#4c1d95' },
        conge_sans_solde:       { bg: '#fff7ed', border: '#ea580c', text: '#9a3412' },
        absence_exceptionnelle: { bg: '#e0f2fe', border: '#0284c7', text: '#0c4a6e' },
        report_conge:           { bg: '#faf5ff', border: '#9333ea', text: '#581c87' },
      },
    };
  },

  computed: {
    peutFiltrerDept() {
      const path = this.$route.path;
      return path.startsWith('/directeur-rh') || path.startsWith('/admin');
    },

    parDepartementFiltre() {
      let data = this.parDepartement;
      if (!this.filtreType) return data;
      return data
        .map(dept => ({
          ...dept,
          employes: dept.employes
            .map(emp => ({
              ...emp,
              conges: emp.conges.filter(c => c.type === this.filtreType),
            }))
            .map(emp => ({
              ...emp,
              congesByDay: this.buildCongesByDay(emp.conges),
            }))
            .filter(emp => emp.conges.length > 0),
        }))
        .filter(dept => dept.employes.length > 0);
    },

    totalApprouves() {
      return this.parDepartementFiltre
        .flatMap(d => d.employes)
        .flatMap(e => e.conges)
        .filter(c => c.statut === 'approuve').length;
    },

    totalAttente() {
      return this.parDepartementFiltre
        .flatMap(d => d.employes)
        .flatMap(e => e.conges)
        .filter(c => c.statut === 'en_attente').length;
    },

    nbDepartements() {
      return this.parDepartementFiltre.length;
    },

    gridStyle() {
      // 1 colonne nom (220px) + nb_jours colonnes de 36px
      return {
        gridTemplateColumns: `220px repeat(${this.meta.nb_jours}, 36px)`,
      };
    },
  },

  async mounted() {
    await Promise.all([
      this.charger(),
      this.chargerDepartements(),
    ]);
  },

  methods: {
    async chargerDepartements() {
      if (!this.peutFiltrerDept || this.departments.length > 0) return;

      const res = await departmentsApi.list();
      if (res.data.success) this.departments = res.data.data;
    },

    async charger() {
      this.loading = true;
      this.erreur = null;
      try {
        const params = { annee: this.annee, mois: this.mois };
        if (this.filtreDepId) params.department_id = this.filtreDepId;

        const res = await apiClient.get('/calendrier-equipe', { params });
        if (res.data.success) {
          const data = res.data.data;
          this.meta            = data.meta;
          this.updateDayCache();
          this.parDepartement  = this.prepareCalendarData(data.par_departement);
          this.totalDemandes   = data.total_demandes;

          // Ouvrir tous les départements par défaut
          const nextOpenState = {};
          this.parDepartement.forEach((d, index) => {
            const wasOpen = this.deptOuvert[d.department_id];
            nextOpenState[d.department_id] = this.filtreDepId ? true : (wasOpen ?? index === 0);
          });
          this.deptOuvert = nextOpenState;
        }
      } catch (e) {
        this.erreur = 'Impossible de charger le calendrier. Vérifiez votre connexion.';
        console.error('Erreur calendrier:', e);
      } finally {
        this.loading = false;
      }
    },

    updateDayCache() {
      this.weekendDays = [];
      for (let jour = 1; jour <= this.meta.nb_jours; jour++) {
        const d = new Date(this.annee, this.mois - 1, jour);
        if (d.getDay() === 0 || d.getDay() === 6) {
          this.weekendDays.push(jour);
        }
      }

      const today = new Date();
      this.todayDay =
        today.getFullYear() === this.annee &&
        today.getMonth() + 1 === this.mois
          ? today.getDate()
          : null;
    },

    prepareCalendarData(departements) {
      return departements.map(dept => {
        const employes = dept.employes.map(employe => {
          const conges = employe.conges.map(conge => {
            const enriched = {
              ...conge,
              startsByDay: {},
              endsByDay: {},
            };

            const debut = new Date(`${conge.date_debut}T00:00:00`);
            const fin = new Date(`${conge.date_fin}T00:00:00`);
            const startDay = debut.getFullYear() === this.annee && debut.getMonth() + 1 === this.mois
              ? debut.getDate()
              : 1;
            const endDay = fin.getFullYear() === this.annee && fin.getMonth() + 1 === this.mois
              ? fin.getDate()
              : this.meta.nb_jours;

            enriched.startsByDay[startDay] = true;
            enriched.endsByDay[endDay] = true;

            return enriched;
          });

          return { ...employe, conges, congesByDay: this.buildCongesByDay(conges) };
        });

        return {
          ...dept,
          employes,
          demandeCount: employes.reduce((sum, employe) => sum + employe.conges.length, 0),
        };
      });
    },

    buildCongesByDay(conges) {
      const congesByDay = {};

      conges.forEach(conge => {
        const startDay = Number(Object.keys(conge.startsByDay)[0] || 1);
        const endDay = Number(Object.keys(conge.endsByDay)[0] || this.meta.nb_jours);
        for (let jour = startDay; jour <= endDay; jour++) {
          if (!congesByDay[jour]) congesByDay[jour] = [];
          congesByDay[jour].push(conge);
        }
      });

      return congesByDay;
    },

    changerMois(delta) {
      let m = this.mois + delta;
      let a = this.annee;
      if (m < 1)  { m = 12; a--; }
      if (m > 12) { m = 1;  a++; }
      this.mois  = m;
      this.annee = a;
      this.charger();
    },

    allerAujourdhui() {
      const today = new Date();
      this.mois  = today.getMonth() + 1;
      this.annee = today.getFullYear();
      this.charger();
    },

    appliquerFiltres() { /* filtreType est réactif — computed se recalcule */ },

    toggleDept(id) {
      this.deptOuvert[id] = !this.deptOuvert[id];
    },

    // ---- Helpers calendrier ----
    isWeekend(jour) {
      return this.weekendDays.includes(jour);
    },

    isToday(jour) {
      return this.todayDay === jour;
    },

    congesDuJour(employe, jour) {
      return employe.congesByDay?.[jour] || [];
    },

    jourEstDansConge(jour, conge) {
      const d = new Date(this.annee, this.mois - 1, jour);
      const debut = new Date(conge.date_debut + 'T00:00:00');
      const fin   = new Date(conge.date_fin   + 'T00:00:00');
      return d >= debut && d <= fin;
    },

    jourEstDebut(jour, conge) {
      const d     = new Date(this.annee, this.mois - 1, jour);
      const debut = new Date(conge.date_debut + 'T00:00:00');
      return d.toDateString() === debut.toDateString();
    },

    jourEstFin(jour, conge) {
      const d   = new Date(this.annee, this.mois - 1, jour);
      const fin = new Date(conge.date_fin + 'T00:00:00');
      return d.toDateString() === fin.toDateString();
    },

    segmentStyle(conge) {
      const colors = this.typeColors[conge.type] || { bg: '#e5e7eb', border: '#9ca3af' };
      const isAttente = conge.statut === 'en_attente';
      return {
        background:   isAttente ? '#fef9c3' : colors.bg,
        borderTop:    `2px solid ${isAttente ? '#ca8a04' : colors.border}`,
        borderBottom: `2px solid ${isAttente ? '#ca8a04' : colors.border}`,
      };
    },

    tooltipConge(conge) {
      return `${conge.type_label} · ${this.formatDate(conge.date_debut)} → ${this.formatDate(conge.date_fin)} · ${conge.statut_label}`;
    },

    ouvrirDetail(conge, employe) {
      this.detail = { conge, employe };
      this.detailVisible = true;
    },

    fermerDetail() {
      this.detailVisible = false;
    },

    formatDate(d) {
      if (!d) return '';
      return new Date(d + 'T00:00:00').toLocaleDateString('fr-FR');
    },

    initiales(nom) {
      return nom
        .split(' ')
        .map(p => p[0] || '')
        .slice(0, 2)
        .join('')
        .toUpperCase();
    },
  },
};
</script>

<style scoped>
/* ══════════════════════════════════════════
   VARIABLES
══════════════════════════════════════════ */
:root {
  --primary: #008a9b;
  --primary-dark: #006d7a;
  --accent: #261555;
  --bg: #f8fafc;
  --white: #ffffff;
  --border: #e2e8f0;
  --text: #1e293b;
  --muted: #64748b;
  --radius: 16px;
}

/* ══════════════════════════════════════════
   LAYOUT PRINCIPAL
══════════════════════════════════════════ */
.calendrier-equipe {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
  font-family: 'Inter', 'Segoe UI', sans-serif;
  color: #1e293b;
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

/* ══════════════════════════════════════════
   EN-TÊTE
══════════════════════════════════════════ */
.cal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  background: white;
  border-radius: 20px;
  padding: 1.5rem 2rem;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.08);
  border-left: 5px solid #008a9b;
}

.cal-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #261555;
  margin: 0 0 .25rem;
  display: flex;
  align-items: center;
  gap: .6rem;
}

.cal-title i { color: #008a9b; }

.cal-subtitle {
  color: #64748b;
  font-size: .95rem;
  margin: 0;
}

.cal-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.cal-select {
  padding: .5rem 1rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: .875rem;
  color: #1e293b;
  background: white;
  cursor: pointer;
  transition: border-color .2s;
}
.cal-select:focus { outline: none; border-color: #008a9b; }

.cal-nav {
  display: flex;
  align-items: center;
  gap: .75rem;
  background: #f1f5f9;
  border-radius: 12px;
  padding: .4rem .8rem;
}

.nav-btn {
  background: white;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: .85rem;
  color: #008a9b;
  transition: all .2s;
}
.nav-btn:hover { background: #008a9b; color: white; border-color: #008a9b; }

.nav-label {
  font-weight: 600;
  font-size: .95rem;
  color: #261555;
  min-width: 130px;
  text-align: center;
  text-transform: capitalize;
}

.btn-today {
  padding: .5rem 1.2rem;
  background: linear-gradient(135deg, #008a9b, #006d7a);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all .2s;
}
.btn-today:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,138,155,.3); }

/* ══════════════════════════════════════════
   LÉGENDE
══════════════════════════════════════════ */
.cal-legend {
  display: flex;
  flex-wrap: wrap;
  gap: .75rem 1.5rem;
  margin-bottom: 1.5rem;
  background: white;
  border-radius: 14px;
  padding: 1rem 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,.06);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .8rem;
  color: #475569;
  font-weight: 500;
}

.legend-dot {
  width: 14px;
  height: 14px;
  border-radius: 4px;
  flex-shrink: 0;
}

/* ══════════════════════════════════════════
   RÉSUMÉ RAPIDE
══════════════════════════════════════════ */
.cal-summary {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.summary-card {
  flex: 1;
  min-width: 130px;
  background: white;
  border-radius: 14px;
  padding: 1.2rem 1.5rem;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0,0,0,.06);
  border-top: 3px solid #008a9b;
  display: flex;
  flex-direction: column;
  gap: .25rem;
}

.summary-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #008a9b;
}

.summary-label {
  font-size: .8rem;
  color: #64748b;
  font-weight: 500;
}

/* ══════════════════════════════════════════
   BLOC DÉPARTEMENT
══════════════════════════════════════════ */
.dept-block {
  background: white;
  border-radius: 20px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.08);
  overflow: hidden;
  border: 1px solid #e2e8f0;
  content-visibility: auto;
  contain-intrinsic-size: 220px;
}

.dept-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.75rem;
  cursor: pointer;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  transition: background .2s;
}
.dept-header:hover { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); }

.dept-header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.dept-icon {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #008a9b, #006d7a);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
}

.dept-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #261555;
  margin: 0 0 .2rem;
}

.dept-count {
  font-size: .8rem;
  color: #64748b;
}

.dept-toggle {
  color: #94a3b8;
  font-size: .9rem;
  transition: transform .2s;
}

/* ══════════════════════════════════════════
   GANTT
══════════════════════════════════════════ */
.gantt-wrapper {
  overflow-x: auto;
  padding: 0;
}

.gantt-grid {
  display: grid;
  min-width: max-content;
}

/* En-têtes */
.gantt-name-header {
  position: sticky;
  left: 0;
  z-index: 3;
  background: #f8fafc;
  border-right: 2px solid #e2e8f0;
  border-bottom: 2px solid #e2e8f0;
  padding: .75rem 1rem;
  font-size: .8rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: .05em;
}

.gantt-day-header {
  border-right: 1px solid #f1f5f9;
  border-bottom: 2px solid #e2e8f0;
  padding: .6rem 0;
  text-align: center;
  font-size: .75rem;
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
  min-width: 36px;
}

.gantt-day-header.gantt-day-weekend { background: #fef2f2; color: #ef4444; }
.gantt-day-header.gantt-day-today   {
  background: #008a9b;
  color: white;
  font-weight: 700;
  position: relative;
}

/* Cellules */
.gantt-name-cell {
  position: sticky;
  left: 0;
  z-index: 2;
  background: white;
  border-right: 2px solid #e2e8f0;
  border-bottom: 1px solid #f1f5f9;
  padding: .6rem 1rem;
  display: flex;
  align-items: center;
  gap: .75rem;
  min-height: 46px;
}

.gantt-name-cell:hover { background: #f8fafc; }

.employe-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #261555, #4c1d95);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .75rem;
  font-weight: 700;
  flex-shrink: 0;
}

.employe-nom {
  font-size: .85rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  display: block;
}

.employe-role {
  font-size: .72rem;
  color: #94a3b8;
  display: block;
}

.gantt-cell {
  border-right: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
  position: relative;
  min-height: 46px;
  min-width: 36px;
}

.gantt-cell.gantt-cell-weekend { background: #fafafa; }
.gantt-cell.gantt-cell-today   { background: rgba(0,138,155,.05); }

/* Segments de congé */
.conge-segment {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  left: 0;
  right: 0;
  height: 22px;
  cursor: pointer;
  transition: height .15s, box-shadow .15s;
  z-index: 1;
}

.conge-segment:hover {
  height: 30px;
  box-shadow: 0 2px 8px rgba(0,0,0,.2);
  z-index: 5;
}

/* Arrondi début / fin */
.conge-segment.conge-debut {
  border-radius: 6px 0 0 6px;
  border-left: 2px solid currentColor;
}
.conge-segment.conge-fin {
  border-radius: 0 6px 6px 0;
  border-right: 2px solid currentColor;
}
.conge-segment.conge-debut.conge-fin {
  border-radius: 6px;
  border: 2px solid;
}

/* ══════════════════════════════════════════
   ÉTAT VIDE / CHARGEMENT / ERREUR
══════════════════════════════════════════ */
.cal-loading, .cal-error, .cal-empty {
  text-align: center;
  padding: 4rem;
  color: #64748b;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #008a9b;
  border-radius: 50%;
  animation: spin .8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin { to { transform: rotate(360deg); } }

.cal-error i  { font-size: 2.5rem; color: #ef4444; margin-bottom: .75rem; display: block; }
.cal-empty .empty-icon-wrap {
  width: 80px;
  height: 80px;
  background: #f1f5f9;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
}
.cal-empty .empty-icon-wrap i { font-size: 2rem; color: #94a3b8; }
.cal-empty h3 { font-size: 1.2rem; color: #1e293b; margin: 0 0 .5rem; }

.btn-retry {
  margin-top: 1rem;
  padding: .5rem 1.5rem;
  background: #008a9b;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: .875rem;
}

/* ══════════════════════════════════════════
   MODAL DÉTAIL
══════════════════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-card {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 440px;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0,0,0,.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: .5rem;
}

.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  color: #64748b;
  font-size: 1.1rem;
  padding: .25rem;
  border-radius: 6px;
  transition: background .2s;
}
.modal-close:hover { background: #f1f5f9; }

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-row {
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  font-size: .9rem;
  color: #334155;
}

.modal-row i { color: #008a9b; margin-top: .15rem; flex-shrink: 0; }

.modal-badge {
  display: inline-block;
  padding: .2rem .6rem;
  border-radius: 6px;
  font-size: .8rem;
  font-weight: 600;
  margin-left: .25rem;
}

.badge-approuve  { background: #dcfce7; color: #16a34a; }
.badge-en_attente{ background: #fef9c3; color: #ca8a04; }
.badge-rejete    { background: #fee2e2; color: #dc2626; }

/* Transition modal */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity .2s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media (max-width: 768px) {
  .calendrier-equipe { padding: 1rem; }
  .cal-header { flex-direction: column; align-items: flex-start; }
  .cal-controls { width: 100%; justify-content: flex-start; }
  .cal-summary { gap: .75rem; }
  .summary-card { min-width: 100px; padding: 1rem; }
  .summary-value { font-size: 1.4rem; }
}
</style>
