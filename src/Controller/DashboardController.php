<?php

namespace App\Controller;

use App\Entity\Month;
use App\Manager\MonthManager;
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
     * @var MonthManager
     */
    protected $manager;

    /**
     * DashboardController constructor.
     *
     * @param Teamwork $teamwork
     * @param MonthManager $manager
     */
    public function __construct(Teamwork $teamwork, MonthManager $manager) {
        $this->teamwork = $teamwork;
        $this->manager = $manager;
    }


    /**
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param array $ignoreDays
     *
     * @return int
     *
     * @throws \Exception
     */
    protected static function getWorkingHours(\DateTimeInterface $from, \DateTimeInterface $to, array $ignoreDays = []): int {
        $count = 0;
        $counter = $from instanceof \DateTimeImmutable ? new \DateTime('@'.$from->getTimestamp()) : clone $from;
        $counter->setTimezone($from->getTimezone());
        while ($counter < $to) {
            if ((int)$counter->format('N') < 6 && (int)$counter->format('G') >= 10 && (int)$counter->format('G') < 18 && \in_array($counter->format('w'), $ignoreDays, false) === false) {
                $count++;
            }
            $counter->add(new \DateInterval('PT1H'));
        }
        return $count;
    }

    /**
     * @param Month $month
     *
     * @return array
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \TeamWorkPm\Exception
     * @throws \Exception
     */
    private function addMonth(Month $month) {
        $fromDate = $month->getStartDate();
        $toDate = $month->getEndDate();
        $hours = 0;
        $isThisMonth = $fromDate->diff(new \DateTimeImmutable(), true)->m < 1;
        foreach ($this->teamwork->getTime($fromDate, $toDate) as $logEntry) {
            $hours += ((int)$logEntry->hours) + ((int)$logEntry->minutes) / 60;
        }
        $hours = round($hours, 2);
        $common = [
            'title' => (string)$month,
            'subtitle' => $fromDate->format('m.d').' - '.$toDate->format('m.d'),
        ];
        if ($isThisMonth) {
            $workingHoursAll = self::getWorkingHours($fromDate, $toDate);
            $workingHoursLeft = self::getWorkingHours(new \DateTimeImmutable('now'), $toDate);

            $percent1 = round($hours / $workingHoursAll * 100);
            $percent2 = 100 - round(($workingHoursLeft / $workingHoursAll) * 100) - $percent1;

            $price1 = $hours / self::UNIT * self::UNIT_PRICE;
            $priceMax = $workingHoursAll / self::UNIT * self::UNIT_PRICE;
            $price2 = ($workingHoursAll - $workingHoursLeft) / self::UNIT * self::UNIT_PRICE;
            return $common + [
                'percent1' => $percent1,
                'percent2' => $percent2,
                'price1' => $price1,
                'price2' => $price2,
                'price_max' => $priceMax,
                'hours1' => $hours,
                'hours2' => $workingHoursAll - $workingHoursLeft,
                'hours_max' => $workingHoursAll,
            ];
        } else {
            $workingHours = self::getWorkingHours($fromDate, $toDate);
            $percent = round($hours / $workingHours * 100);
            $price = $hours / self::UNIT * self::UNIT_PRICE;
            $priceMax = $workingHours / self::UNIT * self::UNIT_PRICE;
            return $common + [
                'percent1' => $percent,
                'price1' => $price,
                'price_max' => $priceMax,
                'hours1' => $hours,
                'hours_max' => $workingHours,
            ];
        }
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
        $bars = [];
        /** @var Month $month */
        foreach ($this->manager->findBy([], ['id' => 'ASC'], 5) as $month) {
            $bars[] = $this->addMonth($month);
        }


        return [
            'bars' => $bars,
        ];
    }
}
