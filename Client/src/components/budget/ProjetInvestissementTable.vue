<template>
  <div class="projet-container">
    <div class="form-copy">
      <h2>Suivi des Projets d'Investissement (Travaux)</h2>
      <p>Gestion des projets CAPEX, financements et budgets annuels.</p>
    </div>

    <v-data-table
      :headers="headers"
      :items="projets"
      :loading="loading"
      :items-per-page="50"
      class="elevation-0 projet-table"
      hover
      no-data-text="Aucun projet d'investissement enregistré pour cette année"
    >
      <template #item.libelle="{ item }">
        <div class="projet-libelle">
          <strong>{{ item.code_projet }}</strong>
          <span class="muted">{{ item.libelle }}</span>
        </div>
      </template>

      <template #item.bailleur="{ item }">
        <v-chip size="small" variant="tonal" color="primary">
          {{ item.bailleur }}
        </v-chip>
      </template>

      <template #item.montant_marche="{ item }">
        {{ formatMoney(item.montant_marche) }}
      </template>

      <template #item.cout_projet="{ item }">
        {{ formatMoney(item.cout_projet) }}
      </template>

      <template #item.fp_annee="{ item }">
        <span class="font-weight-bold text-success">{{ formatMoney(item.fp_annee) }}</span>
      </template>

      <template #item.fe_annee="{ item }">
        <span class="font-weight-bold text-info">{{ formatMoney(item.fe_annee) }}</span>
      </template>

      <template #item.total_annee="{ item }">
        <v-chip color="secondary" size="small" variant="elevated">
          {{ formatMoney(Number(item.fp_annee || 0) + Number(item.fe_annee || 0)) }}
        </v-chip>
      </template>

      <template #body.append>
        <tr class="table-totals" v-if="projets.length > 0">
          <td colspan="3" class="text-right"><strong>Total Général :</strong></td>
          <td class="text-right"><strong>{{ formatMoney(totalMontantMarche) }}</strong></td>
          <td class="text-right"><strong>{{ formatMoney(totalCoutProjet) }}</strong></td>
          <td class="text-right text-success"><strong>{{ formatMoney(totalFp) }}</strong></td>
          <td class="text-right text-info"><strong>{{ formatMoney(totalFe) }}</strong></td>
          <td class="text-right"><strong>{{ formatMoney(totalAnnee) }}</strong></td>
        </tr>
      </template>
    </v-data-table>

    <!-- Modal d'ajout de projet -->
    <div class="mt-4 form-actions">
      <v-btn color="primary" prepend-icon="mdi-plus" @click="dialog = true">
        Nouveau Projet
      </v-btn>
    </div>

    <v-dialog v-model="dialog" max-width="800px">
      <v-card>
        <v-card-title>
          <span class="text-h6">Nouveau Projet d'Investissement</span>
        </v-card-title>

        <v-card-text>
          <v-form ref="formRef" @submit.prevent="save">
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.code_projet" label="Code Projet" variant="outlined" density="compact" :rules="[requiredRule]" />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field v-model="form.libelle" label="Libellé du projet" variant="outlined" density="compact" :rules="[requiredRule]" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.bailleur" label="Financement / Bailleur" variant="outlined" density="compact" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.cr" label="CR" variant="outlined" density="compact" />
              </v-col>
              
              <v-col cols="12"><v-divider class="my-2"></v-divider></v-col>
              
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.montant_marche" type="number" label="Montant Marché" variant="outlined" density="compact" :rules="[positiveRule]" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.cout_projet" type="number" label="Coût du Projet" variant="outlined" density="compact" :rules="[positiveRule]" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.fp_annee" type="number" :label="'Fonds Propres ' + annee" variant="outlined" density="compact" :rules="[positiveRule]" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.fe_annee" type="number" :label="'Financement Extérieur ' + annee" variant="outlined" density="compact" :rules="[positiveRule]" />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="close">Annuler</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useProjetInvestissementStore } from '@/stores/projetInvestissement'

const props = defineProps({
  annee: {
    type: Number,
    required: true
  }
})

const store = useProjetInvestissementStore()
const { projets, loading, saving, totalMontantMarche, totalCoutProjet, totalFp, totalFe, totalAnnee } = storeToRefs(store)

const dialog = ref(false)
const formRef = ref(null)

const headers = [
  { title: 'Projet', key: 'libelle' },
  { title: 'Financement', key: 'bailleur' },
  { title: 'CR', key: 'cr' },
  { title: 'Montant Marché', key: 'montant_marche', align: 'end' },
  { title: 'Coût Projet', key: 'cout_projet', align: 'end' },
  { title: 'FP Année', key: 'fp_annee', align: 'end' },
  { title: 'FE Année', key: 'fe_annee', align: 'end' },
  { title: 'Total Année', key: 'total_annee', align: 'end' }
]

const requiredRule = value => Boolean(value) || 'Champ requis'
const positiveRule = value => Number(value) >= 0 || 'Montant invalide'

const form = reactive({
  code_projet: '',
  libelle: '',
  bailleur: 'FONDS PROPRES',
  cr: '',
  montant_marche: 0,
  cout_projet: 0,
  fp_annee: 0,
  fe_annee: 0
})

const formatMoney = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}

const close = () => {
  dialog.value = false
  formRef.value?.reset()
  form.bailleur = 'FONDS PROPRES'
}

const save = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  await store.createProjet({ ...form, annee: props.annee })
  close()
}

watch(() => props.annee, (newVal) => {
  store.fetchProjets(newVal)
})

onMounted(() => {
  store.fetchProjets(props.annee)
})
</script>

<style scoped>
.projet-container {
  padding: 1rem;
}

.projet-libelle {
  display: flex;
  flex-direction: column;
  padding: 0.5rem 0;
}

.projet-libelle strong {
  color: #0f172a;
}

.muted {
  color: #64748b;
  font-size: 0.85rem;
}

.projet-table {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.table-totals td {
  background-color: #f8fafc;
  border-top: 2px solid #e2e8f0;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  font-size: 1.05em;
}

.form-copy h2 {
  margin: 0;
  color: #111827;
  font-size: 1.25rem;
}

.form-copy p {
  margin: 0.25rem 0 1rem;
  color: #64748b;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}
</style>
