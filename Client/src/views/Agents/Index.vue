<template>
  <v-container fluid class="agents-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex flex-wrap align-center ga-4 pa-4">
        <div>
          <span class="text-h5 font-weight-bold">Liste des Agents</span>
          <div class="text-body-2 text--secondary">Total agents : {{ pagination.total }}</div>
        </div>
        <v-spacer></v-spacer>
        <v-btn color="primary" prepend-icon="mdi-account-plus-outline" @click="openCreateDialog">
          Nouvel agent
        </v-btn>
        <v-text-field
          v-model="filters.search"
          append-inner-icon="mdi-magnify"
          label="Rechercher par nom, prénom, matricule..."
          variant="outlined"
          density="compact"
          hide-details
          class="mr-4"
          style="max-width: 400px"
          clearable
          @update:model-value="handleSearchInput"
        >
          <template v-slot:append>
            <v-tooltip
              text="Recherche dans : nom, prénom, matricule"
              location="bottom"
            >
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" color="grey" size="small">
                  mdi-help-circle-outline
                </v-icon>
              </template>
            </v-tooltip>
          </template>
        </v-text-field>
      </v-card-title>

      <!-- Filtres -->
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.service"
              :items="unites"
              item-title="nom"
              item-value="id_unite"
              label="Service"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="value => updateFilters({ service: value || '' })"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.gf"
              :items="gfs"
              item-title="ordre"
              item-value="id_gf"
              label="GF"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="value => updateFilters({ gf: value || '' })"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.sexe"
              :items="sexes"
              item-title="label"
              item-value="value"
              label="Sexe"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="value => updateFilters({ sexe: value || '' })"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.lieu"
              label="Lieu"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="value => updateFilters({ lieu: value || '' })"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-checkbox
              v-model="filters.plafonne"
              label="Plafonnés uniquement"
              @change="() => updateFilters({ plafonne: filters.plafonne })"
            ></v-checkbox>
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
          {{ error }}
        </v-alert>
      </v-card-text>

      <!-- Table -->
      <v-data-table
        :headers="headers"
        :items="agents"
        :items-per-page="pagination.per_page"
        :items-per-page-options="[10, 20, 50, 100]"
        class="elevation-0"
        hover
        no-data-text="Aucun agent trouvé"
        :loading="loading"
        loading-text="Chargement des agents..."
        @update:options="onTableOptionsUpdate"
      >
        <template v-slot:item.poste="{ item }">
          {{ item.poste?.intitule || 'N/A' }}
        </template>
        <template v-slot:item.actions="{ item }">
          <div class="d-flex ga-2 justify-end">
            <v-btn
              size="small"
              color="primary"
              variant="outlined"
              @click="goToAgentDetail(item)"
            >
              Voir plus
            </v-btn>
            <v-btn
              size="small"
              color="secondary"
              variant="outlined"
              prepend-icon="mdi-pencil-outline"
              @click="openEditDialog(item)"
            >
              Modifier
            </v-btn>
          </div>
        </template>
      </v-data-table>

      <!-- Pagination -->
      <v-card-actions v-if="!loading && agents.length > 0">
        <v-spacer></v-spacer>
        <v-btn
          @click="prevPage"
          :disabled="pagination.page <= 1"
          variant="outlined"
        >
          Précédent
        </v-btn>
        <span class="mx-4">Page {{ pagination.page }} sur {{ Math.ceil(pagination.total / pagination.per_page) }}</span>
        <v-btn
          @click="nextPage"
          :disabled="pagination.page >= Math.ceil(pagination.total / pagination.per_page)"
          variant="outlined"
        >
          Suivant
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Modal Détail Agent -->
    <v-dialog v-model="showDetailModal" max-width="900px">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">Détail Agent</span>
          <v-btn icon @click="showDetailModal = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text v-if="selectedAgent" class="pa-6">
          <v-row>
            <!-- Informations personnelles -->
            <v-col cols="12" md="6">
              <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations Personnelles</h3>
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title>Matricule</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.matricule }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Nom</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.nom }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Prénom</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.prenom }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Sexe</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.sexe === 'M' ? 'Masculin' : 'Féminin' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Date de Naissance</v-list-item-title>
                  <v-list-item-subtitle>{{ formatDate(selectedAgent.date_naissance) }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Situation Familiale</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.situation_familiale || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Nombre d'Enfants</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.nombre_enfants || 0 }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-col>

            <!-- Informations professionnelles -->
            <v-col cols="12" md="6">
              <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations Professionnelles</h3>
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title>Poste</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.poste?.intitule || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Unité</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.poste?.unite?.nom || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Date d'Embauche</v-list-item-title>
                  <v-list-item-subtitle>{{ formatDate(selectedAgent.date_embauche) }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Lieu</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.lieu || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>GF Actuel</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.gf_actuel?.ordre || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>NR Actuel</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.nr_actuel?.ordre || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Salaire de Base</v-list-item-title>
                  <v-list-item-subtitle v-if="selectedAgent.salaire_base">{{ formatCurrency(selectedAgent.salaire_base) }}</v-list-item-subtitle>
                  <v-list-item-subtitle v-else>N/A</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-col>

            <!-- Informations Bancaires -->
            <v-col cols="12" md="6">
              <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations Bancaires</h3>
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title>Banque</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.banque || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Compte</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.compte || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>RIB</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.rib || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-col>

            <!-- Autres Informations -->
            <v-col cols="12" md="6">
              <h3 class="text-subtitle-1 font-weight-bold mb-3">Autres Informations</h3>
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title>Titre</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.titre || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Statut</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedAgent.statut || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            variant="outlined"
            @click="goToFullDetail"
          >
            Voir tous les détails
          </v-btn>
          <v-btn
            variant="text"
            @click="showDetailModal = false"
          >
            Fermer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal Formulaire Agent -->
    <v-dialog v-model="showFormDialog" max-width="980px">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-4">
          <span class="text-h5 font-weight-bold">{{ formTitle }}</span>
          <v-btn icon="mdi-close" variant="text" @click="closeFormDialog"></v-btn>
        </v-card-title>

        <v-card-text class="pa-6">
          <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-form ref="formRef" @submit.prevent="submitAgentForm">
            <v-row>
              <v-col cols="12">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">Identité</h3>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.matricule" label="Matricule" variant="outlined" density="compact" :rules="[requiredRule]"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.titre" label="Titre" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.nom" label="Nom" variant="outlined" density="compact" :rules="[requiredRule]"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.prenom" label="Prénom" variant="outlined" density="compact" :rules="[requiredRule]"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-select v-model="form.sexe" :items="sexes" item-title="label" item-value="value" label="Sexe" variant="outlined" density="compact" :rules="[requiredRule]"></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.date_naissance" label="Date de naissance" type="date" variant="outlined" density="compact" :rules="[requiredRule]"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.lieu_naissance" label="Lieu de naissance" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.nationalite" label="Nationalité" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.ethnie" label="Ethnie" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.religion" label="Religion" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <v-col cols="12">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">Poste</h3>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="form.id_post"
                  :items="postes"
                  item-title="intitule"
                  item-value="id_post"
                  label="Poste"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                  @update:model-value="syncGfWithSelectedPoste"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.lieu" label="Lieu" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.date_embauche" label="Date d'embauche" type="date" variant="outlined" density="compact" :rules="[requiredRule]"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="form.id_gf_actuel"
                  :items="filteredGfsForSelectedPoste"
                  item-title="ordre"
                  item-value="id_gf"
                  label="GF actuel"
                  variant="outlined"
                  density="compact"
                  :rules="[requiredRule]"
                  @update:model-value="syncGfWithSelectedPoste"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-select v-model="form.id_nr_actuel" :items="nrs" item-title="ordre" item-value="id_nr" label="NR actuel" variant="outlined" density="compact" :rules="[requiredRule]"></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.organisation" label="Organisation" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.centre_de_responsabilite" label="Centre de responsabilité" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <v-col cols="12">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">Famille et paie</h3>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.situation_familiale" label="Situation familiale" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.nombre_enfants" label="Nombre d'enfants" type="number" min="0" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.enfants_21_ans" label="Enfants 21 ans" type="number" min="0" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.enfants_18_ans" label="Enfants 18 ans" type="number" min="0" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.salaire_base" label="Salaire de base" type="number" min="0" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.mode_reglement" label="Mode de règlement" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-checkbox v-model="form.part_trimf" label="Part TRIMF" density="compact" hide-details></v-checkbox>
              </v-col>
              <v-col cols="12" md="3">
                <v-checkbox v-model="form.part_ir" label="Part IR" density="compact" hide-details></v-checkbox>
              </v-col>

              <v-col cols="12">
                <h3 class="text-subtitle-1 font-weight-bold mb-3">Informations complémentaires</h3>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.banque" label="Banque" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.compte" label="Compte" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.rib" label="RIB" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.domiciliation" label="Domiciliation" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.num_identite" label="Numéro identité" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.num_ipres" label="Numéro IPRES" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.num_secu_social" label="Numéro sécurité sociale" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.situation_affectation" label="Situation d'affectation" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.syndicat" label="Syndicat" variant="outlined" density="compact"></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="closeFormDialog">Annuler</v-btn>
          <v-btn color="primary" :loading="saving" @click="submitAgentForm">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAgentStore } from '../../stores/agent'

const router = useRouter()
const agentStore = useAgentStore()
const { agents, loading, saving, unites, gfs, nrs, postes, filters, pagination, error } = storeToRefs(agentStore)
const { fetchAgents, fetchUnites, fetchGfs, fetchNrs, fetchPostes, createAgent, updateAgent, updateFilters, setPage, setPerPage } = agentStore

const showDetailModal = ref(false)
const showFormDialog = ref(false)
const selectedAgent = ref(null)
const formRef = ref(null)
let searchTimer = null

const sexes = [
  { label: 'Masculin', value: 'M' },
  { label: 'Féminin', value: 'F' }
]

const emptyForm = () => ({
  matricule: '',
  titre: '',
  nom: '',
  prenom: '',
  sexe: '',
  date_naissance: '',
  lieu_naissance: '',
  nationalite: 'Senegalaise',
  ethnie: '',
  religion: '',
  situation_familiale: '',
  nombre_enfants: 0,
  enfants_21_ans: 0,
  enfants_18_ans: 0,
  part_trimf: false,
  part_ir: false,
  num_ipres: '',
  num_secu_social: '',
  date_embauche: '',
  organisation: '',
  centre_de_responsabilite: '',
  lieu: '',
  salaire_base: null,
  mode_reglement: '',
  banque: '',
  compte: '',
  domiciliation: '',
  rib: '',
  num_identite: '',
  situation_affectation: '',
  syndicat: '',
  id_post: '',
  id_gf_actuel: '',
  id_nr_actuel: ''
})

const form = ref(emptyForm())

const headers = [
  { title: 'Matricule', key: 'matricule' },
  { title: 'Nom', key: 'nom' },
  { title: 'Prénom', key: 'prenom' },
  { title: 'Lieu', key: 'lieu' },
  { title: 'Poste', key: 'poste' },
  { title: 'GF', key: 'gf_actuel.ordre' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
]

const isEditing = computed(() => Boolean(selectedAgent.value))
const formTitle = computed(() => isEditing.value ? 'Modifier l\'agent' : 'Nouvel agent')

const requiredRule = value => Boolean(value) || 'Champ requis'

const selectedPoste = computed(() => {
  return postes.value.find((poste) => poste.id_post === form.value.id_post)
})

const filteredGfsForSelectedPoste = computed(() => {
  const poste = selectedPoste.value
  if (!poste) {
    return gfs.value
  }

  const tubeMin = typeof poste.tube_min === 'object' ? poste.tube_min?.id_gf : poste.tube_min
  const tubeMax = typeof poste.tube_max === 'object' ? poste.tube_max?.id_gf : poste.tube_max

  const minOrdre = gfs.value.find(gf => gf.id_gf === tubeMin)?.ordre
  const maxOrdre = gfs.value.find(gf => gf.id_gf === tubeMax)?.ordre

  if (!minOrdre || !maxOrdre) {
    return gfs.value
  }

  return gfs.value.filter(gf => Number(gf.ordre) >= Number(minOrdre) && Number(gf.ordre) <= Number(maxOrdre))
})

const syncGfWithSelectedPoste = () => {
  const poste = selectedPoste.value
  if (!poste) {
    return
  }

  const allowed = filteredGfsForSelectedPoste.value
  if (!allowed.length) {
    return
  }

  const currentGfId = form.value.id_gf_actuel
  const isAllowed = allowed.some(gf => gf.id_gf === currentGfId)
  if (!isAllowed) {
    form.value.id_gf_actuel = allowed[0].id_gf
  }
}

const setItemsPerPage = (value) => {
  const perPage = Number(value || 10)
  setPerPage(perPage)
}

const onTableOptionsUpdate = (options) => {
  const perPage = Number(options?.itemsPerPage || pagination.value.per_page || 10)
  if (perPage !== Number(pagination.value.per_page)) {
    setPerPage(perPage)
  }
}

const toInputDate = (date) => {
  if (!date) return ''
  return String(date).slice(0, 10)
}

const handleSearchInput = (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    updateFilters({ search: value || '' })
  }, 250)
}

const openDetailModal = (agent) => {
  selectedAgent.value = agent
  showDetailModal.value = true
}

const goToAgentDetail = (agent) => {
  router.push(`/agents/${agent.matricule}`)
}

const goToFullDetail = () => {
  showDetailModal.value = false
  router.push(`/agents/${selectedAgent.value.matricule}`)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (value) => {
  if (!value) return 'N/A'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF'
  }).format(value)
}

const fillForm = (agent) => {
  form.value = {
    ...emptyForm(),
    matricule: agent.matricule || '',
    titre: agent.titre || '',
    nom: agent.nom || '',
    prenom: agent.prenom || '',
    sexe: agent.sexe || '',
    date_naissance: toInputDate(agent.date_naissance),
    lieu_naissance: agent.lieu_naissance || '',
    nationalite: agent.nationalite || 'Senegalaise',
    ethnie: agent.ethnie || '',
    religion: agent.religion || '',
    situation_familiale: agent.situation_familiale || '',
    nombre_enfants: agent.nombre_enfants ?? 0,
    enfants_21_ans: agent.enfants_21_ans ?? 0,
    enfants_18_ans: agent.enfants_18_ans ?? 0,
    part_trimf: Boolean(agent.part_trimf),
    part_ir: Boolean(agent.part_ir),
    num_ipres: agent.num_ipres || '',
    num_secu_social: agent.num_secu_social || '',
    date_embauche: toInputDate(agent.date_embauche),
    organisation: agent.organisation || '',
    centre_de_responsabilite: agent.centre_de_responsabilite || '',
    lieu: agent.lieu || '',
    salaire_base: agent.salaire_base ?? null,
    mode_reglement: agent.mode_reglement || '',
    banque: agent.banque || '',
    compte: agent.compte || '',
    domiciliation: agent.domiciliation || '',
    rib: agent.rib || '',
    num_identite: agent.num_identite || '',
    situation_affectation: agent.situation_affectation || '',
    syndicat: agent.syndicat || '',
    id_post: agent.id_post || agent.poste?.id_post || '',
    id_gf_actuel: agent.id_gf_actuel || agent.gf_actuel?.id_gf || '',
    id_nr_actuel: agent.id_nr_actuel || agent.nr_actuel?.id_nr || ''
  }

  syncGfWithSelectedPoste()
}

const openCreateDialog = () => {
  selectedAgent.value = null
  form.value = emptyForm()
  formRef.value?.resetValidation()
  showFormDialog.value = true
}

const openEditDialog = (agent) => {
  selectedAgent.value = agent
  fillForm(agent)
  formRef.value?.resetValidation()
  showFormDialog.value = true
}

const closeFormDialog = () => {
  showFormDialog.value = false
  selectedAgent.value = null
  form.value = emptyForm()
}

const buildPayload = () => ({
  ...form.value,
  nombre_enfants: Number(form.value.nombre_enfants || 0),
  enfants_21_ans: Number(form.value.enfants_21_ans || 0),
  enfants_18_ans: Number(form.value.enfants_18_ans || 0),
  part_trimf: Boolean(form.value.part_trimf),
  part_ir: Boolean(form.value.part_ir),
  salaire_base: form.value.salaire_base === '' || form.value.salaire_base === null ? null : Number(form.value.salaire_base)
})

const submitAgentForm = async () => {
  const validation = await formRef.value?.validate()
  if (!validation?.valid) return

  if (isEditing.value) {
    await updateAgent(selectedAgent.value.matricule, buildPayload())
  } else {
    await createAgent(buildPayload())
  }

  closeFormDialog()
}

const prevPage = () => {
  if (pagination.value.page > 1) {
    setPage(pagination.value.page - 1)
  }
}

const nextPage = () => {
  setPage(pagination.value.page + 1)
}

onMounted(() => {
  fetchUnites()
  fetchGfs()
  fetchNrs()
  fetchPostes()
  fetchAgents()
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
})
</script>
