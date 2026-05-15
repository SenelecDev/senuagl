import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
  return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

const makeEmptyMonths = () => {
  const mois = {}
  for (let m = 1; m <= 12; m++) mois[m] = 0
  return mois
}

export const useBudgetStore = defineStore('budget', {
  state: () => ({
    annee: new Date().getFullYear(),
    previsions: [],
    realisations: [],
    services: [],
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
    budgetRows: (state) => {
      const rows = new Map()
      const compteById = new Map(state.comptes.map(compte => [Number(compte.id), compte]))

      state.previsions.forEach((prevision) => {
        const key = `${prevision.service_id}-${prevision.compte_id}-${prevision.annee}`
        rows.set(key, {
          key,
          service_id: prevision.service_id,
          compte_id: prevision.compte_id,
          annee: prevision.annee,
          service: prevision.service,
          compte: prevision.compte,
          montant_prevu: Number(prevision.montant_prevu || 0),
          montant_realise: 0
        })
      })

      state.realisations.forEach((realisation) => {
        const key = `${realisation.service_id}-${realisation.compte_id}-${realisation.annee}`
        const existing = rows.get(key) || {
          key,
          service_id: realisation.service_id,
          compte_id: realisation.compte_id,
          annee: realisation.annee,
          service: realisation.service,
          compte: realisation.compte,
          montant_prevu: 0,
          montant_realise: 0
        }

        existing.montant_realise += Number(realisation.montant_realise || 0)
        rows.set(key, existing)
      })

      const detailRows = [...rows.values()].map((row) => {
        const ecart = row.montant_prevu - row.montant_realise
        return {
          ...row,
          ecart,
          niveau: row.compte?.parent_id ? 1 : 0,
          is_regroupement: false,
          taux_execution: row.montant_prevu ? Math.round((row.montant_realise / row.montant_prevu) * 1000) / 10 : 0
        }
      })

      const regroupements = new Map()
      detailRows.forEach((row) => {
        const parentId = Number(row.compte?.parent_id || 0)
        const parent = compteById.get(parentId)
        if (!parent) return

        const key = `regroupement-${row.service_id}-${parent.id}-${row.annee}`
        const existing = regroupements.get(key) || {
          key,
          service_id: row.service_id,
          compte_id: parent.id,
          annee: row.annee,
          service: row.service,
          compte: parent,
          montant_prevu: 0,
          montant_realise: 0,
          is_regroupement: true,
          niveau: 0
        }

        existing.montant_prevu += row.montant_prevu
        existing.montant_realise += row.montant_realise
        regroupements.set(key, existing)
      })

      const regroupementRows = [...regroupements.values()].map((row) => {
        const ecart = row.montant_prevu - row.montant_realise
        return {
          ...row,
          ecart,
          taux_execution: row.montant_prevu ? Math.round((row.montant_realise / row.montant_prevu) * 1000) / 10 : 0
        }
      })

      return [...regroupementRows, ...detailRows].sort((a, b) => {
        const serviceCompare = String(a.service?.code || '').localeCompare(String(b.service?.code || ''))
        if (serviceCompare !== 0) return serviceCompare
        const compteCompare = String(a.compte?.numero || '').localeCompare(String(b.compte?.numero || ''))
        if (compteCompare !== 0) return compteCompare
        return Number(a.niveau || 0) - Number(b.niveau || 0)
      })
    },

    /**
     * Vue mensuelle pivot : pour chaque combinaison service/compte,
     * un objet avec les montants réalisés par mois (1-12).
     */
    vuesMensuelles: (state) => {
      const rows = new Map()
      const compteById = new Map(state.comptes.map(compte => [Number(compte.id), compte]))

      // Initialiser avec les prévisions
      state.previsions.forEach((prevision) => {
        const key = `${prevision.service_id}-${prevision.compte_id}`
        if (!rows.has(key)) {
          rows.set(key, {
            key,
            service_id: prevision.service_id,
            compte_id: prevision.compte_id,
            service: prevision.service,
            compte: prevision.compte,
            mois: makeEmptyMonths(),
            montant_prevu: 0
          })
        }
        rows.get(key).montant_prevu += Number(prevision.montant_prevu || 0)
      })

      // Ventiler les réalisations par mois
      state.realisations.forEach((realisation) => {
        const key = `${realisation.service_id}-${realisation.compte_id}`
        if (!rows.has(key)) {
          rows.set(key, {
            key,
            service_id: realisation.service_id,
            compte_id: realisation.compte_id,
            service: realisation.service,
            compte: realisation.compte,
            mois: makeEmptyMonths(),
            montant_prevu: 0
          })
        }
        const row = rows.get(key)
        const m = Number(realisation.mois)
        if (m >= 1 && m <= 12) {
          row.mois[m] += Number(realisation.montant_realise || 0)
        }
      })

      const detailRows = [...rows.values()].map((row) => {
        const totalRealise = Object.values(row.mois).reduce((s, v) => s + v, 0)
        const ecart = row.montant_prevu - totalRealise
        return {
          ...row,
          totalRealise,
          ecart,
          niveau: row.compte?.parent_id ? 1 : 0,
          is_regroupement: false,
          taux_execution: row.montant_prevu
            ? Math.round((totalRealise / row.montant_prevu) * 1000) / 10
            : 0
        }
      })

      const regroupements = new Map()
      detailRows.forEach((row) => {
        const parentId = Number(row.compte?.parent_id || 0)
        const parent = compteById.get(parentId)
        if (!parent) return

        const key = `regroupement-${row.service_id}-${parent.id}`
        const existing = regroupements.get(key) || {
          key,
          service_id: row.service_id,
          compte_id: parent.id,
          service: row.service,
          compte: parent,
          mois: makeEmptyMonths(),
          montant_prevu: 0,
          is_regroupement: true,
          niveau: 0
        }

        existing.montant_prevu += row.montant_prevu
        for (let m = 1; m <= 12; m++) {
          existing.mois[m] += Number(row.mois[m] || 0)
        }
        regroupements.set(key, existing)
      })

      const regroupementRows = [...regroupements.values()].map((row) => {
        const totalRealise = Object.values(row.mois).reduce((s, v) => s + v, 0)
        const ecart = row.montant_prevu - totalRealise
        return {
          ...row,
          totalRealise,
          ecart,
          taux_execution: row.montant_prevu
            ? Math.round((totalRealise / row.montant_prevu) * 1000) / 10
            : 0
        }
      })

      return [...regroupementRows, ...detailRows].sort((a, b) => {
        const serviceCompare = String(a.service?.code || '').localeCompare(String(b.service?.code || ''))
        if (serviceCompare !== 0) return serviceCompare
        const compteCompare = String(a.compte?.numero || '').localeCompare(String(b.compte?.numero || ''))
        if (compteCompare !== 0) return compteCompare
        return Number(a.niveau || 0) - Number(b.niveau || 0)
      })
    }
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
        this.services = response.data.services || []
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
        this.error = getErrorMessage(error, 'Erreur lors de la création de la prévision.')
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
