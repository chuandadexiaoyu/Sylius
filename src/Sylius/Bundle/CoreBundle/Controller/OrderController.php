<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\CoreBundle\SyliusOrderEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\GenericEvent;

class OrderController extends ResourceController
{
    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function indexByUserAction(Request $request, $id)
    {
        $user = $this->get('sylius.repository.user')->findOneById($id);

        if (!$user) {
            throw new NotFoundHttpException('Requested user does not exist.');
        }

        $paginator = $this
            ->getRepository()
            ->createByUserPaginator($user, $this->config->getSorting())
        ;

        $paginator->setCurrentPage($request->get('page', 1), true, true);
        $paginator->setMaxPerPage($this->config->getPaginationMaxPerPage());

        return $this->render('SyliusWebBundle:Backend/Order:indexByUser.html.twig', array(
            'user'   => $user,
            'orders' => $paginator
        ));
    }

    /**
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function releaseInventoryAction()
    {
        $order = $this->findOr404($this->getRequest());

        $this->get('event_dispatcher')->dispatch(SyliusOrderEvents::PRE_RELEASE, new GenericEvent($order));

        $this->domainManager->update($order);

        $this->get('event_dispatcher')->dispatch(SyliusOrderEvents::POST_RELEASE, new GenericEvent($order));

        return $this->redirectHandler->redirectToReferer();
    }

    private function getFormFactory()
    {
        return $this->get('form.factory');
    }
}
