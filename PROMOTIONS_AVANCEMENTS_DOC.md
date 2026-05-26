# Système de Promotions GF et Avancements NR - Documentation

## Vue d'ensemble

Ce système gère les promotions (changements de GF) et les avancements (changements de NR) des agents basés sur des notes d'appréciation annuelles.

### Règles métier implémentées

1. **Notes d'appréciation** : Chaque agent reçoit une note de 0 à 100 chaque année
   - Promotion GF : note > 75
   - Avancement NR : note > 50

2. **Délais de carence**
   - GF : 3 ans minimum entre deux promotions
   - NR : 2 ans minimum entre deux avancements

3. **Tri de priorité** pour la sélection
   - Note décroissante (la plus élevée en premier)
   - Ancienneté croissante (les plus anciens en premier si notes égales)
   - Date de dernière promotion/avancement croissante (les plus anciens en premier)

4. **Quotas** (sélection manuelle)
   - Promotion GF : ~15% de l'effectif
   - Avancement NR : ~35% de l'effectif

## Structure des tables

### `notes_appreciation`
```sql
- id (PK)
- matricule_agent (FK → agents.matricule)
- annee (année)
- note (0-100)
- commentaire (nullable)
- created_at, updated_at
```

Contrainte unique : (matricule_agent, annee)

### `avancements` (existant, enrichi)
```sql
- id (PK)
- date
- motif
- matricule_agent (FK)
- id_gf_ancien
- id_gf_nouveau
- id_nr_ancien
- id_nr_nouveau
```

## API Endpoints

### Notes d'appréciation
```
GET    /api/notes-appreciation              - Liste avec filtres
GET    /api/notes-appreciation/{id}         - Détail
POST   /api/notes-appreciation              - Créer
PUT    /api/notes-appreciation/{id}         - Modifier
DELETE /api/notes-appreciation/{id}         - Supprimer
GET    /api/notes-appreciation/agent/{matricule} - Par agent
GET    /api/notes-appreciation/annee/{annee}    - Par année
```

### Promotions/Avancements
```
GET  /api/promotions/liste-priorite-gf/{directionId}/{annee}    - Liste priorité GF
GET  /api/avancements/liste-priorite-nr/{directionId}/{annee}   - Liste priorité NR
POST /api/promotions/promouvoir                                  - Exécuter promotion
POST /api/avancements/avancer                                    - Exécuter avancement
```

## Pages Vue 3

### `/notes-appreciation`
- CRUD complet des notes d'appréciation
- Filtrage par agent et année
- Code couleur pour les notes

### `/promotions`
- Liste de priorité GF pour une direction et année
- Sélecteur direction et année
- Bouton "Promouvoir" pour chaque agent
- Vérification des règles avant application

### `/avancements-liste-priorite`
- Liste de priorité NR pour une direction et année
- Sélecteur direction et année
- Bouton "Avancer" pour chaque agent

### `/avancements` (existant)
- Historique des avancements (promotions GF + changements NR)

## Seeders

### `NoteAppreciationSeeder`
Génère des notes d'appréciation pour tous les agents sur 4 années (2023-2026).
- Distribution aléatoire entre 45 et 95
- Commentaires basés sur la valeur de la note

### `PromotionAndAvancementSeeder`
Génère des avancements basés sur les notes d'appréciation.
- Crée des promotions GF (note > 75)
- Crée des avancements NR (note > 50)
- Respecte les délais de carence
- Met à jour les agents (id_gf_actuel, id_nr_actuel)

## Workflow d'utilisation

### 1. Récupérer la liste de priorité
```javascript
// Promotions GF
const response = await axios.get(`/api/promotions/liste-priorite-gf/1/2026`)
// Retourne: direction, annee, agents[], total

// Avancements NR
const response = await axios.get(`/api/avancements/liste-priorite-nr/1/2026`)
```

### 2. Exécuter une promotion/avancement
```javascript
// Promotion GF
await axios.post('/api/promotions/promouvoir', {
  matricule_agent: 'MAT001',
  id_gf_nouveau: 'GF05',
  date: '2026-06-15'
})

// Avancement NR
await axios.post('/api/avancements/avancer', {
  matricule_agent: 'MAT001',
  id_nr_nouveau: 'NR03',
  date: '2026-09-15'
})
```

### 3. Gérer les notes
```javascript
// Créer une note
await axios.post('/api/notes-appreciation', {
  matricule_agent: 'MAT001',
  annee: 2026,
  note: 85,
  commentaire: 'Excellent travail'
})

// Modifier
await axios.put('/api/notes-appreciation/1', {
  note: 88,
  commentaire: 'Excellent travail, très motivé'
})
```

## Installation & Migration

```bash
# 1. Exécuter les migrations
php artisan migrate

# 2. Exécuter les seeders
php artisan db:seed

# 3. Démarrer le frontend
npm run dev
```

## Modèles & Relations

### Agent
- `notes()` : hasMany NoteAppreciation
- `dernierePromotionGF()` : dernière promotion GF
- `dernierAvancementNR()` : dernier avancement NR
- `estPlafonn` : accesseur (bool) - agent plafonné pour GF

### NoteAppreciation
- `agent()` : belongsTo Agent

### Avancement
- `agent()` : belongsTo Agent
- `gfAncien(), gfNouveau()` : belongsTo GF
- `nrAncien(), nrNouveau()` : belongsTo NR

## Service `PromotionEligibilityService`

Calcule les listes de priorité respecting les règles métier.

```php
// Promotion GF
$liste = $service->listePrioriteGF($directionId, 2026);

// Avancement NR
$liste = $service->listePrioriteNR($directionId, 2026);
```

Chaque entrée retourne :
- agent (modèle complet)
- note (score appréciation)
- date_embauche
- derniere_promotion / dernier_avancement
- anciennete_ans

## Menu Sidebar

3 nouveaux éléments ajoutés :
- "Notes d'appréciation" → `/notes-appreciation`
- "Promotions GF" → `/promotions`
- "Avancements NR" → `/avancements-liste-priorite`
- "Historique avancements" → `/avancements` (renommé)
