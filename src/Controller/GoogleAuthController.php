<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository         $userRepository,
    ) {}

    // ─── Step 1: Redirect to Google ───────────────────────────────────────────
    // ─── Step 1: Redirect user to Google's consent screen ─────────────────────
    #[Route('/auth/google', name: 'app_google_auth')]
    public function redirectToGoogle(Request $request): Response
    {
        // Generate a random state token to prevent CSRF
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('google_oauth_state', $state);

        $redirectUri = $request->getSchemeAndHttpHost()
            . $this->generateUrl('app_google_callback');

        $query = http_build_query([
            'client_id'     => $_ENV['GOOGLE_CLIENT_ID'],
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    // ─── Step 2: Google redirects back here with a code ───────────────────────
    #[Route('/auth/google/callback', name: 'app_google_callback')]
    public function handleCallback(Request $request): Response
    {
        $code  = $request->query->get('code');
        $state = $request->query->get('state');

        // Validate state (CSRF protection)
        if (!$code || !$state || $state !== $request->getSession()->get('google_oauth_state')) {
            $this->addFlash('danger', 'Google sign-in was cancelled or failed. Please try again.');
            return $this->redirectToRoute('app_login');
        }
        $request->getSession()->remove('google_oauth_state');

        $redirectUri = $request->getSchemeAndHttpHost()
            . $this->generateUrl('app_google_callback');

        // ── Exchange authorization code for access token ───────────────────────
        try {
            $tokenRes = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code'          => $code,
                    'client_id'     => $_ENV['GOOGLE_CLIENT_ID'],
                    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                    'redirect_uri'  => $redirectUri,
                    'grant_type'    => 'authorization_code',
                ],
            ]);

            $tokens = $tokenRes->toArray(false);

            if (empty($tokens['access_token'])) {
                throw new \RuntimeException('Google returned no access token: '
                    . ($tokens['error_description'] ?? 'unknown error'));
            }

            // ── Fetch the authenticated user's profile from Google ─────────────
            $profileRes = $this->httpClient->request('GET',
                'https://www.googleapis.com/oauth2/v3/userinfo',
                ['headers' => ['Authorization' => 'Bearer ' . $tokens['access_token']]]
            );

            $googleProfile = $profileRes->toArray(false);

        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Google sign-in failed. Please try again.');
            return $this->redirectToRoute('app_login');
        }

        $googleEmail = $googleProfile['email'] ?? null;
        $googleName  = $googleProfile['name']  ?? 'Google User';

        if (!$googleEmail) {
            $this->addFlash('danger', 'Could not retrieve your email address from Google.');
            return $this->redirectToRoute('app_login');
        }

        // ── Find existing user or create a new one ────────────────────────────
        $user = $this->userRepository->findOneBy(['email' => $googleEmail]);

        if (!$user) {
            // Auto-register — Google accounts are pre-verified
            $user = new User();
            $user->setName($googleName);
            $user->setEmail($googleEmail);
            $user->setPasswordHash(''); // No local password
            $user->setIsVerified(true);
            $user->setGoogleAccount(true);
            $this->em->persist($user);
        } else {
            // Tag existing account as linked to Google
            if (!$user->getGoogleAccount()) {
                $user->setGoogleAccount(true);
            }

            // Check account is active
            if (!$user->isActive()) {
                $this->addFlash('danger', 'Your account has been disabled. Contact an administrator.');
                return $this->redirectToRoute('app_login');
            }
        }

        // Stamp last login
        $user->setLastLogin(new \DateTime());
        $this->em->flush();

        // ── Manually log the user into Symfony's security system ─────────────
        // This is the standard Symfony 6.x way to programmatically authenticate a user.
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $request->getSession()->set('_security_main', serialize($token));
        $request->getSession()->save();

        $this->addFlash('success', 'Welcome, ' . $user->getName() . '! You are signed in with Google.');
        return $this->redirectToRoute('app_home');
    }
}
