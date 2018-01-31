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
use Symfony\Component\OptionsResolver\OptionsResolver;

class UrlType extends AbstractType {

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'label' => 'Please provide an external URL',
            'attr' => [
                'placeholder' => 'Please provide an external URL'
            ]
        ]);
    }

    public function getParent() {
        return \Symfony\Component\Form\Extension\Core\Type\UrlType::class;
    }

    public function getBlockPrefix() {
        return 'menu_builder_client_bundle_form_menu_item_url';
    }
}
