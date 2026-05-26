<template>
  <v-container fluid class="notes-appreciation-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex flex-wrap align-center ga-4 pa-4">
        <div>
          <span class="text-h5 font-weight-bold">Notes d'appréciation</span>
          <div class="text-body-2 text-medium-emphasis">
            {{ pagination.total }} note(s) enregistrée(s)
          </div>
        </div>
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
          Nouvelle note
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-autocomplete
              v-model="filters.matricule_agent"
              :items="agents"
              :item-title="getAgentTitle"
              item-value="matricule"
              label="Agent"
              variant="outlined"
              density="compact"
              clearable
              hide-details
              @update:model-value="value => updateFilters({ matricule_agent: value || '' })"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="filters.annee"
              type="number"
              label="Année"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="value => updateFilters({ matricule_agent: value || '' })"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-btn
              block
              variant="outlined"
              color="primary"
              prepend-icon="mdi-refresh"
              :loading="loading"
              @click="fetchNotes"
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
        :items="notes"
        :items-per-page="pagination.per_page"
        :loading="loading"
        class="elevation-0"
        hover
        no-data-text="Aucune note trouvée"
        loading-text="Chargement..."
      >
        <template #item.agent="{ item }">
          <div class="agent-cell">
            <strong>{{ item.agent?.prenom }} {{ item.agent?.nom }}</strong>
            <span>{{ item.matricule_agent }}</span>
          </div>
        </template>

        <template #item.note="{ item }">
          <v-chip :color="getNoteColor(item.note)" size="small" variant="tonal">
            {{ item.note }}/100
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-end ga-2">
            <v-btn
              size="small"
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-pencil"
              @click="openEditDialog(item)"
            >
              Modifier
            </v-btn>
            <v-btn
              size="small"
              color="error"
              variant="outlined"
              prepend-icon="mdi-delete"
              @click="confirmDelete(item)"
            >
              Supprimer
            </v-btn>
          </div>
        </template>
      </v-data-table>

      <v-card-actions v-if="!loading && notes.length > 0">
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

    <!-- Dialog Création/Modification -->
    <v-dialog v-model="showFormDialog" max-width="600">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">{{ editingId ? 'Modifier' : 'Nouvelle' }} note</span>
          <v-btn icon="mdi-close" variant="text" @click="closeFormDialog" />
        </v-card-title>

        <v-card-text>
          <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-form ref="formRef" @submit.prevent="submitForm">
            <v-row>
              <v-col cols="12">
                <v-autocomplete
                  v-model="form.matricule_agent"
                  :items="agents"
                  :item-title="getAgentTitle"
                  item-value="matricule"
                  label="Agent"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                  :disabled="!!editingId"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="form.annee"
                  type="number"
                  label="Année"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                  :disabled="!!editingId"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="form.note"
                  type="number"
                  label="Note (0-100)"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule, noteRule]"
                  min="0"
                  max="100"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.commentaire"
                  label="Commentaire"
                  variant="outlined"
                  density="compact"
                  rows="3"
                  counter
                  maxlength="1000"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="closeFormDialog">
            Annuler
          </v-btn>
          <v-btn color="primary" :loading="saving" @click="submitForm">
            {{ editingId ? 'Modifier' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Confirmation Suppression -->
    <v-dialog v-model="showDeleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6 font-weight-bold pa-4">
          Confirmer la suppression
        </v-card-title>
        <v-card-text>
          Êtes-vous certain de vouloir supprimer cette note d'appréciation ?
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="showDeleteDialog = false">
            Annuler
          </v-btn>
          <v-btn color="error" :loading="saving" @click="submitDelete">
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useNoteAppreciationStore } from '@/stores/noteAppreciation'

const noteStore = useNoteAppreciationStore()
const { notes, agents, loading, saving, error, filters, pagination } = storeToRefs(noteStore)
const {
  fetchNotes,
  fetchAgents,
  createNote,
  updateNote,
  deleteNote,
  updateFilters,
  setPage
} = noteStore

const formRef = ref(null)
const showFormDialog = ref(false)
const showDeleteDialog = ref(false)
const editingId = ref(null)
const deleteTarget = ref(null)

const form = ref({
  matricule_agent: '',
  annee: new Date().getFullYear(),
  note: '',
  commentaire: ''
})

const headers = [
  { title: 'Agent', key: 'agent' },
  { title: 'Année', key: 'annee', width: '100px' },
  { title: 'Note', key: 'note', width: '120px' },
  { title: 'Commentaire', key: 'commentaire' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
]

const requiredRule = value => Boolean(value) || 'Champ requis'
const noteRule = value => (value >= 0 && value <= 100) || 'La note doit être entre 0 et 100'

const pageCount = computed(() => {
  return Math.max(Math.ceil((pagination.value.total || 0) / pagination.value.per_page), 1)
})

const getAgentTitle = (agent) => {
  if (!agent) return ''
  return `${agent.matricule} - ${agent.prenom || ''} ${agent.nom || ''}`.trim()
}

const getNoteColor = (note) => {
  if (note >= 90) return 'success'
  if (note >= 75) return 'info'
  if (note >= 50) return 'warning'
  return 'error'
}

const openCreateDialog = () => {
  editingId.value = null
  form.value = {
    matricule_agent: '',
    annee: new Date().getFullYear(),
    note: '',
    commentaire: ''
  }
  formRef.value?.resetValidation()
  showFormDialog.value = true
}

const openEditDialog = (item) => {
  editingId.value = item.id
  form.value = {
    matricule_agent: item.matricule_agent,
    annee: item.annee,
    note: item.note,
    commentaire: item.commentaire
  }
  formRef.value?.resetValidation()
  showFormDialog.value = true
}

const closeFormDialog = () => {
  showFormDialog.value = false
  editingId.value = null
}

const submitForm = async () => {
  const validation = await formRef.value?.validate()
  if (!validation?.valid) return

  try {
    if (editingId.value) {
      await updateNote(editingId.value, {
        note: form.value.note,
        commentaire: form.value.commentaire
      })
    } else {
      await createNote(form.value)
    }
    closeFormDialog()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
  }
}

const confirmDelete = (item) => {
  deleteTarget.value = item
  showDeleteDialog.value = true
}

const submitDelete = async () => {
  if (!deleteTarget.value) return

  try {
    await deleteNote(deleteTarget.value.id)
    showDeleteDialog.value = false
    deleteTarget.value = null
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
  }
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
  fetchNotes()
})
</script>

<style scoped>
.notes-appreciation-view {
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
