import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useNoteAppreciationStore = defineStore('noteAppreciation', {
    state: () => ({
        notes: [],
        agents: [],
        loading: false,
        saving: false,
        error: null,
        filters: {
            annee: new Date().getFullYear(),
            matricule_agent: ''
        },
        pagination: {
            page: 1,
            per_page: 50,
            total: 0
        }
    }),

    actions: {
        async fetchNotes() {
            this.loading = true
            this.error = null

            try {
                const params = {
                    page: this.pagination.page,
                    per_page: this.pagination.per_page,
                    ...Object.fromEntries(
                        Object.entries(this.filters).filter(([, value]) => value !== '' && value !== null)
                    )
                }
                const response = await api.get('/notes-appreciation', { params })
                this.notes = response.data.data || response.data || []
                this.pagination.total = response.data.total || 0
            } catch (error) {
                console.error('Erreur lors de la récupération des notes:', error)
                this.notes = []
                this.error = getErrorMessage(error, 'Erreur lors de la récupération des notes.')
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

        async createNote(payload) {
            this.saving = true
            this.error = null

            try {
                await api.post('/notes-appreciation', payload)
                await this.fetchNotes()
            } catch (error) {
                console.error('Erreur lors de la création de la note:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la création de la note.')
                throw error
            } finally {
                this.saving = false
            }
        },

        async updateNote(id, payload) {
            this.saving = true
            this.error = null

            try {
                await api.put(`/notes-appreciation/${id}`, payload)
                await this.fetchNotes()
            } catch (error) {
                console.error('Erreur lors de la modification de la note:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la modification.')
                throw error
            } finally {
                this.saving = false
            }
        },

        async deleteNote(id) {
            this.saving = true
            this.error = null

            try {
                await api.delete(`/notes-appreciation/${id}`)
                await this.fetchNotes()
            } catch (error) {
                console.error('Erreur lors de la suppression:', error)
                this.error = getErrorMessage(error, 'Erreur lors de la suppression.')
                throw error
            } finally {
                this.saving = false
            }
        },

        updateFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters }
            this.pagination.page = 1
            this.fetchNotes()
        },

        setPage(page) {
            this.pagination.page = page
            this.fetchNotes()
        }
    }
})
