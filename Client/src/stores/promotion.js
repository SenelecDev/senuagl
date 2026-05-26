import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const usePromotionStore = defineStore('promotion', {
    state: () => ({
        agents: [],
        gfs: [],
        stats: null,
        loading: false,
        saving: false,
        error: null
    }),

    actions: {
        async fetchListePrioriteGF(annee) {
            this.loading = true
            this.error = null

            try {
                const response = await api.get('/promotions/liste-priorite-gf', {
                    params: { annee }
                })
                this.agents = response.data.agents || []
                this.stats = response.data.stats || null
            } catch (error) {
                console.error('Erreur lors de la récupération de la liste de priorité GF:', error)
                this.agents = []
                this.stats = null
                this.error = getErrorMessage(error, 'Erreur lors de la récupération de la liste.')
            } finally {
                this.loading = false
            }
        },

        async fetchGfs() {
            try {
                const response = await api.get('/gfs')
                this.gfs = response.data.data || response.data || []
            } catch (error) {
                console.error('Erreur lors de la récupération des GFs:', error)
            }
        },

        async promouvoir(matriculeAgent, idGfNouveau, date, annee) {
            this.saving = true
            this.error = null

            try {
                await api.post('/promotions/promouvoir', {
                    matricule_agent: matriculeAgent,
                    id_gf_nouveau: idGfNouveau,
                    date: date
                })
                await this.fetchListePrioriteGF(annee)
            } catch (error) {
                console.error('Erreur lors de la promotion:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la promotion.')
                throw error
            } finally {
                this.saving = false
            }
        }
    }
})
