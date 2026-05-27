import { defineStore } from 'pinia'
import api from '@/api/axios'
import { buildMensuelPivotRows, buildMensuelRows, buildSuiviRows } from '@/utils/budgetHierarchy'

const getErrorMessage = (error, fallback) => {
  return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useBudgetStore = defineStore('budget', {
  state: () => ({
    annee: new Date().getFullYear(),
    previsions: [],
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
    totalPrevu: (state) => state.previsions.reduce((sum, row) => sum + Number(row.montant_prevu || 0), 0),
    totalRealise: (state) => state.realisations.reduce((sum, row) => sum + Number(row.montant_realise || 0), 0),
    ecartGlobal() {
      return this.totalPrevu - this.totalRealise
    },
    tauxExecution() {
      if (!this.totalPrevu) return 0
      return Math.round((this.totalRealise / this.totalPrevu) * 1000) / 10
    },
    comptesSaisissables: (state) => {
      return state.comptes.filter(compte => Number(compte.enfants_count || 0) === 0)
    },
    budgetRows: (state) => buildSuiviRows(state.previsions, state.realisations, state.comptes),

    /**
     * Vue mensuelle pivot : pour chaque combinaison service/compte,
     * un objet avec les montants réalisés par mois (1-12).
     */
    vuesMensuelles: (state) => buildMensuelRows(state.previsions, state.realisations, state.comptes),
    vuesMensuellesPivot: (state) =>
      buildMensuelPivotRows(state.previsions, state.realisations, state.comptes)
  },

  actions: {
    setAnnee(annee) {
      this.annee = Number(annee)
      return this.fetchBudget()
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
        this.realisations = response.data.realisations || []
      } catch (error) {
        this.previsions = []
        this.realisations = []
        this.error = getErrorMessage(error, 'Erreur lors du chargement du suivi budgétaire.')
      } finally {
        this.loading = false
      }
    },

    async createPrevision(payload) {
      this.saving = true
      this.error = null

      try {
        await api.post('/budget', { ...payload, type: 'prevision' })
        await this.fetchBudget()
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de l’enregistrement de la prévision.')
        throw error
      } finally {
        this.saving = false
      }
    },

    async createRealisation(payload) {
      this.saving = true
      this.error = null

      try {
        await api.post('/budget', { ...payload, type: 'realisation' })
        await this.fetchBudget()
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de la création de la réalisation.')
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
    }
  }
})
