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

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Forci\Bundle\MenuBuilder\Entity\MenuItem;
use Forci\Bundle\MenuBuilder\Entity\Route;
use Forci\Bundle\MenuBuilder\Repository\RouteRepository;

class RouteChoiceType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('route', EntityType::class, [
                'label' => 'Please select a page for your new link',
                'class' => 'Forci\Bundle\MenuBuilder\Entity\Route',
                'query_builder' => function (RouteRepository $repository) {
                    return $repository->getPublicRoutesQueryBuilder();
                },
                'choice_label' => function (Route $route) {
                    if ($route->getName()) {
                        return sprintf('%s (%s)', $route->getName(), $route->getRoute());
                    }

                    $routeName = str_replace(['_', '.'], ' ', $route->getRoute());
                    $words = explode(' ', $routeName);
                    foreach ($words as $key => $word) {
                        $words[$key] = ucfirst($word);
                    }

                    return sprintf('%s (%s)', implode(' ', $words), $route->getRoute());
                },
                'placeholder' => 'Select page',
                'attr' => [
                    'class' => 'select2'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => MenuItem::class
        ]);
    }

    public function getBlockPrefix() {
        return 'forci_menu_builder_menu_item_route_choice_type';
    }
}
