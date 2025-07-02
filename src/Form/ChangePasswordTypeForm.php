<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe actuel',
                    ]),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez entrer un mot de passe',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Répéter le nouveau mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
