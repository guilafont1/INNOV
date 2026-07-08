# MERJ Learn

Plateforme e-learning pour établissements : cours, classes, notes, planning, forum, messagerie et évaluations.

**Stack :** Symfony 7.2 · Doctrine ORM 3 · Twig · Bootstrap 5 · MySQL 8 · FullCalendar 6 · dompdf

---

## Fonctionnalités

| Module | Description |
| ------ | ----------- |
| **Cours** | Modules, chapitres, progression par étudiant |
| **Classes** | Affectation élèves / professeurs / cours |
| **Notes** | Saisie enseignant, consultation étudiant (moyenne, détail par module) |
| **Planning** | Calendrier interactif ; gestion par l'administration école, consultation enseignants/étudiants |
| **Forum** | Discussions par cours (publication étudiants, lecture enseignants, modération admin école) |
| **Messages** | Messagerie instantanée (style chat) entre contacts de classe |
| **Évaluations** | Quiz par cours |
| **Administration** | Dashboard, gestion utilisateurs et classes, planning global |

---

## Rôles et permissions

Quatre rôles distincts, avec hiérarchie Symfony :

```
ROLE_SUPER_ADMIN  →  ROLE_ADMIN_ECOLE  →  ROLE_USER
ROLE_ENSEIGNANT   →  ROLE_USER
ROLE_ETUDIANT     →  ROLE_USER
```

| Rôle | Constante Symfony | Public cible |
| ---- | ----------------- | ------------ |
| Étudiant | `ROLE_ETUDIANT` | Apprenants |
| Enseignant | `ROLE_ENSEIGNANT` | Professeurs |
| Administration école | `ROLE_ADMIN_ECOLE` | Direction, scolarité, vie scolaire |
| Super administrateur | `ROLE_SUPER_ADMIN` | Équipe technique / développeurs |

### Détail des droits

#### Étudiant
- Consulter ses **cours**, chapitres et **progression**
- Voir **ses notes** et **son planning**
- **Publier** sur le forum et participer aux **évaluations**
- Envoyer des **messages** aux contacts de sa classe

#### Enseignant
- Créer et gérer **ses cours** (modules, chapitres)
- Gérer **ses classes** : saisie des **notes** uniquement
- **Consulter** son planning (cours, examens, réunions) — lecture seule
- **Consulter** le forum de ses cours — lecture seule
- Messagerie avec les élèves et collègues de ses classes

> L'enseignant ne gère **pas** le planning, les affectations de classe (élèves / profs / cours) ni la modération du forum. Ces actions relèvent de l'administration école.

#### Administration école
- **Planning global** : voir et gérer tous les événements de l'établissement
- **Classes** : créer, modifier, assigner élèves / professeurs / cours
- **Utilisateurs** : créer des comptes étudiants, enseignants et autres admins école
- **Dashboard** admin avec statistiques
- Export planning **PDF** et **iCal**
- Modération du forum (suppression de messages)
- Ne peut **pas** créer ni modifier un compte super administrateur

#### Super administrateur
- Tous les droits de l'administration école
- Attribuer le rôle `ROLE_SUPER_ADMIN`
- Modifier / supprimer les comptes super admin
- Accès technique complet à la plateforme

---

## Comptes de démonstration

**Mot de passe commun :** `Demo2026!`

**Connexion :** [http://localhost:8000/login](http://localhost:8000/login)

### Administrateurs

| Nom | Email | Rôle |
| --- | ----- | ---- |
| Super Administrateur | `admin@merj.fr` | Super admin (développeur) |
| Sophie Rousseau | `admin.ecole@merj.fr` | Administration école |
| Pierre Lambert | `scolarite@merj.fr` | Administration école (scolarité) |
| Amélie Gérard | `vie.scolaire@merj.fr` | Administration école (vie scolaire) |

### Enseignants

| Nom | Email | Classe(s) principale(s) |
| --- | ----- | ----------------------- |
| Marie Dupont | `prof1@merj.fr` | M1 Dev Web |
| Karim Benali | `prof2@merj.fr` | M2 EISI |
| Julie Martin | `prof3@merj.fr` | B3 DevOps |

### Étudiants

| Nom | Email | Classe |
| --- | ----- | ------ |
| Alice Bernard | `etudiant1@merj.fr` | M1 Dev Web |
| Hugo Lefèvre | `etudiant2@merj.fr` | M1 Dev Web |
| Maya Nguyen | `etudiant3@merj.fr` | M1 Dev Web |
| Nathan Moreau | `etudiant4@merj.fr` | M1 Dev Web |
| Sarah Petit | `etudiant5@merj.fr` | M2 EISI |
| Yanis Said | `etudiant6@merj.fr` | M2 EISI |
| Emma Robin | `etudiant7@merj.fr` | M2 EISI |
| Louis Giraud | `etudiant8@merj.fr` | M2 EISI |
| Chloé Fournier | `etudiant9@merj.fr` | B3 DevOps |
| Samir Kacem | `etudiant10@merj.fr` | B3 DevOps |
| Zoé Chevalier | `etudiant11@merj.fr` | B3 DevOps |
| Thomas Marchand | `etudiant12@merj.fr` | B3 DevOps |
| Léa Fontaine | `etudiant13@merj.fr` | L3 Alternance Web |
| Noah Perrin | `etudiant14@merj.fr` | L3 Alternance Web |
| Inès Blanc | `etudiant15@merj.fr` | L3 Alternance Web |
| Lucas Renard | `etudiant16@merj.fr` | L3 Alternance Web |

---

## Installation

### Prérequis

- PHP 8.2+
- Composer
- MySQL 8 (Aiven recommandé, ou Docker en secours)

### 1. Dépendances

```bash
composer install
```

### 2. Configuration base de données

Créer un fichier `.env.local` (non versionné) avec votre `DATABASE_URL`.

**Aiven (recommandé)** — certificat SSL dans `config/certs/ca.pem` :

```dotenv
DATABASE_URL="mysql://USER:PASSWORD@HOST:PORT/defaultdb?serverVersion=8.0&charset=utf8mb4"
```

**MySQL local (Docker)** :

```bash
docker compose up -d
```

```dotenv
DATABASE_URL="mysql://root:root@127.0.0.1:3306/app?serverVersion=8.0&charset=utf8mb4"
```

> Ne jamais committer de secrets (`.env.local`, mots de passe, clés API). Vérifier que `.env.local` est dans `.gitignore`.

### 3. Schéma et données de démo

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
```

Les fixtures chargent les comptes ci-dessus, 3 classes, 6 cours et des événements de planning sur la **semaine du 7 juillet 2026**.

### 4. Lancer l'application

```bash
symfony serve
# ou
php -S localhost:8000 -t public
```

Ouvrir : [http://localhost:8000/login](http://localhost:8000/login)

---

## Données de démo

| Élément | Quantité |
| ------- | -------- |
| Utilisateurs | 23 (1 super admin, 3 admin école, 3 enseignants, 16 étudiants) |
| Classes | 4 (M1 Dev Web, M2 EISI, B3 DevOps, L3 Alternance Web) |
| Cours | 6 |
| Modules | 13 |
| Chapitres | 34 |
| Notes | 60 |
| Événements planning | 27 |
| Progressions | 24 |
| Posts forum | 36 |
| Messages | 21 |
| Évaluations | 8 |

---

## Parcours de démonstration

### Super admin — `admin@merj.fr`

1. Connexion → `/admin/dashboard`
2. **Utilisateurs** : créer un compte, changer les rôles (y compris super admin)
3. **Classes** : créer une classe, y ajouter élèves, profs et cours
4. **Planning** (`/admin/planning`) : vue globale, filtres par classe / cours / enseignant, export PDF

### Administration école — `admin.ecole@merj.fr`, `scolarite@merj.fr` ou `vie.scolaire@merj.fr`

1. Connexion → `/admin/dashboard`
2. Gérer les **classes** et les **utilisateurs** (sans toucher aux super admins)
3. **Planning global** : créer ou modifier un examen pour n'importe quelle classe
4. Exporter le planning en PDF ou iCal

### Enseignant — `prof1@merj.fr`

1. Dashboard → `/enseignant/dashboard`
2. **Mes cours** → créer / modifier modules et chapitres
3. **Classes** → saisir les **notes** d'une classe
4. **Planning** → consulter son emploi du temps (lecture seule)
5. **Forum** → lire les discussions (sans publier)
6. **Messages** → conversation avec un élève de sa classe

### Étudiant — `etudiant1@merj.fr`

1. Dashboard → progression par cours
2. Consulter un **chapitre** (la progression augmente)
3. **Mes notes** → moyenne et détail par module
4. **Planning** → emploi du temps de la semaine (cliquer sur **Aujourd'hui** si la grille est vide)
5. Poster sur le **forum**, consulter les **évaluations**
6. **Messages** → messagerie avec un professeur

---

## Routes principales

| Zone | URL | Rôle minimum |
| ---- | --- | ------------ |
| Dashboard admin | `/admin/dashboard` | `ROLE_ADMIN_ECOLE` |
| Planning admin | `/admin/planning` | `ROLE_ADMIN_ECOLE` |
| Dashboard enseignant | `/enseignant/dashboard` | `ROLE_ENSEIGNANT` |
| Planning enseignant | `/enseignant/planning` | `ROLE_ENSEIGNANT` |
| Dashboard étudiant | `/etudiant/dashboard` | `ROLE_ETUDIANT` |
| Planning étudiant | `/etudiant/planning` | `ROLE_ETUDIANT` |
| Messagerie | `/messages` | `ROLE_USER` |
| Mon compte | `/mon-compte` | `ROLE_USER` |
| API planning | `/api/planning/events` | `ROLE_USER` |
| Export PDF planning | `/planning/export/pdf` | `ROLE_USER` |

---

## Structure du projet

```
src/
├── Controller/          # Contrôleurs web et API (Planning, Messages…)
├── Entity/              # Entités Doctrine
├── Form/                # Formulaires Symfony
├── Repository/          # Requêtes personnalisées
├── Security/UserRole.php # Constantes et helpers de rôles
└── Service/             # Logique métier (PlanningAccess, MessageAccess…)

templates/               # Vues Twig par zone (admin, enseignant, etudiant, planning…)
public/css/              # app.css, planning.css, messages.css
public/js/               # planning.js, messages.js
migrations/              # Migrations Doctrine
```

---

## Déploiement Render (Docker)

Le projet inclut un `Dockerfile` et un `render.yaml` prêts pour [Render](https://render.com).

### Prérequis

- Dépôt GitHub connecté à Render
- Base **MySQL 8** (Aiven recommandé) accessible depuis Internet
- Certificat CA Aiven déjà présent dans `config/certs/ca.pem` (SSL activé en `APP_ENV=prod`)

### Étapes

1. **Créer un Web Service** sur Render → *Docker* → repo `INNOV`
2. Render détecte `render.yaml` ou utilise le `Dockerfile` à la racine
3. **Variables d'environnement** (dashboard Render) :

| Variable | Valeur |
| -------- | ------ |
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | générer une clé aléatoire (32+ caractères) |
| `DATABASE_URL` | URL Aiven MySQL complète |
| `TRUSTED_PROXIES` | `REMOTE_ADDR` |
| `TRUSTED_HOSTS` | `^.+$` |

Exemple `DATABASE_URL` Aiven :

```dotenv
DATABASE_URL="mysql://USER:PASSWORD@HOST:PORT/defaultdb?serverVersion=8.0&charset=utf8mb4"
```

4. **Appliquer les migrations** (manuellement, avant ou après le premier déploiement) :

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

Sur Render : *Shell* du service web, ou en local avec `DATABASE_URL` pointant vers Aiven.

5. **Déployer** — au démarrage le conteneur :
   - vérifie `APP_SECRET` et `DATABASE_URL`
   - attend la base de données (30 tentatives)
   - réchauffe le cache Symfony (`cache:warmup` + `assets:install`)
   - lance le serveur PHP sur le port `PORT` (Render)

6. **Données de démo** (optionnel, une seule fois en local ou via console Render) :

```bash
php bin/console doctrine:fixtures:load --no-interaction --env=prod
```

> Ne pas charger les fixtures automatiquement en production.

### Vérification locale de l'image Docker

```bash
docker build -t merj-learn .
docker run --rm -p 10000:10000 \
  -e APP_SECRET=change-me \
  -e DATABASE_URL="mysql://..." \
  merj-learn
```

Health check Render : `/login`

---

## Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Appliquer les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Recharger les données de démo (⚠️ purge la base)
php bin/console doctrine:fixtures:load --no-interaction

# Valider le schéma
php bin/console doctrine:schema:validate

# Lister les routes
php bin/console debug:router

# Linter Twig
php bin/console lint:twig templates/
```

---

## Développement

### Workflow Git recommandé

```bash
git pull origin main
git checkout -b feature/ma-feature
# … commits …
git push -u origin feature/ma-feature
# Ouvrir une Pull Request → review → squash merge
```

- Une branche par feature / fix (`feature/…`, `fix/…`, `chore/…`)
- Commits au format [Conventional Commits](https://www.conventionalcommits.org/) : `feat:`, `fix:`, `refactor:`, etc.
- Ne jamais committer de secrets
- Lancer les tests / vérifications avant chaque push si aucune CI n'est configurée

### Conventions de commit (exemples)

```
feat: ajouter le rôle administration école
fix: corriger l'affichage vide du planning
docs: mettre à jour le README des comptes démo
```

---

## Licence

Projet propriétaire — usage interne MERJ Learn.
