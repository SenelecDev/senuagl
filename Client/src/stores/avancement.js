import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

const cleanParams = (filters) => {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => {
            return value !== '' && value !== null && value !== undefined
        })
    )
}

export const useAvancementStore = defineStore('avancement', {
    state: () => ({
        avancements: [],
        agents: [],
        gfs: [],
        nrs: [],
        loading: false,
        saving: false,
        error: null,
        filters: {
            agent: '',
            type: ''
        },
        pagination: {
            page: 1,
            per_page: 50,
            total: 0
        }
    }),

    actions: {
        async fetchAvancements() {
            this.loading = true
            this.error = null

            try {
                const params = {
                    ...cleanParams(this.filters),
                    page: this.pagination.page,
                    per_page: this.pagination.per_page
                }
                const response = await api.get('/avancements', { params })
                this.avancements = response.data.data || response.data || []
                this.pagination.total = response.data.total || 0
            } catch (error) {
                console.error('Erreur lors de la récupération des avancements:', error)
                this.avancements = []
                this.error = getErrorMessage(error, 'Erreur lors de la récupération des avancements.')
            } finally {
                this.loading = false
            }
        },

        async fetchAgents() {
            try {
                const response = await api.get('/agents', { params: { per_page: 100 } })
                this.agents = response.data.data || response.data || []
            } catch (error) {
                console.error('Erreur lors de la récupération des agents:', error)
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

        async fetchNrs() {
            try {
                const response = await api.get('/nrs')
                this.nrs = response.data.data || response.data || []
            } catch (error) {
                console.error('Erreur lors de la récupération des NRs:', error)
            }
        },

        async createAvancement(payload) {
            this.saving = true
            this.error = null

            try {
                await api.post('/avancements', payload)
                await this.fetchAvancements()
                await this.fetchAgents()
            } catch (error) {
                console.error('Erreur lors de la création de l\'avancement:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la création de l\'avancement.')
                throw error
            } finally {
                this.saving = false
            }
        },

        async updateAvancementDate(id, date) {
            this.saving = true
            this.error = null

            try {
                await api.put(`/avancements/${id}`, { date })
                await this.fetchAvancements()
            } catch (error) {
                console.error('Erreur lors de la modification de l\'avancement:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la modification de l\'avancement.')
                throw error
            } finally {
                this.saving = false
            }
        },

        updateFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters }
            this.pagination.page = 1
            this.fetchAvancements()
        },

        setPage(page) {
            this.pagination.page = page
            this.fetchAvancements()
        }
    }
})
