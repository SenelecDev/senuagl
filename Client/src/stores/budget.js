import { defineStore } from 'pinia'
import api from '@/api/axios'
import { buildSuiviRows } from '@/utils/budgetHierarchy'

const getErrorMessage = (error, fallback) => {
  return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useBudgetStore = defineStore('budget', {
  state: () => ({
    annee: new Date().getFullYear(),
    periodeType: 'annee', // 'annee', 'trimestre', 'mois'
    periodeValue: null, // null for annee, 1-4 for trimestre, 1-12 for mois

    previsions: [],
    engagements: [],
    realisations: [],

    comptes: [],
    investissements: [],
    calculation: null,
    loading: false,
    loadingRefs: false,
    loadingInvestments: false,
    saving: false,
    calculating: false,
    error: null
  }),

  getters: {
    // We filter raw lists based on selected period (annee, trimestre, mois)
    filteredPrevisions: (state) => {
      return state.previsions.filter(p => {
        if (state.periodeType === 'annee') return true;
        const m = Number(p.mois);
        if (state.periodeType === 'mois') return m === state.periodeValue;
        if (state.periodeType === 'trimestre') return Math.ceil(m / 3) === state.periodeValue;
        return true;
      });
    },
    filteredEngagements: (state) => {
      return state.engagements.filter(e => {
        if (state.periodeType === 'annee') return true;
        const m = new Date(e.date_engagement).getMonth() + 1;
        if (state.periodeType === 'mois') return m === state.periodeValue;
        if (state.periodeType === 'trimestre') return Math.ceil(m / 3) === state.periodeValue;
        return true;
      });
    },
    filteredRealisations: (state) => {
      return state.realisations.filter(r => {
        if (state.periodeType === 'annee') return true;
        const m = new Date(r.date_realisation).getMonth() + 1;
        if (state.periodeType === 'mois') return m === state.periodeValue;
        if (state.periodeType === 'trimestre') return Math.ceil(m / 3) === state.periodeValue;
        return true;
      });
    },

    totalPrevu(state) { return this.filteredPrevisions.reduce((sum, row) => sum + Number(row.montant_prevu || 0), 0) },
    totalEngage(state) { return this.filteredEngagements.reduce((sum, row) => sum + Number(row.montant_engage || 0), 0) },
    totalRealise(state) { return this.filteredRealisations.reduce((sum, row) => sum + Number(row.montant_realise || 0), 0) },
    
    disponible(state) {
      return this.totalPrevu - this.totalEngage - this.totalRealise
    },
    tauxExecution(state) {
      if (!this.totalPrevu) return 0
      return Math.round(((this.totalEngage + this.totalRealise) / this.totalPrevu) * 1000) / 10
    },
    comptesSaisissables: (state) => {
      return state.comptes.filter(compte => Number(compte.enfants_count || 0) === 0)
    },
    
    // Pass filtered lists to hierarchy builder
    budgetRows: (state) => buildSuiviRows(
      state.filteredPrevisions, 
      state.filteredEngagements, 
      state.filteredRealisations, 
      state.comptes,
      state.annee
    )
  },

  actions: {
    setAnnee(annee) {
      this.annee = Number(annee)
      return this.fetchBudget()
    },
    setPeriode(type, value) {
      this.periodeType = type;
      this.periodeValue = value;
    },

    async fetchReferentiels() {
      this.loadingRefs = true
      try {
        const response = await api.get('/budget/referentiels')
        this.comptes = response.data.comptes || []
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors du chargement des référentiels budget.')
        throw error
      } finally {
        this.loadingRefs = false
      }
    },

    async fetchBudget() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/budget', { params: { annee: this.annee } })
        this.previsions = response.data.previsions || []
        this.engagements = response.data.engagements || []
        this.realisations = response.data.realisations || []
      } catch (error) {
        this.previsions = []
        this.engagements = []
        this.realisations = []
        this.error = getErrorMessage(error, 'Erreur lors du chargement du suivi budgétaire.')
      } finally {
        this.loading = false
      }
    },

    async createEntry(payload, type) {
      this.saving = true
      this.error = null
      try {
        await api.post('/budget', { ...payload, type })
        await this.fetchBudget()
      } catch (error) {
        this.error = getErrorMessage(error, `Erreur lors de l’enregistrement de ${type}.`)
        throw error
      } finally {
        this.saving = false
      }
    },
    
    async deleteEntry(type, id) {
       this.saving = true
       try {
         await api.delete(`/budget/${type}/${id}`)
         await this.fetchBudget()
       } catch (error) {
         this.error = getErrorMessage(error, `Erreur lors de la suppression de ${type}.`)
         throw error
       } finally {
         this.saving = false
       }
    },

    async fetchInvestissements() {
      this.loadingInvestments = true
      try {
        const response = await api.get('/investissements')
        this.investissements = response.data || []
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors du chargement des investissements.')
      } finally {
        this.loadingInvestments = false
      }
    },

    async calculateInvestment(payload) {
      this.calculating = true
      this.error = null
      try {
        const response = await api.post('/investissements/calculate', payload)
        this.calculation = response.data
        return response.data
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors du calcul des indicateurs investissement.')
        throw error
      } finally {
        this.calculating = false
      }
    },

    async createInvestment(payload) {
      this.saving = true
      this.error = null
      try {
        const response = await api.post('/investissements', payload)
        await this.fetchInvestissements()
        return response.data
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de l’enregistrement de l’investissement.')
        throw error
      } finally {
        this.saving = false
      }
    },

    async createCompte(payload) {
      this.saving = true
      this.error = null
      try {
        const response = await api.post('/budget/comptes', payload)
        this.comptes.push(response.data)
        return response.data
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de la création du compte.')
        throw error
      } finally {
        this.saving = false
      }
    }
  }
})
