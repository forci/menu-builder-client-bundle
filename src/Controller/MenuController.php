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

namespace Forci\Bundle\MenuBuilderClient\Controller;

use Forci\Bundle\MenuBuilder\Manager\MenuManager;
use Forci\Bundle\MenuBuilder\Repository\MenuItemRepository;
use Forci\Bundle\MenuBuilder\Repository\MenuRepository;
use Forci\Bundle\MenuBuilderClient\Manager\OrderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Forci\Bundle\MenuBuilder\Filter\Menu\MenuFilter;
use Forci\Bundle\MenuBuilder\Form\Menu\CreateType;
use Forci\Bundle\MenuBuilder\Form\Menu\FilterType;

class MenuController extends AbstractController {

    /** @var MenuManager */
    private $menuManager;

    /** @var OrderManager */
    private $orderManager;

    /** @var MenuRepository */
    private $menuRepository;

    /** @var MenuItemRepository */
    private $menuItemRepository;

    /** @var string */
    private $orderRoute;

    public function listAction(Request $request) {
        $filter = new MenuFilter();
        $pagination = $filter->getPagination()->enable();
        $filterForm = $this->createForm(FilterType::class, $filter);
        $filter->load($request, $filterForm);
        $menus = $this->menuRepository->filter($filter);
        $data = [
            'menus' => $menus,
            'filter' => $filter,
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView()
        ];

        return $this->render('@ForciMenuBuilderClient/Menu/list/list.html.twig', $data);
    }

    public function refreshListRowAction($id) {
        $menu = $this->menuRepository->findOneById($id);

        $data = [
            'menu' => $menu
        ];

        return $this->render('@ForciMenuBuilderClient/Menu/list/list_row.html.twig', $data);
    }

    public function updateNameAction($id, Request $request) {
        $post = $request->request;
        // name, value, pk
        $name = $post->get('value', null);

        if (null === $name) {
            return new Response('Error - Empty value', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $menu = $this->menuManager->findOneById($id);
        $menu->setName($name);
        $this->menuManager->save($menu);

        return new Response();
    }

    public function createAction(Request $request) {
        $menu = $this->menuManager->create();
        $form = $this->createForm(CreateType::class, $menu);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->menuManager->save($menu);

            $url = $this->generateUrl($this->orderRoute, [
                'id' => $menu->getId()
            ]);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'redirect' => $url
                ]);
            }

            return $this->redirect($url);
        }

        $data = [
            'form' => $form->createView(),
            'menu' => $menu
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@ForciMenuBuilderClient/Menu/create/create_popup.html.twig', $data)
            ]);
        }

        return $this->render('@ForciMenuBuilderClient/Menu/create/create.html.twig', $data);
    }

    public function nestableAction($id, Request $request) {
        $menu = $this->menuRepository->findOneById($id);

        $form = $this->createForm(CreateType::class, $menu);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->menuManager->save($menu);
        }

        $data = [
            'menu' => $menu,
            'form' => $form->createView()
        ];

        return $this->render('@ForciMenuBuilderClient/Menu/nestable.html.twig', $data);
    }

    public function refreshNestableAction($id) {
        $menu = $this->menuRepository->findOneById($id);

        $data = [
            'menu' => $menu
        ];

        return $this->render('@ForciMenuBuilderClient/Menu/nestable/nestable.html.twig', $data);
    }

    public function updateNestableAction($id, Request $request) {
        $menu = $this->menuRepository->findOneById($id);

        $order = $request->request->get('order');

        if (!is_array($order)) {
            return $this->json([
                'witter' => [
                    'title' => 'Error',
                    'text' => 'Submitted is not an array.'
                ]
            ]);
        }

        try {
            $this->orderManager->orderNestable($menu, $order);

            return $this->json([
                'witter' => [
                    'title' => 'Success',
                    'text' => 'Menu reordered successfully'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'witter' => [
                    'title' => sprintf('Error: Uncaught %s', get_class($e)),
                    'text' => $e->getMessage()
                ]
            ]);
        }
    }

    public function sortableAction($id, $class, Request $request) {
        $menu = $this->menuRepository->findOneById($id);

        $form = $this->createForm(CreateType::class, $menu);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->menuManager->save($menu);
        }

        $data = [
            'menu' => $menu,
            'form' => $form->createView(),
            'class' => $class
        ];

        return $this->render('@ForciMenuBuilderClient/Menu/sortable.html.twig', $data);
    }

    public function refreshSortableAction($id) {
        $menu = $this->menuRepository->findOneById($id);

        $data = [
            'menu' => $menu,
            'class' => 'vertical-simple'
        ];

        return $this->render('@ForciMenuBuilderClient/Menu/sortable/sortable.html.twig', $data);
    }

    public function updateSortableAction($id, Request $request) {
        $menu = $this->menuRepository->findOneById($id);

        $order = $request->request->get('order');

        if (!is_array($order)) {
            return $this->json([
                'witter' => [
                    'title' => 'Error',
                    'text' => 'Submitted is not an array.'
                ]
            ]);
        }

        try {
            $this->orderManager->orderSortable($menu, $order);

            return $this->json([
                'witter' => [
                    'title' => 'Success',
                    'text' => 'Menu reordered successfully'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'witter' => [
                    'title' => sprintf('Error: Uncaught %s', get_class($e)),
                    'text' => $e->getMessage()
                ]
            ]);
        }
    }

    public function removeAction($id, Request $request) {
        $menu = $this->menuRepository->findOneById($id);

        if ($request->isXmlHttpRequest()) {
            if ($menu->getIsSystem()) {
                return $this->json([
                    'witter' => [
                        'title' => sprintf('Failed removing %s', $menu->getName()),
                        'text' => sprintf('You can not delete "%s" because it is a System menu and doing so will break the application.', $menu->getName())
                    ]
                ]);
            }

            $isConfirmed = $request->request->get('is_confirmed');

            if ($isConfirmed) {
                $this->menuRepository->remove($menu);

                return $this->json([
                    'redirect' => $this->generateUrl('forci_menu_builder_client_menu_list')
                ]);
            }

            return $this->json([
                'witter' => [
                    'title' => 'You must confirm this action',
                    'text' => 'You must confirm first in order to delete this Menu'
                ]
            ]);
        }

        $referer = $request->headers->get('Referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('forci_menu_builder_client_menu_list');
    }

    public function removeItemAction(Request $request) {
        $id = $request->request->get('id');
        $itemId = $request->request->get('itemId');
        $isFull = (int)$request->request->get('isFull');

        if (!$id || !$itemId || !is_numeric($isFull)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'witter' => [
                        'title' => 'Error: id, itemId or isFull is not set',
                        'text' => sprintf('%s, %s, %s', $id, $itemId, $isFull)
                    ]
                ]);
            }

            if ($id) {
                return $this->redirectToRoute($this->orderRoute, [
                    'id' => $id
                ]);
            }

            return $this->redirectToRoute('forci_menu_builder_client_menu_list');
        }

        $item = $this->menuItemRepository->findOneById($itemId);

        if (!$item) {
            return $this->json([
                'success' => false,
                'witter' => [
                    'text' => 'Link not found'
                ]
            ]);
        }

        $this->menuItemRepository->remove($item, $isFull);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'refresh' => true
            ]);
        }

        return $this->redirectToRoute($this->orderRoute, [
            'id' => $id
        ]);
    }
}
