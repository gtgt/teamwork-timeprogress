<?php
namespace App\Entity;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Month
 *
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class Month implements ManagerAwareEntityInterface {
    use ManagerAwareEntityTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="date")
     * @Required()
     *
     * @var \DateTimeInterface
     */
    protected $startDate;

    /**
     * @ORM\Column(type="date")
     *
     * @var \DateTimeInterface
     */
    protected $endDate;

    /**
     * Month constructor.
     *
     * @throws \Exception
     */
    public function __construct() {
        $this->startDate = new \DateTime('first day of this month');
        $this->endDate = new \DateTime('last day of this month');
        $this->setTime();
    }

    public function __toString() {
        return strftime('%Y %B', $this->startDate->getTimestamp());
    }

    /**
     * @ORM\PrePersist()
     */
    public function generateId(): void {
        $this->id = (int)$this->startDate->format('Ym');
    }

    /**
     * @ORM\PostLoad()
     */
    public function setTime(): void {
        $this->startDate->setTime(0, 0, 0);
        $this->endDate->setTime(23, 59, 59);
    }
    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartDate(): \DateTimeInterface {
        return $this->startDate;
    }

    /**
     * @param \DateTimeInterface $startDate
     */
    public function setStartDate(\DateTimeInterface $startDate): void {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEndDate(): \DateTimeInterface {
        return $this->endDate;
    }

    /**
     * @param \DateTimeInterface $endDate
     */
    public function setEndDate(\DateTimeInterface $endDate): void {
        $this->endDate = $endDate;
    }
}