<template>
  <div class="solde-container">
    <!-- Cartes des congés -->
    <div class="conges-grid">
      <!-- Congé annuel -->
      <div class="conge-card">
        <div class="conge-icon">
          <i class="fas fa-umbrella-beach"></i>
        </div>
        <div class="conge-content">
          <h3>Congé annuel</h3>
          <div class="conge-stats">
            <div class="stat-row">
              <span>Acquis</span>
              <span class="stat-value">{{ congesAnnuel.acquis }} jours</span>
            </div>
            <div class="stat-row">
              <span>Pris</span>
              <span class="stat-value taken">{{ congesAnnuel.pris }} jours</span>
            </div>
            <div class="stat-row highlight">
              <span>Reste</span>
              <span class="stat-value">{{ congesAnnuel.reste }} jours</span>
            </div>
          </div>

          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: congesAnnuel.pourcentage + '%' }"
            ></div>
          </div>
          <div class="progress-text">{{ congesAnnuel.pourcentage }}% utilisé</div>
        </div>
      </div>

      <!-- Congés fractionnés -->
      <div class="conge-card">
        <div class="conge-icon">
          <i class="fas fa-calendar-week"></i>
        </div>
        <div class="conge-content">
          <h3>Congés fractionnés</h3>
          <div class="conge-stats">
            <div class="stat-row">
              <span>Acquis</span>
              <span class="stat-value">{{ congesFractionnes.acquis }} jours</span>
            </div>
            <div class="stat-row">
              <span>Pris</span>
              <span class="stat-value taken">{{ congesFractionnes.pris }} jours</span>
            </div>
            <div class="stat-row highlight">
              <span>Reste</span>
              <span class="stat-value">{{ congesFractionnes.reste }} jours</span>
            </div>
          </div>

          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: congesFractionnes.pourcentage + '%' }"
            ></div>
          </div>
          <div class="progress-text">{{ congesFractionnes.pourcentage }}% utilisé</div>
        </div>
      </div>

      <!-- Autres congés -->
      <div class="conge-card">
        <div class="conge-icon">
          <i class="fas fa-gavel"></i>
        </div>
        <div class="conge-content">
          <h3>Autres congés légaux</h3>
          <div class="conge-stats">
            <div class="stat-row">
              <span>Acquis</span>
              <span class="stat-value">{{ autresConges.acquis }} jours</span>
            </div>
            <div class="stat-row">
              <span>Pris</span>
              <span class="stat-value taken">{{ autresConges.pris }} jours</span>
            </div>
            <div class="stat-row highlight">
              <span>Reste</span>
              <span class="stat-value">{{ autresConges.reste }} jours</span>
            </div>
          </div>

          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: autresConges.pourcentage + '%' }"
            ></div>
          </div>
          <div class="progress-text">{{ autresConges.pourcentage }}% utilisé</div>
        </div>
      </div>
    </div>

    <!-- Résumé global -->
    <div class="resume-section">
      <h2>Résumé global</h2>
      <div class="resume-grid">
        <div class="resume-card">
          <div class="resume-icon">
            <i class="fas fa-plus-circle"></i>
          </div>
          <div class="resume-content">
            <h3>Total acquis</h3>
            <p class="resume-value">{{ totalAcquis }} jours</p>
          </div>
        </div>

        <div class="resume-card">
          <div class="resume-icon">
            <i class="fas fa-minus-circle"></i>
          </div>
          <div class="resume-content">
            <h3>Total pris</h3>
            <p class="resume-value">{{ totalPris }} jours</p>
          </div>
        </div>

        <div class="resume-card">
          <div class="resume-icon">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="resume-content">
            <h3>Planifiés</h3>
            <p class="resume-value">{{ congesPlanifies }} jours</p>
          </div>
        </div>

        <div class="resume-card primary">
          <div class="resume-icon">
            <i class="fas fa-wallet"></i>
          </div>
          <div class="resume-content">
            <h3>Solde disponible</h3>
            <p class="resume-value">{{ soldeDisponible }} jours</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useCongesStore } from '@/stores/conges';
import { computed, onMounted } from 'vue';

export default {
  name: 'SoldeConges',
  setup() {
    const congesStore = useCongesStore();

    onMounted(async () => {
      await congesStore.fetchStats();
    });

    return {
      congesAnnuel: computed(() => congesStore.soldeConges.congesAnnuel),
      congesFractionnes: computed(() => congesStore.soldeConges.congesFractionnes),
      autresConges: computed(() => congesStore.soldeConges.autresConges),
      congesPlanifies: computed(() => congesStore.soldeConges.congesPlanifies),
      totalAcquis: computed(() => congesStore.getTotalAcquis),
      totalPris: computed(() => congesStore.getTotalPris),
      soldeDisponible: computed(() => congesStore.getSoldeDisponible),
      loading: computed(() => congesStore.loading),
    };
  },
};
</script>

<style scoped>
.solde-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  font-family: "Inter", sans-serif;
}

/* Grille des congés */
.conges-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  padding: 1rem;
  margin-bottom: 3rem;
}

.conge-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
}

.conge-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border-color: #008a9b;
}

.conge-icon {
  width: 60px;
  height: 60px;
  background: #f0f9ff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.conge-icon i {
  font-size: 24px;
  color: #008a9b;
}

.conge-content {
  flex: 1;
}

.conge-content h3 {
  color: #261555;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

/* Statistiques */
.conge-stats {
  margin-bottom: 1.5rem;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  color: #64748b;
  font-size: 0.95rem;
}

.stat-row.highlight {
  color: #261555;
  font-weight: 600;
}

.stat-value {
  font-weight: 600;
}

.stat-value.taken {
  color: #008a9b;
}

/* Barre de progression */
.progress-bar {
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: #008a9b;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  color: #64748b;
  font-size: 0.875rem;
  text-align: right;
}

/* Résumé */
.resume-section {
  margin-top: 2rem;
}

.resume-section h2 {
  color: #261555;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.resume-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  padding: 1rem;
}

.resume-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
}

.resume-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border-color: #008a9b;
}

.resume-card.primary {
  background: #008a9b;
}

.resume-card.primary .resume-icon {
  background: rgba(255, 255, 255, 0.2);
}

.resume-card.primary .resume-icon i {
  color: white;
}

.resume-card.primary .resume-content h3,
.resume-card.primary .resume-value {
  color: white;
}

.resume-icon {
  width: 60px;
  height: 60px;
  background: #f0f9ff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.resume-icon i {
  font-size: 24px;
  color: #008a9b;
}

.resume-content {
  flex: 1;
}

.resume-content h3 {
  color: #261555;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.resume-value {
  color: #008a9b;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

@media (max-width: 768px) {
  .solde-container {
    padding: 1rem;
  }

  .conges-grid,
  .resume-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .conge-card,
  .resume-card {
    padding: 1.5rem;
  }
}
</style>
