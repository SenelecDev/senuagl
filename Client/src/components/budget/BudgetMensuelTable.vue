<template>
  <div class="mensuel-wrapper">
    <div class="mensuel-scroll">
      <table class="mensuel-table">
        <thead>
          <tr>
            <th class="col-sticky col-service">Service</th>
            <th class="col-sticky col-compte">Compte</th>
            <th
              v-for="m in visibleMonths"
              :key="m.value"
              class="col-month"
            >
              {{ m.short }}
            </th>
            <th class="col-total">Total réalisé</th>
            <th class="col-total">Prévu</th>
            <th class="col-total">Écart</th>
            <th class="col-total">Exécution</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.key" :class="{ 'group-row': row.is_regroupement, 'child-row': row.niveau > 0 }">
            <td class="col-sticky col-service">
              <strong>{{ row.service?.code || 'N/A' }}</strong>
              <div class="muted">{{ row.service?.intitule || '' }}</div>
            </td>
            <td class="col-sticky col-compte">
              <strong>{{ row.compte?.numero || 'N/A' }}</strong>
              <span v-if="row.is_regroupement" class="group-badge">Regroupement</span>
              <div class="muted">{{ row.compte?.intitule || '' }}</div>
            </td>
            <td
              v-for="m in visibleMonths"
              :key="m.value"
              class="col-month"
              :class="getCellClass(row, m.value)"
            >
              {{ formatAmount(getMonthValue(row, m.value)) }}
            </td>
            <td class="col-total total-cell">
              {{ formatAmount(getFilteredTotal(row)) }}
            </td>
            <td class="col-total">
              {{ formatAmount(row.montant_prevu) }}
            </td>
            <td class="col-total">
              <span class="ecart-chip" :class="getFilteredEcart(row) >= 0 ? 'ecart-ok' : 'ecart-neg'">
                {{ formatAmount(getFilteredEcart(row)) }}
              </span>
            </td>
            <td class="col-total">
              <div class="rate-cell">
                <span>{{ getFilteredTaux(row) }}%</span>
                <div class="rate-bar-bg">
                  <div
                    class="rate-bar-fill"
                    :class="getRateClass(getFilteredTaux(row))"
                    :style="{ width: Math.min(Number(getFilteredTaux(row)), 100) + '%' }"
                  />
                </div>
              </div>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="visibleMonths.length + 6" class="empty-row">
              Aucune donnée budgétaire.
            </td>
          </tr>
        </tbody>
        <tfoot v-if="rows.length > 0">
          <tr class="totals-row">
            <td class="col-sticky col-service"><strong>TOTAL</strong></td>
            <td class="col-sticky col-compte"></td>
            <td
              v-for="m in visibleMonths"
              :key="m.value"
              class="col-month"
            >
              <strong>{{ formatAmount(getColumnTotal(m.value)) }}</strong>
            </td>
            <td class="col-total"><strong>{{ formatAmount(grandTotalRealise) }}</strong></td>
            <td class="col-total"><strong>{{ formatAmount(grandTotalPrevu) }}</strong></td>
            <td class="col-total">
              <span class="ecart-chip" :class="grandEcart >= 0 ? 'ecart-ok' : 'ecart-neg'">
                <strong>{{ formatAmount(grandEcart) }}</strong>
              </span>
            </td>
            <td class="col-total">
              <strong>{{ grandTaux }}%</strong>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  moisDebut: { type: Number, default: 1 },
  moisFin: { type: Number, default: 12 }
})

const MONTH_LABELS = [
  { value: 1, short: 'Jan' },
  { value: 2, short: 'Fév' },
  { value: 3, short: 'Mar' },
  { value: 4, short: 'Avr' },
  { value: 5, short: 'Mai' },
  { value: 6, short: 'Jun' },
  { value: 7, short: 'Jul' },
  { value: 8, short: 'Aoû' },
  { value: 9, short: 'Sep' },
  { value: 10, short: 'Oct' },
  { value: 11, short: 'Nov' },
  { value: 12, short: 'Déc' }
]

const visibleMonths = computed(() =>
  MONTH_LABELS.filter((m) => m.value >= props.moisDebut && m.value <= props.moisFin)
)

const getMonthValue = (row, month) => row.mois?.[month] ?? 0

const getFilteredTotal = (row) => {
  let sum = 0
  for (let m = props.moisDebut; m <= props.moisFin; m++) {
    sum += row.mois?.[m] ?? 0
  }
  return sum
}

const getFilteredEcart = (row) => row.montant_prevu - getFilteredTotal(row)

const getFilteredTaux = (row) => {
  if (!row.montant_prevu) return 0
  return Math.round((getFilteredTotal(row) / row.montant_prevu) * 1000) / 10
}

const rowsForTotals = computed(() => {
  const detailRows = props.rows.filter(row => !row.is_regroupement)
  return detailRows.length > 0 ? detailRows : props.rows
})

const getColumnTotal = (month) =>
  rowsForTotals.value.reduce((sum, row) => sum + (row.mois?.[month] ?? 0), 0)

const grandTotalRealise = computed(() =>
  rowsForTotals.value.reduce((sum, row) => sum + getFilteredTotal(row), 0)
)

const grandTotalPrevu = computed(() =>
  rowsForTotals.value.reduce((sum, row) => sum + (row.montant_prevu ?? 0), 0)
)

const grandEcart = computed(() => grandTotalPrevu.value - grandTotalRealise.value)

const grandTaux = computed(() => {
  if (!grandTotalPrevu.value) return 0
  return Math.round((grandTotalRealise.value / grandTotalPrevu.value) * 1000) / 10
})

const formatAmount = (value) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(Number(value || 0))

const getCellClass = (row, month) => {
  const val = row.mois?.[month] ?? 0
  if (val === 0) return ''
  const prorata = row.montant_prevu / 12
  if (prorata <= 0) return ''
  if (val > prorata * 1.15) return 'cell-over'
  if (val > prorata * 0.85) return 'cell-warn'
  return 'cell-ok'
}

const getRateClass = (value) => {
  const rate = Number(value || 0)
  if (rate > 100) return 'rate-over'
  if (rate >= 85) return 'rate-warn'
  return 'rate-ok'
}
</script>

<style scoped>
.mensuel-wrapper {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}

.mensuel-scroll {
  overflow-x: auto;
}

.mensuel-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  white-space: nowrap;
}

.mensuel-table th,
.mensuel-table td {
  padding: 0.6rem 0.75rem;
  text-align: right;
  border-bottom: 1px solid #f1f5f9;
}

.mensuel-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 700;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 2px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 1;
}

.mensuel-table tbody tr:hover {
  background: #f8fafc;
}

.group-row {
  background: #f8fafc;
  font-weight: 700;
}

.child-row .col-compte {
  padding-left: 1.5rem;
}

.group-badge {
  display: inline-block;
  margin-left: 0.4rem;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.68rem;
  font-weight: 700;
}

.col-service,
.col-compte {
  text-align: left;
  min-width: 130px;
}

.col-service {
  min-width: 100px;
}

.col-month {
  min-width: 90px;
}

.col-total {
  min-width: 110px;
  font-weight: 600;
  border-left: 2px solid #e2e8f0;
}

.total-cell {
  color: #0f172a;
  font-weight: 700;
}

.muted {
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 400;
}

/* Cell color coding */
.cell-ok {
  background: #f0fdf4;
}

.cell-warn {
  background: #fffbeb;
}

.cell-over {
  background: #fef2f2;
}

/* Ecart chips */
.ecart-chip {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.82rem;
  font-weight: 600;
}

.ecart-ok {
  background: #dcfce7;
  color: #166534;
}

.ecart-neg {
  background: #fee2e2;
  color: #991b1b;
}

/* Rate bar */
.rate-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: flex-end;
}

.rate-cell span {
  min-width: 42px;
  text-align: right;
  font-weight: 600;
  font-size: 0.82rem;
}

.rate-bar-bg {
  width: 60px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.rate-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease;
}

.rate-ok {
  background: #22c55e;
}

.rate-warn {
  background: #f59e0b;
}

.rate-over {
  background: #ef4444;
}

/* Totals row */
.totals-row {
  background: #f1f5f9;
}

.totals-row td {
  border-top: 2px solid #cbd5e1;
  border-bottom: none;
}

/* Empty row */
.empty-row {
  text-align: center !important;
  color: #94a3b8;
  padding: 2rem !important;
  font-style: italic;
}

@media (max-width: 760px) {
  .col-service,
  .col-compte {
    min-width: 100px;
  }

  .col-month {
    min-width: 72px;
  }
}
</style>
