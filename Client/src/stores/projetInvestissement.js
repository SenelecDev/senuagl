import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
  return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useProjetInvestissementStore = defineStore('projetInvestissement', {
  state: () => ({
    projets: [],
    loading: false,
    saving: false,
    error: null
  }),

  getters: {
    totalMontantMarche: (state) => state.projets.reduce((sum, p) => sum + Number(p.montant_marche || 0), 0),
    totalCoutProjet: (state) => state.projets.reduce((sum, p) => sum + Number(p.cout_projet || 0), 0),
    totalFp: (state) => state.projets.reduce((sum, p) => sum + Number(p.fp_annee || 0), 0),
    totalFe: (state) => state.projets.reduce((sum, p) => sum + Number(p.fe_annee || 0), 0),
    totalAnnee: (state) => state.projets.reduce((sum, p) => sum + Number(p.fp_annee || 0) + Number(p.fe_annee || 0), 0),
  },

  actions: {
    async fetchProjets(annee) {
      this.loading = true
      this.error = null

      try {
        const response = await api.get('/projet-investissements', { params: { annee } })
        this.projets = response.data.projets || []
      } catch (error) {
        this.projets = []
        this.error = getErrorMessage(error, 'Erreur lors du chargement des projets.')
      } finally {
        this.loading = false
      }
    },

    async createProjet(payload) {
      this.saving = true
      this.error = null

      try {
        const response = await api.post('/projet-investissements', payload)
        this.projets.push(response.data)
        return response.data
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de la création du projet.')
        throw error
      } finally {
        this.saving = false
      }
    },

    async updateProjet(id, payload) {
      this.saving = true
      this.error = null

      try {
        const response = await api.put(`/projet-investissements/${id}`, payload)
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de la mise à jour du projet.')
        throw error
      } finally {
        this.saving = false
      }
    },

    async deleteProjet(id) {
      this.saving = true
      this.error = null

      try {
        await api.delete(`/projet-investissements/${id}`)
        this.projets = this.projets.filter(p => p.id !== id)
      } catch (error) {
        this.error = getErrorMessage(error, 'Erreur lors de la suppression du projet.')
        throw error
      } finally {
        this.saving = false
      }
    }
  }
})
