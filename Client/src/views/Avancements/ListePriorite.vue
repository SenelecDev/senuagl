<template>
  <v-container fluid class="avancements-prio-view">
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
          <span class="text-h6 font-weight-bold">Liste de priorité d'avancement</span>
        </div>
        <v-btn
          variant="outlined"
          color="primary"
          prepend-icon="mdi-refresh"
          :loading="avancementStore.loading"
          @click="loadData"
        >
          Actualiser
        </v-btn>
      </v-card-title>

      <v-card-text v-if="avancementStore.error">
        <v-alert type="error" variant="tonal">
          {{ avancementStore.error }}
        </v-alert>
      </v-card-text>

      <v-data-table
        :headers="headers"
        :items="avancementRows"
        :loading="avancementStore.loading"
        class="elevation-0"
        hover
        no-data-text="Aucun agent éligible pour l'avancement"
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

        <template #item.nr_actuel="{ item }">
          <v-chip size="small" variant="outlined">
            {{ item.nr_actuel }}
          </v-chip>
        </template>

        <template #item.dernier_avancement="{ item }">
          {{ item.dernier_avancement ? formatDate(item.dernier_avancement) : 'Aucun' }}
        </template>

        <template #item.actions="{ item }">
          <div v-if="item.deja_avance" class="action-tags">
            <v-chip color="info" size="small" class="font-weight-bold">
              Avancé
            </v-chip>
            <v-chip color="warning" size="small" variant="tonal">
              Prochain avancement en {{ item.prochain_avancement_annee }}
            </v-chip>
          </div>
          <v-btn
            v-else
            size="small"
            color="info"
            variant="tonal"
            prepend-icon="mdi-arrow-up"
            @click="openAvancementDialog(item)"
          >
            Avancer
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="showAvancementDialog" max-width="600">
      <v-card v-if="selectedAgent">
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">Avancement NR</span>
          <v-btn icon="mdi-close" variant="text" @click="showAvancementDialog = false" />
        </v-card-title>

        <v-card-text>
          <v-alert v-if="avancementStore.error" type="error" variant="tonal" class="mb-4">
            {{ avancementStore.error }}
          </v-alert>

          <div class="mb-4">
            <div class="text-body-2 font-weight-bold">Agent</div>
            <div>{{ selectedAgent.prenom }} {{ selectedAgent.nom }} ({{ selectedAgent.matricule }})</div>
            <div class="text-caption text-medium-emphasis">Note: {{ selectedAgent.note }}/100</div>
          </div>

          <v-form ref="avancementFormRef" @submit.prevent="submitAvancement">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="avancementForm.date"
                  type="date"
                  label="Date d'avancement"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-body-2 font-weight-bold mb-2">NR actuel: {{ selectedAgent.nr_actuel }}</div>
              </v-col>

              <v-col cols="12">
                <v-select
                  v-model="avancementForm.id_nr_nouveau"
                  :items="availableNrs"
                  item-title="id_nr"
                  item-value="id_nr"
                  label="Nouveau NR"
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
          <v-btn variant="text" @click="showAvancementDialog = false">
            Annuler
          </v-btn>
          <v-btn color="info" :loading="avancementStore.saving" @click="submitAvancement">
            Valider l'avancement
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAvancementPrioriteStore } from '@/stores/avancementPriorite'

const avancementStore = useAvancementPrioriteStore()
const { nrs } = storeToRefs(avancementStore)

const avancementFormRef = ref(null)
const showAvancementDialog = ref(false)
const selectedAgent = ref(null)
const lastAdvancedMatricule = ref(null)

const selectedAnnee = ref(new Date().getFullYear())

const avancementForm = ref({
  date: new Date().toISOString().slice(0, 10),
  id_nr_nouveau: ''
})

const headers = [
  { title: 'Nom & Prenom', key: 'nom' },
  { title: 'Note', key: 'note', width: '100px' },
  { title: 'Ancienneté', key: 'anciennete_ans', width: '120px' },
  { title: 'NR Actuel', key: 'nr_actuel', width: '100px' },
  { title: 'Dernier Avancement', key: 'dernier_avancement' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
]

const requiredRule = value => Boolean(value) || 'Champ requis'

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current - 2, current - 1, current, current + 1, current + 2]
})

const avancementRows = computed(() => {
  return [...avancementStore.agents].sort((a, b) => {
    if (a.matricule === lastAdvancedMatricule.value && b.matricule === lastAdvancedMatricule.value) return 0
    if (a.matricule === lastAdvancedMatricule.value) return 1
    if (b.matricule === lastAdvancedMatricule.value) return -1

    if (Boolean(a.deja_avance) !== Boolean(b.deja_avance)) {
      return Number(Boolean(a.deja_avance)) - Number(Boolean(b.deja_avance))
    }

    if (a.deja_avance) {
      return new Date(a.dernier_avancement || 0) - new Date(b.dernier_avancement || 0)
    }

    return 0
  })
})

const availableNrs = computed(() => {
  if (!selectedAgent.value) return []
  const currentNrOrdre = selectedAgent.value.agent?.nr_actuel?.ordre || 0
  return nrs.value.filter(nr => nr.ordre > currentNrOrdre)
})

const getNoteColor = (note) => {
  if (note >= 90) return 'success'
  if (note >= 75) return 'info'
  if (note >= 50) return 'warning'
  return 'error'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const defaultAvancementDate = () => {
  const today = new Date()
  if (Number(selectedAnnee.value) === today.getFullYear()) {
    return today.toISOString().slice(0, 10)
  }

  return `${selectedAnnee.value}-01-01`
}

const loadData = () => {
  if (selectedAnnee.value) {
    avancementStore.fetchListePrioriteNR(selectedAnnee.value)
  }
}

watch(selectedAnnee, () => {
  loadData()
})

const openAvancementDialog = (agent) => {
  selectedAgent.value = agent
  avancementForm.value.date = defaultAvancementDate()
  avancementForm.value.id_nr_nouveau = ''
  avancementFormRef.value?.resetValidation()
  showAvancementDialog.value = true
}

const submitAvancement = async () => {
  const validation = await avancementFormRef.value?.validate()
  if (!validation?.valid) return

  try {
    await avancementStore.avancer(
      selectedAgent.value.matricule,
      avancementForm.value.id_nr_nouveau,
      avancementForm.value.date,
      selectedAnnee.value
    )
    lastAdvancedMatricule.value = selectedAgent.value.matricule
    showAvancementDialog.value = false
    selectedAgent.value = null
  } catch (error) {
    console.error('Erreur lors de l\'avancement:', error)
  }
}

onMounted(async () => {
  avancementStore.fetchNrs()
  loadData()
})
</script>

<style scoped>
.avancements-prio-view {
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
