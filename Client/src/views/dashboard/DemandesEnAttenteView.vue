<template>
  <div class="demandes-en-attente">
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input
          type="text"
          v-model="searchTerm"
          placeholder="Rechercher par nom, matricule..."
          @input="filterDemandes"
        />
      </div>
      <div class="filter-buttons">
        <button
          v-for="filter in filters"
          :key="filter.value"
          @click="setFilter(filter.value)"
          :class="['filter-btn', { active: currentFilter === filter.value }]"
        >
          {{ filter.label }}
        </button>
      </div>
    </div>

    <div class="demandes-list">
      <div
        v-for="demande in filteredDemandes"
        :key="demande.id"
        class="demande-card"
        :class="getStatusClass(demande.status)"
      >
        <div class="demande-header">
          <div class="demande-info">
            <h3>{{ demande.prenom }} {{ demande.nom }}</h3>
            <p class="matricule">Matricule: {{ demande.matricule }}</p>
            <p class="unite">{{ demande.unite }}</p>
          </div>
          <div class="demande-status">
            <span :class="['status-badge', getStatusClass(demande.status)]">
              {{ getStatusLabel(demande.status) }}
            </span>
            <span class="date-demande">
              {{ formatDate(demande.dateDemande) }}
            </span>
          </div>
        </div>

        <div class="demande-details">
          <div class="detail-row">
            <span class="detail-label">Type de demande:</span>
            <span class="detail-value">{{ demande.typeDemande }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Période:</span>
            <span class="detail-value">
              {{ formatDate(demande.dateDebut) }} -
              {{ formatDate(demande.dateFin) }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Durée:</span>
            <span class="detail-value">{{ demande.duree }} jour(s)</span>
          </div>
          <div class="detail-row" v-if="demande.motif">
            <span class="detail-label">Motif:</span>
            <span class="detail-value">{{ demande.motif }}</span>
          </div>
        </div>

        <div class="demande-actions">
          <button @click="voirDetails(demande)" class="btn-details">
            <i class="fas fa-eye"></i>
            Voir détails
          </button>
        </div>
      </div>

      <div v-if="filteredDemandes.length === 0" class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucune demande trouvée</h3>
        <p>
          {{
            searchTerm
              ? "Aucune demande ne correspond à votre recherche."
              : "Toutes les demandes ont été traitées."
          }}
        </p>
      </div>
    </div>

    <!-- Modal pour les détails -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Détails de la demande</h2>
          <button @click="closeModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Contenu du modal -->
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useDemandesStore } from '@/stores/demandes';
import { computed, onMounted, ref } from 'vue';

export default {
  name: 'DemandesEnAttenteView',
  setup() {
    const demandesStore = useDemandesStore();
    const searchTerm = ref('');
    const currentFilter = ref('toutes');
    const showDetailsModal = ref(false);
    const selectedDemande = ref(null);

    const filters = [
      { label: 'Toutes', value: 'toutes' },
      { label: 'En attente', value: 'en_attente' },
      { label: 'Approuvées', value: 'approuve' },
      { label: 'Rejetées', value: 'rejete' },
    ];

    onMounted(async () => {
      await demandesStore.fetchDemandesAValider();
    });

    const filteredDemandes = computed(() => {
      let list = demandesStore.demandesAValider.map(d => ({
        id: d.id,
        prenom: d.user?.first_name || '—',
        nom: d.user?.name || '—',
        matricule: d.user?.matricule || '—',
        unite: d.user?.department?.name || '—',
        typeDemande: d.type_label || d.type_demande,
        dateDebut: d.date_debut,
        dateFin: d.date_fin,
        duree: d.duree_jours,
        motif: d.motif,
        status: d.statut,
        dateDemande: d.created_at,
      }));

      if (currentFilter.value !== 'toutes') {
        list = list.filter(d => d.status === currentFilter.value);
      }

      if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(d =>
          d.nom.toLowerCase().includes(term) ||
          d.prenom.toLowerCase().includes(term) ||
          d.matricule.toLowerCase().includes(term)
        );
      }

      return list;
    });

    const voirDetails = (demande) => {
      selectedDemande.value = demande;
      showDetailsModal.value = true;
    };

    const closeModal = () => {
      showDetailsModal.value = false;
      selectedDemande.value = null;
    };

    const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '';

    const getStatusClass = (s) => ({ en_attente: 'pending', approuve: 'approved', rejete: 'rejected' }[s] || '');
    const getStatusLabel = (s) => ({ en_attente: 'En attente', approuve: 'Approuvée', rejete: 'Rejetée' }[s] || s);

    return {
      searchTerm, currentFilter, filters, filteredDemandes,
      showDetailsModal, selectedDemande,
      voirDetails, closeModal, formatDate,
      getStatusClass, getStatusLabel,
      setFilter: (f) => { currentFilter.value = f; },
      loading: computed(() => demandesStore.loading),
    };
  },
};
</script>

<style scoped>
.demandes-en-attente {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  font-family: "Inter", sans-serif;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  border-left: 4px solid #008a9b;
}

.page-header h1 {
  color: #261555;
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.page-header p {
  color: #6c757d;
  font-size: 1.1rem;
  margin: 0;
}

.filters-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  gap: 20px;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-box i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-box input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: #008a9b;
  box-shadow: 0 0 0 3px rgba(0, 138, 155, 0.1);
}

.filter-buttons {
  display: flex;
  gap: 10px;
}

.filter-btn {
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
  color: #64748b;
}

.filter-btn:hover {
  border-color: #008a9b;
  color: #008a9b;
}

.filter-btn.active {
  background: linear-gradient(135deg, #008a9b, #00b4d8);
  color: white;
  border-color: transparent;
}

.demandes-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.demande-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border-left: 4px solid #ddd;
  transition: all 0.2s ease;
  border: 1px solid #e9ecef;
  position: relative;
  overflow: hidden;
}

.demande-card:hover {
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.demande-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(135deg, #008a9b, #00b4d8);
}

.demande-card.pending {
  border-left-color: #b10064;
}

.demande-card.pending::before {
  background: linear-gradient(135deg, #b10064, #d63384);
}

.demande-card.approved {
  border-left-color: #008a9b;
}

.demande-card.approved::before {
  background: linear-gradient(135deg, #008a9b, #00b4d8);
}

.demande-card.rejected {
  border-left-color: #261555;
}

.demande-card.rejected::before {
  background: linear-gradient(135deg, #261555, #4c1d95);
}

.demande-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
}

.demande-info h3 {
  margin: 0 0 5px 0;
  font-size: 18px;
  color: #261555;
}

.matricule {
  margin: 0 0 3px 0;
  color: #64748b;
  font-size: 14px;
}

.unite {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.demande-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 5px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.pending {
  background: rgba(177, 0, 100, 0.15);
  color: #b10064;
  border: 1px solid rgba(177, 0, 100, 0.3);
}

.status-badge.approved {
  background: rgba(0, 138, 155, 0.15);
  color: #008a9b;
  border: 1px solid rgba(0, 138, 155, 0.3);
}

.status-badge.rejected {
  background: rgba(38, 21, 85, 0.15);
  color: #261555;
  border: 1px solid rgba(38, 21, 85, 0.3);
}

.date-demande {
  font-size: 12px;
  color: #64748b;
}

.demande-details {
  margin-bottom: 15px;
}

.detail-row {
  display: flex;
  margin-bottom: 8px;
}

.detail-label {
  font-weight: 500;
  color: #64748b;
  min-width: 120px;
  font-size: 14px;
}

.detail-value {
  color: #261555;
  font-size: 14px;
}

.demande-actions {
  display: flex;
  justify-content: center;
}

.btn-details {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: all 0.2s ease;
  font-weight: 500;
  background: #f1f5f9;
  color: #64748b;
}

.btn-details:hover {
  background: #e2e8f0;
  transform: translateY(-2px);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 15px;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 10px 0;
  color: #64748b;
}

.empty-state p {
  margin: 0;
  font-size: 16px;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  color: #261555;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #64748b;
  padding: 5px;
}

.close-btn:hover {
  color: #475569;
}

.modal-body {
  padding: 20px;
}

@media (max-width: 768px) {
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    max-width: none;
  }

  .filter-buttons {
    justify-content: center;
  }

  .demande-header {
    flex-direction: column;
    gap: 10px;
  }

  .demande-status {
    align-items: flex-start;
  }

  .demande-actions {
    flex-wrap: wrap;
  }
}
</style>
