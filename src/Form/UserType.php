<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('email')
            ->add('passwordHash')
            ->add('roleId')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('isVerified')
            ->add('phone')
            ->add('verificationCode')
            ->add('googleAccount')
            ->add('lastLogin')
            ->add('faceRegistered')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
