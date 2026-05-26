<template>
  <v-container fluid class="promotions-view">
    <!-- Header / Filtres -->
    <v-card class="rounded-lg mb-6" elevation="2">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="selectedAnnee"
              :items="years"
              label="Année cible"
              variant="outlined"
              density="compact"
              hide-details
              prepend-inner-icon="mdi-calendar"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>


    <!-- Table principale -->
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex justify-space-between align-center pa-4 flex-wrap ga-4">
        <div>
          <span class="text-h6 font-weight-bold">Liste de priorité de promotion</span>
        </div>
        <v-btn
          variant="outlined"
          color="primary"
          prepend-icon="mdi-refresh"
          :loading="promotionStore.loading"
          @click="loadData"
        >
          Actualiser
        </v-btn>
      </v-card-title>

      <v-card-text v-if="promotionStore.error">
        <v-alert type="error" variant="tonal">
          {{ promotionStore.error }}
        </v-alert>
      </v-card-text>

      <v-data-table
        :headers="headers"
        :items="promotionRows"
        :loading="promotionStore.loading"
        class="elevation-0"
        hover
        no-data-text="Aucun agent éligible pour la promotion"
        loading-text="Chargement des agents..."
      >
        <template #item.nom="{ item }">
          <div class="agent-cell">
            <strong>{{ item.prenom }} {{ item.nom }}</strong>
            <span>{{ item.matricule }}</span>
          </div>
        </template>

        <template #item.note="{ item }">
          <v-chip :color="getNoteColor(item.note)" size="small" variant="tonal">
            {{ item.note }}/100
          </v-chip>
          <div class="text-caption text-medium-emphasis mt-1">
            {{ item.mention_note }}
          </div>
        </template>

        <template #item.anciennete_ans="{ item }">
          {{ Math.floor(item.anciennete_ans) }} ans
        </template>

        <template #item.gf_actuel="{ item }">
          <v-chip size="small" variant="outlined">
            {{ item.gf_actuel }}
          </v-chip>
        </template>

        <template #item.derniere_promotion="{ item }">
          {{ item.derniere_promotion ? formatDate(item.derniere_promotion) : 'Aucune' }}
        </template>

        <template #item.actions="{ item }">
          <div v-if="item.deja_promu" class="action-tags">
            <v-chip color="success" size="small" class="font-weight-bold">
              Promu
            </v-chip>
            <v-chip color="warning" size="small" variant="tonal">
              Prochaine promotion en {{ item.prochaine_promotion_annee }}
            </v-chip>
          </div>
          <v-btn
            v-else
            size="small"
            color="success"
            variant="tonal"
            prepend-icon="mdi-arrow-up"
            @click="openPromotionDialog(item)"
          >
            Promouvoir
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="showPromotionDialog" max-width="600">
      <v-card v-if="selectedAgent">
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">Promotion GF</span>
          <v-btn icon="mdi-close" variant="text" @click="showPromotionDialog = false" />
        </v-card-title>

        <v-card-text>
          <v-alert v-if="promotionStore.error" type="error" variant="tonal" class="mb-4">
            {{ promotionStore.error }}
          </v-alert>

          <div class="mb-4">
            <div class="text-body-2 font-weight-bold">Agent</div>
            <div>{{ selectedAgent.prenom }} {{ selectedAgent.nom }} ({{ selectedAgent.matricule }})</div>
            <div class="text-caption text-medium-emphasis">Note: {{ selectedAgent.note }}/100</div>
          </div>

          <v-form ref="promotionFormRef" @submit.prevent="submitPromotion">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="promotionForm.date"
                  type="date"
                  label="Date de promotion"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-body-2 font-weight-bold mb-2">GF actuel: {{ selectedAgent.gf_actuel }}</div>
              </v-col>

              <v-col cols="12">
                <v-select
                  v-model="promotionForm.id_gf_nouveau"
                  :items="availableGfs"
                  item-title="id_gf"
                  item-value="id_gf"
                  label="Nouveau GF"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="showPromotionDialog = false">
            Annuler
          </v-btn>
          <v-btn color="success" :loading="promotionStore.saving" @click="submitPromotion">
            Valider la promotion
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { usePromotionStore } from '@/stores/promotion'

const promotionStore = usePromotionStore()
const { gfs } = storeToRefs(promotionStore)

const promotionFormRef = ref(null)
const showPromotionDialog = ref(false)
const selectedAgent = ref(null)
const lastPromotedMatricule = ref(null)

const selectedAnnee = ref(new Date().getFullYear())

const promotionForm = ref({
  date: new Date().toISOString().slice(0, 10),
  id_gf_nouveau: ''
})

const headers = [
  { title: 'Nom & Prenom', key: 'nom' },
  { title: 'Note', key: 'note', width: '100px' },
  { title: 'Ancienneté', key: 'anciennete_ans', width: '120px' },
  { title: 'GF Actuel', key: 'gf_actuel', width: '100px' },
  { title: 'Dernière Promotion', key: 'derniere_promotion' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
]

const requiredRule = value => Boolean(value) || 'Champ requis'

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current - 2, current - 1, current, current + 1, current + 2]
})

const promotionRows = computed(() => {
  return [...promotionStore.agents].sort((a, b) => {
    if (a.matricule === lastPromotedMatricule.value && b.matricule === lastPromotedMatricule.value) return 0
    if (a.matricule === lastPromotedMatricule.value) return 1
    if (b.matricule === lastPromotedMatricule.value) return -1

    if (Boolean(a.deja_promu) !== Boolean(b.deja_promu)) {
      return Number(Boolean(a.deja_promu)) - Number(Boolean(b.deja_promu))
    }

    if (a.deja_promu) {
      return new Date(a.derniere_promotion || 0) - new Date(b.derniere_promotion || 0)
    }

    return 0
  })
})

const availableGfs = computed(() => {
  if (!selectedAgent.value) return []
  const currentGfOrdre = selectedAgent.value.agent?.gf_actuel?.ordre || 0
  
  // Limiter selon le tube_max du poste de l'agent
  const poste = selectedAgent.value.agent?.poste
  if (!poste) {
    return gfs.value.filter(gf => gf.ordre > currentGfOrdre)
  }

  const tubeMaxId = typeof poste.tube_max === 'object' ? poste.tube_max?.id_gf : poste.tube_max
  const tubeMaxGf = gfs.value.find(gf => gf.id_gf === tubeMaxId)
  const maxOrdre = tubeMaxGf ? tubeMaxGf.ordre : null

  return gfs.value.filter(gf => {
    const isAboveCurrent = gf.ordre > currentGfOrdre
    const isBelowOrEqualMax = maxOrdre ? gf.ordre <= maxOrdre : true
    return isAboveCurrent && isBelowOrEqualMax
  })
})

const getNoteColor = (note) => {
  if (note >= 90) return 'success'
  if (note >= 75) return 'info'
  return 'warning'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const defaultPromotionDate = () => {
  const today = new Date()
  if (Number(selectedAnnee.value) === today.getFullYear()) {
    return today.toISOString().slice(0, 10)
  }

  return `${selectedAnnee.value}-01-01`
}

const loadData = () => {
  if (selectedAnnee.value) {
    promotionStore.fetchListePrioriteGF(selectedAnnee.value)
  }
}

watch(selectedAnnee, () => {
  loadData()
})

const openPromotionDialog = (agent) => {
  selectedAgent.value = agent
  promotionForm.value.date = defaultPromotionDate()
  promotionForm.value.id_gf_nouveau = ''
  promotionFormRef.value?.resetValidation()
  showPromotionDialog.value = true
}

const submitPromotion = async () => {
  const validation = await promotionFormRef.value?.validate()
  if (!validation?.valid) return

  try {
    await promotionStore.promouvoir(
      selectedAgent.value.matricule,
      promotionForm.value.id_gf_nouveau,
      promotionForm.value.date,
      selectedAnnee.value
    )
    lastPromotedMatricule.value = selectedAgent.value.matricule
    showPromotionDialog.value = false
    selectedAgent.value = null
  } catch (error) {
    console.error('Erreur lors de la promotion:', error)
  }
}

onMounted(async () => {
  promotionStore.fetchGfs()
  loadData()
})
</script>

<style scoped>
.promotions-view {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.agent-cell {
  display: flex;
  flex-direction: column;
  line-height: 1.35;
}

.agent-cell span {
  color: #6b7280;
  font-size: 0.85rem;
}

.action-tags {
  display: flex;
  justify-content: flex-end;
  gap: 0.35rem;
  flex-wrap: wrap;
}
</style>
