<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', defaults: ['_locale' => 'de'], priority: 350)]
    #[Route('/en/login', name: 'app_login_en', defaults: ['_locale' => 'en'], priority: 350)]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@EasyAdmin/page/login.html.twig', [
            // parameters usually defined in Symfony login forms
            'error' => $error,
            'last_username' => $lastUsername,

            // OPTIONAL parameters to customize the login form:

            // the translation_domain to use (define this option only if you are
            // rendering the login template in a regular Symfony controller; when
            // rendering it from an EasyAdmin Dashboard this is automatically set to
            // the same domain as the rest of the Dashboard)
            'translation_domain' => 'admin',

            // by default EasyAdmin displays a black square as its default favicon;
            // use this method to display a custom favicon: the given path is passed
            // "as is" to the Twig asset() function:
            // <link rel="shortcut icon" href="{{ asset('...') }}">
            'favicon_path' => '/favicon-admin.svg',

            // the string used to generate the CSRF token. If you don't define
            // this parameter, the login form won't include a CSRF token
            'csrf_token_intention' => 'authenticate',

            // the URL users are redirected to after the login (default: '/admin')
            'target_path' => $this->generateUrl('admin'),

            // the label displayed for the username form field (the |trans filter is applied to it)
            'username_label' => 'Email Adresse',

            // the label displayed for the password form field (the |trans filter is applied to it)
            'password_label' => 'Password',

            // the label displayed for the Sign In form button (the |trans filter is applied to it)
            'sign_in_label' => 'Einloggen',

            // the 'name' HTML attribute of the <input> used for the username field (default: '_username')
            'username_parameter' => 'email',

            // the 'name' HTML attribute of the <input> used for the password field (default: '_password')
            'password_parameter' => 'password',
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}
