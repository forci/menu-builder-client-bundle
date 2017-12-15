<?php

/*
 * This file is part of the ForciMenuBuilderClientBundle package.
 *
 * (c) Martin Kirilov <wucdbm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Forci\Bundle\MenuBuilderClient\Controller\Menu;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Forci\Bundle\MenuBuilderBundle\Entity\Menu;
use Forci\Bundle\MenuBuilderBundle\Entity\MenuItem;
use Forci\Bundle\MenuBuilderClient\Form\Menu\Item\MenuItemType;
use Forci\Bundle\MenuBuilderClient\Form\Menu\Item\RouteChoiceType;

class ItemController extends Controller {

    public function chooseRouteAction(Menu $menu, $parentId, Request $request) {
        $item = new MenuItem();
        $form = $this->createForm(RouteChoiceType::class, $item);

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->redirectToRoute('forci_menu_builder_client_menu_item_add', [
                'id' => $menu->getId(),
                'routeId' => $item->getRoute()->getId(),
                'parentId' => $parentId
            ]);
        }

        $data = [
            'menu' => $menu,
            'parentId' => $parentId,
            'form' => $form->createView()
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@ForciMenuBuilderClient/Menu/Item/route_choice/choose_popup.html.twig', $data)
            ]);
        }

        return $this->render('@ForciMenuBuilderClient/Menu/Item/route_choice/choose.html.twig', $data);
    }

    public function addItemAction(Menu $menu, $routeId, $parentId, Request $request) {
        $routeRepository = $this->container->get('forci_menu_builder.repo.routes');
        $route = $routeRepository->findOneById($routeId);
        $item = new MenuItem();
        $item->setRoute($route);
        $item->setMenu($menu);
        $menu->addItem($item);

        if ($parentId) {
            $menuItemRepository = $this->container->get('forci_menu_builder.repo.menus_items');
            $parent = $menuItemRepository->findOneById($parentId);
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
        $repo = $this->container->get('forci_menu_builder.repo.menus_items');
        $item = $repo->findOneById($itemId);

        return $this->editCreateItem($item, $this->generateUrl('forci_menu_builder_client_menu_item_edit', [
            'id' => $id,
            'itemId' => $itemId
        ]), $request);
    }

    protected function editCreateItem(MenuItem $item, $action, Request $request) {
        $form = $this->createForm(MenuItemType::class, $item);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $repo = $this->container->get('forci_menu_builder.repo.menus_items');
            $repo->save($item);

            $route = $this->container->getParameter('forci_menu_builder_client.order_route');

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'mfp' => $this->renderView('@ForciMenuBuilderClient/Menu/Item/create/success_popup.html.twig'),
                    'refreshMenu' => true
                ]);
            }

            return $this->redirectToRoute($route, [
                'id' => $item->getMenu()->getId()
            ]);
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

    public function updateItemNameAction($id, $itemId, Request $request) {
        $post = $request->request;
        // name, value, pk
        $name = $post->get('value', null);

        if (null === $name) {
            return new Response('Error - Empty value', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repo = $this->container->get('forci_menu_builder.repo.menus_items');
        $item = $repo->findOneById($itemId);
        $item->setName($name);
        $repo->save($item);

        return new Response();
    }
}
