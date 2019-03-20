<?php
namespace App\Entity;

use App\Manager\AbstractManager;

/**
 * Interface ManagerAwareEntityInterface
 *
 * @package App\Entity
 * @see MappingListener::postLoad()
 */
interface ManagerAwareEntityInterface {
    public function setManager(AbstractManager $manager);
}