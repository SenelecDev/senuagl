import { defineStore } from 'pinia';
import { dashboardApi, demandesApi } from '@/services/api';

export const useCongesStore = defineStore('conges', {
  state: () => ({
    stats: {
      conges_restants: 0,
      conges_pris: 0,
      demandes_en_attente: 0,
      demandes_approuvees: 0,
      demandes_rejetees: 0,
    },
    prochainsConges: [],
    soldeConges: {
      congesAnnuel:     { acquis: 0, pris: 0, reste: 0, pourcentage: 0 },
      congesFractionnes:{ acquis: 0, pris: 0, reste: 0, pourcentage: 0 },
      autresConges:     { acquis: 0, pris: 0, reste: 0, pourcentage: 0 },
      congesPlanifies: 0,
    },
    historiqueConges: [],
    loading: false,
    error: null,
  }),

  getters: {
    getTotalAcquis: (state) =>
      state.soldeConges.congesAnnuel.acquis +
      state.soldeConges.congesFractionnes.acquis +
      state.soldeConges.autresConges.acquis,

    getTotalPris: (state) =>
      state.soldeConges.congesAnnuel.pris +
      state.soldeConges.congesFractionnes.pris +
      state.soldeConges.autresConges.pris,

    getSoldeDisponible: (state) =>
      state.soldeConges.congesAnnuel.reste +
      state.soldeConges.congesFractionnes.reste +
      state.soldeConges.autresConges.reste,
  },

  actions: {
    async fetchStats() {
      this.loading = true;
      this.error = null;
      try {
        // Charger les stats dashboard ET toutes les demandes en parallèle
        const [statsResponse, demandesResponse] = await Promise.all([
          dashboardApi.stats(),
          demandesApi.list({ per_page: 100 }),
        ]);

        if (statsResponse.data.success) {
          const data = statsResponse.data.data;
          this.stats = data.stats;
          this.prochainsConges = data.prochains_conges || [];
        }

        if (demandesResponse.data.success) {
          const demandes = demandesResponse.data.data.data || [];
          this._calculerSoldes(demandes);
        }
      } catch (error) {
        this.error = error.message;
        console.error('Erreur fetchStats:', error);
      } finally {
        this.loading = false;
      }
    },

    _calculerSoldes(demandes) {
  const typesAnnuel      = ['conge_annuel'];
  const typesFractionnes = ['conge_sans_solde', 'report_conge'];
  const typesAutres      = ['conge_maladie', 'conge_maternite', 'conge_paternite', 'absence_exceptionnelle'];

  const calculerType = (types, quotaFixe = null) => {
    const approuves = demandes
      .filter(d => types.includes(d.type_demande) && d.statut === 'approuve')
      .reduce((sum, d) => sum + (d.duree_jours || 0), 0);

    const enAttente = demandes
      .filter(d => types.includes(d.type_demande) && d.statut === 'en_attente')
      .reduce((sum, d) => sum + (d.duree_jours || 0), 0);

    if (quotaFixe !== null) {
      // Congé annuel : quota fixe attribué par l'admin
      const reste = Math.max(0, quotaFixe - approuves);
      const pourcentage = quotaFixe > 0 ? Math.round((approuves / quotaFixe) * 100) : 0;
      return { acquis: quotaFixe, pris: approuves, reste, pourcentage, enAttente };
    } else {
      // Fractionnés / autres : pas de quota, on suit approuvés + en attente
      const total = approuves + enAttente;
      const pourcentage = total > 0 ? Math.round((approuves / total) * 100) : 0;
      return { acquis: total, pris: approuves, reste: enAttente, pourcentage };
    }
  };

  const quotaAnnuel = this.stats.conges_restants + this.stats.conges_pris || 30;

  this.soldeConges.congesAnnuel      = calculerType(typesAnnuel, quotaAnnuel);
  this.soldeConges.congesFractionnes = calculerType(typesFractionnes);
  this.soldeConges.autresConges      = calculerType(typesAutres);

  this.soldeConges.congesPlanifies = demandes
    .filter(d => d.statut === 'en_attente')
    .reduce((sum, d) => sum + (d.duree_jours || 0), 0);
},

    async fetchHistorique(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await demandesApi.list(params);
        if (response.data.success) {
          this.historiqueConges = response.data.data.data || [];
        }
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },

    resetState() {
      this.stats = { conges_restants: 0, conges_pris: 0, demandes_en_attente: 0, demandes_approuvees: 0, demandes_rejetees: 0 };
      this.prochainsConges = [];
      this.historiqueConges = [];
      this.soldeConges = {
        congesAnnuel:     { acquis: 0, pris: 0, reste: 0, pourcentage: 0 },
        congesFractionnes:{ acquis: 0, pris: 0, reste: 0, pourcentage: 0 },
        autresConges:     { acquis: 0, pris: 0, reste: 0, pourcentage: 0 },
        congesPlanifies: 0,
      };
      this.error = null;
      this.loading = false;
    },
  },
});