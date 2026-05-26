import { defineStore } from 'pinia'
import api from '@/api/axios'

const getErrorMessage = (error, fallback) => {
    return error.response?.data?.message || error.response?.data?.error || error.message || fallback
}

export const useStatistiqueStore = defineStore('statistique', {
    state: () => ({
        pyramideAges: {},
        repartitionHF: {
            hommes: { nombre: 0, pourcentage: 0 },
            femmes: { nombre: 0, pourcentage: 0 },
            total: 0
        },
        repartitionHFParService: [],
        departsRetraite: {
            comptage: {
                moins_1_an: 0,
                entre_1_et_2_ans: 0,
                entre_2_et_3_ans: 0,
                entre_1_et_3_ans: 0,
                entre_3_et_5_ans: 0,
                plus_5_ans: 0,
                total: 0
            },
            liste: {
                moins_1_an: [],
                entre_1_et_2_ans: [],
                entre_2_et_3_ans: [],
                entre_1_et_3_ans: [],
                entre_3_et_5_ans: [],
                plus_5_ans: []
            }
        },
        plafonnementAnomalies: [],
        agentsPlafonnes: [],
        loading: false,
        error: null
    }),

    actions: {
        async fetchAll() {
            this.loading = true
            this.error = null

            try {
                const [
                    pyramideResponse,
                    repartitionResponse,
                    repartitionServiceResponse,
                    retraiteResponse,
                    anomaliesResponse,
                    plafonnesResponse
                ] = await Promise.all([
                    api.get('/statistiques/pyramide-ages'),
                    api.get('/statistiques/repartition-hf'),
                    api.get('/statistiques/repartition-hf-par-service'),
                    api.get('/statistiques/departs-retraite'),
                    api.get('/statistiques/plafonnement-anomalies'),
                    api.get('/statistiques/agents-plafonnes')
                ])

                this.pyramideAges = pyramideResponse.data || {}
                this.repartitionHF = repartitionResponse.data || this.repartitionHF
                this.repartitionHFParService = repartitionServiceResponse.data || []
                this.departsRetraite = retraiteResponse.data || this.departsRetraite
                this.plafonnementAnomalies = anomaliesResponse.data || []
                this.agentsPlafonnes = plafonnesResponse.data || []
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error)
                this.error = getErrorMessage(error, 'Erreur lors du chargement des statistiques.')
            } finally {
                this.loading = false
            }
        }
    }
})
