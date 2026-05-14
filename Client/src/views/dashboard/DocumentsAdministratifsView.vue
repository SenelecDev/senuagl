<template>
  <div class="documents-administratifs">
    <!-- Section des filtres modernisée -->
    <div class="filters-container">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Rechercher une demande..."
          class="search-input"
        >
      </div>

      <div class="filter-chips">
        <div class="filter-dropdown">
          <button 
            class="filter-button" 
            :class="{ active: showTypeFilter }"
            @click="toggleTypeFilter"
          >
            <i class="fas fa-filter"></i>
            Type de demande
            <i class="fas fa-chevron-down"></i>
          </button>
          <div class="dropdown-content" v-if="showTypeFilter">
            <div 
              class="filter-option" 
              v-for="type in typesDemande" 
              :key="type.value"
              :class="{ selected: typeDemandeFiltre === type.value }"
              @click="selectType(type.value)"
            >
              <i :class="type.icon"></i>
              {{ type.label }}
              <i class="fas fa-check" v-if="typeDemandeFiltre === type.value"></i>
            </div>
          </div>
        </div>

        <div class="filter-dropdown">
          <button 
            class="filter-button" 
            :class="{ active: showPeriodFilter }"
            @click="togglePeriodFilter"
          >
            <i class="fas fa-calendar"></i>
            Période
            <i class="fas fa-chevron-down"></i>
          </button>
          <div class="dropdown-content" v-if="showPeriodFilter">
            <div 
              class="filter-option" 
              v-for="period in periodes" 
              :key="period.value"
              :class="{ selected: periodeFiltre === period.value }"
              @click="selectPeriode(period.value)"
            >
              <i :class="period.icon"></i>
              {{ period.label }}
              <i class="fas fa-check" v-if="periodeFiltre === period.value"></i>
            </div>
          </div>
        </div>

        <!-- Chips pour les filtres actifs -->
        <div class="active-filters" v-if="hasActiveFilters">
          <div class="filter-chip" v-if="typeDemandeFiltre !== 'tous'">
            {{ getTypeLabel(typeDemandeFiltre) }}
            <button @click="resetTypeFilter">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="filter-chip" v-if="periodeFiltre !== 'tous'">
            {{ getPeriodeLabel(periodeFiltre) }}
            <button @click="resetPeriodFilter">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <p v-if="loadError" class="load-banner load-banner--error">{{ loadError }}</p>
    <p v-else-if="loading" class="load-banner">Chargement des demandes approuvées…</p>

    <!-- Liste des demandes avec design moderne -->
    <div class="demandes-container">
      <div class="demandes-grid">
        <div 
          v-for="demande in demandesFiltrees" 
          :key="demande.id" 
          class="demande-card"
          :class="getTypeBadgeClass(demande.type)"
        >
          <div class="card-header">
            <div class="employee-info">
              <div class="avatar">{{ getInitials(demande.employe) }}</div>
              <div class="details">
                <h3>{{ demande.employe }}</h3>
                <span class="service">{{ demande.service }}</span>
              </div>
            </div>
            <div class="type-indicator">
              <i :class="getTypeIcon(demande.type)"></i>
              {{ demande.type }}
            </div>
          </div>

          <div class="card-body">
            <div class="date-range">
              <i class="fas fa-calendar-alt"></i>
              <div class="dates">
                <div class="date-item">
                  <span class="label">Début</span>
                  <span class="value">{{ formatDate(demande.dateDebut) }}</span>
                </div>
                <div class="date-separator"></div>
                <div class="date-item">
                  <span class="label">Fin</span>
                  <span class="value">{{ formatDate(demande.dateFin) }}</span>
                </div>
              </div>
            </div>

            <div class="approval-flow">
              <div class="flow-item approved">
                <i class="fas fa-check-circle"></i>
                <span>Supérieur</span>
              </div>
              <div class="flow-arrow">
                <i class="fas fa-chevron-right"></i>
              </div>
              <div class="flow-item approved">
                <i class="fas fa-check-circle"></i>
                <span>Directeur</span>
              </div>
              <div class="flow-arrow">
                <i class="fas fa-chevron-right"></i>
              </div>
              <div class="flow-item approved">
                <i class="fas fa-check-circle"></i>
                <span>RH</span>
              </div>
            </div>
          </div>

          <div class="card-actions">
            <button class="btn-preview" @click="previewDocument(demande)">
              <i class="fas fa-eye"></i>
              Aperçu
            </button>
            <button
              class="btn-generate"
              :disabled="pdfGenerating"
              @click="generateDocument(demande)"
            >
              <i class="fas fa-file-pdf"></i>
              Générer
            </button>
          </div>
        </div>
      </div>

      <!-- État vide amélioré -->
      <div v-if="demandesFiltrees.length === 0" class="empty-state">
        <div class="empty-illustration">
          <i class="fas fa-file-alt"></i>
        </div>
        <h3>Aucune demande trouvée</h3>
        <p>Il n'y a aucune demande correspondant à vos critères de recherche</p>
        <button class="btn-reset" @click="resetFilters">
          <i class="fas fa-redo"></i>
          Réinitialiser les filtres
        </button>
      </div>
    </div>

    <!-- Modal d'aperçu modernisé -->
    <div v-if="showPreviewModal" class="modal-overlay" @click="closePreview">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <div class="modal-title">
            <i class="fas fa-file-alt"></i>
            <h2>Aperçu du Document</h2>
          </div>
          <button class="btn-close" @click="closePreview">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="document-preview">
            <div class="preview-content">
              <div class="preview-header">
                <img src="@/assets/images/logo-senelec.png" alt="Logo SENELEC" class="preview-logo">
                <h1>{{ getDocumentTitle(selectedDemande?.type) }}</h1>
              </div>
              <div class="preview-body">
                <p class="preview-text">Je soussigné, Directeur des Ressources Humaines de SENELEC,</p>
                <p class="preview-text">Atteste que M./Mme <strong>{{ selectedDemande?.employe }}</strong>,</p>
                <p class="preview-text">En service au département <strong>{{ selectedDemande?.service }}</strong>,</p>
                <p class="preview-text">
                  Bénéficie {{ absenceArticle(selectedDemande?.type) }}
                  {{ (selectedDemande?.type || '').toLowerCase() }} pour la période du
                  <strong>{{ formatDate(selectedDemande?.dateDebut) }}</strong> au
                  <strong>{{ formatDate(selectedDemande?.dateFin) }}</strong>.
                </p>
                <div class="signature-section">
                  <p>Fait à Dakar, le {{ formatDate(new Date()) }}</p>
                  <div class="signature-placeholder">
                    <p>Le Directeur des Ressources Humaines</p>
                    <div class="signature-box">
                      <i class="fas fa-pen"></i>
                      Signature
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closePreview">
            <i class="fas fa-times"></i>
            Annuler
          </button>
          <button
            class="btn-primary"
            :disabled="pdfGenerating"
            @click="generateDocument(selectedDemande)"
          >
            <i class="fas fa-file-pdf"></i>
            {{ pdfGenerating ? 'Génération…' : 'Générer le PDF' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { demandesApi } from "@/services/api";

export default {
  name: "DocumentsAdministratifsView",
  data() {
    return {
      searchQuery: "",
      showTypeFilter: false,
      showPeriodFilter: false,
      typeDemandeFiltre: "tous",
      periodeFiltre: "tous",
      showPreviewModal: false,
      selectedDemande: null,
      loading: false,
      loadError: null,
      pdfGenerating: false,
      typesDemande: [
        { value: "tous", label: "Tous les types", icon: "fas fa-list" },
        { value: "conges", label: "Congés annuels", icon: "fas fa-umbrella-beach" },
        { value: "absence", label: "Absences", icon: "fas fa-user-clock" },
        { value: "report", label: "Reports", icon: "fas fa-history" }
      ],
      periodes: [
        { value: "tous", label: "Toutes les périodes", icon: "fas fa-calendar" },
        { value: "semaine", label: "Cette semaine", icon: "fas fa-calendar-week" },
        { value: "mois", label: "Ce mois", icon: "fas fa-calendar-alt" },
        { value: "trimestre", label: "Ce trimestre", icon: "fas fa-calendar-check" }
      ],
      demandes: []
    };
  },
  mounted() {
    this.loadDemandes();
  },
  computed: {
    hasActiveFilters() {
      return this.typeDemandeFiltre !== "tous" || this.periodeFiltre !== "tous";
    },
    demandesFiltrees() {
      let demandes = [...this.demandes];
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        demandes = demandes.filter(d => 
          d.employe.toLowerCase().includes(query) ||
          d.service.toLowerCase().includes(query)
        );
      }

      if (this.typeDemandeFiltre !== "tous") {
        demandes = demandes.filter((d) => {
          const t = (d.type_demande || "").toLowerCase();
          if (this.typeDemandeFiltre === "conges") {
            return (
              t.includes("conge") &&
              !t.includes("report") &&
              t !== "report_conge"
            );
          }
          if (this.typeDemandeFiltre === "absence") {
            return t.includes("absence");
          }
          if (this.typeDemandeFiltre === "report") {
            return t.includes("report");
          }
          return true;
        });
      }

      return demandes;
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "long",
        year: "numeric"
      });
    },
    getInitials(name) {
      return name
        .split(" ")
        .map(n => n[0])
        .join("")
        .toUpperCase();
    },
    getTypeIcon(type) {
      const t = (type || "").toLowerCase();
      if (t.includes("absence")) return "fas fa-user-clock";
      if (t.includes("report")) return "fas fa-history";
      if (t.includes("congé") || t.includes("conge")) return "fas fa-umbrella-beach";
      const icons = {
        "Congés Annuels": "fas fa-umbrella-beach",
        Absence: "fas fa-user-clock",
        Report: "fas fa-history"
      };
      return icons[type] || "fas fa-file-alt";
    },
    getTypeBadgeClass(type) {
      const t = (type || "").toLowerCase();
      if (t.includes("absence")) return "type-absence";
      if (t.includes("report")) return "type-report";
      if (t.includes("congé") || t.includes("conge")) return "type-conges";
      const classes = {
        "Congés Annuels": "type-conges",
        Absence: "type-absence",
        Report: "type-report"
      };
      return classes[type] || "type-default";
    },
    getTypeLabel(value) {
      const type = this.typesDemande.find(t => t.value === value);
      return type ? type.label : "";
    },
    getPeriodeLabel(value) {
      const periode = this.periodes.find(p => p.value === value);
      return periode ? periode.label : "";
    },
    getDocumentTitle(type) {
      const t = (type || "").toLowerCase();
      if (t.includes("absence")) return "ATTESTATION D'ABSENCE";
      if (t.includes("report")) return "ATTESTATION DE REPORT DE CONGÉ";
      return "ATTESTATION DE CONGÉ";
    },
    absenceArticle(typeLabel) {
      const t = (typeLabel || "").toLowerCase();
      return t.includes("absence") ? "d'une" : "d'un";
    },
    mapDemandeRow(row) {
      const u = row.user || {};
      const dept = u.department || {};
      const employe =
        u.full_name ||
        [u.first_name, u.name].filter(Boolean).join(" ").trim() ||
        "—";
      return {
        id: row.id,
        type_demande: row.type_demande,
        type: row.type_label || row.type_demande || "—",
        employe,
        service: dept.name || "—",
        dateDebut: row.date_debut,
        dateFin: row.date_fin,
        statut: row.statut
      };
    },
    async loadDemandes() {
      this.loading = true;
      this.loadError = null;
      try {
        const { data: body } = await demandesApi.listApprovedForDocuments({
          per_page: 100
        });
        const page = body?.data;
        const rows = Array.isArray(page?.data)
          ? page.data
          : Array.isArray(page)
            ? page
            : [];
        this.demandes = rows.map((r) => this.mapDemandeRow(r));
      } catch (e) {
        const status = e.response?.status;
        const apiMsg = e.response?.data?.message;
        if (status === 404) {
          this.loadError =
            "L’API ne trouve pas la route des documents (serveur pas à jour ou cache routes). " +
            "Sur Docker : reconstruire ou redémarrer le service Laravel, par ex. " +
            "`docker compose up -d --build laravel` puis `docker compose restart laravel`.";
        } else {
          this.loadError = apiMsg || e.message || "Impossible de charger les demandes.";
        }
        this.demandes = [];
      } finally {
        this.loading = false;
      }
    },
    toggleTypeFilter() {
      this.showTypeFilter = !this.showTypeFilter;
      this.showPeriodFilter = false;
    },
    togglePeriodFilter() {
      this.showPeriodFilter = !this.showPeriodFilter;
      this.showTypeFilter = false;
    },
    selectType(type) {
      this.typeDemandeFiltre = type;
      this.showTypeFilter = false;
    },
    selectPeriode(periode) {
      this.periodeFiltre = periode;
      this.showPeriodFilter = false;
    },
    resetTypeFilter() {
      this.typeDemandeFiltre = "tous";
    },
    resetPeriodFilter() {
      this.periodeFiltre = "tous";
    },
    resetFilters() {
      this.typeDemandeFiltre = "tous";
      this.periodeFiltre = "tous";
      this.searchQuery = "";
    },
    previewDocument(demande) {
      this.selectedDemande = demande;
      this.showPreviewModal = true;
    },
    closePreview() {
      this.showPreviewModal = false;
      this.selectedDemande = null;
    },
    async generateDocument(demande) {
      if (!demande?.id) {
        alert("Demande invalide.");
        return;
      }
      this.pdfGenerating = true;
      try {
        const response = await demandesApi.attestationPdf(demande.id);
        const blob = new Blob([response.data], { type: "application/pdf" });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", `attestation-conge-${demande.id}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        this.closePreview();
      } catch (e) {
        let msg = "Échec de la génération du PDF.";
        const d = e.response?.data;
        if (d instanceof Blob) {
          try {
            const text = await d.text();
            const j = JSON.parse(text);
            if (j.message) msg = j.message;
          } catch (_) {
            /* ignore */
          }
        } else if (e.response?.data?.message) {
          msg = e.response.data.message;
        }
        alert(msg);
      } finally {
        this.pdfGenerating = false;
      }
    }
  }
};
</script>

<style scoped>
.documents-administratifs {
  padding: 2rem;
  background: #f8fafc;
  min-height: 100vh;
}

.load-banner {
  margin: 0 0 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.95rem;
}

.load-banner--error {
  background: #fef2f2;
  color: #b91c1c;
}

/* Style du header harmonisé avec le dashboard */
.dashboard-header {
  text-align: center;
  margin-bottom: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  border-left: 4px solid #008a9b;
}

.dashboard-header h1 {
  color: #261555;
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.dashboard-header p {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0;
}

.filters-container {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.search-bar {
  position: relative;
  margin-bottom: 1rem;
}

.search-bar i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #008a9b;
  box-shadow: 0 0 0 3px rgba(0, 138, 155, 0.1);
}

.filter-chips {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-dropdown {
  position: relative;
}

.filter-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-button:hover {
  border-color: #008a9b;
  color: #008a9b;
}

.filter-button.active {
  background: #f0f9ff;
  border-color: #008a9b;
  color: #008a9b;
}

.dropdown-content {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 0.5rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  min-width: 200px;
  z-index: 10;
}

.filter-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-option:hover {
  background: #f0f9ff;
}

.filter-option.selected {
  background: #008a9b;
  color: white;
}

.active-filters {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.filter-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f0f9ff;
  border-radius: 9999px;
  color: #008a9b;
  font-size: 0.875rem;
}

.filter-chip button {
  background: none;
  border: none;
  color: #008a9b;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
}

.demandes-container {
  margin-top: 2rem;
}

.demandes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.demande-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.demande-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.avatar {
  width: 40px;
  height: 40px;
  background: #008a9b;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.details h3 {
  color: #1e293b;
  font-size: 1.1rem;
  margin: 0;
}

.service {
  color: #64748b;
  font-size: 0.875rem;
}

.type-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #008a9b;
}

.card-body {
  padding: 1.5rem;
}

.date-range {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.date-range i {
  color: #008a9b;
  margin-top: 0.25rem;
}

.dates {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.date-item {
  display: flex;
  flex-direction: column;
}

.date-item .label {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.date-item .value {
  color: #1e293b;
  font-weight: 500;
}

.date-separator {
  width: 20px;
  height: 2px;
  background: #e2e8f0;
  margin-top: 1.25rem;
}

.approval-flow {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.flow-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.flow-item.approved i {
  color: #059669;
}

.flow-item span {
  font-size: 0.75rem;
  color: #64748b;
}

.flow-arrow {
  color: #cbd5e1;
}

.card-actions {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 1rem;
}

.btn-preview,
.btn-generate {
  flex: 1;
  padding: 0.75rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-preview {
  background: white;
  border: 1px solid #008a9b;
  color: #008a9b;
}

.btn-preview:hover {
  background: #f0f9ff;
}

.btn-generate {
  background: #008a9b;
  border: none;
  color: white;
}

.btn-generate:hover:not(:disabled) {
  background: #006d7a;
}

.btn-generate:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-illustration {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  color: #1e293b;
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

.btn-reset {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: white;
  border: 1px solid #008a9b;
  color: #008a9b;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-reset:hover {
  background: #f0f9ff;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #1e293b;
}

.modal-title i {
  color: #008a9b;
}

.btn-close {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.modal-body {
  padding: 2rem;
}

.preview-content {
  background: white;
  padding: 3rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.preview-header {
  text-align: center;
  margin-bottom: 3rem;
}

.preview-logo {
  width: 200px;
  margin-bottom: 2rem;
}

.preview-header h1 {
  color: #1e293b;
  font-size: 1.5rem;
  font-weight: 700;
}

.preview-text {
  margin-bottom: 1.5rem;
  line-height: 1.8;
  color: #1e293b;
}

.signature-section {
  margin-top: 4rem;
  text-align: right;
}

.signature-placeholder {
  margin-top: 2rem;
}

.signature-box {
  margin-top: 1rem;
  padding: 2rem;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary,
.btn-primary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-secondary {
  background: white;
  border: 1px solid #e2e8f0;
  color: #64748b;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-primary {
  background: #008a9b;
  border: none;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #006d7a;
}

.btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .documents-administratifs {
    padding: 1rem;
  }

  .demandes-grid {
    grid-template-columns: 1fr;
  }

  .filter-chips {
    flex-direction: column;
  }

  .filter-dropdown {
    width: 100%;
  }

  .filter-button {
    width: 100%;
    justify-content: space-between;
  }

  .card-actions {
    flex-direction: column;
  }

  .modal-content {
    width: 95%;
    margin: 1rem;
  }

  .preview-content {
    padding: 1.5rem;
  }
}
</style> 