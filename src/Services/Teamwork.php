<?php
namespace App\Services;

use Psr\Cache\CacheItemPoolInterface;

class Teamwork {

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * Teamwork constructor.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache) {
        $this->cache = $cache;
        \TeamWorkPm\Auth::set('twp_od3QG88QkAUJVQA3dMw6U6JO3oqG');
    }


    /**
     * @return \TeamWorkPm\Response\Model
     *
     * @throws \TeamWorkPm\Exception
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function getAccount(): \TeamWorkPm\Response\Model {
        $cacheItem = $this->cache->getItem('account');
        if (!$cacheItem->isHit()) {
            /** @var \TeamWorkPm\Account $account */
            $account = \TeamWorkPm\Factory::build('account');
            $cacheItem->set($account->get());
            $cacheItem->expiresAt(new \DateTimeImmutable('+4 hours'));
            $this->cache->saveDeferred($cacheItem);
        }
        return $cacheItem->get();
    }

    /**
     * @return \TeamWorkPm\Response\Model
     *
     * @throws \TeamWorkPm\Exception
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function getPerson(): \TeamWorkPm\Response\Model {
        $cacheItem = $this->cache->getItem('person');
        if (!$cacheItem->isHit()) {
            /** @var \TeamWorkPm\Me $account */
            $account = \TeamWorkPm\Factory::build('me');
            $cacheItem->set($account->get());
            $cacheItem->expiresAt(new \DateTimeImmutable('+4 hours'));
            $this->cache->saveDeferred($cacheItem);
        }
        return $cacheItem->get();
    }

    /**
     * @return \ArrayObject
     *
     * @throws \TeamWorkPm\Exception
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function getActiveProject(): \ArrayObject {
        $cacheItem = $this->cache->getItem('project');
        if (!$cacheItem->isHit()) {
            /** @var \TeamWorkPm\Project $project */
            $project = \TeamWorkPm\Factory::build('project');
            $cacheItem->set($project->getActive()[0] ?? null);
            $cacheItem->expiresAt(new \DateTimeImmutable('+4 hours'));
            $this->cache->saveDeferred($cacheItem);
        }
        return $cacheItem->get();
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return \TeamWorkPm\Response\Model
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \TeamWorkPm\Exception
     * @throws \Exception
     */
    public function getTime($from = 'first day of this month', $to = 'last day of this month'): \TeamWorkPm\Response\Model {
        $fromDate = new \DateTimeImmutable($from);
        $toDate = new \DateTimeImmutable($to);
        $isThisMonth = $fromDate->diff(new \DateTimeImmutable(), true)->m < 1;
        $cacheKey = 'time|'.$fromDate->format('Ymd').'|'.$toDate->format('Ymd');
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        /** @var \TeamWorkPm\Time $time */
        $time = \TeamWorkPm\Factory::build('time');
        $person = $this->getPerson();
        $timeEntries = $time->getAll(['userId' => $person->id, 'fromDate' => $fromDate->format('Ymd'), 'toDate' => $toDate->format('Ymd')]);

        $cacheItem->set($timeEntries);
        $cacheItem->expiresAt(new \DateTimeImmutable($isThisMonth ? '+2 minutes' : '+4 hours'));
        $this->cache->saveDeferred($cacheItem);

        return $timeEntries;
    }
}