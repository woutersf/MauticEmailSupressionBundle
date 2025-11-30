<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SupressionListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.supressionlist.form.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.supressionlist.form.name.help',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.supressionlist.name.notblank',
                    ]),
                ],
            ]
        );

        $builder->add(
            'segments',
            ChoiceType::class,
            [
                'label'      => 'mautic.supressionlist.form.segments',
                'label_attr' => ['class' => 'control-label'],
                'choices'    => $options['segment_choices'],
                'expanded'   => true,
                'multiple'   => true,
                'required'   => false,
                'mapped'     => false,
                'data'       => $options['selected_segments'],
            ]
        );

        $builder->add(
            'campaigns',
            ChoiceType::class,
            [
                'label'      => 'mautic.supressionlist.form.campaigns',
                'label_attr' => ['class' => 'control-label'],
                'choices'    => $options['campaign_choices'],
                'expanded'   => true,
                'multiple'   => true,
                'required'   => false,
                'mapped'     => false,
                'data'       => $options['selected_campaigns'],
            ]
        );

        $builder->add('buttons', FormButtonsType::class, [
            'apply_text' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \MauticPlugin\MauticEmailSupressionBundle\Entity\SuprList::class,
            'segment_choices' => [],
            'campaign_choices' => [],
            'selected_segments' => [],
            'selected_campaigns' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'supressionlist';
    }
}
