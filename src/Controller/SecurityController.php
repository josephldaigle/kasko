<?php

namespace Kasko\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * SecurityController.
 *
 * @package Kasko\Controller
 */
class SecurityController extends AbstractController
{
	/**
	 * @param AuthenticationUtils $authenticationUtils
	 *
	 * @return Response
	 * @throws \LogicException
	 */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('page/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
}
