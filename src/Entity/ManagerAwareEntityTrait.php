<?php
namespace App\Entity;

use App\Manager\AbstractManager;

trait ManagerAwareEntityTrait {
    /**
     * @var AbstractManager;
     */
    protected $manager;

    /**
     * @param AbstractManager $manager
     */
    public function setManager(AbstractManager $manager) {
        $this->manager = $manager;
    }
}