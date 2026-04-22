<template>
  <div class="validation-demandes">
    <div class="validation-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="['tab-btn', { active: activeTab === tab.id }]"
      >
        <i :class="tab.icon"></i>
        {{ tab.label }}
        <span v-if="tab.count > 0" class="tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <div class="validation-content">
      <!-- Onglet Demandes en attente -->
      <div v-if="activeTab === 'en-attente'" class="tab-content">
        <div class="demandes-grid">
          <div
            v-for="demande in demandesEnAttente"
            :key="demande.id"
            class="demande-validation-card"
          >
            <div class="card-header">
              <div class="demande-info">
                <h3>{{ demande.prenom }} {{ demande.nom }}</h3>
                <p class="matricule">{{ demande.matricule }}</p>
                <p class="unite">{{ demande.unite }}</p>
              </div>
              <div class="demande-type">
                <span
                  :class="['type-badge', getTypeClass(demande.typeDemande)]"
                >
                  {{ demande.typeDemande }}
                </span>
              </div>
            </div>

            <div class="demande-details">
              <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <span
                  >{{ formatDate(demande.dateDebut) }} -
                  {{ formatDate(demande.dateFin) }}</span
                >
              </div>
              <div class="detail-item">
                <i class="fas fa-clock"></i>
                <span>{{ demande.duree }} jour(s)</span>
              </div>
              <div class="detail-item" v-if="demande.motif">
                <i class="fas fa-comment"></i>
                <span>{{ demande.motif }}</span>
              </div>
            </div>

            <div class="validation-actions">
              <button
                @click="ouvrirModalValidation(demande, 'approve')"
                class="btn-approve"
              >
                <i class="fas fa-check"></i>
                Approuver
              </button>
              <button
                @click="ouvrirModalValidation(demande, 'reject')"
                class="btn-reject"
              >
                <i class="fas fa-times"></i>
                Rejeter
              </button>
              <button @click="voirDetails(demande)" class="btn-details">
                <i class="fas fa-eye"></i>
                Détails
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Demandes approuvées -->
      <div v-if="activeTab === 'approuvees'" class="tab-content">
        <div class="demandes-grid">
          <div
            v-for="demande in demandesApprouvees"
            :key="demande.id"
            class="demande-validation-card approved"
          >
            <div class="card-header">
              <div class="demande-info">
                <h3>{{ demande.prenom }} {{ demande.nom }}</h3>
                <p class="matricule">{{ demande.matricule }}</p>
                <p class="unite">{{ demande.unite }}</p>
              </div>
              <div class="status-approved">
                <i class="fas fa-check-circle"></i>
                <span>Approuvée</span>
              </div>
            </div>

            <div class="demande-details">
              <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <span
                  >{{ formatDate(demande.dateDebut) }} -
                  {{ formatDate(demande.dateFin) }}</span
                >
              </div>
              <div class="detail-item">
                <i class="fas fa-clock"></i>
                <span>{{ demande.duree }} jour(s)</span>
              </div>
              <div class="detail-item" v-if="demande.commentaireValidation">
                <i class="fas fa-comment"></i>
                <span>{{ demande.commentaireValidation }}</span>
              </div>
            </div>

            <div class="validation-info">
              <p class="validation-date">
                Approuvée le {{ formatDate(demande.dateValidation) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Demandes rejetées -->
      <div v-if="activeTab === 'rejetees'" class="tab-content">
        <div class="demandes-grid">
          <div
            v-for="demande in demandesRejetees"
            :key="demande.id"
            class="demande-validation-card rejected"
          >
            <div class="card-header">
              <div class="demande-info">
                <h3>{{ demande.prenom }} {{ demande.nom }}</h3>
                <p class="matricule">{{ demande.matricule }}</p>
                <p class="unite">{{ demande.unite }}</p>
              </div>
              <div class="status-rejected">
                <i class="fas fa-times-circle"></i>
                <span>Rejetée</span>
              </div>
            </div>

            <div class="demande-details">
              <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <span
                  >{{ formatDate(demande.dateDebut) }} -
                  {{ formatDate(demande.dateFin) }}</span
                >
              </div>
              <div class="detail-item">
                <i class="fas fa-clock"></i>
                <span>{{ demande.duree }} jour(s)</span>
              </div>
              <div class="detail-item" v-if="demande.commentaireValidation">
                <i class="fas fa-comment"></i>
                <span>{{ demande.commentaireValidation }}</span>
              </div>
            </div>

            <div class="validation-info">
              <p class="validation-date">
                Rejetée le {{ formatDate(demande.dateValidation) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de validation -->
    <div v-if="showValidationModal" class="modal-overlay" @click="fermerModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>
            {{ validationAction === "approve" ? "Approuver" : "Rejeter" }} la
            demande
          </h2>
          <button @click="fermerModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="demande-summary">
            <h3>{{ selectedDemande?.prenom }} {{ selectedDemande?.nom }}</h3>
            <p>
              {{ selectedDemande?.typeDemande }} -
              {{ selectedDemande?.duree }} jour(s)
            </p>
          </div>

          <div class="comment-section">
            <label for="commentaire">Commentaire (optionnel)</label>
            <textarea
              id="commentaire"
              v-model="commentaireValidation"
              rows="4"
              placeholder="Ajoutez un commentaire pour expliquer votre décision..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="fermerModal" class="btn-cancel">Annuler</button>
          <button
            @click="validerDemande"
            :class="[
              'btn-confirm',
              validationAction === 'approve' ? 'btn-approve' : 'btn-reject',
            ]"
          >
            <i
              :class="
                validationAction === 'approve' ? 'fas fa-check' : 'fas fa-times'
              "
            ></i>
            {{ validationAction === "approve" ? "Approuver" : "Rejeter" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useDemandesStore } from '@/stores/demandes';
import { useNotificationsStore } from '@/stores/notifications';
import { computed, onMounted, ref } from 'vue';

export default {
  name: 'ValidationDemandesView',
  setup() {
    const demandesStore = useDemandesStore();
    const notificationsStore = useNotificationsStore();

    const activeTab = ref('en-attente');
    const showValidationModal = ref(false);
    const selectedDemande = ref(null);
    const validationAction = ref(null);
    const commentaireValidation = ref('');

    onMounted(async () => {
      await demandesStore.fetchDemandesAValider();
    });

    const formater = (d) => ({
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
      dateValidation: d.date_validation,
      commentaireValidation: d.commentaire_validation,
    });

    const demandesEnAttente = computed(() =>
      demandesStore.demandesAValider
        .filter(d => d.statut === 'en_attente')
        .map(formater)
    );

    const demandesApprouvees = computed(() =>
      demandesStore.demandesAValider
        .filter(d => d.statut === 'approuve')
        .map(formater)
    );

    const demandesRejetees = computed(() =>
      demandesStore.demandesAValider
        .filter(d => d.statut === 'rejete')
        .map(formater)
    );

    const tabs = computed(() => [
      { id: 'en-attente', label: 'En attente', icon: 'fas fa-clock', count: demandesEnAttente.value.length },
      { id: 'approuvees', label: 'Approuvées', icon: 'fas fa-check-circle', count: demandesApprouvees.value.length },
      { id: 'rejetees', label: 'Rejetées', icon: 'fas fa-times-circle', count: demandesRejetees.value.length },
    ]);

    const ouvrirModalValidation = (demande, action) => {
      selectedDemande.value = demande;
      validationAction.value = action;
      commentaireValidation.value = '';
      showValidationModal.value = true;
    };

    const fermerModal = () => {
      showValidationModal.value = false;
      selectedDemande.value = null;
      validationAction.value = null;
      commentaireValidation.value = '';
    };

    const validerDemande = async () => {
      if (!selectedDemande.value) return;
      const result = await demandesStore.validateDemande(
        selectedDemande.value.id,
        validationAction.value,
        commentaireValidation.value
      );
      if (result.success) {
        const demandeur = `${selectedDemande.value.prenom} ${selectedDemande.value.nom}`;
        notificationsStore.notifyDemandeValidated?.(validationAction.value, demandeur);
        fermerModal();
        await demandesStore.fetchDemandesAValider();
      } else {
        alert('Erreur : ' + result.error);
      }
    };

    const voirDetails = (demande) => console.log('Détails:', demande);

    const formatDate = (dateString) => {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString('fr-FR');
    };

    const getTypeClass = (type) => {
      if (!type) return 'other';
      if (type.includes('annuel')) return 'annual';
      if (type.includes('maladie')) return 'sick';
      if (type.includes('report')) return 'report';
      return 'other';
    };

    return {
      activeTab, tabs, showValidationModal, selectedDemande,
      validationAction, commentaireValidation,
      demandesEnAttente, demandesApprouvees, demandesRejetees,
      ouvrirModalValidation, fermerModal, validerDemande,
      voirDetails, formatDate, getTypeClass,
      loading: computed(() => demandesStore.loading),
    };
  },
};
</script>

<style scoped>
.validation-demandes {
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

.validation-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 10px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  background: none;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
  font-size: 14px;
  color: #64748b;
  position: relative;
}

.tab-btn:hover {
  background: #f8fafc;
  color: #261555;
}

.tab-btn.active {
  background: linear-gradient(135deg, #008a9b, #00b4d8);
  color: white;
}

.tab-btn i {
  font-size: 16px;
}

.tab-count {
  background: rgba(255, 255, 255, 0.2);
  color: inherit;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
}

.tab-content {
  min-height: 400px;
}

.demandes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.demande-validation-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.demande-validation-card:hover {
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.demande-validation-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(135deg, #008a9b, #00b4d8);
}

.demande-validation-card.approved {
  border-left: 4px solid #008a9b;
}

.demande-validation-card.approved::before {
  background: linear-gradient(135deg, #008a9b, #00b4d8);
}

.demande-validation-card.rejected {
  border-left: 4px solid #261555;
}

.demande-validation-card.rejected::before {
  background: linear-gradient(135deg, #261555, #4c1d95);
}

.card-header {
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

.demande-type {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.type-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.type-badge.annual {
  background: rgba(0, 138, 155, 0.15);
  color: #008a9b;
  border: 1px solid rgba(0, 138, 155, 0.3);
}

.type-badge.sick {
  background: rgba(177, 0, 100, 0.15);
  color: #b10064;
  border: 1px solid rgba(177, 0, 100, 0.3);
}

.type-badge.report {
  background: rgba(38, 21, 85, 0.15);
  color: #261555;
  border: 1px solid rgba(38, 21, 85, 0.3);
}

.type-badge.other {
  background: #f3e8ff;
  color: #9333ea;
}

.status-approved,
.status-rejected {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 14px;
  font-weight: 500;
}

.status-approved {
  color: #008a9b;
}

.status-rejected {
  color: #261555;
}

.demande-details {
  margin-bottom: 15px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 14px;
}

.detail-item i {
  width: 16px;
  color: #64748b;
}

.detail-item span {
  color: #261555;
}

.validation-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.btn-approve,
.btn-reject,
.btn-details {
  padding: 8px 12px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: all 0.2s ease;
  font-weight: 500;
}

.btn-approve {
  background: linear-gradient(135deg, #008a9b, #00b4d8);
  color: white;
}

.btn-approve:hover {
  background: linear-gradient(135deg, #006d7a, #008a9b);
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-reject {
  background: linear-gradient(135deg, #261555, #4c1d95);
  color: white;
}

.btn-reject:hover {
  background: linear-gradient(135deg, #1e1b4b, #261555);
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-details {
  background: #f1f5f9;
  color: #64748b;
}

.btn-details:hover {
  background: #e2e8f0;
  transform: translateY(-2px);
}

.validation-info {
  border-top: 1px solid #e2e8f0;
  padding-top: 10px;
}

.validation-date {
  margin: 0;
  font-size: 12px;
  color: #64748b;
  text-align: right;
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
  max-width: 500px;
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
  font-size: 20px;
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

.demande-summary {
  background: #f8fafc;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.demande-summary h3 {
  margin: 0 0 5px 0;
  color: #261555;
}

.demande-summary p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.comment-section label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #261555;
}

.comment-section textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  resize: vertical;
  min-height: 100px;
  transition: border-color 0.2s ease;
}

.comment-section textarea:focus {
  outline: none;
  border-color: #008a9b;
  box-shadow: 0 0 0 3px rgba(0, 138, 155, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #e2e8f0;
}

.btn-cancel {
  padding: 10px 20px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #64748b;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-confirm {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 5px;
  color: white;
  transition: all 0.2s ease;
  font-weight: 500;
}

.btn-confirm.btn-approve {
  background: linear-gradient(135deg, #008a9b, #00b4d8);
}

.btn-confirm.btn-approve:hover {
  background: linear-gradient(135deg, #006d7a, #008a9b);
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-confirm.btn-reject {
  background: linear-gradient(135deg, #261555, #4c1d95);
}

.btn-confirm.btn-reject:hover {
  background: linear-gradient(135deg, #1e1b4b, #261555);
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

@media (max-width: 1024px) {
  .demandes-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .validation-tabs {
    flex-wrap: wrap;
  }

  .demandes-grid {
    grid-template-columns: 1fr;
  }

  .validation-actions {
    flex-wrap: wrap;
  }

  .modal-content {
    width: 95%;
    margin: 20px;
  }
}
</style>
