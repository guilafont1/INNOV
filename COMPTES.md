# Comptes de démonstration — MERJ Learn

Liste des comptes créés par les fixtures (`AppFixtures`).  
**Mot de passe commun pour tous :** `Demo2026!`

---

## Connexion


| Environnement       | URL                                                        |
| ------------------- | ---------------------------------------------------------- |
| Local               | [http://localhost:8000/login](http://localhost:8000/login) |
| Production (Render) | `https://innov-xnk0.onrender.com/`                         |


---

## Comptes rapides (tests par rôle)


| Rôle                 | Email                 | Mot de passe |
| -------------------- | --------------------- | ------------ |
| Super administrateur | `admin@merj.fr`       | `Demo2026!`  |
| Administration école | `admin.ecole@merj.fr` | `Demo2026!`  |
| Enseignant           | `prof1@merj.fr`       | `Demo2026!`  |
| Étudiant             | `etudiant1@merj.fr`   | `Demo2026!`  |


---



## Super administrateur (1)

Accès technique complet : gestion des super admins, toutes les fonctionnalités.


| Nom                  | Email           | Rôle Symfony       |
| -------------------- | --------------- | ------------------ |
| Super Administrateur | `admin@merj.fr` | `ROLE_SUPER_ADMIN` |


---



## Administration école (3)

Gestion des classes, utilisateurs (hors super admins), planning global, exports PDF/iCal.


| Nom             | Email                  | Rôle Symfony       |
| --------------- | ---------------------- | ------------------ |
| Sophie Rousseau | `admin.ecole@merj.fr`  | `ROLE_ADMIN_ECOLE` |
| Pierre Lambert  | `scolarite@merj.fr`    | `ROLE_ADMIN_ECOLE` |
| Amélie Gérard   | `vie.scolaire@merj.fr` | `ROLE_ADMIN_ECOLE` |


---



## Enseignants (3)

Cours (modules/chapitres), notes des classes, consultation planning et forum.


| Nom          | Email           | Classe(s) principale(s) | Rôle Symfony      |
| ------------ | --------------- | ----------------------- | ----------------- |
| Marie Dupont | `prof1@merj.fr` | M1 Dev Web              | `ROLE_ENSEIGNANT` |
| Karim Benali | `prof2@merj.fr` | M2 EISI                 | `ROLE_ENSEIGNANT` |
| Julie Martin | `prof3@merj.fr` | B3 DevOps               | `ROLE_ENSEIGNANT` |


---



## Étudiants (16)


| Nom             | Email                | Classe            | Rôle Symfony    |
| --------------- | -------------------- | ----------------- | --------------- |
| Alice Bernard   | `etudiant1@merj.fr`  | M1 Dev Web        | `ROLE_ETUDIANT` |
| Hugo Lefèvre    | `etudiant2@merj.fr`  | M1 Dev Web        | `ROLE_ETUDIANT` |
| Maya Nguyen     | `etudiant3@merj.fr`  | M1 Dev Web        | `ROLE_ETUDIANT` |
| Nathan Moreau   | `etudiant4@merj.fr`  | M1 Dev Web        | `ROLE_ETUDIANT` |
| Sarah Petit     | `etudiant5@merj.fr`  | M2 EISI           | `ROLE_ETUDIANT` |
| Yanis Said      | `etudiant6@merj.fr`  | M2 EISI           | `ROLE_ETUDIANT` |
| Emma Robin      | `etudiant7@merj.fr`  | M2 EISI           | `ROLE_ETUDIANT` |
| Louis Giraud    | `etudiant8@merj.fr`  | M2 EISI           | `ROLE_ETUDIANT` |
| Chloé Fournier  | `etudiant9@merj.fr`  | B3 DevOps         | `ROLE_ETUDIANT` |
| Samir Kacem     | `etudiant10@merj.fr` | B3 DevOps         | `ROLE_ETUDIANT` |
| Zoé Chevalier   | `etudiant11@merj.fr` | B3 DevOps         | `ROLE_ETUDIANT` |
| Thomas Marchand | `etudiant12@merj.fr` | B3 DevOps         | `ROLE_ETUDIANT` |
| Léa Fontaine    | `etudiant13@merj.fr` | L3 Alternance Web | `ROLE_ETUDIANT` |
| Noah Perrin     | `etudiant14@merj.fr` | L3 Alternance Web | `ROLE_ETUDIANT` |
| Inès Blanc      | `etudiant15@merj.fr` | L3 Alternance Web | `ROLE_ETUDIANT` |
| Lucas Renard    | `etudiant16@merj.fr` | L3 Alternance Web | `ROLE_ETUDIANT` |


---



## Récapitulatif


| Catégorie            | Nombre |
| -------------------- | ------ |
| Super administrateur | 1      |
| Administration école | 3      |
| Enseignants          | 3      |
| Étudiants            | 16     |
| **Total**            | **23** |


---



## Charger les comptes en base

```bash
# Local
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Production (Render Shell)
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console doctrine:fixtures:load --no-interaction --env=prod
```

> `doctrine:fixtures:load` **purge la base** avant d'insérer les données de démo. À utiliser uniquement en environnement de démonstration.

---



## Parcours suggérés



### Super admin — `admin@merj.fr`

1. `/admin/dashboard` → utilisateurs, classes, planning global



### Administration école — `admin.ecole@merj.fr`

1. Gestion classes et utilisateurs (sans super admins)
2. Planning global + export PDF/iCal



### Enseignant — `prof1@merj.fr`

1. Mes cours → modules et chapitres
2. Notes de classe, forum (lecture), messagerie



### Étudiant — `etudiant1@merj.fr`

1. Dashboard → progression par cours
2. Chapitres, notes, forum, messagerie, évaluations

