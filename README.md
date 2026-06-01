# SENUAGL - Application de gestion des conges

Application web de gestion des conges pour SENELEC. Le projet est compose d'un
frontend Vue 3/Vite et d'un backend Laravel expose derriere Nginx. Cette
documentation sert de guide de reprise pour une nouvelle personne qui arrive sur
le projet.

## Sommaire

- [Vue d'ensemble](#vue-densemble)
- [Stack technique](#stack-technique)
- [Structure du projet](#structure-du-projet)
- [Prerequis](#prerequis)
- [Demarrage rapide avec Docker](#demarrage-rapide-avec-docker)
- [Demarrage en local sans Docker](#demarrage-en-local-sans-docker)
- [Configuration importante](#configuration-importante)
- [Comptes de test](#comptes-de-test)
- [Commandes utiles](#commandes-utiles)
- [Tests et verification](#tests-et-verification)
- [Depannage](#depannage)
- [Points d'attention pour la reprise](#points-dattention-pour-la-reprise)

## Vue d'ensemble

L'application permet de gerer le cycle de vie des demandes de conges :

- connexion et redirection selon le role utilisateur ;
- tableau de bord pour Employe, Superieur, Directeur Unite, Responsable RH,
  Directeur RH et Admin ;
- creation et suivi des demandes de conges, reports et absences ;
- validation des demandes selon le role ;
- gestion admin des utilisateurs, departements, planning, historique et logs ;
- notifications en temps reel via Soketi/Pusher.

## Stack technique

Frontend :

- Vue 3
- Vite
- Vuetify
- Pinia
- Vue Router
- PrimeVue
- Axios

Backend :

- Laravel
- Sanctum pour l'authentification API
- PostgreSQL en environnement Docker
- PHPUnit pour les tests
- Soketi pour les notifications temps reel

Infrastructure :

- Docker Compose
- Nginx comme reverse proxy
- pgAdmin pour inspecter la base

## Structure du projet

```text
senuagl/
|-- Client/                         # Frontend Vue 3
|   |-- src/
|   |   |-- assets/                 # Images et styles globaux
|   |   |-- components/             # Composants reutilisables
|   |   |-- components/admin/       # Layout et composants admin
|   |   |-- components/dashboard/   # Layout et composants RH/employe
|   |   |-- router/                 # Routes frontend
|   |   |-- services/               # Client API Axios
|   |   |-- stores/                 # Stores Pinia
|   |   `-- views/                  # Pages principales
|   |-- package.json
|   `-- vite.config.js
|-- Server/                         # Backend Laravel
|   |-- app/
|   |-- database/
|   |   |-- migrations/
|   |   `-- seeders/
|   |-- routes/api.php              # Routes API
|   |-- tests/
|   `-- composer.json
|-- docker/nginx/default.conf       # Reverse proxy Nginx
|-- docker-compose.yml              # Services Docker
|-- Makefile                        # Raccourcis commandes Docker
`-- ci-cd.yml                       # Pipeline CI/CD
```

## Prerequis

Pour Docker :

- Docker Desktop
- Docker Compose

Pour un lancement local :

- Node.js 18+ recommande
- npm
- PHP 8.1+
- Composer
- PostgreSQL ou MySQL selon la configuration choisie

Sur Windows, preferer `npm.cmd` dans PowerShell si `npm` est bloque par la
politique d'execution.

## Demarrage rapide avec Docker

Depuis la racine du projet :

```bash
docker compose up -d --build
```

Puis initialiser Laravel dans le conteneur :

```bash
docker compose exec laravel composer install
docker compose exec laravel cp .env.example .env
docker compose exec laravel php artisan key:generate
docker compose exec laravel php artisan migrate --seed
```

Construire le frontend si necessaire :

```bash
docker compose exec vue.js npm install
docker compose exec vue.js npm run build
```

Acces :

- Application : `http://127.0.0.1:8081`
- API : `http://127.0.0.1:8081/api`
- pgAdmin : `http://127.0.0.1:5050`
- Soketi : `http://127.0.0.1:6001`

Identifiants pgAdmin par defaut :

- email : `admin@admin.com`
- mot de passe : `admin`

## Demarrage en local sans Docker

### Backend Laravel

```bash
cd Server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Par defaut, `php artisan serve` expose le backend sur `http://127.0.0.1:8000`.
Si le frontend local utilise le proxy Vite actuel, il cible plutot
`http://127.0.0.1:8081`. Dans ce cas, utiliser Docker/Nginx ou adapter
`Client/vite.config.js`.

### Frontend Vue

```bash
cd Client
npm install
npm run dev
```

Sur Windows PowerShell, si `npm` est bloque :

```bash
npm.cmd install
npm.cmd run dev
```

Frontend local :

- `http://127.0.0.1:5173`

Le proxy API est configure dans `Client/vite.config.js` :

```text
/api -> http://127.0.0.1:8081
```

## Configuration importante

### Backend

Le fichier de reference est `Server/.env.example`.

En Docker, la base PostgreSQL definie dans `docker-compose.yml` utilise :

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bd-app-conges
DB_USERNAME=postgres
DB_PASSWORD=admin123
```

Le fichier `Server/.env.example` est aligne avec Docker. Si vous lancez le
backend sans Docker avec une autre base, adaptez seulement les variables `DB_*`.

### Notifications temps reel

Soketi est compatible avec le protocole Pusher. Les valeurs utilisees dans
Docker sont :

```env
PUSHER_APP_ID=senuagl
PUSHER_APP_KEY=senuagl-key
PUSHER_APP_SECRET=senuagl-secret
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

Pour le frontend :

```env
VITE_PUSHER_APP_KEY=senuagl-key
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
```

## Comptes de test

Les comptes sont crees par `Server/database/seeders/UserSeeder.php` avec
`php artisan migrate --seed` ou `php artisan db:seed`.

| Role | Email | Mot de passe |
| --- | --- | --- |
| Admin | `insasarr1@gmail.com` | `admin` |
| Directeur RH | `iboug670@gmail.com` | `directeur` |
| Responsable RH | `responsable.rh@mail.com` | `responsable` |
| Directeur Unite | `directeur.unite@mail.com` | `directeur` |
| Superieur | `alimalaye54@gmail.com` | `superieur` |
| Employe | `kaousmane3599@gmail.com` | `employe` |

## Commandes utiles

Avec Docker :

```bash
docker compose up -d --build
docker compose down
docker compose logs -f
docker compose ps
```

Backend :

```bash
docker compose exec laravel php artisan migrate
docker compose exec laravel php artisan migrate:fresh --seed
docker compose exec laravel php artisan test
docker compose exec laravel php artisan cache:clear
docker compose exec laravel php artisan config:clear
docker compose exec laravel php artisan route:clear
```

Frontend :

```bash
cd Client
npm.cmd run build
npm.cmd run dev
```

Base de donnees :

```bash
docker compose exec postgres psql -U postgres -d bd-app-conges
```

## Tests et verification

Backend :

```bash
cd Server
php artisan test
```

ou avec Docker :

```bash
docker compose exec laravel php artisan test
```

Frontend :

```bash
cd Client
npm.cmd run build
```

Note : le projet n'a pas de script `npm test` declare dans
`Client/package.json` au moment de cette documentation. Le build Vite sert de
verification minimale cote frontend.

## Depannage

### PowerShell bloque `npm.ps1`

Erreur possible :

```text
Impossible de charger le fichier C:\Program Files\nodejs\npm.ps1,
car l'execution de scripts est desactivee sur ce systeme.
```

Solution rapide :

```bash
npm.cmd run build
npm.cmd run dev
```

### Le frontend local n'appelle pas la bonne API

Verifier `Client/vite.config.js`. Le proxy actuel envoie les appels `/api` vers :

```text
http://127.0.0.1:8081
```

Si Laravel tourne avec `php artisan serve` sur le port `8000`, modifier
temporairement le proxy vers :

```text
http://127.0.0.1:8000
```

### Erreur de base de donnees au demarrage

Verifier que le `.env` Laravel utilise la meme base que Docker :

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bd-app-conges
DB_USERNAME=postgres
DB_PASSWORD=admin123
```

Puis relancer :

```bash
docker compose exec laravel php artisan config:clear
docker compose exec laravel php artisan migrate --seed
```

### Les routes frontend affichent une page blanche apres refresh

Nginx doit renvoyer les routes Vue vers `index.html`. La configuration est dans
`docker/nginx/default.conf` :

```text
try_files $uri $uri/ /index.html;
```

### Les notifications ne remontent pas

Verifier que le service Soketi est lance :

```bash
docker compose ps
docker compose logs -f soketi
```

Verifier aussi les variables `PUSHER_*` cote Laravel et `VITE_PUSHER_*` cote
frontend.

## Points d'attention pour la reprise

- Le projet contient plusieurs dashboards selon les roles. Les layouts RH se
  trouvent dans `Client/src/views/*Dashboard.vue` et les composants communs dans
  `Client/src/components/dashboard/`.
- Le dashboard Admin a son layout separe :
  `Client/src/views/AdminDashboard.vue`,
  `Client/src/components/admin/AdminSidebar.vue` et
  `Client/src/components/admin/AdminToolbar.vue`.
- Pour eviter un espace entre le sidebar Admin et le header, garder la meme
  largeur partout : sidebar `300px`, toolbar `margin-left: 300px`, contenu
  `margin-left: 300px`.
- Les routes API principales sont dans `Server/routes/api.php`.
- La logique de validation des demandes est principalement dans
  `Server/app/Http/Controllers/Api/DemandeCongeController.php`.
- Les statistiques dashboard sont dans
  `Server/app/Http/Controllers/Api/DashboardController.php`.
- Les stores Pinia importants sont dans `Client/src/stores/`.
- Avant de modifier une fonctionnalite, tester avec au moins deux roles :
  l'utilisateur concerne et un role RH/Admin.

## Etat connu

- Le build frontend passe avec `npm.cmd run build`.
- Vite peut afficher un warning sur `notifications.js` importe a la fois
  statiquement et dynamiquement. Ce warning n'empeche pas le build.
- Des fichiers peuvent etre deja modifies dans le working tree. Verifier
  `git status --short` avant de commencer une nouvelle tache.

## Support

Pour reprendre le projet rapidement :

1. lancer Docker ;
2. verifier le `.env` Laravel ;
3. lancer les migrations et seeders ;
4. tester la connexion avec les comptes de test ;
5. lancer `npm.cmd run build` cote frontend ;
6. lancer `php artisan test` cote backend.

Documenter ici tout nouveau probleme rencontre, avec l'erreur exacte et la
solution appliquee.
