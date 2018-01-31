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

namespace Forci\Bundle\MenuBuilderClient\Form\Menu;

use Forci\Bundle\MenuBuilder\Filter\Menu\MenuFilter;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Forci\Bundle\MenuBuilder\Entity\Route;
use Forci\Bundle\MenuBuilder\Repository\RouteRepository;
use Wucdbm\Bundle\QuickUIBundle\Form\Filter\BaseFilterType;
use Wucdbm\Bundle\QuickUIBundle\Form\Filter\EntityFilterType;
use Wucdbm\Bundle\QuickUIBundle\Form\Filter\TextFilterType;

class FilterType extends BaseFilterType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('route', EntityFilterType::class, [
                'class' => Route::class,
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
                'placeholder' => 'Route'
            ])
            ->add('name', TextFilterType::class, [
                'placeholder' => 'Name'
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => MenuFilter::class
        ]);
    }
}
