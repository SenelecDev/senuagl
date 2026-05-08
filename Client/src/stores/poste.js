import { defineStore } from 'pinia'
import api from '@/api/axios'

const cleanParams = (filters) => {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => {
            return value !== '' && value !== null && value !== false && value !== undefined
        })
    )
}

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const usePosteStore = defineStore('poste', {
    state: () => ({
        postes: [],
        postesVacants: [],
        totalVacants: 0,
        currentPoste: null,
        unites: [],
        gfs: [],
        loading: false,
        loadingVacants: false,
        loadingPoste: false,
        saving: false,
        deleting: false,
        error: null,
        errorVacants: null,
        errorPoste: null,
        filters: {
            unite: '',
            search: '',
            vacants: false
        }
    }),

    getters: {
        filteredPostes: (state) => {
            const search = state.filters.search.trim().toLowerCase()

            if (!search) {
                return state.postes
            }

            return state.postes.filter((poste) => {
                const unite = typeof poste.unite === 'string' ? poste.unite : poste.unite?.nom
                return [
                    // poste.id_post,
                    poste.intitule,
                    unite
                ].some((value) => String(value || '').toLowerCase().includes(search))
            })
        }
    },

    actions: {
        async fetchPostes() {
            this.loading = true
            this.error = null

            try {
                const params = cleanParams({
                    unite: this.filters.unite,
                    vacants: this.filters.vacants
                })
                const response = await api.get('/postes', { params })
                this.postes = response.data.data || response.data || []
            } catch (error) {
                console.error('Erreur lors de la récupération des postes:', error)
                this.postes = []
                this.error = getErrorMessage(error, 'Erreur lors de la récupération des postes.')
            } finally {
                this.loading = false
            }
        },

        async fetchPoste(id) {
            this.loadingPoste = true
            this.errorPoste = null
            this.currentPoste = null

            try {
                const response = await api.get(`/postes/${id}`)
                this.currentPoste = response.data.data || response.data
            } catch (error) {
                console.error('Erreur lors de la récupération du poste:', error)
                this.errorPoste = getErrorMessage(error, 'Erreur lors du chargement du poste.')
            } finally {
                this.loadingPoste = false
            }
        },

        async fetchPostesVacants() {
            this.loadingVacants = true
            this.errorVacants = null

            try {
                const response = await api.get('/postes-vacants')
                this.totalVacants = response.data.total_vacants || 0
                this.postesVacants = response.data.postes || []
            } catch (error) {
                console.error('Erreur lors de la récupération des postes vacants:', error)
                this.totalVacants = 0
                this.postesVacants = []
                this.errorVacants = getErrorMessage(error, 'Erreur lors de la récupération des postes vacants.')
            } finally {
                this.loadingVacants = false
            }
        },

        async fetchUnites() {
            try {
                const response = await api.get('/unites')
                this.unites = response.data.data || response.data || []
            } catch (error) {
                console.error('Erreur lors de la récupération des unités:', error)
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

        async createPoste(payload) {
            this.saving = true
            this.error = null

            try {
                await api.post('/postes', payload)
                await this.fetchPostes()
                await this.fetchPostesVacants()
            } catch (error) {
                console.error('Erreur lors de la création du poste:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la création du poste.')
                throw error
            } finally {
                this.saving = false
            }
        },

        async updatePoste(id, payload) {
            this.saving = true
            this.error = null

            try {
                await api.put(`/postes/${id}`, payload)
                await this.fetchPostes()
                await this.fetchPostesVacants()
                if (this.currentPoste?.id_post === id) {
                    await this.fetchPoste(id)
                }
            } catch (error) {
                console.error('Erreur lors de la modification du poste:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la modification du poste.')
                throw error
            } finally {
                this.saving = false
            }
        },

        async deletePoste(id) {
            this.deleting = true
            this.error = null

            try {
                await api.delete(`/postes/${id}`)
                await this.fetchPostes()
                await this.fetchPostesVacants()
            } catch (error) {
                console.error('Erreur lors de la suppression du poste:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la suppression du poste.')
                throw error
            } finally {
                this.deleting = false
            }
        },

        updateFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters }

            if ('unite' in newFilters || 'vacants' in newFilters) {
                this.fetchPostes()
            }
        },

        resetCurrentPoste() {
            this.currentPoste = null
            this.errorPoste = null
        }
    }
})
