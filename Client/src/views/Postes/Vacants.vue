<template>
  <v-container fluid class="postes-vacants-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex flex-wrap align-center ga-4 pa-4">
        <div>
          <span class="text-h5 font-weight-bold">Postes vacants</span>
          <div class="text-body-2 text-medium-emphasis">
            Total des postes à pourvoir : {{ totalVacants }}
          </div>
        </div>
        <v-spacer />
        <v-btn
          color="primary"
          variant="outlined"
          prepend-icon="mdi-refresh"
          :loading="loadingVacants"
          @click="fetchPostesVacants"
        >
          Actualiser
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-sheet class="vacant-summary pa-4 rounded-lg">
              <div class="text-caption text-medium-emphasis">Postes concernés</div>
              <div class="text-h4 font-weight-bold">{{ postesVacants.length }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" md="4">
            <v-sheet class="vacant-summary pa-4 rounded-lg">
              <div class="text-caption text-medium-emphasis">Besoin total</div>
              <div class="text-h4 font-weight-bold">{{ totalVacants }}</div>
            </v-sheet>
          </v-col>
          <v-col cols="12" md="4">
            <v-sheet class="vacant-summary pa-4 rounded-lg">
              <div class="text-caption text-medium-emphasis">Unités impactées</div>
              <div class="text-h4 font-weight-bold">{{ impactedUnits }}</div>
            </v-sheet>
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text v-if="errorVacants">
        <v-alert type="error" variant="tonal">
          {{ errorVacants }}
        </v-alert>
      </v-card-text>

      <v-data-table
        :headers="headers"
        :items="postesVacants"
        :items-per-page="10"
        :items-per-page-options="[10, 20, 50, 100]"
        :loading="loadingVacants"
        class="elevation-0"
        hover
        no-data-text="Aucun poste vacant trouvé"
        loading-text="Chargement des postes vacants..."
      >
        <template #item.effectifs="{ item }">
          {{ item.effectif_reel ?? 0 }} / {{ item.effectif_theorique ?? 0 }}
        </template>

        <template #item.postes_vacants="{ item }">
          <v-chip color="warning" size="small" variant="tonal">
            {{ item.postes_vacants }}
          </v-chip>
        </template>
      </v-data-table>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { usePosteStore } from '@/stores/poste'

const posteStore = usePosteStore()
const { postesVacants, totalVacants, loadingVacants, errorVacants } = storeToRefs(posteStore)
const { fetchPostesVacants } = posteStore

const headers = [
  // { title: 'Code', key: 'id_post' },
  { title: 'Intitulé', key: 'intitule' },
  { title: 'Unité', key: 'unite' },
  { title: 'Effectifs', key: 'effectifs', sortable: false },
  { title: 'Vacants', key: 'postes_vacants' }
]

const impactedUnits = computed(() => {
  return new Set(postesVacants.value.map(poste => poste.unite).filter(Boolean)).size
})

onMounted(() => {
  fetchPostesVacants()
})
</script>

<style scoped>
.postes-vacants-view {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.vacant-summary {
  border: 1px solid #e5e7eb;
  background-color: #f9fafb;
}
</style>
