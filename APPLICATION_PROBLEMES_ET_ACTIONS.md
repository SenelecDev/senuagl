# Rapport des problèmes détectés

## 1. Constat général
Le projet contient beaucoup d’écrans qui affichent des données statiques ou fictives plutôt que des données réelles du backend.
Cela explique pourquoi vous voyez :
- des demandes de congé qui n’existent pas,
- un historique qui n’affiche pas votre demande,
- des soldes de congé fictifs,
- des comptes de département à zéro,
- des tableaux « en attente » remplis de données prototypes.

## 2. Problèmes principaux identifiés

### 2.1 Données fictives affichées dans l’application
- `Client/src/components/dashboard/EtatDemandes.vue` contient un tableau local `demandes` statique.
- `Client/src/components/dashboard/HistoriqueConges.vue` contient un tableau local `conges` statique.
- `Client/src/components/dashboard/SoldeConges.vue` contient des soldes de congés codés en dur.
- `Client/src/views/dashboard/DocumentsAdministratifsView.vue` affiche aussi des demandes codées en dur.

### 2.2 Données non synchronisées avec le backend
- Les composants affichent souvent des objets en local (`data()`) et ne récupèrent pas les bonnes données depuis l’API.
- Le store `Client/src/stores/conges.js` contient des états statiques et ne semble pas être utilisé pour charger les vraies données de l’utilisateur.
- Les pages de validation/demandes utilisent également des comptes statiques (« 3 demandes », « 5 demandes ») dans `ValidationDemandesView.vue`.

### 2.3 Rôles et permissions confondus
- Les rôles `Directeur RH`, `Responsable RH`, `Directeur d’Unité`, `Supérieur` semblent tous afficher les mêmes fonctionnalités.
- Il n’y a pas de différence claire entre les interfaces de validation : l’UI semble réutiliser les mêmes vues pour chaque rôle.
- Cela peut indiquer un manque de système de rôle/permission réel ou un routage trop simpliste.

### 2.4 Fonctionnalités admin incomplètes
- La gestion des utilisateurs, des départements, des logs d’activité, des paramètres de sécurité (2FA) et des notifications e-mail sont mentionnés mais pas complètement implémentés.
- Le tableau des départements affiche des directeurs non assignés et 0 employé(s) ; cela peut venir d’une absence de chargement des utilisateurs ou d’un mauvais champ de liaison entre user/department.
- `Client/src/stores/departments.js` utilise `departmentsApi.list()` mais bascule sur des valeurs par défaut si l’API échoue.

### 2.5 État des demandes incorrect
- Votre demande n’apparaît pas dans l’historique, car l’écran utilise des données prototypes au lieu de recharger l’historique depuis l’API réelle.
- La page d’état des demandes (`EtatDemandes`) annule aussi les demandes uniquement en local sans appeler l’API.

### 2.6 Absence de logique temps réel ou de rafraîchissement
- Aucun composant ne semble utiliser de rafraîchissement automatique, de websocket ou de polling pour mettre à jour les états en temps réel.
- Les comptes de notifications affichent une valeur fixe (`3`) dans plusieurs dashboards.

## 3. Analyse technique

### 3.1 Backend existant
Le backend Laravel expose des routes API utiles :
- `POST /login`
- `POST /logout`, `GET /user`, `POST /refresh`
- `GET /dashboard/stats`, `GET /dashboard/recent-activity`, `GET /dashboard/stats-manager`
- `apiResource('demandes-conges')`
- `POST /demandes-conges/{id}/validate`
- `GET /demandes-a-valider`
- `apiResource('users')`
- `apiResource('roles')`
- `apiResource('departments')`
- notifications (`/notifications`, `/notifications/unread`, etc.)

### 3.2 Problème de connexion frontend/backend
- `Client/src/services/api.js` est bien configuré avec un client Axios et des endpoints.
- Mais de nombreuses pages utilisent encore des tableaux locaux au lieu de consommer `useDemandesStore.fetchDemandes()` ou d’autres appels API.

## 4. Fichiers suspects à corriger en priorité

- `Client/src/components/dashboard/EtatDemandes.vue`
- `Client/src/components/dashboard/HistoriqueConges.vue`
- `Client/src/components/dashboard/SoldeConges.vue`
- `Client/src/views/dashboard/ValidationDemandesView.vue`
- `Client/src/views/dashboard/GestionDemandesView.vue`
- `Client/src/views/dashboard/DocumentsAdministratifsView.vue`
- `Client/src/stores/conges.js`
- `Client/src/stores/demandes.js`
- `Client/src/stores/departments.js`
- `Client/src/stores/usersAdmin.js`
- `Client/src/views/admin/RoleManagementView.vue`
- `Client/src/components/LoginForm.vue` (vérifier routage rôle)

## 5. Recommandations pour avancer

### 5.1 Première étape : audit du flux des données
- Vérifier quelles pages représentent des prototypes et lesquelles utilisent de vraies données API.
- Identifier chaque page où `data()` contient des objets codés en dur.
- Vérifier que le store approprié est bien appelé au chargement de la page.

### 5.2 Deuxième étape : rediriger vers l’API
- Remplacer les données codées en dur par des appels à `useDemandesStore.fetchDemandes()` ou `demandesApi.list()`.
- Afficher ensuite les réponses réelles et vérifier le format JSON attendu.
- Harmoniser les noms de champs entre backend et front-end (`department_id`, `departementId`, `type_demande`, etc.).

### 5.3 Troisième étape : corriger la logique de rôle
- Implémenter des gardes de route (`route guard`) basés sur le rôle authentifié.
- Permettre à chaque rôle d’accéder uniquement à ses vues réelles.
- Supprimer les vues en double si elles affichent la même chose.

### 5.4 Quatrième étape : consolider l’admin
- Vérifier que la liste des utilisateurs et des départements est chargée avant calcul des comptes.
- Corriger la liaison entre utilisateurs et départements.
- Ajouter un tableau réel d’activité / logs si le backend en fournit.
- Mettre en place des notifications e-mail ou des notifs internes si c’est une exigence.

### 5.5 Cinquième étape : rendre l’application moderne et temps réel
- Ajouter un système de notifications en temps réel (polling, WebSocket, Laravel Echo, Pusher, etc.).
- Faire apparaître les nouvelles demandes immédiatement après création.
- Afficher des badges de notification dynamiques.
- Proposer un vrai tableau de bord moderne : nombre de demandes, soldes à jour, délais d’approbation.

## 6. Ce que je te conseille maintenant

1. Commencer par lister les pages qui affichent des données statiques (tu as déjà identifié `EtatDemandes`, `Historique`, `Solde`).
2. Pour chaque page, remplacer la source de données par un `fetch` depuis l’API.
3. Vérifier ensuite que la requête répond bien et corriger les clés des objets.
4. Tester avec un seul rôle (par exemple Employé) puis passer aux rôles RH / Admin.
5. Ensuite, traiter l’admin et le tableau des départements.

## 7. Si tu veux, je peux aussi

- Te proposer un plan de migration étape par étape.
- T’aider à corriger un composant précis (`EtatDemandes.vue`, `HistoriqueConges.vue`, etc.).
- Vérifier la correspondance entre le frontend et le backend Laravel.
- Refaire le routage/permissions pour les rôles.

---

> Fichier créé : `APPLICATION_PROBLEMES_ET_ACTIONS.md`
