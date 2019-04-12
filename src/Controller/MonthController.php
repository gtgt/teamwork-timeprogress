<?php

namespace App\Controller;

use App\Entity\Month;
use App\Form\MonthType;
use App\Manager\MonthManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MonthController extends AbstractController {
    /**
     * @var MonthManager
     */
    protected $manager;

    /**
     * DashboardController constructor.
     *
     * @param MonthManager $manager
     */
    public function __construct(MonthManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @Route("/month", name="month")
     *
     * @Template(template="month/index.html.twig")
     */
    public function index() {
        return [
            'months' => $this->manager->findAll(),
        ];
    }

    /**
     * @Route("/month/new", name="month_new")
     * @Route("/month/{id}", name="month_edit")
     *
     * @Template(template="month/form.html.twig")
     *
     * @param Request $request
     * @param Month|null $month
     *
     * @return array|Response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function monthForm(Request $request, ?Month $month) {
        if ($month instanceof Month) {
            $this->manager->injectDependency($month);
        } else {
            $month = $this->manager->createNew();
        }
        $form = $this->createForm(MonthType::class, $month);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // not really need since $month is reference and gets updates
            //$month = $form->getData();
            $this->manager->save($month, MonthManager::FLAG_VALIDATE | MonthManager::FLAG_AUTO_FLUSH);
            return $this->redirectToRoute('month', ['id' => $month->getId()]);
        }
        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/month/delete/{id}", name="month_delete")
     *
     * @param Request $request
     * @param Month|null $month
     *
     * @return array|Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(Request $request, Month $month) {
        $this->manager->delete($month);
        return $this->redirectToRoute('month');
    }
}
