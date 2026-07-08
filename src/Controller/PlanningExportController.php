<?php



namespace App\Controller;



use App\Entity\Calendrier;

use App\Entity\User;

use App\Repository\CalendrierRepository;

use Dompdf\Dompdf;

use Dompdf\Options;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

use Twig\Environment;



class PlanningExportController extends AbstractController

{

    #[Route('/planning/export/ics', name: 'planning_export_ics')]

    #[IsGranted('ROLE_USER')]

    public function exportIcs(Request $request, CalendrierRepository $calendrierRepository): Response

    {

        $events = $this->resolveEvents($request, $calendrierRepository);

        $ics = $this->buildIcs($events);



        return new Response($ics, 200, [

            'Content-Type' => 'text/calendar; charset=utf-8',

            'Content-Disposition' => 'attachment; filename="jeai-planning.ics"',

        ]);

    }



    #[Route('/planning/export/pdf', name: 'planning_export_pdf')]

    #[IsGranted('ROLE_USER')]

    public function exportPdf(

        Request $request,

        CalendrierRepository $calendrierRepository,

        Environment $twig,

    ): Response {

        /** @var User $user */

        $user = $this->getUser();

        [$start, $end] = $this->resolveDateRange($request);

        $events = $this->resolveEvents($request, $calendrierRepository);

        $days = $this->groupEventsByDay($events, $start, $end);



        $html = $twig->render('planning/export_pdf.html.twig', [

            'user' => $user,

            'days' => $days,

            'periodLabel' => $this->formatPeriodLabel($start, $end),

            'generatedAt' => new \DateTime(),

            'typeLabels' => [

                'cours' => 'Cours',

                'examen' => 'Examen',

                'reunion' => 'Réunion',

                'autre' => 'Autre',

            ],

        ]);



        $options = new Options();

        $options->set('isRemoteEnabled', false);

        $options->set('defaultFont', 'DejaVu Sans');



        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();



        $filename = sprintf('jeai-planning-%s.pdf', $start->format('Y-m-d'));



        return new Response($dompdf->output(), 200, [

            'Content-Type' => 'application/pdf',

            'Content-Disposition' => 'attachment; filename="' . $filename . '"',

        ]);

    }



    /**

     * @return Calendrier[]

     */

    private function resolveEvents(Request $request, CalendrierRepository $calendrierRepository): array

    {

        /** @var User $user */

        $user = $this->getUser();

        [$start, $end] = $this->resolveDateRange($request);



        return $calendrierRepository->findForUserScope(

            $user,

            $start,

            $end,

            $this->parseList($request->query->get('type')),

            $this->parseIntList($request->query->get('classe')),

            $this->parseIntList($request->query->get('enseignant')),

            $this->parseIntList($request->query->get('cours')),

        );

    }



    /**

     * @return array{0: \DateTime, 1: \DateTime}

     */

    private function resolveDateRange(Request $request): array

    {

        try {

            $start = $request->query->get('start')

                ? new \DateTime($request->query->get('start'))

                : (new \DateTime('monday this week'))->setTime(0, 0, 0);

            $end = $request->query->get('end')

                ? new \DateTime($request->query->get('end'))

                : (clone $start)->modify('+7 days');

        } catch (\Exception) {

            $start = (new \DateTime('monday this week'))->setTime(0, 0, 0);

            $end = (clone $start)->modify('+7 days');

        }



        return [$start, $end];

    }



    /**

     * @param Calendrier[] $events

     *

     * @return array<string, array{label: string, events: Calendrier[]}>

     */

    private function groupEventsByDay(array $events, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array

    {

        $days = [];

        $current = \DateTime::createFromInterface($rangeStart)->setTime(0, 0, 0);

        $lastDay = \DateTime::createFromInterface($rangeEnd)->modify('-1 second')->setTime(0, 0, 0);



        while ($current <= $lastDay) {

            $key = $current->format('Y-m-d');

            $days[$key] = [

                'label' => $this->formatDayLabel($current),

                'events' => [],

            ];

            $current->modify('+1 day');

        }



        foreach ($events as $event) {

            $eventStart = $event->getDateDebut();

            if ($eventStart === null) {

                continue;

            }



            $key = $eventStart->format('Y-m-d');

            if (isset($days[$key])) {

                $days[$key]['events'][] = $event;

            }

        }



        return $days;

    }



    private function formatDayLabel(\DateTimeInterface $date): string

    {

        $weekdays = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

        $months = [

            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',

            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',

            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',

        ];



        $weekday = $weekdays[(int) $date->format('w')];

        $month = $months[(int) $date->format('n')];



        return sprintf(

            '%s %d %s %s',

            ucfirst($weekday),

            (int) $date->format('j'),

            $month,

            $date->format('Y'),

        );

    }



    private function formatPeriodLabel(\DateTimeInterface $start, \DateTimeInterface $end): string

    {

        $endDisplay = \DateTime::createFromInterface($end)->modify('-1 second');

        $months = [

            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',

            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',

            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',

        ];



        if ($start->format('Y-m') === $endDisplay->format('Y-m')) {

            return sprintf(

                '%d – %d %s %s',

                (int) $start->format('j'),

                (int) $endDisplay->format('j'),

                $months[(int) $start->format('n')],

                $start->format('Y'),

            );

        }



        if ($start->format('Y') === $endDisplay->format('Y')) {

            return sprintf(

                '%d %s – %d %s %s',

                (int) $start->format('j'),

                $months[(int) $start->format('n')],

                (int) $endDisplay->format('j'),

                $months[(int) $endDisplay->format('n')],

                $start->format('Y'),

            );

        }



        return sprintf(

            '%d %s %s – %d %s %s',

            (int) $start->format('j'),

            $months[(int) $start->format('n')],

            $start->format('Y'),

            (int) $endDisplay->format('j'),

            $months[(int) $endDisplay->format('n')],

            $endDisplay->format('Y'),

        );

    }



    /**

     * @param Calendrier[] $events

     */

    private function buildIcs(array $events): string

    {

        $lines = [

            'BEGIN:VCALENDAR',

            'VERSION:2.0',

            'PRODID:-//MERJ//Planning//FR',

            'CALSCALE:GREGORIAN',

            'METHOD:PUBLISH',

        ];



        foreach ($events as $event) {

            $start = $event->getDateDebut();

            $end = $event->getDateFin() ?? (clone $start)->modify('+2 hours');

            if ($start === null) {

                continue;

            }



            $lines[] = 'BEGIN:VEVENT';

            $lines[] = 'UID:jeai-event-' . $event->getId() . '@jeai.fr';

            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');

            $lines[] = 'DTSTART:' . $start->format('Ymd\THis');

            $lines[] = 'DTEND:' . $end->format('Ymd\THis');

            $lines[] = 'SUMMARY:' . $this->escapeIcs($event->getTitre() ?? '');

            if ($event->getLieu()) {

                $lines[] = 'LOCATION:' . $this->escapeIcs($event->getLieu());

            }

            if ($event->getDescription()) {

                $lines[] = 'DESCRIPTION:' . $this->escapeIcs($event->getDescription());

            }

            $lines[] = 'END:VEVENT';

        }



        $lines[] = 'END:VCALENDAR';



        return implode("\r\n", $lines) . "\r\n";

    }



    private function escapeIcs(string $value): string

    {

        return str_replace(["\r", "\n", ',', ';'], ['', '\\n', '\\,', '\\;'], $value);

    }



    /**

     * @return string[]

     */

    private function parseList(?string $value): array

    {

        if ($value === null || $value === '') {

            return [];

        }



        return array_values(array_filter(array_map('trim', explode(',', $value))));

    }



    /**

     * @return int[]

     */

    private function parseIntList(?string $value): array

    {

        return array_map('intval', $this->parseList($value));

    }

}


