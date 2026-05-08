// src/stores/dashboard.js
import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        kpi: {
            total_agents: 0,
            postes_vacants: 0,
            taux_vacants: 0,
            departs_retraite_5ans: 0,
            departs_retraite_12mois: 0,
            anomalies_plafonnement: 0
        },
        loading: false,
        error: null
    }),

    actions: {
        async fetchKPI() {
            this.loading = true
            this.error = null
            try {
                const response = await api.get('/dashboard/kpi')
                this.kpi = response.data
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Erreur chargement KPI:', error)
            } finally {
                this.loading = false
            }
        }
    }
})