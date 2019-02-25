<?php

namespace App\Controller;

use App\Services\Teamwork;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController {
    protected const UNIT_PRICE = 7500;
    protected const UNIT = 1;
    protected const TARGET_ENOUGH = 650000;

    /**
     * @var Teamwork
     */
    protected $teamwork;

    /**
     * DashboardController constructor.
     *
     * @param Teamwork $teamwork
     */
    public function __construct(Teamwork $teamwork) {
        $this->teamwork = $teamwork;
    }


    /**
     * @param string $from
     * @param array $ignore
     *
     * @return int
     */
    protected static function getWorkingDays(string $from = 'first day of this month', array $ignore = []): int {
        $count = 0;
        $counter = strtotime($from);
        $month = (int)date('n', $counter);
        while ((int)date('n', $counter) === $month) {
            if ((int)date('N', $counter) < 6 && \in_array(date('w', $counter), $ignore, false) === false) {
                $count++;
            }
            $counter = strtotime('+1 day', $counter);
        }
        return $count;
    }

    /**
     * @param $monthStr
     *
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \TeamWorkPm\Exception
     */
    private function addPrevMonth($monthStr) {
        $hours = 0;
        foreach ($this->teamwork->getTime('first day of '.$monthStr, 'last day of '.$monthStr) as $logEntry) {
            $hours += ((int)$logEntry->hours) + ((int)$logEntry->minutes) / 60;
        }
        $workingHours = self::getWorkingDays('first day of '.$monthStr) * 8;
        $percent = round($hours / $workingHours * 100);
        $price = $hours / self::UNIT * self::UNIT_PRICE;
        $priceMax = $workingHours / self::UNIT * self::UNIT_PRICE;
        return [
            'title' => strftime('%Y %B', strtotime($monthStr)),
            'percent1' => $percent,
            'price1' => $price,
            'price_max' => $priceMax,
            'hours1' => $hours,
            'hours_max' => $workingHours,
        ];
    }


    /**
     * @Route("/", name="home")
     * @Route("/dashboard", name="dashboard")
     *
     * @Template(template="dashboard/index.html.twig")
     *
     * @throws \TeamWorkPm\Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function index() {
        $hours = 0;
        foreach ($this->teamwork->getTime() as $logEntry) {
            $hours += (int)$logEntry->hours + (int)$logEntry->minutes / 60;
        }
        $workingHoursAll = self::getWorkingDays() * 8;
        $workingHoursLeft = self::getWorkingDays('now') * 8;

        $percent1 = round($hours / $workingHoursAll * 100);
        $percent2 = 100 - round($workingHoursLeft / $workingHoursAll * 100) - $percent1;

        $price1 = $hours / self::UNIT * self::UNIT_PRICE;
        $priceMax = $workingHoursAll / self::UNIT * self::UNIT_PRICE;
        $price2 = $priceMax - $price1;
        $bars[] =  [
            'title' => strftime('%Y %B'),
            'percent1' => $percent1,
            'percent2' => $percent2,
            'price1' => $price1,
            'price2' => $price2,
            'price_max' => $priceMax,
            'hours1' => $hours,
            'hours2' => $workingHoursAll - $workingHoursLeft,
            'hours_max' => $workingHoursAll,
        ];

        $bars[] = $this->addPrevMonth('-1 month');
        $bars[] = $this->addPrevMonth('-2 month');


        return [
            'bars' => array_reverse($bars),
        ];
    }
}
