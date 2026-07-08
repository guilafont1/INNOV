<?php

namespace App\DataFixtures;

use App\Entity\Calendrier;
use App\Entity\Chapitre;
use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Entity\ForumPost;
use App\Entity\Message;
use App\Entity\Module;
use App\Entity\Note;
use App\Entity\Progression;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $password = 'Demo2026!';
        $now = new \DateTimeImmutable();
        $monday = new \DateTimeImmutable('2026-07-07');

        // --- USERS ---
        $admin = $this->createUser(
            email: 'admin@jeai.fr',
            nom: 'Administrateur',
            prenom: 'Super',
            roles: ['ROLE_SUPER_ADMIN'],
            password: $password
        );
        $manager->persist($admin);

        $adminsEcoleData = [
            'ae1' => ['email' => 'admin.ecole@jeai.fr', 'prenom' => 'Sophie', 'nom' => 'Rousseau'],
            'ae2' => ['email' => 'scolarite@jeai.fr', 'prenom' => 'Pierre', 'nom' => 'Lambert'],
            'ae3' => ['email' => 'vie.scolaire@jeai.fr', 'prenom' => 'Amélie', 'nom' => 'Gérard'],
        ];
        $adminsEcole = [];
        foreach ($adminsEcoleData as $key => $data) {
            $adminsEcole[$key] = $this->createUser(
                email: $data['email'],
                nom: $data['nom'],
                prenom: $data['prenom'],
                roles: ['ROLE_ADMIN_ECOLE'],
                password: $password,
            );
            $adminsEcole[$key]->setDerniereConnexion($now->modify('-' . match ($key) {
                'ae1' => 1,
                'ae2' => 3,
                default => 6,
            } . ' days'));
            $manager->persist($adminsEcole[$key]);
        }

        $teachersData = [
            't1' => ['email' => 'prof1@jeai.fr', 'prenom' => 'Marie', 'nom' => 'Dupont'],
            't2' => ['email' => 'prof2@jeai.fr', 'prenom' => 'Karim', 'nom' => 'Benali'],
            't3' => ['email' => 'prof3@jeai.fr', 'prenom' => 'Julie', 'nom' => 'Martin'],
        ];
        $teachers = [];
        foreach ($teachersData as $key => $data) {
            $teachers[$key] = $this->createUser(
                email: $data['email'],
                nom: $data['nom'],
                prenom: $data['prenom'],
                roles: ['ROLE_ENSEIGNANT'],
                password: $password
            );
            $manager->persist($teachers[$key]);
        }

        $studentsData = [
            's1' => ['email' => 'etudiant1@jeai.fr', 'prenom' => 'Alice', 'nom' => 'Bernard'],
            's2' => ['email' => 'etudiant2@jeai.fr', 'prenom' => 'Hugo', 'nom' => 'Lefèvre'],
            's3' => ['email' => 'etudiant3@jeai.fr', 'prenom' => 'Maya', 'nom' => 'Nguyen'],
            's4' => ['email' => 'etudiant4@jeai.fr', 'prenom' => 'Nathan', 'nom' => 'Moreau'],
            's5' => ['email' => 'etudiant5@jeai.fr', 'prenom' => 'Sarah', 'nom' => 'Petit'],
            's6' => ['email' => 'etudiant6@jeai.fr', 'prenom' => 'Yanis', 'nom' => 'Said'],
            's7' => ['email' => 'etudiant7@jeai.fr', 'prenom' => 'Emma', 'nom' => 'Robin'],
            's8' => ['email' => 'etudiant8@jeai.fr', 'prenom' => 'Louis', 'nom' => 'Giraud'],
            's9' => ['email' => 'etudiant9@jeai.fr', 'prenom' => 'Chloé', 'nom' => 'Fournier'],
            's10' => ['email' => 'etudiant10@jeai.fr', 'prenom' => 'Samir', 'nom' => 'Kacem'],
            's11' => ['email' => 'etudiant11@jeai.fr', 'prenom' => 'Zoé', 'nom' => 'Chevalier'],
            's12' => ['email' => 'etudiant12@jeai.fr', 'prenom' => 'Thomas', 'nom' => 'Marchand'],
            's13' => ['email' => 'etudiant13@jeai.fr', 'prenom' => 'Léa', 'nom' => 'Fontaine'],
            's14' => ['email' => 'etudiant14@jeai.fr', 'prenom' => 'Noah', 'nom' => 'Perrin'],
            's15' => ['email' => 'etudiant15@jeai.fr', 'prenom' => 'Inès', 'nom' => 'Blanc'],
            's16' => ['email' => 'etudiant16@jeai.fr', 'prenom' => 'Lucas', 'nom' => 'Renard'],
        ];
        $students = [];
        foreach ($studentsData as $key => $data) {
            $students[$key] = $this->createUser(
                email: $data['email'],
                nom: $data['nom'],
                prenom: $data['prenom'],
                roles: ['ROLE_ETUDIANT'],
                password: $password
            );
            $manager->persist($students[$key]);
        }

        // --- COURSES / MODULES / CHAPTERS ---
        $coursesData = [
            'symfony' => [
                'titre' => 'Symfony avancé',
                'description' => "Approfondissez les composants Symfony (Doctrine, Form, Security) et optimisez la performance des requêtes.\n\nVous construirez une base solide pour des projets maintenables et testables.",
                'createdBy' => 't1',
                'modules' => [
                    [
                        'titre' => 'Architecture Doctrine & Performance',
                        'chapitres' => [
                            'Comprendre les repositories Doctrine ORM',
                            'Optimiser les requêtes : indexes, fetch strategies et pagination',
                            'Stratégies de filtrage et chargement maîtrisé',
                        ],
                    ],
                    [
                        'titre' => 'Tests et qualité de code',
                        'chapitres' => [
                            'Écrire des tests fonctionnels et d’intégration',
                            'Bonnes pratiques de refactoring et couverture utile',
                        ],
                    ],
                ],
            ],
            'db' => [
                'titre' => 'Bases de données',
                'description' => "Modélisez des données fiables, maîtrisez les relations et améliorez les performances.\n\nObjectif : savoir concevoir un schéma cohérent et l’optimiser.",
                'createdBy' => 't2',
                'modules' => [
                    [
                        'titre' => 'Modélisation & normalisation',
                        'chapitres' => [
                            'Entités, relations et contraintes',
                            'Normalisation : du besoin au schéma',
                            'Gestion des suppressions et intégrité référentielle',
                        ],
                    ],
                    [
                        'titre' => 'SQL avancé & indexation',
                        'chapitres' => [
                            'Requêtes performantes : plans d’exécution',
                            'Index : choisir, mesurer, itérer',
                            'Bonnes pratiques MySQL pour du e-learning',
                        ],
                    ],
                ],
            ],
            'docker' => [
                'titre' => 'Docker & CI/CD',
                'description' => "Construisez des images légères, orchestrer un environnement de dev et automatiser les déploiements.\n\nVous verrez comment intégrer CI/CD à votre workflow.",
                'createdBy' => 't3',
                'modules' => [
                    [
                        'titre' => 'Docker : construire des images',
                        'chapitres' => [
                            'Dockerfile : multi-stage et bonnes pratiques',
                            'Volumes, caches et stratégies de build',
                            'Configuration d’environnements (dev/test/prod)',
                        ],
                    ],
                    [
                        'titre' => 'CI/CD avec pipelines',
                        'chapitres' => [
                            'Automatiser tests, lint et build',
                            'Stratégies de déploiement progressif',
                            'Gestion des secrets en CI',
                        ],
                    ],
                    [
                        'titre' => 'Observabilité & opérations',
                        'chapitres' => [
                            'Logs utiles : corrélation et structure',
                            'Monitoring : métriques et alerting',
                        ],
                    ],
                ],
            ],
            'security' => [
                'titre' => 'Sécurité web',
                'description' => "Comprenez les attaques courantes et renforcez l’application : authentification, autorisation, CSRF et validation.\n\nL’objectif est d’implémenter une sécurité pragmatique et robuste.",
                'createdBy' => 't1',
                'modules' => [
                    [
                        'titre' => 'AuthN/AuthZ et contrôles d’accès',
                        'chapitres' => [
                            'Rôles, access_control et limitations côté serveur',
                            'Gestion sécurisée des erreurs et redirections',
                        ],
                    ],
                    [
                        'titre' => 'CSRF, validation et hygiène des données',
                        'chapitres' => [
                            'Pourquoi le CSRF est indispensable',
                            'Validation côté contrôleur et formulaires Symfony',
                            'Nettoyage des données et robustesse',
                        ],
                    ],
                ],
            ],
            'js' => [
                'titre' => 'JavaScript moderne',
                'description' => "Rendre l’UI plus réactive avec une approche moderne du JavaScript.\n\nVous apprendrez à structurer le code côté client et à maîtriser les requêtes asynchrones.",
                'createdBy' => 't2',
                'modules' => [
                    [
                        'titre' => 'Async, fetch et gestion d’erreurs',
                        'chapitres' => [
                            'fetch : conventions, body et parsing JSON',
                            'Gestion des erreurs réseau et UI',
                            'Design des endpoints et compatibilité frontend',
                        ],
                    ],
                    [
                        'titre' => 'Qualité et maintenabilité',
                        'chapitres' => [
                            'Organisation du code et lisibilité',
                            'Tests front et bonnes pratiques',
                        ],
                    ],
                ],
            ],
            'agile' => [
                'titre' => 'Gestion de projet agile',
                'description' => "Planifiez, suivez et améliorez votre delivery grâce aux pratiques agiles.\n\nVous produirez des artefacts clairs : backlog, priorisation, et rétrospectives.",
                'createdBy' => 't3',
                'modules' => [
                    [
                        'titre' => 'Planning et communication',
                        'chapitres' => [
                            'Découpage en tickets et estimations',
                            'Suivi de l’avancement et rituels d’équipe',
                            'Gestion des risques',
                        ],
                    ],
                    [
                        'titre' => 'Qualité, démo et amélioration continue',
                        'chapitres' => [
                            'Définir “done” et critères d’acceptation',
                            'Rétrospectives orientées action',
                        ],
                    ],
                ],
            ],
        ];

        $courses = [];
        $modulesByCourseKey = [];
        $chapitresByCourseKey = [];

        foreach ($coursesData as $courseKey => $courseData) {
            $cours = new Cours();
            $cours->setTitre($courseData['titre']);
            $cours->setDescription($courseData['description']);
            $cours->setCreatedAt($now);
            $cours->setCreatedBy($teachers[$courseData['createdBy']]);

            $manager->persist($cours);
            $courses[$courseKey] = $cours;
            $modulesByCourseKey[$courseKey] = [];
            $chapitresByCourseKey[$courseKey] = [];

            foreach ($courseData['modules'] as $moduleData) {
                $module = new Module();
                $module->setTitre($moduleData['titre']);
                $cours->addModule($module);
                $manager->persist($module);
                $modulesByCourseKey[$courseKey][] = $module;

                foreach ($moduleData['chapitres'] as $chapterTitle) {
                    $chapitre = new Chapitre();
                    $chapitre->setTitre($chapterTitle);
                    $chapitre->setContenu(
                        "Ce chapitre détaille les notions nécessaires pour avancer sereinement.\n\nVous verrez les concepts, puis un exemple concret d’application.\n\nEnfin, un mini-checklist vous aidera à réutiliser ce qui a été appris dans vos projets."
                    );
                    $chapitre->setFichierMedia(null);
                    $module->addChapitre($chapitre);
                    $manager->persist($chapitre);
                    $chapitresByCourseKey[$courseKey][] = $chapitre;
                }
            }
        }

        // --- CLASSES (4 étudiants, 1 enseignant, 2-3 cours) ---
        $classesData = [
            'm1' => [
                'nom' => 'M1 Dev Web',
                'description' => 'Projet web, sécurité et bonnes pratiques : un parcours solide pour coder proprement.',
                'teacher' => 't1',
                'students' => ['s1', 's2', 's3', 's4'],
                'courses' => ['symfony', 'security', 'agile'],
            ],
            'm2' => [
                'nom' => 'M2 EISI',
                'description' => 'Data, performance et requêtes : modéliser pour que ça reste rapide.',
                'teacher' => 't2',
                'students' => ['s5', 's6', 's7', 's8'],
                'courses' => ['db', 'js'],
            ],
            'b3' => [
                'nom' => 'B3 DevOps',
                'description' => 'Livrer plus souvent : Docker, CI/CD, déploiement et observabilité.',
                'teacher' => 't3',
                'students' => ['s9', 's10', 's11', 's12'],
                'courses' => ['docker', 'agile'],
            ],
            'l3' => [
                'nom' => 'L3 Alternance Web',
                'description' => 'Parcours alternance : consolidation Symfony, sécurité et gestion de projet.',
                'teacher' => 't1',
                'students' => ['s13', 's14', 's15', 's16'],
                'courses' => ['symfony', 'security', 'agile'],
            ],
        ];

        $classes = [];
        foreach ($classesData as $classKey => $classData) {
            $classe = new Classe();
            $classe->setNom($classData['nom']);
            $classe->setDescription($classData['description']);
            $classe->setCreatedAt($now);

            $manager->persist($classe);
            $classes[$classKey] = $classe;

            $classe->addProfesseur($teachers[$classData['teacher']]);
            foreach ($classData['students'] as $studentKey) {
                $classe->addEtudiant($students[$studentKey]);
            }
            foreach ($classData['courses'] as $courseKey) {
                $classe->addCours($courses[$courseKey]);
            }
        }

        // --- NOTES (modules des cours accessibles via les classes) ---
        $noteValues = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
        foreach ($classesData as $classKey => $classData) {
            $classe = $classes[$classKey];
            $etudiants = $classe->getEtudiants();
            $coursClasse = $classe->getCours();

            foreach ($coursClasse as $cours) {
                $professeur = $cours->getCreatedBy();

                foreach ($cours->getModules() as $moduleIndex => $module) {
                    $i = 0;
                    foreach ($etudiants as $etudiant) {
                        $noteIdx = ($i + ($classKey === 'm1' ? 1 : ($classKey === 'm2' ? 2 : 3)) + $moduleIndex * 3) % count($noteValues);
                        $note = $noteValues[$noteIdx];

                        $noteEntity = new Note();
                        $noteEntity->setEtudiant($etudiant);
                        $noteEntity->setModule($module);
                        $noteEntity->setNote((string) $note);
                        $noteEntity->setNoteMax('20');

                        $noteEntity->setCommentaire(
                            "Bon travail sur le module : on sent la progression.\n\nProchaine étape : consolider les points clés et systématiser les exercices."
                        );
                        $noteEntity->setProfesseur($professeur);
                        $noteEntity->setCreatedAt($now->modify('-'.(3 + $noteIdx).' days'));

                        $manager->persist($noteEntity);
                        $i++;
                    }
                }
            }
        }

        // --- PLANNING (15-20 événements) ---
        $eventDefs = [
            // {dayOffset, time, durationMinutes, type, titre, description, lieu, classeKey, courseKey, teacherKey}
            ['offset' => 1, 'time' => '09:00', 'dur' => 90, 'type' => 'cours', 'titre' => 'Symfony : architecture des endpoints', 'desc' => 'Atelier guidé + Q/R', 'lieu' => 'Salle B204', 'class' => 'm1', 'course' => 'symfony', 'teacher' => 't1'],
            ['offset' => 2, 'time' => '13:30', 'dur' => 60, 'type' => 'cours', 'titre' => 'CSRF & formulaires Symfony', 'desc' => 'Cas pratiques et vérifications', 'lieu' => 'Salle B204', 'class' => 'm1', 'course' => 'security', 'teacher' => 't1'],
            ['offset' => 3, 'time' => '10:00', 'dur' => 120, 'type' => 'reunion', 'titre' => 'Rétro équipe projet (S1)', 'desc' => 'Bilan et actions d’amélioration', 'lieu' => 'Distanciel', 'class' => 'm1', 'course' => 'agile', 'teacher' => 't3'],
            ['offset' => 4, 'time' => '14:00', 'dur' => 75, 'type' => 'cours', 'titre' => 'Indexation & performance SQL', 'desc' => 'Optimiser des requêtes lentes', 'lieu' => 'Amphi A', 'class' => 'm2', 'course' => 'db', 'teacher' => 't2'],
            ['offset' => 5, 'time' => '09:30', 'dur' => 60, 'type' => 'cours', 'titre' => 'JavaScript : fetch et erreurs', 'desc' => 'Endpoints + intégration front', 'lieu' => 'Salle C102', 'class' => 'm2', 'course' => 'js', 'teacher' => 't2'],
            ['offset' => 6, 'time' => '11:00', 'dur' => 90, 'type' => 'examen', 'titre' => 'Quiz Sécurité web', 'desc' => 'Contrôles d’accès & validation', 'lieu' => 'Salle B204', 'class' => 'm1', 'course' => 'security', 'teacher' => 't1'],

            // Semaine suivante (toujours futures)
            ['offset' => 8, 'time' => '09:00', 'dur' => 90, 'type' => 'cours', 'titre' => 'Docker : multi-stage builds', 'desc' => 'Cas pratiques sur dev/test', 'lieu' => 'Amphi A', 'class' => 'b3', 'course' => 'docker', 'teacher' => 't3'],
            ['offset' => 9, 'time' => '14:30', 'dur' => 60, 'type' => 'reunion', 'titre' => 'Daily DevOps (S2)', 'desc' => 'Risques, blocages, next steps', 'lieu' => 'Distanciel', 'class' => 'b3', 'course' => 'agile', 'teacher' => 't3'],
            ['offset' => 10, 'time' => '10:00', 'dur' => 120, 'type' => 'cours', 'titre' => 'Pipeline CI/CD : tests & déploiement', 'desc' => 'Automatiser la livraison', 'lieu' => 'Salle D210', 'class' => 'b3', 'course' => 'docker', 'teacher' => 't3'],
            ['offset' => 11, 'time' => '13:00', 'dur' => 75, 'type' => 'cours', 'titre' => 'Modélisation : contraintes et relations', 'desc' => 'Du besoin au schéma', 'lieu' => 'Amphi A', 'class' => 'm2', 'course' => 'db', 'teacher' => 't2'],
            ['offset' => 12, 'time' => '09:45', 'dur' => 60, 'type' => 'cours', 'titre' => 'Symfony avancé : performance Doctrine', 'desc' => 'Chargement et pagination', 'lieu' => 'Salle C102', 'class' => 'm1', 'course' => 'symfony', 'teacher' => 't1'],
            ['offset' => 13, 'time' => '15:00', 'dur' => 90, 'type' => 'examen', 'titre' => 'Mini-projet agile : critères “done”', 'desc' => 'Livrer proprement', 'lieu' => 'Salle B204', 'class' => 'b3', 'course' => 'agile', 'teacher' => 't3'],

            // Extra pour avoir 15-20
            ['offset' => 2, 'time' => '16:00', 'dur' => 60, 'type' => 'cours', 'titre' => 'SQL : plans d’exécution', 'desc' => 'Comprendre puis améliorer', 'lieu' => 'Salle C102', 'class' => 'm2', 'course' => 'db', 'teacher' => 't2'],
            ['offset' => 5, 'time' => '12:00', 'dur' => 60, 'type' => 'cours', 'titre' => 'JavaScript : structure et maintenabilité', 'desc' => 'Qualité côté client', 'lieu' => 'Salle D210', 'class' => 'm2', 'course' => 'js', 'teacher' => 't2'],
            ['offset' => 3, 'time' => '08:30', 'dur' => 90, 'type' => 'cours', 'titre' => 'Architecture Symfony : refactor & tests', 'desc' => 'Un exemple “bout en bout”', 'lieu' => 'Salle B204', 'class' => 'm1', 'course' => 'symfony', 'teacher' => 't1'],
            ['offset' => 9, 'time' => '08:00', 'dur' => 60, 'type' => 'reunion', 'titre' => 'Rétro Sprint : démo + actions', 'desc' => 'S’améliorer en continu', 'lieu' => 'Distanciel', 'class' => 'm1', 'course' => 'agile', 'teacher' => 't3'],
            ['offset' => 12, 'time' => '11:30', 'dur' => 75, 'type' => 'cours', 'titre' => 'CI/CD : gestion des secrets', 'desc' => 'Pratiques de sécurité en pipeline', 'lieu' => 'Salle D210', 'class' => 'b3', 'course' => 'docker', 'teacher' => 't3'],

            // Semaine en cours + chevauchements (planning dense)
            ['offset' => 0, 'time' => '10:00', 'dur' => 120, 'type' => 'reunion', 'titre' => 'Conseil pédagogique — rentrée', 'desc' => 'Point direction / enseignants', 'lieu' => 'Salle du conseil', 'class' => 'm1', 'course' => 'agile', 'teacher' => 't1'],
            ['offset' => 1, 'time' => '09:00', 'dur' => 90, 'type' => 'cours', 'titre' => 'Atelier Symfony : formulaires avancés', 'desc' => 'CollectionType et validation', 'lieu' => 'Salle B204', 'class' => 'l3', 'course' => 'symfony', 'teacher' => 't1'],
            ['offset' => 1, 'time' => '09:30', 'dur' => 90, 'type' => 'cours', 'titre' => 'TP JavaScript : API planning', 'desc' => 'Consommation JSON côté client', 'lieu' => 'Salle C102', 'class' => 'm2', 'course' => 'js', 'teacher' => 't2'],
            ['offset' => 1, 'time' => '14:00', 'dur' => 60, 'type' => 'examen', 'titre' => 'Contrôle continu BDD', 'desc' => 'Requêtes et index', 'lieu' => 'Amphi A', 'class' => 'm2', 'course' => 'db', 'teacher' => 't2'],
            ['offset' => 2, 'time' => '08:30', 'dur' => 90, 'type' => 'cours', 'titre' => 'Sécurité : authentification JWT', 'desc' => 'Bonnes pratiques et pièges', 'lieu' => 'Salle B204', 'class' => 'l3', 'course' => 'security', 'teacher' => 't1'],
            ['offset' => 2, 'time' => '08:30', 'dur' => 90, 'type' => 'cours', 'titre' => 'Docker : réseaux et volumes', 'desc' => 'Compose avancé', 'lieu' => 'Salle D210', 'class' => 'b3', 'course' => 'docker', 'teacher' => 't3'],
            ['offset' => 3, 'time' => '15:30', 'dur' => 45, 'type' => 'reunion', 'titre' => 'Bureau des études — planning examens', 'desc' => 'Calendrier partiel juillet', 'lieu' => 'Bureau scolarité', 'class' => 'm1', 'course' => 'agile', 'teacher' => 't1'],
            ['offset' => 4, 'time' => '11:00', 'dur' => 90, 'type' => 'examen', 'titre' => 'Examen blanc Symfony', 'desc' => 'Épreuve pratique 1h30', 'lieu' => 'Salle B204', 'class' => 'l3', 'course' => 'symfony', 'teacher' => 't1'],
            ['offset' => 7, 'time' => '13:30', 'dur' => 60, 'type' => 'autre', 'titre' => 'Forum parents — M1 Dev Web', 'desc' => 'Présentation des projets', 'lieu' => 'Amphi B', 'class' => 'm1', 'course' => 'symfony', 'teacher' => 't1'],
            ['offset' => 14, 'time' => '09:00', 'dur' => 120, 'type' => 'examen', 'titre' => 'Examen final DevOps', 'desc' => 'Pipeline + déploiement', 'lieu' => 'Salle D210', 'class' => 'b3', 'course' => 'docker', 'teacher' => 't3'],
        ];

        foreach ($eventDefs as $def) {
            $event = new Calendrier();
            $event->setTitre($def['titre']);
            $event->setDescription($def['desc']);
            $event->setType($def['type']);
            $event->setLieu($def['lieu']);

            [$hour, $minute] = array_map('intval', explode(':', $def['time']));
            $dateDebut = $monday
                ->modify('+' . $def['offset'] . ' days')
                ->setTime($hour, $minute);

            $dateFin = $dateDebut->modify('+' . $def['dur'] . ' minutes');

            $event->setDateDebut($dateDebut);
            $event->setDateFin($dateFin);

            $event->setClasse($classes[$def['class']]);
            $event->setCours($courses[$def['course']]);
            $event->setEnseignant($teachers[$def['teacher']]);

            $manager->persist($event);
        }

        // --- PROGRESSIONS (quelques exemples réalistes) ---
        $progressionDefs = [
            // studentKey, courseKey, percentage, updatedAtOffsetDays
            's1' => [['symfony', 45, 1], ['security', 60, 2]],
            's2' => [['symfony', 30, 3], ['security', 75, 5]],
            's3' => [['symfony', 70, 2]],
            's4' => [['security', 40, 1]],

            's5' => [['db', 55, 4], ['js', 65, 6]],
            's6' => [['db', 35, 3], ['js', 50, 2]],
            's7' => [['db', 80, 1]],
            's8' => [['js', 30, 4]],

            's9' => [['docker', 60, 2], ['agile', 40, 3]],
            's10' => [['docker', 85, 1]],
            's11' => [['agile', 70, 2]],
            's12' => [['docker', 35, 5], ['agile', 55, 4]],
            's13' => [['symfony', 25, 2], ['security', 15, 1]],
            's14' => [['symfony', 50, 3], ['agile', 20, 2]],
            's15' => [['security', 40, 4]],
            's16' => [['symfony', 60, 1], ['security', 55, 2], ['agile', 30, 3]],
        ];

        foreach ($progressionDefs as $studentKey => $entries) {
            $student = $students[$studentKey];
            foreach ($entries as $entry) {
                [$courseKey, $percentage, $updatedOffsetDays] = $entry;
                $course = $courses[$courseKey];
                $allChapitres = $chapitresByCourseKey[$courseKey];
                $total = count($allChapitres);
                if ($total === 0) {
                    continue;
                }

                $consultCount = max(1, min($total, (int) round(($percentage / 100) * $total)));
                $subset = array_slice($allChapitres, 0, $consultCount);
                $computedAvancement = round(($consultCount / $total) * 100, 2);

                $progression = new Progression();
                $progression->setUser($student);
                $progression->setCours($course);
                $progression->setAvancement($computedAvancement);
                $progression->setDernierChapitre($subset[array_key_last($subset)]);

                $progression->setUpdatedAt($now->modify('-' . (2 + $updatedOffsetDays) . ' days'));
                foreach ($subset as $chapitre) {
                    $progression->addChapitreConsulte($chapitre);
                }

                $manager->persist($progression);
            }
        }

        // --- FORUM (3-5 posts par cours) ---
        $forumContent = [
            'question' => "Bonjour, j’ai une question : comment aborder proprement ce point dans un projet Symfony ?",
            'reponse' => "Pour moi, le plus efficace est de commencer par une petite expérience et de valider par un test fonctionnel.",
            'detail' => "J’ai essayé une approche et j’obtiens un résultat stable : voici les étapes et les points de vigilance.",
            'retour' => "Merci, ça m’a aidé. Je vois mieux comment structurer les entités et les validations côté serveur.",
        ];

        foreach ($coursesData as $courseKey => $courseData) {
            $course = $courses[$courseKey];

            // classes qui ont ce cours
            $classesAvecCours = [];
            foreach ($classes as $classe) {
                foreach ($classe->getCours() as $c) {
                    if ($c === $course) {
                        $classesAvecCours[] = $classe;
                    }
                }
            }
            $studentsPool = [];
            foreach ($classesAvecCours as $classe) {
                foreach ($classe->getEtudiants() as $student) {
                    $studentsPool[] = $student;
                }
            }

            $postsCount = 6;
            for ($i = 0; $i < $postsCount; $i++) {
                $post = new ForumPost();
                $post->setCours($course);
                if ($i === 0) {
                    $post->setAuteur($adminsEcole['ae2']);
                    $post->setContenu(
                        "Annonce scolarité — Les créneaux d'examens du mois sont publiés sur le planning.\n\n"
                        . "Merci de vérifier vos convocations et de signaler tout chevauchement à vie.scolaire@jeai.fr avant vendredi."
                    );
                } else {
                    $post->setAuteur($studentsPool[($i - 1) % max(1, count($studentsPool))]);
                    $post->setContenu(
                        ($i % 2 === 0 ? $forumContent['question'] : $forumContent['reponse'])
                        . "\n\n"
                        . $forumContent['detail']
                        . "\n\n"
                        . $forumContent['retour']
                    );
                }
                $post->setCreatedAt($monday->modify('+' . (1 + $i * 2) . ' days'));
                $manager->persist($post);
            }
        }

        // --- MESSAGES (échanges entre membres d'une même classe) ---
        $messageDefs = [
            ['from' => 's1', 'to' => 't1', 'days' => 2, 'text' => "Bonjour professeur, j'ai une question sur le chapitre Doctrine : faut-il toujours utiliser des repositories personnalisés ?"],
            ['from' => 't1', 'to' => 's1', 'days' => 1, 'text' => "Bonjour Alice, oui dès que la logique de requête devient métier. On en reparle en cours de vendredi."],
            ['from' => 's2', 'to' => 's3', 'days' => 3, 'text' => "Salut Maya, tu as réussi l'exercice sur les formulaires Symfony ? Je bloque sur la validation des dates."],
            ['from' => 's3', 'to' => 's2', 'days' => 2, 'text' => "Oui Hugo ! J'ai utilisé GreaterThan sur dateFin avec propertyPath, ça marche bien."],
            ['from' => 's5', 'to' => 't2', 'days' => 4, 'text' => "Bonjour, pourriez-vous m'indiquer les index à ajouter sur la table progression pour optimiser les requêtes étudiant ?"],
            ['from' => 't2', 'to' => 's5', 'days' => 3, 'text' => "Sarah, commence par indexer user_id et cours_id. On verra EXPLAIN en TP."],
            ['from' => 's9', 'to' => 't3', 'days' => 1, 'text' => "Bonjour, le pipeline CI échoue au build Docker : avez-vous un exemple de Dockerfile multi-stage ?"],
            ['from' => 't3', 'to' => 's9', 'days' => 0, 'text' => "Chloé, regarde le chapitre Docker du cours : j'ai mis un exemple complet avec cache Composer."],
            ['from' => 's10', 'to' => 's11', 'days' => 5, 'text' => "Zoé, tu viens à la réunion agile de demain ? On doit préparer la démo."],
            ['from' => 's11', 'to' => 's10', 'days' => 4, 'text' => "Oui Samir, j'ai presque fini les critères d'acceptation du sprint."],
            ['from' => 's6', 'to' => 's7', 'days' => 2, 'text' => "Emma, tu as testé fetch avec les headers CSRF pour le planning admin ?"],
            ['from' => 's7', 'to' => 's6', 'days' => 1, 'text' => "Oui Yanis, il faut passer X-CSRF-Token dans les requêtes POST JSON."],
            ['from' => 's13', 'to' => 't1', 'days' => 2, 'text' => "Bonjour, pour l'alternance L3, le planning affiche deux cours en même temps mardi matin — c'est normal ?"],
            ['from' => 't1', 'to' => 's13', 'days' => 1, 'text' => "Léa, non : signale-le à la scolarité via scolarite@jeai.fr, ils corrigeront le planning global."],
            ['from' => 's14', 'to' => 's15', 'days' => 3, 'text' => "Inès, tu as commencé le module sécurité ? Les exercices CSRF sont costauds."],
            ['from' => 's15', 'to' => 's14', 'days' => 2, 'text' => "Oui Noah, j'ai fini le chapitre validation — je t'envoie mes notes ce soir."],
            ['from' => 's16', 'to' => 't1', 'days' => 4, 'text' => "Professeur, puis-je déposer mon projet Symfony sur le forum ou uniquement par message ?"],
            ['from' => 's4', 'to' => 's1', 'days' => 6, 'text' => "Alice, tu viens à l'examen blanc de jeudi ? On révise ensemble mercredi ?"],
            ['from' => 's8', 'to' => 't2', 'days' => 5, 'text' => "Bonjour, le contrôle continu BDD est bien en salle Amphi A à 14h ?"],
            ['from' => 't2', 'to' => 's8', 'days' => 4, 'text' => "Louis, oui — pense à une calculatrice et une feuille de synthèse manuscrite autorisée."],
            ['from' => 's10', 'to' => 's12', 'days' => 7, 'text' => "Thomas, tu as vu l'annonce sur le forum pour les examens de juillet ?"],
        ];

        foreach ($messageDefs as $def) {
            $message = new Message();
            $message->setExpediteur($students[$def['from']] ?? $teachers[$def['from']]);
            $message->setDestinataire($students[$def['to']] ?? $teachers[$def['to']]);
            $message->setContenu($def['text']);
            $message->setSentAt($now->modify('-' . $def['days'] . ' days'));
            $manager->persist($message);
        }

        // --- ÉVALUATIONS (1 à 2 par cours) ---
        $evaluationDefs = [
            'symfony' => [
                ['titre' => 'QCM Symfony & Doctrine', 'questions' => "1. Quelle est la différence entre persist() et flush() ?\n2. Citez deux stratégies pour éviter le problème N+1.\n3. À quoi sert l'annotation JoinColumn(onDelete: 'CASCADE') ?"],
                ['titre' => 'TP — API REST sécurisée', 'questions' => "1. Créez un endpoint GET /api/cours protégé par ROLE_USER.\n2. Ajoutez la validation CSRF sur un formulaire de création.\n3. Documentez vos choix d'architecture en 5 lignes."],
            ],
            'db' => [
                ['titre' => 'Contrôle SQL & modélisation', 'questions' => "1. Normalisez un schéma avec utilisateurs, classes et cours (MCD).\n2. Écrivez une requête JOIN pour lister les cours d'un étudiant.\n3. Proposez un index pertinent et justifiez-le."],
            ],
            'docker' => [
                ['titre' => 'Quiz Docker & CI/CD', 'questions' => "1. Qu'est-ce qu'un build multi-stage ?\n2. Différence entre COPY et ADD dans un Dockerfile.\n3. Citez 3 étapes typiques d'un pipeline CI."],
                ['titre' => 'Projet — Pipeline complet', 'questions' => "1. Configurez un workflow qui lance les tests PHPUnit.\n2. Ajoutez un job de build d'image Docker.\n3. Décrivez votre stratégie de déploiement."],
            ],
            'security' => [
                ['titre' => 'Évaluation Sécurité web', 'questions' => "1. Expliquez le mécanisme CSRF et son implémentation Symfony.\n2. Quelle est la différence entre authentification et autorisation ?\n3. Donnez un exemple de faille XSS et sa mitigation."],
            ],
            'js' => [
                ['titre' => 'Contrôle JavaScript moderne', 'questions' => "1. Différence entre Promise et async/await.\n2. Comment gérer une erreur réseau avec fetch() ?\n3. Écrivez une fonction qui parse une réponse JSON en toute sécurité."],
            ],
            'agile' => [
                ['titre' => 'Évaluation Gestion de projet', 'questions' => "1. Définissez « Definition of Done ».\n2. Quels sont les rôles dans Scrum ?\n3. Rédigez 3 user stories pour une messagerie interne."],
            ],
        ];

        foreach ($evaluationDefs as $courseKey => $evaluations) {
            $course = $courses[$courseKey];
            $creator = $course->getCreatedBy();

            foreach ($evaluations as $evalData) {
                $evaluation = new Evaluation();
                $evaluation->setTitre($evalData['titre']);
                $evaluation->setQuestions($evalData['questions']);
                $evaluation->setCours($course);
                $evaluation->setCreatedBy($creator);
                $manager->persist($evaluation);
            }
        }

        $manager->flush();
    }

    private function createUser(string $email, string $nom, string $prenom, array $roles, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setDerniereConnexion(new \DateTimeImmutable('-' . random_int(1, 14) . ' days'));
        return $user;
    }
}

