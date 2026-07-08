<?php

namespace App\Service;

use App\Entity\Classe;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\CalendrierRepository;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\EvaluationRepository;
use App\Repository\ForumPostRepository;
use App\Repository\MessageRepository;
use App\Repository\ModuleRepository;
use App\Repository\NoteRepository;
use App\Repository\ProgressionRepository;
use App\Repository\UserRepository;
use App\Security\UserRole;

class AdminDashboardAnalyticsService
{
    private const ACTIVITY_DAYS = 7;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ClasseRepository $classeRepository,
        private readonly CoursRepository $coursRepository,
        private readonly MessageRepository $messageRepository,
        private readonly ForumPostRepository $forumPostRepository,
        private readonly CalendrierRepository $calendrierRepository,
        private readonly NoteRepository $noteRepository,
        private readonly ModuleRepository $moduleRepository,
        private readonly ProgressionRepository $progressionRepository,
        private readonly EvaluationRepository $evaluationRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSuperAdmin(): array
    {
        $roleCounts = [
            'Super admin' => count($this->userRepository->findByRole(UserRole::SUPER_ADMIN)),
            'Admin école' => count($this->userRepository->findByRole(UserRole::ADMIN_ECOLE)),
            'Enseignants' => count($this->userRepository->findByRole(UserRole::ENSEIGNANT)),
            'Étudiants' => count($this->userRepository->findByRole(UserRole::ETUDIANT)),
        ];

        return [
            'stats' => [
                'totalClasses' => $this->classeRepository->count([]),
                'totalEtudiants' => $roleCounts['Étudiants'],
                'totalEnseignants' => $roleCounts['Enseignants'],
                'totalCours' => $this->coursRepository->count([]),
                'totalModules' => $this->moduleRepository->count([]),
                'totalMessages' => $this->messageRepository->count([]),
                'totalForumPosts' => $this->forumPostRepository->count([]),
                'totalPlanningEvents' => $this->calendrierRepository->count([]),
                'totalNotes' => $this->noteRepository->count([]),
                'totalProgressions' => $this->progressionRepository->count([]),
                'totalEvaluations' => $this->evaluationRepository->count([]),
                'unreadMessages' => $this->messageRepository->countAllUnread(),
                'totalUsers' => array_sum($roleCounts),
            ],
            'charts' => [
                'roles' => $this->buildRoleChart($roleCounts),
                'studentsByClass' => $this->buildStudentsByClassChart(),
                'activity' => $this->buildPlatformActivitySeries(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSchoolAdmin(): array
    {
        $students = $this->userRepository->findByRole(UserRole::ETUDIANT);
        $allNotes = $this->noteRepository->findAllOrdered();
        $globalAverage = $this->computeAverageOn20($allNotes);
        $passRate = $this->computePassRate($allNotes);
        $classSummaries = $this->buildClassSummaries();
        $studentRankings = $this->buildStudentRankings($students);

        return [
            'stats' => [
                'totalClasses' => $this->classeRepository->count([]),
                'totalEtudiants' => count($students),
                'totalEnseignants' => count($this->userRepository->findByRole(UserRole::ENSEIGNANT)),
                'totalCours' => $this->coursRepository->count([]),
                'totalNotes' => count($allNotes),
                'averageGrade' => $globalAverage,
                'passRate' => $passRate,
                'totalProgressions' => $this->progressionRepository->count([]),
                'totalEvaluations' => $this->evaluationRepository->count([]),
                'totalPlanningEvents' => $this->calendrierRepository->count([]),
            ],
            'charts' => [
                'studentsByClass' => $this->buildStudentsByClassChart(),
                'gradesByClass' => $this->buildGradesByClassChart($classSummaries),
                'gradeDistribution' => $this->buildGradeDistributionChart($allNotes),
                'notesActivity' => $this->buildNotesActivitySeries(),
            ],
            'classSummaries' => $classSummaries,
            'topStudents' => array_slice($studentRankings, 0, 5),
            'studentsToWatch' => array_slice(array_reverse($studentRankings), 0, 5),
        ];
    }

    /**
     * @param array<string, int> $roleCounts
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildRoleChart(array $roleCounts): array
    {
        return [
            'labels' => array_keys($roleCounts),
            'values' => array_values($roleCounts),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildStudentsByClassChart(): array
    {
        $labels = [];
        $values = [];

        foreach ($this->classeRepository->findBy([], ['nom' => 'ASC']) as $classe) {
            $labels[] = $classe->getNom() ?? 'Classe';
            $values[] = $classe->getEtudiants()->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: list<string>, messages: list<int>, forum: list<int>, logins: list<int>}
     */
    private function buildPlatformActivitySeries(): array
    {
        $since = (new \DateTimeImmutable('today'))->modify('-' . (self::ACTIVITY_DAYS - 1) . ' days');
        $dayKeys = $this->buildDayKeys($since);
        $labels = $this->buildDayLabels($since);

        return [
            'labels' => $labels,
            'messages' => $this->fillDailyCounts($this->messageRepository->countGroupedByDaySince($since), $dayKeys),
            'forum' => $this->fillDailyCounts($this->forumPostRepository->countGroupedByDaySince($since), $dayKeys),
            'logins' => $this->fillDailyCounts($this->userRepository->countLoginsGroupedByDaySince($since), $dayKeys),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildNotesActivitySeries(): array
    {
        $since = (new \DateTimeImmutable('today'))->modify('-' . (self::ACTIVITY_DAYS - 1) . ' days');
        $dayKeys = $this->buildDayKeys($since);

        return [
            'labels' => $this->buildDayLabels($since),
            'values' => $this->fillDailyCounts($this->noteRepository->countGroupedByDaySince($since), $dayKeys),
        ];
    }

    /**
     * @param list<array{name: string, students: int, average: float|null, notesCount: int}> $classSummaries
     * @return array{labels: list<string>, values: list<float>}
     */
    private function buildGradesByClassChart(array $classSummaries): array
    {
        $labels = [];
        $values = [];

        foreach ($classSummaries as $summary) {
            if ($summary['average'] === null) {
                continue;
            }
            $labels[] = $summary['name'];
            $values[] = $summary['average'];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param Note[] $notes
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildGradeDistributionChart(array $notes): array
    {
        $buckets = [
            'Insuffisant (<10)' => 0,
            'Passable (10-12)' => 0,
            'Assez bien (12-14)' => 0,
            'Bien (14-16)' => 0,
            'Très bien (≥16)' => 0,
        ];

        foreach ($notes as $note) {
            $score = $this->noteRepository->scoreOn20($note);
            if ($score < 10) {
                ++$buckets['Insuffisant (<10)'];
            } elseif ($score < 12) {
                ++$buckets['Passable (10-12)'];
            } elseif ($score < 14) {
                ++$buckets['Assez bien (12-14)'];
            } elseif ($score < 16) {
                ++$buckets['Bien (14-16)'];
            } else {
                ++$buckets['Très bien (≥16)'];
            }
        }

        return [
            'labels' => array_keys($buckets),
            'values' => array_values($buckets),
        ];
    }

    /**
     * @return list<array{name: string, students: int, average: float|null, notesCount: int}>
     */
    private function buildClassSummaries(): array
    {
        $summaries = [];

        foreach ($this->classeRepository->findBy([], ['nom' => 'ASC']) as $classe) {
            $notes = $this->noteRepository->findByClasse($classe);
            $summaries[] = [
                'name' => $classe->getNom() ?? 'Classe',
                'students' => $classe->getEtudiants()->count(),
                'average' => $this->computeAverageOn20($notes),
                'notesCount' => count($notes),
            ];
        }

        return $summaries;
    }

    /**
     * @param User[] $students
     * @return list<array{id: int, name: string, className: string, average: float, notesCount: int}>
     */
    private function buildStudentRankings(array $students): array
    {
        $rankings = [];

        foreach ($students as $student) {
            $notes = $this->noteRepository->findByEtudiant($student);
            if ($notes === []) {
                continue;
            }

            $rankings[] = [
                'id' => $student->getId(),
                'name' => trim(($student->getPrenom() ?? '') . ' ' . ($student->getNom() ?? '')),
                'className' => $this->resolveStudentClassName($student),
                'average' => $this->computeAverageOn20($notes),
                'notesCount' => count($notes),
            ];
        }

        usort($rankings, static fn (array $a, array $b): int => $b['average'] <=> $a['average']);

        return $rankings;
    }

    private function resolveStudentClassName(User $student): string
    {
        $classes = $student->getClasses();
        if ($classes->isEmpty()) {
            return 'Non assigné';
        }

        return $classes->first()?->getNom() ?? 'Classe';
    }

    /**
     * @param Note[] $notes
     */
    private function computeAverageOn20(array $notes): ?float
    {
        if ($notes === []) {
            return null;
        }

        $sum = 0.0;
        foreach ($notes as $note) {
            $sum += $this->noteRepository->scoreOn20($note);
        }

        return round($sum / count($notes), 2);
    }

    /**
     * @param Note[] $notes
     */
    private function computePassRate(array $notes): float
    {
        if ($notes === []) {
            return 0.0;
        }

        $passed = 0;
        foreach ($notes as $note) {
            if ($this->noteRepository->scoreOn20($note) >= 10) {
                ++$passed;
            }
        }

        return round(($passed / count($notes)) * 100, 1);
    }

    /**
     * @return list<string>
     */
    private function buildDayKeys(\DateTimeImmutable $since): array
    {
        $keys = [];
        for ($i = 0; $i < self::ACTIVITY_DAYS; ++$i) {
            $keys[] = $since->modify('+' . $i . ' days')->format('Y-m-d');
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function buildDayLabels(\DateTimeImmutable $since): array
    {
        $labels = [];
        for ($i = 0; $i < self::ACTIVITY_DAYS; ++$i) {
            $labels[] = $this->formatDayLabel($since->modify('+' . $i . ' days'));
        }

        return $labels;
    }

    /**
     * @param array<string, int> $counts
     * @param list<string>       $dayKeys
     * @return list<int>
     */
    private function fillDailyCounts(array $counts, array $dayKeys): array
    {
        $series = [];
        foreach ($dayKeys as $key) {
            $series[] = $counts[$key] ?? 0;
        }

        return $series;
    }

    private function formatDayLabel(\DateTimeImmutable $day): string
    {
        $jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];

        return $jours[(int) $day->format('w')] . ' ' . $day->format('d/m');
    }
}
