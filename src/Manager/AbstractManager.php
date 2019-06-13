<?php
namespace App\Manager;

use App\Entity\ManagerAwareEntityInterface;
use App\Exception\InvalidManagerArgumentException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AbstractManager
 *
 * @package App\Manager
 *
 * @property EntityManager $em Use getEm() instead!
 * @property EntityManager $_em Use getEm() instead!
 */
abstract class AbstractManager {

    /**
     * Validate before save
     */
    public const FLAG_VALIDATE = 1;
    /**
     * Merge before save (Doctrine UnitOfWork)
     */
    public const FLAG_MERGE = 2;
    /**
     * Flush Doctrine UnitOfWork after save
     */
    public const FLAG_AUTO_FLUSH = 4;

    /**
     * When FLAG_VALIDATE is used, you can define which validation groups you want validate against
     */
    public const OPTION_VALIDATION_GROUPS = 'validation_groups';

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var ObjectManager|EntityManager
     */
    private $_em;

    /**
     * @var ObjectRepository|EntityRepository
     */
    private $repository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $entityAlias;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine(Registry $doctrine) {
        $this->doctrine = $doctrine;
    }

    /**
     * @return ObjectManager|EntityManager
     */
    protected function getEm() {
        if (null === $this->_em) {
            $entityClass = $this->getEntityClass();
            $em = $this->doctrine->getManagerForClass($entityClass);
            if (null === $em) {
                throw new InvalidConfigurationException(sprintf('%s entity has no doctrine entity manager.', $entityClass));
            }
            $this->_em = $em;
        }
        return $this->_em;
    }

    /** @noinspection MagicMethodsValidityInspection */
    /**
     * @deprecated Compatibilty for $this->em and $this->_em, but use getEm().
     *
     * @param $name
     *
     * @return ObjectManager|EntityManager
     */
    public function __get($name) {
        switch ($name) {
            case 'em':
            case '_em':
                return $this->getEm();
        }
    }

    protected function getRepository() {
        if (null === $this->repository) {
            if (null !== $this->_em) {
                $this->repository = $this->_em->getRepository($this->getEntityClass());
            } else {
                $this->repository = $this->doctrine->getRepository($this->getEntityClass());
            }
        }
        return $this->repository;
    }


    /**
     * Override this to link to custom entity class
     *
     * @return string
     */
    public function getEntityClass(): string {
        if (!$this->entityClass) {
            $this->entityClass = preg_replace('/\\\(?:Manager|Services)\\\([a-z]+)Manager$/i', '\\Entity\\\$1', static::class);
        }
        return $this->entityClass;
    }

    /**
     * Override this to set a custom entity alias
     */
    public function getEntityAlias() {
        if (!$this->entityAlias) {
            $entityClass = $this->getEntityClass();
            $this->entityAlias = strtolower(preg_replace('/[^A-Z]/', '', substr($entityClass, strrpos($entityClass, '\\') + 1)));
        }
        return $this->entityAlias;
    }

    /**
     * Check if entity can be handled by repository of the manager
     *
     * @param object $entity
     */
    protected function checkEntityClass($entity): void {
        if (\get_class($entity) !== $this->getEntityClass()) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = end($caller);
            throw InvalidManagerArgumentException::invalidObject($caller['class'].$caller['type'].$caller['function'] ?? __CLASS__, $this->getEntityClass(), $entity);
        }
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator): void {
        $this->validator = $validator;
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(string $alias = null, string $indexBy = null): QueryBuilder {
        if (method_exists($this->getRepository(), 'createQueryBuilder')) {
            if (null === $alias) {
                $alias = $this->getEntityAlias();
            }
            return $this->getRepository()->createQueryBuilder($alias, $indexBy);
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Creates a new Query instance based on a predefined metadata named query.
     *
     * @param string $queryName
     *
     * @return Query
     */
    public function createNamedQuery(string $queryName): Query {
        if (method_exists($this->getRepository(), 'createNamedQuery')) {
            return $this->getRepository()->createNamedQuery($queryName);
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Creates a native SQL query.
     *
     * @param string $queryName
     *
     * @return NativeQuery
     */
    public function createNativeNamedQuery(string $queryName): NativeQuery {
        if (method_exists($this->getRepository(), 'clear')) {
            return $this->getRepository()->createNativeNamedQuery($queryName);
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear() {
        if (method_exists($this->getRepository(), 'clear')) {
            $this->getRepository()->clear();
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     * @param int|null $lockMode One of the \Doctrine\DBAL\LockMode::* constants
     *                              or NULL if no specific lock mode should be used
     *                              during the search. (Works only when repository is an EntityRepositotry)
     * @param int|null $lockVersion The lock version. (Works only when repository is an EntityRepositotry)
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findById($id, $lockMode = null, $lockVersion = null) {
        return $this->getRepository()->find($id, $lockMode, $lockVersion);
    }

    /**
     * Finds all entities in the repository.
     *
     * @return array The entities.
     */
    public function findAll(): array {
        return $this->getRepository()->findAll();
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array The objects.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param array $criteria
     * @param array|null $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = null) {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @param Criteria $criteria
     *
     * @return Collection
     */
    public function matching(Criteria $criteria): Collection {
        if (method_exists($this->getRepository(), 'matching')) {
            return $this->getRepository()->matching($criteria);
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @param array $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     */
    public function count(array $criteria = []): int {
        if (method_exists($this->getRepository(), 'count')) {
            return $this->getRepository()->count($criteria);
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Adds support for magic method calls.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed The returned value from the resolved method.
     *
     * @throws \BadMethodCallException If the method called is invalid
     * @throws ORMException
     */
    public function __call($method, $arguments) {
        if (method_exists($this->getRepository(), '__call')) {
            return $this->getRepository()->__call($method, $arguments);
        }
        throw new \BadMethodCallException(sprintf('%s not implemented.', __METHOD__));
    }

    /**
     * Checks database connection by ping and reconnects if necessary
     */
    public function checkConnection() {
        $em = $this->getEm();
        if ($em->getConnection()->ping() === false) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @param object $entity
     * @param int $flags Can be: self::FLAG_VALIDATE, self::FLAG_MERGE, self::FLAG_AUTO_SAVE
     * @param array $options Keys are: self::OPTION_VALIDATION_GROUPS
     *
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save($entity, $flags = self::FLAG_AUTO_FLUSH, $options = []) {
        $this->checkEntityClass($entity);
        if ($flags & self::FLAG_VALIDATE) {
            $this->validator->validate($entity, null, $options[self::OPTION_VALIDATION_GROUPS] ?? null);
        }
        if ($flags & self::FLAG_MERGE) {
            $this->getEm()->merge($entity);
        } else {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->getEm()->persist($entity);
        }
        if ($flags & self::FLAG_AUTO_FLUSH) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->getEm()->flush();
        }
    }

    /**
     * @param object $entity
     * @param int $flags
     *
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($entity, $flags = self::FLAG_AUTO_FLUSH): void {
        $this->checkEntityClass($entity);
        $this->getEm()->remove($entity);
        if ($flags & self::FLAG_AUTO_FLUSH) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->getEm()->flush();
        }
    }

    /**
     * @param callable $func Gets EntityManager by argument.
     *
     * @return bool|mixed
     * @throws \Throwable
     */
    public function transactional(callable $func) {
        return $this->getEm()->transactional($func);
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function injectDependency($entity): void {
        if ($entity instanceof ManagerAwareEntityInterface) {
            $entity->setManager($this);
        }
    }

    /**
     * @return object
     */
    public function createNew() {
        $class = $this->getEntityClass();
        $entity = new $class();
        $this->injectDependency($entity);
        return $entity;
    }

    /**
     * Determines that an enity is new or not (by UnitOfWork state)
     *
     * @param object $entity
     *
     * @return bool
     */
    final public function isNewEntity($entity) {
        return $this->getEm()->getUnitOfWork()->getEntityState($entity) === UnitOfWork::STATE_NEW;
    }

    /**
     * De-registers (detach) an entity from UnitOfWork
     *
     * @param object $entity
     */
    final public function detachEntity($entity) {
        $this->getEm()->detach($entity);
    }

    /**
     * Registers (attach) an entity into UnitOfWork
     *
     * @param object $entity
     * @param array $data
     */
    protected function registerManagedEntity($entity, array $data = []) {
        $this->getEm()->getUnitOfWork()->registerManaged($entity, ['id' => $entity->getId()], !empty($data) ? $data : $entity->toArray());
    }

    /**
     * Clones an (uow attached) entity into a non-uow attached object
     *
     * @param object $entity
     *
     * @return object
     */
    final public function cloneEntity($entity) {

        $this->checkEntityClass($entity);

        // first, detach the original
        $oldData = $this->getEm()->getUnitOfWork()->getOriginalEntityData($entity);
        $this->detachEntity($entity);

        // clone
        $newEntity = clone $entity;
        $newEntity->resetId();

        // re-attach the original
        $this->registerManagedEntity($entity, $oldData);

        return $this->doCloneEntity($entity, $newEntity);
    }

    /**
     * A lemasoláshoz egy hook amit felül lehet írni.
     *
     * @param object $oldEntity
     * @param object $newEntity
     *
     * @return object
     */
    protected function doCloneEntity($oldEntity, $newEntity) {
        return $newEntity;
    }
}
