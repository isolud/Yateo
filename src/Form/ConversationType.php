<?php

namespace App\Form;


use App\Entity\Conversation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConversationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recipients', EntityType::class, [
                'row_attr' => [
                    'class' => 'col-12'
                ],
                'label' => 'form.recipients',
                'class' => User::class,
                'choice_label' => 'displayusername',
                'multiple' => true,
                'required' => true,
                'mapped' => true
            ])
            ->add('subject', null , [
                'row_attr' => [
                    'class' => 'col-12'
                ],
                'label' => 'form.subject'
            ])
            ->add('firstMessage', PrivateMessageType::class, [
                'row_attr' => [
                    'class' => 'col-12'
                ],
                'attr' => [ 'class' => 'p-0' ]
                ,
                'label' => false
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Conversation::class,
            'attr' => ['class'=>'form-row col-12']
        ]);
    }
}
