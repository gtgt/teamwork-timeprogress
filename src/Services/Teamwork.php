<?php
namespace App\Services;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use TeamWorkPm\Account;
use TeamWorkPm\Auth;
use TeamWorkPm\Exception;
use TeamWorkPm\Factory;
use TeamWorkPm\Me;
use TeamWorkPm\Project;
use TeamWorkPm\Response\Model;
use TeamWorkPm\Time;

class Teamwork {

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * Teamwork constructor.
     *
     * @param CacheItemPoolInterface $cache
     * @param string $teamworkToken
     */
    public function __construct(CacheItemPoolInterface $cache, string $teamworkToken) {
        $this->cache = $cache;
        Auth::set($teamworkToken);
    }


    /**
     * @return Model
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getAccount(): Model {
        $cacheItem = $this->cache->getItem('account');
        if (!$cacheItem->isHit()) {
            /** @var Account $account */
            $account = Factory::build('account');
            $cacheItem->set($account->get());
            $cacheItem->expiresAt(new \DateTimeImmutable('+4 hours'));
            $this->cache->saveDeferred($cacheItem);
        }
        return $cacheItem->get();
    }

    /**
     * @return Model
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getPerson(): Model {
        $cacheItem = $this->cache->getItem('person');
        if (!$cacheItem->isHit()) {
            /** @var Me $account */
            $account = Factory::build('me');
            $cacheItem->set($account->get());
            $cacheItem->expiresAt(new \DateTimeImmutable('+4 hours'));
            $this->cache->saveDeferred($cacheItem);
        }
        return $cacheItem->get();
    }

    /**
     * @return \ArrayObject
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getActiveProject(): \ArrayObject {
        $cacheItem = $this->cache->getItem('project');
        if (!$cacheItem->isHit()) {
            /** @var Project $project */
            $project = Factory::build('project');
            $cacheItem->set($project->getActive()[0] ?? null);
            $cacheItem->expiresAt(new \DateTimeImmutable('+4 hours'));
            $this->cache->saveDeferred($cacheItem);
        }
        return $cacheItem->get();
    }

    /**
     * @param \DateTimeInterface $fromDate
     * @param \DateTimeInterface $toDate
     *
     * @return Model
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Exception
     */
    public function getTime(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): Model {
        $now = new \DateTimeImmutable();
        $isThisMonth = $now < $toDate && $now > $fromDate;
        $cacheKey = 'time|'.$fromDate->format('Ymd').'|'.$toDate->format('Ymd');
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        /** @var Time $time */
        $time = Factory::build('time');
        $person = $this->getPerson();
        $timeEntries = $time->getAll(['userId' => $person->id, 'fromDate' => $fromDate->format('Ymd'), 'toDate' => $toDate->format('Ymd')]);

        $cacheItem->set($timeEntries);
        $cacheItem->expiresAt(new \DateTimeImmutable($isThisMonth ? '+2 minutes' : '+4 hours'));
        $this->cache->saveDeferred($cacheItem);

        return $timeEntries;
    }
}