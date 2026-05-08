import { defineStore } from 'pinia'
import api from '../api/axios'

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useAgentStore = defineStore('agent', {
    state: () => ({
        agents: [],
        loading: false,
        saving: false,
        unites: [],
        gfs: [],
        nrs: [],
        postes: [],
        currentAgent: null,
        loadingAgent: false,
        errorAgent: null,
        error: null,
        filters: {
            service: '',
            gf: '',
            sexe: '',
            lieu: '',
            search: '',
            plafonne: false
        },
        pagination: {
            page: 1,
            per_page: 10,
            total: 0
        }
    }),
    actions: {
        async fetchAgents() {
            this.loading = true
            this.error = null
            try {
                const activeFilters = Object.fromEntries(
                    Object.entries(this.filters).filter(([, value]) => {
                        return value !== '' && value !== null && value !== false && value !== undefined
                    })
                )

                const params = {
                    ...activeFilters,
                    page: this.pagination.page,
                    per_page: this.pagination.per_page
                }
                const response = await api.get('/agents', { params })
                this.agents = response.data.data || response.data
                this.pagination.total = response.data.total || 0
            } catch (error) {
                console.error('Erreur lors de la récupération des agents:', error)
                this.agents = []
                this.error = getErrorMessage(error, 'Erreur lors de la récupération des agents.')
            } finally {
                this.loading = false
            }
        },
        async fetchUnites() {
            try {
                const response = await api.get('/unites')
                this.unites = response.data.data || response.data
            } catch (error) {
                console.error('Erreur lors de la récupération des unités:', error)
            }
        },
        async fetchGfs() {
            try {
                const response = await api.get('/gfs')
                this.gfs = response.data.data || response.data
            } catch (error) {
                console.error('Erreur lors de la récupération des GFs:', error)
            }
        },
        async fetchNrs() {
            try {
                const response = await api.get('/nrs')
                this.nrs = response.data.data || response.data
            } catch (error) {
                console.error('Erreur lors de la récupération des NRs:', error)
            }
        },
        async fetchPostes() {
            try {
                const response = await api.get('/postes')
                this.postes = response.data.data || response.data
            } catch (error) {
                console.error('Erreur lors de la récupération des postes:', error)
            }
        },
        async fetchAgent(matricule) {
            this.loadingAgent = true
            this.errorAgent = null
            this.currentAgent = null

            try {
                const response = await api.get(`/agents/${matricule}`)
                this.currentAgent = response.data.data || response.data
            } catch (error) {
                console.error('Erreur lors de la récupération de l\'agent:', error)
                this.errorAgent = getErrorMessage(error, 'Erreur lors du chargement de l\'agent.')
            } finally {
                this.loadingAgent = false
            }
        },
        async createAgent(payload) {
            this.saving = true
            this.error = null

            try {
                await api.post('/agents', payload)
                await this.fetchAgents()
            } catch (error) {
                console.error('Erreur lors de la création de l\'agent:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la création de l\'agent.')
                throw error
            } finally {
                this.saving = false
            }
        },
        async updateAgent(matricule, payload) {
            this.saving = true
            this.error = null

            try {
                await api.put(`/agents/${matricule}`, payload)
                await this.fetchAgents()
            } catch (error) {
                console.error('Erreur lors de la modification de l\'agent:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la modification de l\'agent.')
                throw error
            } finally {
                this.saving = false
            }
        },
        updateFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters }
            this.pagination.page = 1 // Reset to first page on filter change
            this.fetchAgents()
        },
        setPage(page) {
            this.pagination.page = page
            this.fetchAgents()
        }
        ,
        setPerPage(perPage) {
            const value = Number(perPage || 10)
            this.pagination.per_page = value
            this.pagination.page = 1
            this.fetchAgents()
        }
    }
})
