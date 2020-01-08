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

namespace Forci\Bundle\MenuBuilderClient\Controller\Menu;

use Forci\Bundle\MenuBuilder\Entity\MenuItem;
use Forci\Bundle\MenuBuilder\Manager\MenuManager;
use Forci\Bundle\MenuBuilder\Repository\MenuItemRepository;
use Forci\Bundle\MenuBuilder\Repository\MenuRepository;
use Forci\Bundle\MenuBuilder\Repository\RouteRepository;
use Forci\Bundle\MenuBuilderClient\Form\Menu\Item\ExternalUrlItemType;
use Forci\Bundle\MenuBuilderClient\Form\Menu\Item\MenuItemType;
use Forci\Bundle\MenuBuilderClient\Form\Menu\Item\RouteChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends AbstractController {

    /** @var MenuManager */
    private $menuManager;

    /** @var MenuRepository */
    private $menuRepository;

    /** @var MenuItemRepository */
    private $menuItemRepository;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var string */
    private $orderRoute;

    public function __construct(
        MenuManager $menuManager, MenuRepository $menuRepository,
        MenuItemRepository $menuItemRepository, RouteRepository $routeRepository,
        string $orderRoute
    ) {
        $this->menuManager = $menuManager;
        $this->menuRepository = $menuRepository;
        $this->menuItemRepository = $menuItemRepository;
        $this->routeRepository = $routeRepository;
        $this->orderRoute = $orderRoute;
    }

    public function chooseRouteAction($id, $parentId, Request $request) {
        $menu = $this->menuManager->findOneById($id);
        $item = $this->menuManager->createItem();

        $activeForm = 'route';

        $routeForm = $this->createForm(RouteChoiceType::class, $item);

        $routeForm->handleRequest($request);

        if ($routeForm->isSubmitted() && $routeForm->isValid()) {
            return $this->redirectToRoute('forci_menu_builder_client_menu_item_add', [
                'id' => $menu->getId(),
                'routeId' => $item->getRoute()->getId(),
                'parentId' => $parentId
            ]);
        }

        $urlForm = $this->createForm(ExternalUrlItemType::class, $item);

        $urlForm->handleRequest($request);

        if ($urlForm->isSubmitted() && $urlForm->isValid()) {
            $item->setMenu($menu);
            $menu->addItem($item);

            if ($parentId) {
                $parent = $this->menuItemRepository->findOneById($parentId);
                $item->setParent($parent);
                $parent->addChild($item);
            }

            return $this->editCreateItemSuccess($item, $request);
        }

        if ($routeForm->isSubmitted()) {
            $activeForm = 'route';
        } elseif ($urlForm->isSubmitted()) {
            $activeForm = 'url';
        }

        $data = [
            'menu' => $menu,
            'parentId' => $parentId,
            'routeForm' => $routeForm->createView(),
            'urlForm' => $urlForm->createView(),
            'activeForm' => $activeForm,
            'action' => $this->generateUrl('forci_menu_builder_client_menu_item_choose_route', [
                'id' => $id,
                'parentId' => $parentId
            ])
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@ForciMenuBuilderClient/Menu/Item/route_choice/choose_popup.html.twig', $data)
            ]);
        }

        return $this->render('@ForciMenuBuilderClient/Menu/Item/route_choice/choose.html.twig', $data);
    }

    public function addItemAction($id, $routeId, $parentId, Request $request) {
        $item = $this->menuManager->createItem();
        $menu = $this->menuRepository->findOneById($id);
        $route = $this->routeRepository->findOneById($routeId);
        $item->setRoute($route);
        $item->setMenu($menu);
        $menu->addItem($item);

        if ($parentId) {
            $parent = $this->menuItemRepository->findOneById($parentId);
            $item->setParent($parent);
            $parent->addChild($item);
        }

        return $this->editCreateItem($item, $this->generateUrl('forci_menu_builder_client_menu_item_add', [
            'id' => $menu->getId(),
            'routeId' => $routeId,
            'parentId' => $parentId
        ]), $request);
    }

    public function editItemAction($id, $itemId, Request $request) {
        $item = $this->menuItemRepository->findOneById($itemId);

        return $this->editCreateItem($item, $this->generateUrl('forci_menu_builder_client_menu_item_edit', [
            'id' => $id,
            'itemId' => $itemId
        ]), $request);
    }

    protected function editCreateItem(MenuItem $item, $action, Request $request) {
        $form = $this->createForm(MenuItemType::class, $item);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->editCreateItemSuccess($item, $request);
        }

        $data = [
            'menu' => $item->getMenu(),
            'route' => $item->getRoute(),
            'item' => $item,
            'parent' => $item->getParent(),
            'form' => $form->createView(),
            'action' => $action
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@ForciMenuBuilderClient/Menu/Item/create/create_popup.html.twig', $data)
            ]);
        }

        return $this->render('@ForciMenuBuilderClient/Menu/Item/create/create.html.twig', $data);
    }

    protected function editCreateItemSuccess(MenuItem $item, Request $request) {
        $this->menuItemRepository->save($item);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@ForciMenuBuilderClient/Menu/Item/create/success_popup.html.twig', [
                    'item' => $item
                ]),
                'refreshMenu' => true
            ]);
        }

        return $this->redirectToRoute($this->orderRoute, [
            'id' => $item->getMenu()->getId()
        ]);
    }

    public function updateItemNameAction($id, $itemId, Request $request) {
        $post = $request->request;
        // name, value, pk
        $name = $post->get('value', null);

        if (null === $name) {
            return new Response('Error - Empty value', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $item = $this->menuItemRepository->findOneById($itemId);
        $item->setName($name);
        $this->menuItemRepository->save($item);

        return new Response();
    }
}
