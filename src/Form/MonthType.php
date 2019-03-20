<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class MonthType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('startDate', null, [
                'widget' => 'single_text',
                'datepicker' => true,
            ])
            ->add('endDate', null, [
                'widget' => 'single_text',
                'datepicker' => true,
            ])
            ->add('save', SubmitType::class, [
                'icon' => 'save'
            ]);
    }
}