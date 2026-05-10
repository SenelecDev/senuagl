<template>
  <v-container fluid class="postes-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex flex-wrap align-center ga-4 pa-4">
        <div>
          <span class="text-h5 font-weight-bold">Gestion des postes</span>
          <div class="text-body-2 text-medium-emphasis">
            {{ filteredPostes.length }} poste(s) affiché(s)
          </div>
        </div>
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
          Nouveau poste
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="5">
            <v-text-field
              v-model="filters.search"
              append-inner-icon="mdi-magnify"
              label="Rechercher par intitulé ou unité"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="handleSearchInput"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="filters.unite"
              :items="unites"
              item-title="nom"
              item-value="id_unite"
              label="Unité"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="value => updateFilters({ unite: value || '' })"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-switch
              v-model="filters.vacants"
              color="primary"
              label="Vacants uniquement"
              hide-details
              @update:model-value="value => updateFilters({ vacants: value })"
            />
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
        :items="filteredPostes"
        :items-per-page="10"
        :items-per-page-options="[10, 20, 50, 100]"
        :loading="loading"
        class="elevation-0"
        hover
        no-data-text="Aucun poste trouvé"
        loading-text="Chargement des postes..."
      >
        <template #item.unite="{ item }">
          {{ getUniteName(item) }}
        </template>

        <template #item.tube="{ item }">
          {{ getGfLabel(item.tube_min) }} - {{ getGfLabel(item.tube_max) }}
        </template>

        <template #item.effectifs="{ item }">
          {{ item.effectif_reel ?? 0 }} / {{ item.effectif_theorique ?? 0 }}
        </template>

        <template #item.postes_vacants="{ item }">
          <v-chip
            :color="Number(item.postes_vacants || 0) > 0 ? 'warning' : 'success'"
            size="small"
            variant="tonal"
          >
            {{ item.postes_vacants ?? 0 }}
          </v-chip>
        </template>

        <template #item.taux_occupation="{ item }">
          <div class="occupation-cell">
            <span>{{ item.taux_occupation ?? 0 }}%</span>
            <v-progress-linear
              :model-value="Number(item.taux_occupation || 0)"
              :color="getOccupationColor(item.taux_occupation)"
              height="6"
              rounded
            />
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex ga-2 justify-end">
            <v-tooltip text="Détails" location="top">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-eye-outline"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openDetailDialog(item)"
                />
              </template>
            </v-tooltip>
            <v-tooltip text="Modifier" location="top">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-pencil-outline"
                  size="small"
                  variant="text"
                  color="secondary"
                  @click="openEditDialog(item)"
                />
              </template>
            </v-tooltip>
            <v-tooltip text="Supprimer" location="top">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-delete-outline"
                  size="small"
                  variant="text"
                  color="error"
                  :disabled="Number(item.effectif_reel || 0) > 0"
                  @click="openDeleteDialog(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="showFormDialog" max-width="720">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">{{ formTitle }}</span>
          <v-btn icon="mdi-close" variant="text" @click="closeFormDialog" />
        </v-card-title>

        <v-card-text>
          <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-form ref="formRef" @submit.prevent="submitForm">
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.id_post"
                  label="Code poste"
                  variant="outlined"
                  density="compact"
                  :disabled="isEditing"
                  
                />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="form.intitule"
                  label="Intitulé"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model.number="form.effectif_theorique"
                  label="Effectif théorique"
                  type="number"
                  min="1"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule, positiveRule]"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="form.tube_min"
                  :items="gfs"
                  item-title="ordre"
                  item-value="id_gf"
                  label="Tube min"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="form.tube_max"
                  :items="gfs"
                  item-title="ordre"
                  item-value="id_gf"
                  label="Tube max"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                />
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="form.id_unite"
                  :items="unites"
                  item-title="nom"
                  item-value="id_unite"
                  label="Unité"
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
          <v-btn variant="text" @click="closeFormDialog">
            Annuler
          </v-btn>
          <v-btn color="primary" :loading="saving" @click="submitForm">
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDetailDialog" max-width="900">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">Détail du poste</span>
          <v-btn icon="mdi-close" variant="text" @click="closeDetailDialog" />
        </v-card-title>

        <v-card-text>
          <div v-if="loadingPoste" class="text-center py-10">
            <v-progress-circular indeterminate color="primary" />
          </div>

          <v-alert v-else-if="errorPoste" type="error" variant="tonal">
            {{ errorPoste }}
          </v-alert>

          <div v-else-if="currentPoste">
            <v-row class="mb-4" dense>
              <v-col cols="12" md="6">
                <v-sheet class="pa-4 rounded-lg elevation-1 h-100">
                  <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations</h3>
                  <p><strong>Code :</strong> {{ currentPoste.id_post }}</p>
                  <p><strong>Intitulé :</strong> {{ currentPoste.intitule }}</p>
                  <p><strong>Unité :</strong> {{ getUniteName(currentPoste) }}</p>
                  <p><strong>Tube :</strong> {{ getGfLabel(currentPoste.tube_min) }} - {{ getGfLabel(currentPoste.tube_max) }}</p>
                </v-sheet>
              </v-col>
              <v-col cols="12" md="6">
                <v-sheet class="pa-4 rounded-lg elevation-1 h-100">
                  <h3 class="text-subtitle-1 font-weight-bold mb-3">Effectifs</h3>
                  <p><strong>Théorique :</strong> {{ currentPoste.effectif_theorique ?? 0 }}</p>
                  <p><strong>Réel :</strong> {{ currentPoste.effectif_reel ?? 0 }}</p>
                  <p><strong>Vacants :</strong> {{ currentPoste.postes_vacants ?? 0 }}</p>
                  <p><strong>Taux d'occupation :</strong> {{ currentPoste.taux_occupation ?? 0 }}%</p>
                </v-sheet>
              </v-col>
            </v-row>

            <v-sheet class="pa-4 rounded-lg elevation-1">
              <h3 class="text-subtitle-1 font-weight-bold mb-4">Agents occupants</h3>
              <v-data-table
                :headers="agentHeaders"
                :items="currentPoste.agents || []"
                hide-default-footer
                no-data-text="Aucun agent affecté à ce poste"
              >
                <template #item.nom_complet="{ item }">
                  {{ item.prenom }} {{ item.nom }}
                </template>
                <template #item.gf="{ item }">
                  {{ item.gf_actuel?.ordre || 'N/A' }}
                </template>
                <template #item.nr="{ item }">
                  {{ item.nr_actuel?.ordre || 'N/A' }}
                </template>
              </v-data-table>
            </v-sheet>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDeleteDialog" max-width="460">
      <v-card>
        <v-card-title class="text-h6 font-weight-bold pa-4">
          Supprimer le poste
        </v-card-title>
        <v-card-text>
          Confirmer la suppression du poste
          <strong>{{ selectedPoste?.intitule }}</strong> ?
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="showDeleteDialog = false">
            Annuler
          </v-btn>
          <v-btn color="error" :loading="deleting" @click="confirmDelete">
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import { usePosteStore } from '@/stores/poste'

const posteStore = usePosteStore()
const {
  filteredPostes,
  currentPoste,
  unites,
  gfs,
  loading,
  loadingPoste,
  saving,
  deleting,
  error,
  errorPoste,
  filters
} = storeToRefs(posteStore)
const {
  fetchPostes,
  fetchUnites,
  fetchGfs,
  fetchPoste,
  createPoste,
  updatePoste,
  deletePoste,
  updateFilters,
  resetCurrentPoste
} = posteStore

const formRef = ref(null)
const showFormDialog = ref(false)
const showDetailDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedPoste = ref(null)
let searchTimer = null
const form = ref({
  id_post: '',
  intitule: '',
  effectif_theorique: 1,
  tube_min: '',
  tube_max: '',
  id_unite: ''
})

const headers = [
  // { title: 'Code', key: 'id_post' },
  { title: 'Intitulé', key: 'intitule' },
  { title: 'Unité', key: 'unite' },
  { title: 'Tube', key: 'tube', sortable: false, width: '120px'},
  { title: 'Effectifs', key: 'effectifs', sortable: false },
  { title: 'Vacants', key: 'postes_vacants' },
  { title: 'Occupation', key: 'taux_occupation' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
]

const agentHeaders = [
  { title: 'Matricule', key: 'matricule' },
  { title: 'Nom complet', key: 'nom_complet' },
  { title: 'GF', key: 'gf' },
  { title: 'NR', key: 'nr' }
]

const isEditing = computed(() => Boolean(selectedPoste.value))
const formTitle = computed(() => isEditing.value ? 'Modifier le poste' : 'Nouveau poste')

const requiredRule = value => Boolean(value) || 'Champ requis'
const positiveRule = value => Number(value) >= 1 || 'La valeur doit être supérieure ou égale à 1'

const handleSearchInput = (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    updateFilters({ search: value || '' })
  }, 300)
}

const getGfLabel = (gf) => {
  if (!gf) return 'N/A'
  if (typeof gf === 'object') return gf.id_gf || gf.ordre || 'N/A'
  return gf
}

const getUniteName = (poste) => {
  if (!poste?.unite) return 'N/A'
  return typeof poste.unite === 'string' ? poste.unite : poste.unite.nom
}

const getOccupationColor = (value) => {
  const taux = Number(value || 0)
  if (taux >= 100) return 'success'
  if (taux >= 70) return 'primary'
  return 'warning'
}

const resetForm = () => {
  selectedPoste.value = null
  form.value = {
    id_post: '',
    intitule: '',
    effectif_theorique: 1,
    tube_min: '',
    tube_max: '',
    id_unite: ''
  }
  formRef.value?.resetValidation()
}

const openCreateDialog = () => {
  resetForm()
  showFormDialog.value = true
}

const openEditDialog = (poste) => {
  selectedPoste.value = poste
  form.value = {
    id_post: poste.id_post,
    intitule: poste.intitule || '',
    effectif_theorique: poste.effectif_theorique || 1,
    tube_min: typeof poste.tube_min === 'object' ? poste.tube_min?.id_gf : poste.tube_min,
    tube_max: typeof poste.tube_max === 'object' ? poste.tube_max?.id_gf : poste.tube_max,
    id_unite: poste.id_unite || poste.unite?.id_unite || ''
  }
  showFormDialog.value = true
}

const closeFormDialog = () => {
  showFormDialog.value = false
  resetForm()
}

const openDetailDialog = async (poste) => {
  showDetailDialog.value = true
  await fetchPoste(poste.id_post)
}

const closeDetailDialog = () => {
  showDetailDialog.value = false
  resetCurrentPoste()
}

const openDeleteDialog = (poste) => {
  selectedPoste.value = poste
  showDeleteDialog.value = true
}

const submitForm = async () => {
  const validation = await formRef.value?.validate()
  if (!validation?.valid) return

  const payload = {
    intitule: form.value.intitule,
    effectif_theorique: Number(form.value.effectif_theorique),
    tube_min: form.value.tube_min,
    tube_max: form.value.tube_max,
    id_unite: form.value.id_unite
  }

  if (isEditing.value) {
    await updatePoste(selectedPoste.value.id_post, payload)
  } else {
    await createPoste({ ...payload, id_post: form.value.id_post })
  }

  closeFormDialog()
}

const confirmDelete = async () => {
  if (!selectedPoste.value) return

  await deletePoste(selectedPoste.value.id_post)
  showDeleteDialog.value = false
  selectedPoste.value = null
}

onMounted(() => {
  fetchUnites()
  fetchGfs()
  fetchPostes()
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
})
</script>

<style scoped>
.postes-view {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.occupation-cell {
  display: grid;
  grid-template-columns: 44px minmax(90px, 1fr);
  align-items: center;
  gap: 0.75rem;
  min-width: 150px;
}
</style>
