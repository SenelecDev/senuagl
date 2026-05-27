const makeEmptyMonths = () => {
  const mois = {}
  for (let m = 1; m <= 12; m++) mois[m] = 0
  return mois
}

const SERVICE_ORDER = []
const MONTHLY_REPORT_COMPTES = [
  ['605.500', 'ACHATS DIRECT IMPRIM & FOURNI.'],
  ['605.510', 'ACHATS DIRECT FOURNI INFORMATI'],
  ['605.600', 'ACHATS DIRECT DE PETIT MAT.'],
  ['SECTION-AUTRES-ACHATS', 'AUTRES ACHATS'],
  ['618.100', 'VOYAGES & DEPLT MISSION HORS SE'],
  ['618.110', 'VOYAGES & DEPLT PERSONEL MISSION'],
  ['SECTION-TRANSPORTS', 'TRANSPORTS'],
  ['621.000', 'TRAVAUX ET SCES EXTERIEURS'],
  ['622.320', 'LOCATION DE MATERIEL INFORMATIQ (infogerance)'],
  ['624.310', 'MAINTENANCE REPARAT. MATERIEL I'],
  ['626.500', 'DOCUMENTATION GENERALE'],
  ['626.600', 'DOCUMENTATION TECHNIQUE'],
  ['628.800', 'FRAIS DE TELECOMMUNICATION'],
  ['632.440', "PRESTATIONS D'EXPERTISE"],
  ['633.000', 'FRAIS RECYCLAGES ET FORMATIONS'],
  ['634.300', 'REDEVANCES POUR LOGICIELS'],
  ['638.302', 'PAUSES CAFE ET RESTAURATION'],
  ['SECTION-SERVICES-EXTERIEURS', 'SERVICES EXTERIEURS'],
  ['661.101', 'Heures Supplémentaires'],
  ['SECTION-CHARGES-PERSONNEL', 'CHARGES DE PERSONNEL']
]

const isSectionCompte = (compte) => String(compte?.numero || '').startsWith('SECTION-')



export function buildCompteIndex(comptes) {
  const byId = new Map()
  const childrenByParentId = new Map()

  comptes.forEach((compte) => {
    const id = Number(compte.id)
    byId.set(id, compte)

    const parentId = compte.parent_id != null ? Number(compte.parent_id) : null
    if (parentId) {
      if (!childrenByParentId.has(parentId)) {
        childrenByParentId.set(parentId, [])
      }
      childrenByParentId.get(parentId).push(id)
    }
  })

  return { byId, childrenByParentId }
}

export function collectDescendantIds(compteId, childrenByParentId) {
  const rootId = Number(compteId)
  const ids = new Set([rootId])
  const queue = [rootId]

  while (queue.length) {
    const current = queue.shift()
    const children = childrenByParentId.get(current) || []

    children.forEach((childId) => {
      if (!ids.has(childId)) {
        ids.add(childId)
        queue.push(childId)
      }
    })
  }

  return ids
}

export function compteHasChildren(compteId, childrenByParentId) {
  return (childrenByParentId.get(Number(compteId)) || []).length > 0
}

export function getCompteDepth(compteId, byId) {
  let depth = 0
  let current = byId.get(Number(compteId))

  while (current?.parent_id) {
    depth += 1
    current = byId.get(Number(current.parent_id))
  }

  return depth
}

function rollupToAncestors(row, regroupements, byId, keyBuilder) {
  let compte = byId.get(Number(row.compte_id))
  if (!compte?.parent_id) {
    return
  }

  let parent = byId.get(Number(compte.parent_id))
  while (parent) {
    const key = keyBuilder(row, parent.id)
    const existing = regroupements.get(key) || {
      key,
      compte_id: parent.id,
      annee: row.annee,
      compte: parent,
      montant_prevu: 0,
      montant_realise: 0,
      mois: makeEmptyMonths(),
      is_regroupement: true,
      niveau: 0
    }

    existing.montant_prevu += Number(row.montant_prevu || 0)
    existing.montant_realise += Number(row.montant_realise || 0)

    if (row.mois) {
      for (let m = 1; m <= 12; m++) {
        existing.mois[m] += Number(row.mois[m] || 0)
      }
    }

    regroupements.set(key, existing)

    if (!parent.parent_id) {
      break
    }

    parent = byId.get(Number(parent.parent_id))
  }
}

function finalizeRow(row) {
  const montantPrevu = Number(row.montant_prevu || 0)
  const montantRealise = Number(row.montant_realise || 0)
  const ecart = montantPrevu - montantRealise

  return {
    ...row,
    ecart,
    taux_execution: montantPrevu
      ? Math.round((montantRealise / montantPrevu) * 1000) / 10
      : 0
  }
}

function finalizeMensuelRow(row) {
  const totalRealise = Object.values(row.mois || {}).reduce((sum, value) => sum + Number(value || 0), 0)
  const montantPrevu = Number(row.montant_prevu || 0)
  const ecart = montantPrevu - totalRealise

  return {
    ...row,
    totalRealise,
    ecart,
    taux_execution: montantPrevu
      ? Math.round((totalRealise / montantPrevu) * 1000) / 10
      : 0
  }
}

function sortBudgetRows(a, b) {

  const compteCompare = String(a.compte?.numero || '').localeCompare(String(b.compte?.numero || ''))
  if (compteCompare !== 0) return compteCompare

  return Number(a.niveau || 0) - Number(b.niveau || 0)
}

/**
 * Construit les lignes détail + lignes de regroupement (somme des comptes fils).
 */
export function buildSuiviRows(previsions, realisations, comptes) {
  const { byId } = buildCompteIndex(comptes)
  const rows = new Map()

  previsions.forEach((prevision) => {
    const key = `${prevision.compte_id}-${prevision.annee}`
    const compte = byId.get(Number(prevision.compte_id)) || prevision.compte

    rows.set(key, {
      key,
      compte_id: prevision.compte_id,
      annee: prevision.annee,
      compte,
      montant_prevu: Number(prevision.montant_prevu || 0),
      montant_realise: 0
    })
  })

  realisations.forEach((realisation) => {
    const key = `${realisation.compte_id}-${realisation.annee}`
    const compte = byId.get(Number(realisation.compte_id)) || realisation.compte
    const existing = rows.get(key) || {
      key,
      compte_id: realisation.compte_id,
      annee: realisation.annee,
      compte,
      montant_prevu: 0,
      montant_realise: 0
    }

    existing.montant_realise += Number(realisation.montant_realise || 0)
    rows.set(key, existing)
  })

  const detailRows = [...rows.values()].map((row) => finalizeRow({
    ...row,
    niveau: getCompteDepth(row.compte_id, byId),
    is_regroupement: false
  }))

  const regroupements = new Map()
  detailRows.forEach((row) => {
    rollupToAncestors(row, regroupements, byId, (detailRow, parentId) =>
      `regroupement-${parentId}-${detailRow.annee}`
    )
  })

  const regroupementRows = [...regroupements.values()].map(finalizeRow)

  return [...regroupementRows, ...detailRows].sort(sortBudgetRows)
}

/**
 * Vue mensuelle : prévisions annuelles + réalisations ventilées par mois.
 */
export function buildMensuelRows(previsions, realisations, comptes) {
  const { byId } = buildCompteIndex(comptes)
  const rows = new Map()

  previsions.forEach((prevision) => {
    const key = `${prevision.compte_id}`
    const compte = byId.get(Number(prevision.compte_id)) || prevision.compte

    if (!rows.has(key)) {
      rows.set(key, {
        key,
        compte_id: prevision.compte_id,
        compte,
        mois: makeEmptyMonths(),
        montant_prevu: 0,
        montant_realise: 0
      })
    }

    rows.get(key).montant_prevu += Number(prevision.montant_prevu || 0)
  })

  realisations.forEach((realisation) => {
    const key = `${realisation.compte_id}`
    const compte = byId.get(Number(realisation.compte_id)) || realisation.compte

    if (!rows.has(key)) {
      rows.set(key, {
        key,
        compte_id: realisation.compte_id,
        compte,
        mois: makeEmptyMonths(),
        montant_prevu: 0,
        montant_realise: 0
      })
    }

    const row = rows.get(key)
    const mois = Number(realisation.mois)

    if (mois >= 1 && mois <= 12) {
      row.mois[mois] += Number(realisation.montant_realise || 0)
    }
  })

  const detailRows = [...rows.values()].map((row) => {
    const montantRealise = Object.values(row.mois).reduce((sum, value) => sum + Number(value || 0), 0)

    return finalizeMensuelRow({
      ...row,
      montant_realise: montantRealise,
      niveau: getCompteDepth(row.compte_id, byId),
      is_regroupement: false
    })
  })

  const regroupements = new Map()
  detailRows.forEach((row) => {
    rollupToAncestors(row, regroupements, byId, (detailRow, parentId) =>
      `regroupement-${parentId}`
    )
  })

  const regroupementRows = [...regroupements.values()].map(finalizeMensuelRow)

  return [...regroupementRows, ...detailRows].sort(sortBudgetRows)
}

export function buildMensuelPivotRows(previsions, realisations, comptes) {
  const { byId, childrenByParentId } = buildCompteIndex(comptes)
  const compteByNumero = new Map(comptes.map((compte) => [String(compte.numero || ''), compte]))

  const eligibleComptes = MONTHLY_REPORT_COMPTES.map(([numero, intitule]) => {
    const compte = compteByNumero.get(numero)

    return compte
      ? { ...compte, is_section: isSectionCompte(compte) }
      : {
      id: `monthly-report-${numero}`,
      numero,
      intitule,
      parent_id: null,
      enfants_count: 0,
      is_section: String(numero).startsWith('SECTION-')
    }
  })

  const rows = eligibleComptes.map((compte) => {
    return {
      key: `mensuel-pivot-${compte.id}`,
      compte_id: compte.id,
      compte,
      previsions: makeEmptyMonths(),
      mois: makeEmptyMonths(),
      is_regroupement: compteHasChildren(compte.id, childrenByParentId) || compte.is_section,
      is_section: compte.is_section,
      niveau: getCompteDepth(compte.id, byId)
    }
  })

  const rowsByCompteId = new Map(rows.map((row) => [String(row.compte_id), row]))

  const getTargetRows = (compteId) => {
    const targets = []
    let compte = byId.get(Number(compteId))

    if (!compte) return targets

    const ownRow = rowsByCompteId.get(String(compte.id))
    if (ownRow) {
      targets.push(ownRow)
    }

    while (compte?.parent_id) {
      const parentRow = rowsByCompteId.get(String(compte.parent_id))
      if (parentRow) {
        targets.push(parentRow)
      }
      compte = byId.get(Number(compte.parent_id))
    }

    return targets
  }

  previsions.forEach((prevision) => {
    const targetRows = getTargetRows(prevision.compte_id)

    targetRows.forEach((row) => {
      const mois = Number(prevision.mois || 1)
      if (mois >= 1 && mois <= 12) {
        row.previsions[mois] += Number(prevision.montant_prevu || 0)
      }
    })
  })

  realisations.forEach((realisation) => {
    const mois = Number(realisation.mois)
    const targetRows = getTargetRows(realisation.compte_id)

    if (mois < 1 || mois > 12) return

    targetRows.forEach((row) => {
      row.mois[mois] += Number(realisation.montant_realise || 0)
    })
  })

  return rows
}

export function matchesCompteFilter(row, compteId, comptes) {
  if (compteId == null) {
    return true
  }

  const filterId = Number(compteId)
  const rowCompteId = Number(row.compte_id)

  if (rowCompteId === filterId) {
    return true
  }

  const { childrenByParentId } = buildCompteIndex(comptes)

  if (!compteHasChildren(filterId, childrenByParentId)) {
    return false
  }

  const allowedIds = collectDescendantIds(filterId, childrenByParentId)

  return allowedIds.has(rowCompteId)
}

/**
 * Lignes utilisées pour les KPI : évite de compter deux fois regroupement + détail.
 */
export function rowsForKpi(rows, compteId, comptes) {
  if (compteId == null) {
    return rows.filter((row) => !row.is_regroupement)
  }

  const filterId = Number(compteId)
  const { childrenByParentId } = buildCompteIndex(comptes)

  if (compteHasChildren(filterId, childrenByParentId)) {
    const regroupement = rows.filter(
      (row) => row.is_regroupement && Number(row.compte_id) === filterId
    )

    if (regroupement.length) {
      return regroupement
    }

    return rows.filter((row) => !row.is_regroupement && matchesCompteFilter(row, compteId, comptes))
  }

  return rows.filter((row) => !row.is_regroupement && Number(row.compte_id) === filterId)
}
