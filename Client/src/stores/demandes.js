import { defineStore } from 'pinia';
import { demandesApi } from '@/services/api';

export const useDemandesStore = defineStore('demandes', {
  state: () => ({
    demandes: [],
    demandesAValider: [],
    loading: false,
    error: null,
    currentDemande: null,
  }),

  getters: {
    // Demandes par statut
    demandesEnAttente: (state) =>
      state.demandes.filter((d) => d.statut === 'en_attente'),
    demandesApprouvees: (state) =>
      state.demandes.filter((d) => d.statut === 'approuve'),
    demandesRejetees: (state) =>
      state.demandes.filter((d) => d.statut === 'rejete'),

    // Demandes par type
    demandesParType: (state) => {
      return state.demandes.reduce((acc, demande) => {
        if (!acc[demande.type_demande]) {
          acc[demande.type_demande] = [];
        }
        acc[demande.type_demande].push(demande);
        return acc;
      }, {});
    },

    // Statistiques
    totalDemandes: (state) => state.demandes.length,
    totalEnAttente: (state) => state.demandes.filter(d => d.statut === 'en_attente').length,
    totalApprouvees: (state) => state.demandes.filter(d => d.statut === 'approuve').length,
    totalRejetees: (state) => state.demandes.filter(d => d.statut === 'rejete').length,

    // Demandes récentes
    demandesRecentes: (state) => {
      return state.demandes
        .slice()
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        .slice(0, 5);
    },
  },

  actions: {
    async fetchDemandes(params = {}) {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.list(params);
        
        if (response.data.success) {
          this.demandes = response.data.data.data || [];
        } else {
          this.error = response.data.message || 'Erreur lors du chargement des demandes';
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des demandes';
        console.error('Erreur fetchDemandes:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchDemandesAValider(params = {}) {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.demandesAValider(params);
        
        if (response.data.success) {
          this.demandesAValider = response.data.data.data || [];
        } else {
          this.error = response.data.message || 'Erreur lors du chargement des demandes à valider';
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des demandes à valider';
        console.error('Erreur fetchDemandesAValider:', error);
      } finally {
        this.loading = false;
      }
    },

    async createDemande(demandeData) {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.create(demandeData);
        
        if (response.data.success) {
          this.demandes.unshift(response.data.data);
          return { success: true, data: response.data.data };
        } else {
          this.error = response.data.message || 'Erreur lors de la création de la demande';
          return { success: false, error: this.error };
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la création de la demande';
        console.error('Erreur createDemande:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },

    async updateDemande(demandeId, demandeData) {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.update(demandeId, demandeData);
        
        if (response.data.success) {
          const index = this.demandes.findIndex(d => d.id === demandeId);
          if (index !== -1) {
            this.demandes[index] = response.data.data;
          }
          return { success: true, data: response.data.data };
        } else {
          this.error = response.data.message || 'Erreur lors de la mise à jour de la demande';
          return { success: false, error: this.error };
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour de la demande';
        console.error('Erreur updateDemande:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },

    async deleteDemande(demandeId) {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.delete(demandeId);
        
        if (response.data.success) {
          this.demandes = this.demandes.filter(d => d.id !== demandeId);
          return { success: true };
        } else {
          this.error = response.data.message || 'Erreur lors de la suppression de la demande';
          return { success: false, error: this.error };
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la suppression de la demande';
        console.error('Erreur deleteDemande:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },

    async validateDemande(demandeId, action, commentaire = '') {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.validate(demandeId, {
          action,
          commentaire
        });
        
        if (response.data.success) {
          // Mettre à jour la demande dans les listes
          const updateDemande = (list) => {
            const index = list.findIndex(d => d.id === demandeId);
            if (index !== -1) {
              list[index] = response.data.data;
            }
          };

          updateDemande(this.demandes);
          updateDemande(this.demandesAValider);

          await this.fetchDemandesAValider();
          return { success: true, data: response.data.data };
        } else {
          this.error = response.data.message || 'Erreur lors de la validation de la demande';
          return { success: false, error: this.error };
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la validation de la demande';
        console.error('Erreur validateDemande:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },

    async fetchDemande(demandeId) {
      this.loading = true;
      this.error = null;

      try {
        const response = await demandesApi.get(demandeId);
        
        if (response.data.success) {
          this.currentDemande = response.data.data;
          return { success: true, data: response.data.data };
        } else {
          this.error = response.data.message || 'Erreur lors du chargement de la demande';
          return { success: false, error: this.error };
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement de la demande';
        console.error('Erreur fetchDemande:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },

    // Méthodes utilitaires
    clearError() {
      this.error = null;
    },

    clearCurrentDemande() {
      this.currentDemande = null;
    },

    resetState() {
      this.demandes = [];
      this.demandesAValider = [];
      this.currentDemande = null;
      this.error = null;
      this.loading = false;
    },
  },
});
