<?php


namespace App\Manager;


use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ManagerRegistry extends ArrayCollection implements ContainerAwareInterface {
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    private $initialized = false;

    protected function init() {
        foreach ($this as $key => $value) {
            if (\is_string($value)) {
                $this->offsetUnset($key);
                /** @var AbstractManager $manager */
                $manager = $this->container->get($value);
                $this->checkValue($manager);
                $this->set($manager->getEntityClass(), $manager);
            }
        }
        $this->initialized = true;
    }

    /**
     * @param $value
     */
    protected function checkValue($value): void {
        if (!$value instanceof AbstractManager) {
            throw new \InvalidArgumentException(sprintf('Only %s type managers can be registered into %s.', \get_class($value), static::class));
        }
    }

    /**
     * @param AbstractManager $element
     *
     * @return void
     */
    public function add($element) {
        $this->initialized = false;
        parent::add($element);
    }

    /**
     * Load manager services (value) and associate with entity classes (key).
     * Used by init()
     *
     * @param $key
     * @param AbstractManager $value
     */
    public function set($key, $value) {
        $this->checkValue($value);
        parent::set($key, $value);
    }

    /**
     * @param string|object $classOrObject
     *
     * @return AbstractManager|null
     */
    public function getManagerForClass($classOrObject): ?AbstractManager {
        if (false === $this->initialized) {
            $this->init();
        }
        if (\is_object($classOrObject)) {
            $classOrObject = \get_class($classOrObject);
        }
        return $this->containsKey($classOrObject) ? $this->get($classOrObject) : NULL;
    }
}