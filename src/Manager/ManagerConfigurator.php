<?php
namespace App\Manager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ManagerConfigurator
 *
 * @package App\Manager
 */
class ManagerConfigurator {
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * ManagerConfigurator constructor.
     *
     * @param RegistryInterface $doctrine
     * @param ValidatorInterface $validator
     */
    public function __construct(RegistryInterface $doctrine, ValidatorInterface $validator) {
        $this->doctrine = $doctrine;
        $this->validator = $validator;
    }

    /**
     * @param AbstractManager $manager
     */
    public function configure(AbstractManager $manager) {
        $manager->setDoctrine($this->doctrine);
        $manager->setValidator($this->validator);
    }
}