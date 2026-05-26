import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useAvancementPrioriteStore = defineStore('avancementPriorite', {
    state: () => ({
        agents: [],
        nrs: [],
        stats: null,
        loading: false,
        saving: false,
        error: null
    }),

    actions: {
        async fetchListePrioriteNR(annee) {
            this.loading = true
            this.error = null

            try {
                const response = await api.get('/avancements/liste-priorite-nr', {
                    params: { annee }
                })
                this.agents = response.data.agents || []
                this.stats = response.data.stats || null
            } catch (error) {
                console.error('Erreur lors de la récupération de la liste de priorité NR:', error)
                this.agents = []
                this.stats = null
                this.error = getErrorMessage(error, 'Erreur lors de la récupération de la liste.')
            } finally {
                this.loading = false
            }
        },

        async fetchNrs() {
            try {
                const response = await api.get('/nrs')
                this.nrs = response.data.data || response.data || []
            } catch (error) {
                console.error('Erreur lors de la récupération des NRs:', error)
            }
        },

        async avancer(matriculeAgent, idNrNouveau, date, annee) {
            this.saving = true
            this.error = null

            try {
                await api.post('/avancements/avancer', {
                    matricule_agent: matriculeAgent,
                    id_nr_nouveau: idNrNouveau,
                    date: date
                })
                await this.fetchListePrioriteNR(annee)
            } catch (error) {
                console.error('Erreur lors de l\'avancement:', error)
                this.error = getErrorMessage(error, 'Erreur lors de l\'avancement.')
                throw error
            } finally {
                this.saving = false
            }
        }
    }
})
