<template>
  <div class="mensuel-wrapper">
    <div class="mensuel-scroll">
      <table class="mensuel-table">
        <thead>
          <tr>
            <th class="section-heading" colspan="2">{{ title }}</th>
            <th class="service-heading" colspan="4">Général</th>
          </tr>
          <tr>
            <th class="account-number">N° comptes</th>
            <th class="account-label">Intitulé</th>
            <th class="amount-col">Prévision</th>
            <th class="amount-col">Réalisation</th>
            <th class="amount-col">Écart</th>
            <th class="observation-col">Observation</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.key"
            :class="{ 'section-row': row.is_section }"
          >
            <td class="account-number">{{ getCompteNumero(row) }}</td>
            <td class="account-label">{{ row.compte?.intitule || '' }}</td>
              <td
                class="amount-col editable"
                @dblclick="startEdit(row)"
                :class="{ editing: isEditing(row) }"
              >
                <input
                  v-if="isEditing(row)"
                  :value="editValue"
                  @blur="saveEdit(row)"
                  @keydown.enter="saveEdit(row)"
                  @keydown.escape="cancelEdit"
                  type="number"
                  class="edit-input"
                  ref="editInput"
                  autofocus
                />
                <span v-else>{{ formatNumber(getPrevision(row)) }}</span>
              </td>
              <td class="amount-col">{{ formatNumber(getRealisation(row)) }}</td>
              <td class="amount-col">{{ formatNumber(getEcart(row)) }}</td>
              <td
                class="observation-col"
                :class="getEcart(row) >= 0 ? 'observation-ok' : 'observation-neg'"
              >
                {{ getEcart(row) >= 0 ? 'FAVORABLE' : 'DÉFAVORABLE' }}
              </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="6" class="empty-row">
              Aucune donnée budgétaire.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  mois: { type: Number, default: 1 },
  title: { type: String, default: "CHARGES D'EXPLOITATION" },
  annee: { type: Number, default: new Date().getFullYear() }
})

const emit = defineEmits(['prevision-updated'])



const isSectionRow = (row) => row.is_section || String(row.compte?.numero || '').startsWith('SECTION-')

const getCompteNumero = (row) => {
  if (isSectionRow(row)) return ''
  return row.compte?.numero || 'N/A'
}

const getPrevision = (row) => Number(row.previsions?.[Number(props.mois)] || 0)

const getRealisation = (row) => Number(row.mois?.[Number(props.mois)] || 0)

const getEcart = (row) => getPrevision(row) - getRealisation(row)

const formatNumber = (value) =>
  new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits: 0
  }).format(Math.round(Number(value || 0)))

const editingCell = ref(null)
const editValue = ref('')
const editInput = ref(null)

const isEditing = (row) => {
  return editingCell.value && editingCell.value.rowKey === row.key
}

const startEdit = (row) => {
  if (row.is_regroupement || isSectionRow(row)) {
    return
  }

  editingCell.value = { rowKey: row.key }
  editValue.value = getPrevision(row)
  
  setTimeout(() => {
    if (editInput.value) {
      editInput.value.focus()
      editInput.value.select()
    }
  }, 0)
}

const cancelEdit = () => {
  editingCell.value = null
  editValue.value = ''
}

const saveEdit = async (row) => {
  const newValue = Number(editValue.value)
  
  if (!row.compte) {
    console.error('Données manquantes:', { compte: row.compte })
    alert('Données manquantes pour la sauvegarde')
    cancelEdit()
    return
  }

  const payload = {
    type: 'prevision',
    compte_id: row.compte.id,
    montant_prevu: newValue,
    annee: props.annee,
    mois: props.mois
  }

  console.log('Envoi de la prévision:', payload)

  try {
    const response = await api.post('/budget', payload)

    // Mettre à jour les données localement
    if (!row.previsions) {
      row.previsions = {}
    }
    
    // Forcer la réactivité Vue
    row.previsions[props.mois] = newValue

    emit('prevision-updated', response.data)
    cancelEdit()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    const errorMessage = error.response?.data?.message || 
                        error.response?.data?.errors || 
                        error.message || 
                        'Erreur inconnue'
    console.error('Détail de l\'erreur:', errorMessage)
    alert(`Erreur: ${JSON.stringify(errorMessage)}`)
    cancelEdit()
  }
}
</script>

<style scoped>
.mensuel-wrapper {
  border: 1px solid #111827;
  background: #fff;
  overflow: hidden;
}

.mensuel-scroll {
  overflow-x: auto;
  width: 100%;
}

.mensuel-table {
  width: max-content;
  min-width: 100%;
  border-collapse: collapse;
  table-layout: auto;
  font-size: 0.78rem;
  color: #111827;
}

.mensuel-table th,
.mensuel-table td {
  height: 24px;
  padding: 0.15rem 0.25rem;
  border: 1px solid #111827;
  vertical-align: middle;
  white-space: nowrap;
}

.mensuel-table th {
  background: #fff;
  font-weight: 800;
  text-transform: uppercase;
}

.section-row td {
  background: #ccff00;
  font-weight: 800;
  text-align: center;
  text-transform: uppercase;
}

.section-row .account-number {
  background: #ccff00;
}

.section-heading {
  text-align: left;
}

.service-heading {
  text-align: center;
}

.account-number {
  min-width: 78px;
  text-align: right;
  font-weight: 700;
}

.account-label {
  min-width: 382px;
  text-align: left;
  font-weight: 600;
}

.amount-col {
  min-width: 92px;
  text-align: right;
}

.observation-col {
  min-width: 112px;
  text-align: left;
  font-weight: 700;
}

.observation-ok {
  background: #92d050;
}

.observation-neg {
  background: #f87171;
}

.empty-row {
  height: 52px;
  text-align: center;
  color: #6b7280;
  font-style: italic;
}

.editable {
  cursor: pointer;
  user-select: none;
}

.editable:hover {
  background-color: #f0f0f0;
}

.amount-col.editing {
  padding: 0;
  background-color: #fff3cd;
}

.edit-input {
  width: 100%;
  height: 100%;
  border: none;
  padding: 0.15rem 0.25rem;
  font-size: inherit;
  text-align: right;
  background: transparent;
  color: #111827;
  font-weight: 500;
}

.edit-input:focus {
  outline: none;
  background-color: #fff;
}
</style>
