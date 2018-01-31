<?php

/*
 * This file is part of the ForciMenuBuilderClientBundle package.
 *
 * Copyright (c) Forci Web Consulting Ltd.
 *
 * Author Martin Kirilov <martin@forci.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Forci\Bundle\MenuBuilderClient\Form\Menu\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class NameType extends AbstractType {

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'label' => 'Display name - this will be displayed as the text of the generated link',
            'attr' => [
                'placeholder' => 'Display name'
            ],
            'constraints' => [
                new NotBlank()
            ]
        ]);
    }

    public function getParent() {
        return TextType::class;
    }

    public function getBlockPrefix() {
        return 'menu_builder_client_bundle_form_menu_item_name';
    }
}
