<template>
  <div class="mensuel-wrapper">
    <div class="mensuel-scroll">
      <table class="mensuel-table">
        <thead>
          <tr>
            <th class="section-heading" colspan="2">{{ title }}</th>
            <th
              v-for="service in visibleServices"
              :key="service.id"
              class="service-heading"
              colspan="4"
            >
              {{ service.code }}
            </th>
          </tr>
          <tr>
            <th class="account-number">N° comptes</th>
            <th class="account-label">Intitulé</th>
            <template v-for="service in visibleServices" :key="`cols-${service.id}`">
              <th class="amount-col">Prévision</th>
              <th class="amount-col">Réalisation</th>
              <th class="amount-col">Écart</th>
              <th class="observation-col">Observation</th>
            </template>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.key">
            <td class="account-number">{{ row.compte?.numero || 'N/A' }}</td>
            <td class="account-label">{{ row.compte?.intitule || '' }}</td>
            <template v-for="service in visibleServices" :key="`${row.key}-${service.id}`">
              <td
                class="amount-col editable"
                @dblclick="startEdit(row, service.id)"
                :class="{ editing: isEditing(row, service.id) }"
              >
                <input
                  v-if="isEditing(row, service.id)"
                  :value="editValue"
                  @blur="saveEdit(row, service.id)"
                  @keydown.enter="saveEdit(row, service.id)"
                  @keydown.escape="cancelEdit"
                  type="number"
                  class="edit-input"
                  ref="editInput"
                  autofocus
                />
                <span v-else>{{ formatNumber(getPrevision(row, service.id)) }}</span>
              </td>
              <td class="amount-col">{{ formatNumber(getRealisation(row, service.id)) }}</td>
              <td class="amount-col">{{ formatNumber(getEcart(row, service.id)) }}</td>
              <td
                class="observation-col"
                :class="getEcart(row, service.id) >= 0 ? 'observation-ok' : 'observation-neg'"
              >
                {{ getEcart(row, service.id) >= 0 ? 'FAVORABLE' : 'DÉFAVORABLE' }}
              </td>
            </template>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="2 + visibleServices.length * 4" class="empty-row">
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
  services: { type: Array, default: () => [] },
  mois: { type: Number, default: 1 },
  serviceId: { type: [Number, String], default: null },
  title: { type: String, default: "CHARGES D'EXPLOITATION" },
  annee: { type: Number, default: new Date().getFullYear() }
})

const emit = defineEmits(['prevision-updated'])

const SERVICE_ORDER = ['SGB', 'SGS', 'SA', 'SEG', 'SA2']

const editingCell = ref(null)
const editValue = ref('')
const editInput = ref(null)

const sortServices = (a, b) => {
  const aIndex = SERVICE_ORDER.indexOf(String(a.code || ''))
  const bIndex = SERVICE_ORDER.indexOf(String(b.code || ''))

  if (aIndex !== -1 || bIndex !== -1) {
    return (aIndex === -1 ? SERVICE_ORDER.length : aIndex) -
      (bIndex === -1 ? SERVICE_ORDER.length : bIndex)
  }

  return String(a.code || '').localeCompare(String(b.code || ''))
}

const visibleServices = computed(() => {
  const sortedServices = [...props.services].sort(sortServices)

  if (props.serviceId == null) return sortedServices

  return sortedServices.filter((service) => Number(service.id) === Number(props.serviceId))
})

const getServiceValues = (row, serviceId) => row.services?.[Number(serviceId)] || {}

const getPrevision = (row, serviceId) =>
  Number(getServiceValues(row, serviceId).previsions?.[Number(props.mois)] || 0)

const getRealisation = (row, serviceId) =>
  Number(getServiceValues(row, serviceId).mois?.[Number(props.mois)] || 0)

const getEcart = (row, serviceId) => getPrevision(row, serviceId) - getRealisation(row, serviceId)

const formatNumber = (value) =>
  new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits: 0
  }).format(Math.round(Number(value || 0)))

const isEditing = (row, serviceId) => {
  return editingCell.value && 
    editingCell.value.rowKey === row.key && 
    editingCell.value.serviceId === serviceId
}

const startEdit = (row, serviceId) => {
  editingCell.value = { rowKey: row.key, serviceId }
  editValue.value = getPrevision(row, serviceId)
  
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

const saveEdit = async (row, serviceId) => {
  const newValue = Number(editValue.value)
  const service = props.services.find(s => s.id === serviceId)
  
  if (!service || !row.compte) {
    console.error('Données manquantes:', { service, compte: row.compte })
    alert('Données manquantes pour la sauvegarde')
    cancelEdit()
    return
  }

  const payload = {
    type: 'prevision',
    service_id: serviceId,
    compte_id: row.compte.id,
    montant_prevu: newValue,
    annee: props.annee,
    mois: props.mois
  }

  console.log('Envoi de la prévision:', payload)

  try {
    const response = await api.post('/budget', payload)

    // Mettre à jour les données localement
    if (!row.services) {
      row.services = {}
    }
    if (!row.services[serviceId]) {
      row.services[serviceId] = { previsions: {}, mois: {} }
    }
    if (!row.services[serviceId].previsions) {
      row.services[serviceId].previsions = {}
    }
    
    // Forcer la réactivité Vue
    row.services[serviceId].previsions[props.mois] = newValue

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
