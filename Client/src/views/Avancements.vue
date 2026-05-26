<template>
  <v-container fluid class="avancements-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex flex-wrap align-center ga-4 pa-4">
        <div>
          <span class="text-h5 font-weight-bold">Historique des avancements</span>
          <div class="text-body-2 text-medium-emphasis">
            {{ pagination.total }} avancement(s) enregistré(s)
          </div>
        </div>
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
          Nouvel avancement
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="filters.agent"
              :items="agents"
              :item-title="getAgentTitle"
              item-value="matricule"
              label="Agent"
              variant="outlined"
              density="compact"
              clearable
              hide-details
              @update:model-value="value => updateFilters({ agent: value || '' })"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.type"
              :items="typeFilters"
              item-title="label"
              item-value="value"
              label="Type"
              variant="outlined"
              density="compact"
              clearable
              hide-details
              @update:model-value="value => updateFilters({ type: value || '' })"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-btn
              block
              variant="outlined"
              color="primary"
              prepend-icon="mdi-refresh"
              :loading="loading"
              @click="fetchAvancements"
            >
              Actualiser
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text v-if="error">
        <v-alert type="error" variant="tonal">
          {{ error }}
        </v-alert>
      </v-card-text>

      <v-data-table
        :headers="headers"
        :items="avancements"
        :items-per-page="pagination.per_page"
        :loading="loading"
        class="elevation-0"
        hover
        no-data-text="Aucun avancement trouvé"
        loading-text="Chargement des avancements..."
      >
        <template #item.date="{ item }">
          {{ formatDate(item.date) }}
        </template>

        <template #item.agent="{ item }">
          <div class="agent-cell">
            <strong>{{ item.agent?.prenom }} {{ item.agent?.nom }}</strong>
            <span>{{ item.matricule_agent }}</span>
          </div>
        </template>

        <template #item.type="{ item }">
          <v-chip :color="getTypeColor(item)" size="small" variant="tonal">
            {{ getTypeLabel(item) }}
          </v-chip>
        </template>

        <template #item.gf="{ item }">
          {{ item.gf_ancien?.id_gf || 'N/A' }} -> {{ item.gf_nouveau?.id_gf || 'N/A' }}
        </template>

        <template #item.nr="{ item }">
          {{ item.nr_ancien?.id_nr || 'N/A' }} -> {{ item.nr_nouveau?.id_nr || 'N/A' }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-btn
              size="small"
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-calendar-edit"
              @click="openEditDateDialog(item)"
            >
              Corriger date
            </v-btn>
          </div>
        </template>
      </v-data-table>

      <v-card-actions v-if="!loading && avancements.length > 0">
        <v-spacer />
        <v-btn
          @click="prevPage"
          :disabled="pagination.page <= 1"
          variant="outlined"
        >
          Précédent
        </v-btn>
        <span class="mx-4">Page {{ pagination.page }} sur {{ pageCount }}</span>
        <v-btn
          @click="nextPage"
          :disabled="pagination.page >= pageCount"
          variant="outlined"
        >
          Suivant
        </v-btn>
      </v-card-actions>
    </v-card>

    <v-dialog v-model="showCreateDialog" max-width="760">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">Nouvel avancement</span>
          <v-btn icon="mdi-close" variant="text" @click="closeCreateDialog" />
        </v-card-title>

        <v-card-text>
          <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-form ref="createFormRef" @submit.prevent="submitCreate">
            <v-row>
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="form.matricule_agent"
                  :items="agents"
                  :item-title="getAgentTitle"
                  item-value="matricule"
                  label="Agent"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                  @update:model-value="handleAgentChange"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.date"
                  type="date"
                  label="Date"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>

              <v-col cols="12">
                <v-switch
                  v-model="form.changer_gf"
                  color="primary"
                  label="Promotion"
                  hide-details
                  @update:model-value="syncCurrentAgentLevels"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.id_gf_ancien"
                  :items="gfs"
                  item-title="id_gf"
                  item-value="id_gf"
                  label="GF ancien"
                  variant="outlined"
                  density="compact"
                  :disabled="!form.changer_gf"
                  :rules="form.changer_gf ? [requiredRule] : []"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.id_gf_nouveau"
                  :items="gfs"
                  item-title="id_gf"
                  item-value="id_gf"
                  label="GF nouveau"
                  variant="outlined"
                  density="compact"
                  :disabled="!form.changer_gf"
                  :rules="form.changer_gf ? [requiredRule] : []"
                />
              </v-col>

              <v-col cols="12">
                <v-switch
                  v-model="form.changer_nr"
                  color="primary"
                  label="Avancement"
                  hide-details
                  @update:model-value="syncCurrentAgentLevels"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.id_nr_ancien"
                  :items="nrs"
                  item-title="id_nr"
                  item-value="id_nr"
                  label="NR ancien"
                  variant="outlined"
                  density="compact"
                  :disabled="!form.changer_nr"
                  :rules="form.changer_nr ? [requiredRule] : []"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.id_nr_nouveau"
                  :items="nrs"
                  item-title="id_nr"
                  item-value="id_nr"
                  label="NR nouveau"
                  variant="outlined"
                  density="compact"
                  :disabled="!form.changer_nr"
                  :rules="form.changer_nr ? [requiredRule] : []"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="closeCreateDialog">
            Annuler
          </v-btn>
          <v-btn color="primary" :loading="saving" @click="submitCreate">
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showEditDateDialog" max-width="460">
      <v-card>
        <v-card-title class="text-h6 font-weight-bold pa-4">
          Corriger la date
        </v-card-title>
        <v-card-text>
          <v-text-field
            v-model="editDate"
            type="date"
            label="Date"
            variant="outlined"
            density="compact"
          />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="showEditDateDialog = false">
            Annuler
          </v-btn>
          <v-btn color="primary" :loading="saving" @click="submitEditDate">
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useAvancementStore } from '@/stores/avancement'

const avancementStore = useAvancementStore()
const { avancements, agents, gfs, nrs, loading, saving, error, filters, pagination } = storeToRefs(avancementStore)
const {
  fetchAvancements,
  fetchAgents,
  fetchGfs,
  fetchNrs,
  createAvancement,
  updateAvancementDate,
  updateFilters,
  setPage
} = avancementStore

const createFormRef = ref(null)
const showCreateDialog = ref(false)
const showEditDateDialog = ref(false)
const selectedAvancement = ref(null)
const editDate = ref('')

const typeFilters = [
  { label: 'Promotion', value: 'GF' },
  { label: 'Avancement', value: 'NR' }
]

const headers = [
  { title: 'Date', key: 'date' },
  { title: 'Agent', key: 'agent' },
  { title: 'Type', key: 'type', sortable: false },
  { title: 'GF', key: 'gf', sortable: false },
  { title: 'NR', key: 'nr', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
]

const emptyForm = () => ({
  date: new Date().toISOString().slice(0, 10),
  matricule_agent: '',
  changer_gf: false,
  id_gf_ancien: '',
  id_gf_nouveau: '',
  changer_nr: false,
  id_nr_ancien: '',
  id_nr_nouveau: ''
})

const form = ref(emptyForm())
const requiredRule = value => Boolean(value) || 'Champ requis'

const pageCount = computed(() => {
  return Math.max(Math.ceil((pagination.value.total || 0) / pagination.value.per_page), 1)
})

const selectedAgent = computed(() => {
  return agents.value.find(agent => agent.matricule === form.value.matricule_agent)
})

const getAgentTitle = (agent) => {
  if (!agent) return ''
  return `${agent.matricule} - ${agent.prenom || ''} ${agent.nom || ''}`.trim()
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const toInputDate = (date) => {
  if (!date) return ''
  return String(date).slice(0, 10)
}

const getTypeLabel = (item) => {
  const hasGf = Boolean(item.id_gf_nouveau)
  const hasNr = Boolean(item.id_nr_nouveau)
  if (hasGf && hasNr) return 'Promotion + Avancement'
  if (hasGf) return 'Promotion'
  if (hasNr) return 'Avancement'
  return 'Indéfini'
}

const getTypeColor = (item) => {
  if (item.id_gf_nouveau && item.id_nr_nouveau) return 'primary'
  if (item.id_gf_nouveau) return 'success'
  if (item.id_nr_nouveau) return 'info'
  return 'grey'
}

const syncCurrentAgentLevels = () => {
  if (!selectedAgent.value) return

  if (form.value.changer_gf && !form.value.id_gf_ancien) {
    form.value.id_gf_ancien = selectedAgent.value.id_gf_actuel || selectedAgent.value.gf_actuel?.id_gf || ''
  }
  if (!form.value.changer_gf) {
    form.value.id_gf_ancien = ''
    form.value.id_gf_nouveau = ''
  }
  if (form.value.changer_nr && !form.value.id_nr_ancien) {
    form.value.id_nr_ancien = selectedAgent.value.id_nr_actuel || selectedAgent.value.nr_actuel?.id_nr || ''
  }
  if (!form.value.changer_nr) {
    form.value.id_nr_ancien = ''
    form.value.id_nr_nouveau = ''
  }
}

const handleAgentChange = () => {
  form.value.id_gf_ancien = ''
  form.value.id_nr_ancien = ''
  syncCurrentAgentLevels()
}

const openCreateDialog = () => {
  form.value = emptyForm()
  createFormRef.value?.resetValidation()
  showCreateDialog.value = true
}

const closeCreateDialog = () => {
  showCreateDialog.value = false
  form.value = emptyForm()
}

const submitCreate = async () => {
  const validation = await createFormRef.value?.validate()
  if (!validation?.valid) return

  if (!form.value.changer_gf && !form.value.changer_nr) {
    avancementStore.error = 'Sélectionnez au moins une promotion ou un avancement.'
    return
  }

  const payload = {
    date: form.value.date,
    matricule_agent: form.value.matricule_agent,
    id_gf_ancien: form.value.changer_gf ? form.value.id_gf_ancien : null,
    id_gf_nouveau: form.value.changer_gf ? form.value.id_gf_nouveau : null,
    id_nr_ancien: form.value.changer_nr ? form.value.id_nr_ancien : null,
    id_nr_nouveau: form.value.changer_nr ? form.value.id_nr_nouveau : null
  }

  await createAvancement(payload)
  closeCreateDialog()
}

const openEditDateDialog = (item) => {
  selectedAvancement.value = item
  editDate.value = toInputDate(item.date)
  showEditDateDialog.value = true
}

const submitEditDate = async () => {
  if (!selectedAvancement.value || !editDate.value) return

  await updateAvancementDate(selectedAvancement.value.id, editDate.value)
  showEditDateDialog.value = false
  selectedAvancement.value = null
}

const prevPage = () => {
  if (pagination.value.page > 1) {
    setPage(pagination.value.page - 1)
  }
}

const nextPage = () => {
  if (pagination.value.page < pageCount.value) {
    setPage(pagination.value.page + 1)
  }
}

onMounted(() => {
  fetchAgents()
  fetchGfs()
  fetchNrs()
  fetchAvancements()
})
</script>

<style scoped>
.avancements-view {
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
</style>
