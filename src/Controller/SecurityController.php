<?php
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController {
    /**
     * @Route("/login", name="app_login")
     * @Template(template="login.html.twig")
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return array
     */
    public function login(AuthenticationUtils $authenticationUtils): array {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return [
            'last_username' => $lastUsername,
            'error' => $error
        ];
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    public function logout(): void {
        // controller can be blank: it will never be executed!
        throw new \BadMethodCallException('Don\'t forget to activate logout in security.yaml');
    }
}