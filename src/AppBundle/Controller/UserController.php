<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\EditUserType;
use AppBundle\Repository\NotificationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/profile/edit", name="keistiDuomenis")
     */
    public function userEditAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();

            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Duomenys sėkmingai pakeisti!');
        }
        return $this->render('@App/User/editUser.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/profile", name="user")
     * @Security("has_role('ROLE_USER')")
     */
    public function userAction(Request $request)
    {
        return $this->render('@App/User/user.html.twig', []);
    }

    /**
     * @Route("/my-messages/{page}", name="messages")
     * @Security("has_role('ROLE_USER')")
     */
    public function messageListAction($page = 1)
    {
        $repository     = $this->getDoctrine()->getRepository('AppBundle:Notification');
        $notifications  = $repository->getNotifications($page, $this->getUser()->getId());
        $maxPages       = ceil($notifications->count() / NotificationRepository::MAX_RESULTS);

        if ($page > $maxPages) {
            return $this->render('@App/Home/404.html.twig');
        }
        return $this->render('@App/User/messages.html.twig', [
            'thisPage'      => $page,
            'maxPages'      => $maxPages,
            'notifications' => $notifications,
            'limit'         => NotificationRepository::MAX_RESULTS
        ]);
    }

    /**
     * @Route("/my-messages/message/{id}-{slug}", name="viewMessage")
     * @Security("has_role('ROLE_USER')")
     */
    public function messageAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $message = $em->getRepository('AppBundle:Notification')->findOneBy([
            'id'    => $id,
            'user'  => $this->getUser()->getId()
        ]);

        if ($message) {
            $message->setSeen(true);
            $em->persist($message);
            $em->flush();
            return $this->render('@App/User/viewMessage.html.twig', ['message' => $message]);
        }
        return $this->render('AppBundle:Home:404.html.twig');
    }

    /**
     * @Route("/api/user/data", name="user-data")
     */
    public function userDataAction(Request $request)
    {
        $securityContext = $this->get('security.authorization_checker');
        /** @var User $user */
        $user = $this->getUser();

        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Test');

            for($i = 0; $i < 9; $i++ ) {
                $temp = $repository->bestResultsFromEachCategory($user, $i);
                if ($temp == null) {
                    $res[$i] = 0;
                } else {
                    $res[$i] = $temp[0];
                }
            }
            $data = "var data = google.visualization.arrayToDataTable([
                            ['Categories', 'Results', { role: 'style' }, { role: 'annotation' } ],
                            ['1', $res[0], 'color: gray', 'PHP Basics'],
                            ['2', $res[1], 'color: #76A7FA', 'Functions and Arrays'],
                            ['3', $res[2], '', 'OOP'],
                            ['4', $res[3], 'stroke-color: #703593; fill-color: #C5A5CF', 'Security'],
                            ['5', $res[4], 'stroke-color: #871B47; fill-color: #BC5679; ', 'Data format and Types'],
                            ['6', $res[5], 'gold', 'String and Patterns'],
                            ['7', $res[6], 'color: #76A7FA', 'Database and SQL'],
                            ['8', $res[7], 'silver', 'Web features'],
                            ['9', $res[8], 'stroke-color: #703593; fill-color: #C5A5CF', 'INPUT and OUTPUT'],
                        ]);";
            return new JsonResponse(json_encode(['data' => $data]));
        } else {
            return $this->redirectToRoute('homepage');
        }
    }

    /**
     * @Route("/api/user/category/{id}", name="category-data")
     */
    public function userCategoryResultsAction($id)
    {
        $securityContext    = $this->get('security.authorization_checker');
        $user               = $this->getUser();

        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Test');
            $data       = $repository->bestResultsFromEachCategory($user, $id);
            return new JsonResponse(json_encode(['data' => $data, 'category' => $id]));
        } else {
            return $this->redirectToRoute('homepage');
        }
    }
}
