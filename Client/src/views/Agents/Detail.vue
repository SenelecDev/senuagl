<template>
  <v-container fluid>
    <v-row justify="center">
      <v-col cols="12" md="10">
        <v-card elevation="3">
          <v-card-title class="d-flex align-center justify-space-between pa-4">
            <div>
              <div class="text-h5 font-weight-bold">Détail de l'agent</div>
              <div class="text-caption">Matricule : {{ matricule }}</div>
            </div>
            <v-btn variant="outlined" color="primary" @click="goBack">
              Retour à la liste
            </v-btn>
          </v-card-title>

          <v-card-text>
            <div v-if="loadingAgent" class="text-center py-12">
              <v-progress-circular indeterminate color="primary" size="40" />
            </div>

            <div v-else-if="errorAgent">
              <v-alert type="error" variant="tonal">
                {{ errorAgent }}
              </v-alert>
            </div>

            <div v-else-if="agent">
              <v-row class="mb-6" dense>
                <v-col cols="12" md="4">
                  <v-sheet class="pa-4 rounded-lg elevation-1">
                    <h3 class="text-subtitle-1 font-weight-bold mb-3">Profil</h3>
                    <p><strong>Nom complet :</strong> {{ agent.titre || '' }} {{ agent.prenom }} {{ agent.nom }}</p>
                    <p><strong>Matricule :</strong> {{ agent.matricule }}</p>
                    <p><strong>Sexe :</strong> {{ agent.sexe === 'M' ? 'Masculin' : 'Féminin' }}</p>
                    <p><strong>Date de naissance :</strong> {{ formatDate(agent.date_naissance) }}</p>
                    <p><strong>Lieu de naissance :</strong> {{ agent.lieu_naissance || 'N/A' }}</p>
                    <p><strong>Nationalité :</strong> {{ agent.nationalite || 'N/A' }}</p>
                  </v-sheet>
                </v-col>

                <v-col cols="12" md="8">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-sheet class="pa-4 rounded-lg elevation-1 mb-4">
                        <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations professionnelles</h3>
                        <p><strong>Poste :</strong> {{ agent.poste?.intitule || 'N/A' }}</p>
                        <p><strong>Unité :</strong> {{ agent.poste?.unite?.nom || 'N/A' }}</p>
                        <p><strong>Date d'embauche :</strong> {{ formatDate(agent.date_embauche) }}</p>
                        <p><strong>Lieu :</strong> {{ agent.lieu || 'N/A' }}</p>
                        <p><strong>Organisation :</strong> {{ agent.organisation || 'N/A' }}</p>
                        <p><strong>Centre de responsabilité :</strong> {{ agent.centre_de_responsabilite || 'N/A' }}</p>
                      </v-sheet>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-sheet class="pa-4 rounded-lg elevation-1 mb-4">
                        <h3 class="text-subtitle-1 font-weight-bold mb-3">Rémunération</h3>
                        <p><strong>GF actuel :</strong> {{ agent.gf_actuel?.ordre || 'N/A' }}</p>
                        <p><strong>NR actuel :</strong> {{ agent.nr_actuel?.ordre || 'N/A' }}</p>
                        <p><strong>Statut :</strong> {{ agent.statut || 'N/A' }}</p>
                      </v-sheet>
                    </v-col>
                  </v-row>
                </v-col>
              </v-row>



              <v-sheet class="pa-4 rounded-lg elevation-1">
                <h3 class="text-subtitle-1 font-weight-bold mb-4">Historique des avancements</h3>
                <v-data-table
                  :headers="avancementHeaders"
                  :items="agent.avancements || []"
                  dense
                  hide-default-footer
                  no-data-text="Aucun avancement enregistré"
                >
                  <template #item.date="{ item }">
                    {{ formatDate(item.date) }}
                  </template>
                  <template #item.gf_ancien="{ item }">
                    {{ item.gfAncien?.ordre || 'N/A' }}
                  </template>
                  <template #item.gf_nouveau="{ item }">
                    {{ item.gfNouveau?.ordre || 'N/A' }}
                  </template>
                  <template #item.nr_ancien="{ item }">
                    {{ item.nrAncien?.ordre || 'N/A' }}
                  </template>
                  <template #item.nr_nouveau="{ item }">
                    {{ item.nrNouveau?.ordre || 'N/A' }}
                  </template>
                </v-data-table>
              </v-sheet>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAgentStore } from '@/stores/agent'

const route = useRoute()
const router = useRouter()
const agentStore = useAgentStore()

const matricule = route.params.matricule
const agent = computed(() => agentStore.currentAgent)
const loadingAgent = computed(() => agentStore.loadingAgent)
const errorAgent = computed(() => agentStore.errorAgent)

const avancementHeaders = [
  { title: 'Date', key: 'date' },
  { title: 'GF ancien', key: 'gf_ancien' },
  { title: 'GF nouveau', key: 'gf_nouveau' },
  { title: 'NR ancien', key: 'nr_ancien' },
  { title: 'NR nouveau', key: 'nr_nouveau' }
]

const goBack = () => {
  router.push('/agents')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (value) => {
  if (!value && value !== 0) return 'N/A'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF'
  }).format(value)
}

onMounted(() => {
  agentStore.fetchAgent(matricule)
})
</script>
