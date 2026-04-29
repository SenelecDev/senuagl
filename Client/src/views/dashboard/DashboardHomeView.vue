<template>
  <div class="dashboard-home">
    <!-- Statistiques principales avec design moderne -->
    <div class="stats-section">
      <div class="stats-grid">
  <div v-for="card in statsCards" :key="card.label" class="stat-card">
    <div class="stat-icon">
      <i :class="['fas', card.icon]"></i>
    </div>
    <div class="stat-content">
      <h3>{{ card.value ?? 0 }}</h3>
      <p>{{ card.label }}</p>
      <span class="stat-subtitle">{{ card.sub }}</span>
    </div>
  </div>
</div>
    </div>

    <!-- Prochains congés avec design moderne -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">
          <h2>Prochains Congés</h2>
          <p>Aperçu de vos congés à venir</p>
        </div>
        <router-link :to="`${routePrefix}/historique`" class="view-all-link">
          Voir tout <i class="fas fa-arrow-right"></i>
        </router-link>
      </div>

      <div class="conges-container">
        <div
          v-for="conge in prochainsConges"
          :key="conge.id"
          class="conge-item"
        >
          <div class="conge-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <div class="conge-info">
            <div class="conge-dates">
              <span class="date-range">{{ formatDate(conge.date_debut) }} - {{ formatDate(conge.date_fin) }}</span>
              <div class="conge-duration">{{ calculateDuration(conge.date_debut, conge.date_fin) }} jours</div>
            </div>
            <div class="conge-type">
             <span :class="['badge', getTypeClass(conge.type_label)]">
              {{ conge.type_label }}
              </span>
            </div>
          </div>
          <div class="conge-status">
            <span :class="['status', getStatusClass(conge.statut_label)]">
             {{ conge.statut_label }}
            </span>
          </div>
        </div>

        <div v-if="prochainsConges.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-calendar-times"></i>
          </div>
          <h3>Aucun congé planifié</h3>
          <p>Vous n'avez pas de congés prévus pour le moment</p>
          <router-link :to="`${routePrefix}/gestion-demandes`" class="empty-action">
            Planifier des congés
          </router-link>
        </div>
      </div>
    </div>

    <!-- Actions rapides avec design moderne -->
    <div class="section">
      <div class="section-header">
        <div class="section-title">
          <h2>Actions Rapides</h2>
          <p>Accédez rapidement aux fonctions principales</p>
        </div>
      </div>
      <div class="actions-grid">
        <router-link :to="`${routePrefix}/gestion-demandes`" class="action-card primary">
          <div class="action-icon">
            <i class="fas fa-plus"></i>
          </div>
          <div class="action-content">
            <h3>Nouvelle Demande</h3>
            <p>Créer une demande de congés</p>
          </div>
          <div class="action-arrow">
            <i class="fas fa-arrow-right"></i>
          </div>
        </router-link>

        <router-link :to="`${routePrefix}/validation-demandes`" class="action-card" v-if="isValidationRole">
          <div class="action-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="action-content">
            <h3>Validation</h3>
            <p>Valider les demandes en attente</p>
          </div>
          <div class="action-arrow">
            <i class="fas fa-arrow-right"></i>
          </div>
        </router-link>

        <router-link :to="`${routePrefix}/documents-administratifs`" class="action-card" v-if="isDirecteurRH">
          <div class="action-icon">
            <i class="fas fa-file-contract"></i>
          </div>
          <div class="action-content">
            <h3>Documents</h3>
            <p>Gérer les documents administratifs</p>
          </div>
          <div class="action-arrow">
            <i class="fas fa-arrow-right"></i>
          </div>
        </router-link>

        <router-link :to="`${routePrefix}/historique`" class="action-card">
          <div class="action-icon">
            <i class="fas fa-history"></i>
          </div>
          <div class="action-content">
            <h3>Historique</h3>
            <p>Consulter l'historique des congés</p>
          </div>
          <div class="action-arrow">
            <i class="fas fa-arrow-right"></i>
          </div>
        </router-link>

        <router-link :to="`${routePrefix}/solde`" class="action-card">
          <div class="action-icon">
            <i class="fas fa-wallet"></i>
          </div>
          <div class="action-content">
            <h3>Solde</h3>
            <p>Voir le solde de congés</p>
          </div>
          <div class="action-arrow">
            <i class="fas fa-arrow-right"></i>
          </div>
        </router-link>

        <router-link :to="`${routePrefix}/etat-demandes`" class="action-card">
          <div class="action-icon">
            <i class="fas fa-list-alt"></i>
          </div>
          <div class="action-content">
            <h3>État des Demandes</h3>
            <p>Suivre vos demandes en cours</p>
          </div>
          <div class="action-arrow">
            <i class="fas fa-arrow-right"></i>
          </div>
        </router-link>
      </div>
    </div>
    <!-- Calendrier RH -->
<div class="section">
  <div class="section-header">
    <div class="section-title">
      <h2>Calendrier RH</h2>
      <p>Périodes de congé et jours fériés définis par la direction</p>
    </div>
  </div>

  <div class="calendrier-grid">
    <!-- Périodes de congé -->
    <div class="calendrier-card">
      <div class="calendrier-card-header">
        <div class="calendrier-icon" style="background: linear-gradient(135deg, #008a9b, #006d7a)">
          <i class="fas fa-calendar-check"></i>
        </div>
        <h3>Périodes de Congé</h3>
      </div>
      <div v-if="leavePlans.length === 0" class="calendrier-empty">
        <i class="fas fa-calendar-times"></i>
        <p>Aucune période définie</p>
      </div>
      <div v-for="plan in leavePlans" :key="plan.id" class="calendrier-item">
        <div class="calendrier-item-icon">
          <i class="fas fa-umbrella-beach"></i>
        </div>
        <div class="calendrier-item-info">
          <span class="calendrier-item-name">{{ plan.name }}</span>
          <span class="calendrier-item-dates">
            {{ formatDate(plan.start_date) }} → {{ formatDate(plan.end_date) }}
          </span>
        </div>
        <span class="calendrier-item-badge badge-annuel">
          {{ calculateDuration(plan.start_date, plan.end_date) }}j
        </span>
      </div>
    </div>

    <!-- Jours fériés -->
    <div class="calendrier-card">
      <div class="calendrier-card-header">
        <div class="calendrier-icon" style="background: linear-gradient(135deg, #261555, #4c1d95)">
          <i class="fas fa-star"></i>
        </div>
        <h3>Jours Fériés</h3>
      </div>
      <div v-if="holidays.length === 0" class="calendrier-empty">
        <i class="fas fa-calendar-times"></i>
        <p>Aucun jour férié défini</p>
      </div>
      <div v-for="holiday in upcomingHolidays" :key="holiday.id" class="calendrier-item">
        <div class="calendrier-item-icon" style="background: rgba(38,21,85,0.1)">
          <i class="fas fa-star" style="color: #261555"></i>
        </div>
        <div class="calendrier-item-info">
          <span class="calendrier-item-name">{{ holiday.name }}</span>
          <span class="calendrier-item-dates">{{ formatDate(holiday.date) }}</span>
        </div>
        <span class="calendrier-item-badge" style="background:rgba(38,21,85,0.1);color:#261555">
          Férié
        </span>
      </div>
    </div>
  </div>
</div>
  </div>
</template>

<script>
import { useCongesStore } from "@/stores/conges";
import { useDemandesStore } from "@/stores/demandes";
import { planningApi } from "@/services/api";
import { onMounted, onActivated, ref } from 'vue';

export default {
  name: "DashboardHomeView",
  setup() {
    const congesStore = useCongesStore();
    const demandesStore = useDemandesStore();
    const leavePlans = ref([]);
    const holidays = ref([]);

    const chargerStats = async () => {
      await congesStore.fetchStats();
    };

    const chargerCalendrier = async () => {
      try {
        const [plansRes, holidaysRes] = await Promise.all([
          planningApi.plans.list(),
          planningApi.holidays.list(),
        ]);
        if (plansRes.data.success) leavePlans.value = plansRes.data.data;
        if (holidaysRes.data.success) holidays.value = holidaysRes.data.data;
      } catch (e) {
        console.error('Erreur calendrier:', e);
      }
    };

    onMounted(async () => {
      await chargerStats();
      await chargerCalendrier();
    });
    onActivated(chargerStats);

    return { congesStore, demandesStore, leavePlans, holidays };
  },

  computed: {
    statsCards() {
      return [
        { value: this.congesStore.stats.conges_restants, label: 'Congés Restants', sub: 'jours disponibles', icon: 'fa-calendar-check' },
        { value: this.congesStore.stats.demandes_en_attente, label: 'En Attente', sub: 'demandes à traiter', icon: 'fa-clock' },
        { value: this.congesStore.stats.demandes_approuvees, label: 'Approuvées', sub: 'cette année', icon: 'fa-check-circle' },
        { value: this.congesStore.stats.conges_pris, label: 'Congés Pris', sub: 'jours utilisés', icon: 'fa-calendar-times' },
      ];
    },
    prochainsConges() {
      return this.congesStore.prochainsConges;
    },
    upcomingHolidays() {
  return this.holidays
    .sort((a, b) => new Date(a.date) - new Date(b.date))
    .slice(0, 5);
},
    routePrefix() {
      const path = this.$route.path;
      if (path.startsWith('/superieur')) return '/superieur';
      if (path.startsWith('/directeur-unite')) return '/directeur-unite';
      if (path.startsWith('/responsable-rh')) return '/responsable-rh';
      if (path.startsWith('/directeur-rh')) return '/directeur-rh';
      return '/employe';
    },
    isValidationRole() {
      const path = this.$route.path;
      return path.startsWith('/superieur') ||
             path.startsWith('/directeur-unite') ||
             path.startsWith('/responsable-rh') ||
             path.startsWith('/directeur-rh');
    },
    isDirecteurRH() {
      return this.$route.path.startsWith('/directeur-rh');
    }
  },

  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString("fr-FR");
    },
    calculateDuration(dateDebut, dateFin) {
      const debut = new Date(dateDebut);
      const fin = new Date(dateFin);
      return Math.ceil(Math.abs(fin - debut) / (1000 * 60 * 60 * 24)) + 1;
    },
    getTypeClass(type) {
      const classes = {
        "Congé annuel": "badge-annuel",
        "Congés fractionnés": "badge-fractionnes",
        "Autres congés légaux": "badge-autres_legaux",
        "Congés Maladie": "badge-maladie",
      };
      return classes[type] || "badge-default";
    },
    getStatusClass(status) {
      const classes = {
        "En attente": "status-pending",
        "Approuvé": "status-approved",
        "Rejeté": "status-rejected",
      };
      return classes[status] || "status-default";
    },
  },
};
</script>

<style scoped>
.dashboard-home {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: "Inter", sans-serif;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  min-height: 100vh;
}

/* Section des statistiques modernes */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border-left: 4px solid #008a9b;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #008a9b 0%, #261555 100%);
  color: white;
  font-size: 20px;
  flex-shrink: 0;
}

.stat-content h3 {
  margin: 0 0 5px 0;
  font-size: 24px;
  font-weight: 600;
  color: #1f2937;
}

.stat-content p {
  margin: 0 0 3px 0;
  color: #1f2937;
  font-size: 16px;
  font-weight: 500;
}

.stat-subtitle {
  color: #6b7280;
  font-size: 14px;
}

/* Section moderne */
.section {
  margin-bottom: 3rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
}

.section-title h2 {
  color: #261555;
  font-size: 1.875rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.section-title p {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.view-all-link {
  color: #008a9b;
  text-decoration: none;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: 1px solid transparent;
}

.view-all-link:hover {
  background: rgba(0, 138, 155, 0.1);
  border-color: #008a9b;
  transform: translateX(4px);
}

/* Conteneur des congés moderne */
.conges-container {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.conge-item {
  display: flex;
  align-items: center;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 1rem;
  border: 1px solid #f1f5f9;
  transition: all 0.3s ease;
  background: #fafbfc;
}

.conge-item:hover {
  background: #f8fafc;
  border-color: #008a9b;
  transform: translateX(4px);
}

.conge-item:last-child {
  margin-bottom: 0;
}

.conge-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #008a9b, #006d7a);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  flex-shrink: 0;
}

.conge-icon i {
  font-size: 20px;
  color: white;
}

.conge-info {
  flex: 1;
}

.conge-dates {
  margin-bottom: 0.5rem;
}

.date-range {
  color: #1e293b;
  font-weight: 600;
  font-size: 1rem;
}

.conge-duration {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.conge-type {
  display: flex;
  align-items: center;
}

.conge-status {
  display: flex;
  align-items: center;
}

/* Badges modernes */
.badge {
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-right: 0.5rem;
}

.badge-annuel {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.badge-fractionnes {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.badge-autres_legaux {
  background: rgba(168, 85, 247, 0.1);
  color: #7c3aed;
}

.badge-maladie {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

.badge-default {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

/* Status badges */
.status {
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-pending {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
}

.status-approved {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.status-rejected {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

.status-default {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

/* État vide moderne */
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #64748b;
}

.empty-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.empty-icon i {
  font-size: 32px;
  color: #94a3b8;
}

.empty-state h3 {
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.empty-state p {
  color: #64748b;
  margin: 0 0 1.5rem 0;
}

.empty-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #008a9b, #006d7a);
  color: white;
  text-decoration: none;
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.empty-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 138, 155, 0.3);
}

/* Grille des actions modernes */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.action-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  text-decoration: none;
  color: inherit;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  position: relative;
  overflow: hidden;
}

.action-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #008a9b, #261555);
  transform: translateX(-100%);
  transition: transform 0.3s ease;
}

.action-card:hover::before {
  transform: translateX(0);
}

.action-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border-color: #008a9b;
}

.action-card.primary {
  background: linear-gradient(135deg, #008a9b, #006d7a);
  color: white;
}

.action-card.primary .action-icon {
  background: rgba(255, 255, 255, 0.2);
}

.action-card.primary .action-icon i {
  color: white;
}

.action-card.primary .action-content h3 {
  color: white;
}

.action-card.primary .action-content p {
  color: rgba(255, 255, 255, 0.8);
}

.action-card.primary .action-arrow i {
  color: white;
}

.action-icon {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-icon i {
  font-size: 24px;
  color: #008a9b;
}

.action-content {
  flex: 1;
}

.action-content h3 {
  color: #261555;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.action-content p {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.action-arrow {
  opacity: 0;
  transform: translateX(-10px);
  transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
  opacity: 1;
  transform: translateX(0);
}

.action-arrow i {
  font-size: 16px;
  color: #008a9b;
}

/* Responsive design */
@media (max-width: 768px) {
  .dashboard-home {
    padding: 1rem;
  }

  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
  }

  .stat-card {
    padding: 15px;
  }

  .stat-icon {
    width: 45px;
    height: 45px;
    font-size: 18px;
  }

  .stat-content h3 {
    font-size: 20px;
  }

  .stat-content p {
    font-size: 14px;
  }

  .stat-subtitle {
    font-size: 12px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .actions-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .action-card {
    padding: 1.5rem;
  }

  .conge-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .conge-icon {
    margin-right: 0;
  }

  /* Calendrier RH */
.calendrier-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.calendrier-card {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.calendrier-card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.calendrier-card-header h3 {
  color: #261555;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.calendrier-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.calendrier-icon i {
  color: white;
  font-size: 18px;
}

.calendrier-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  margin-bottom: 8px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}

.calendrier-item:hover {
  border-color: #008a9b;
  background: #f0fdfa;
}

.calendrier-item-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: rgba(0, 138, 155, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.calendrier-item-icon i {
  font-size: 14px;
  color: #008a9b;
}

.calendrier-item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.calendrier-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}

.calendrier-item-dates {
  font-size: 12px;
  color: #64748b;
}

.calendrier-item-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  flex-shrink: 0;
}

.calendrier-empty {
  text-align: center;
  padding: 2rem;
  color: #94a3b8;
}

.calendrier-empty i {
  font-size: 32px;
  margin-bottom: 8px;
  display: block;
}

.calendrier-empty p {
  font-size: 13px;
  margin: 0;
}

@media (max-width: 768px) {
  .calendrier-grid {
    grid-template-columns: 1fr;
  }
}
}
</style>