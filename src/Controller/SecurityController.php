<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private string $hcaptchaSiteKey;
    private string $hcaptchaSecretKey;

    public function __construct(string $hcaptchaSiteKey, string $hcaptchaSecretKey)
    {
        $this->hcaptchaSiteKey   = $hcaptchaSiteKey;
        $this->hcaptchaSecretKey = $hcaptchaSecretKey;
    }

    // ── Verify hCaptcha token ─────────────────────────────────────────────────
    private function verifyHcaptcha(string $token): bool
    {
        $response = file_get_contents(
            'https://hcaptcha.com/siteverify',
            false,
            stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query([
                        'secret'   => $this->hcaptchaSecretKey,
                        'response' => $token,
                    ]),
                ],
            ])
        );
        $data = json_decode($response, true);
        return $data['success'] ?? false;
    }
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

   #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
public function signup(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer, UserRepository $userRepository): Response
{

    if ($this->getUser()) {
        return $this->redirectToRoute('app_home');
    }

    // Initialize variables so they're always defined
    $error      = null;
    $last_name  = '';
    $last_email = '';
    $last_phone = '';

    if ($request->isMethod('POST')) {
            // ── Verify hCaptcha ───────────────────────────────────────────────
    $hcaptchaToken = $request->request->get('h-captcha-response', '');
    if (empty($hcaptchaToken) || !$this->verifyHcaptcha($hcaptchaToken)) {
        $this->addFlash('danger', 'Please complete the hCaptcha verification.');
        return $this->render('security/signup.html.twig', [
            'last_name'      => $request->request->get('name', ''),
            'last_email'     => $request->request->get('email', ''),
            'last_phone'     => $request->request->get('phone', ''),
            'recaptcha_key'  => $this->hcaptchaSiteKey,
            'error'          => null,
        ]);}
        $last_name  = $request->request->get('name', '');
        $last_email = $request->request->get('email', '');
        $last_phone = $request->request->get('phone', '');
        $password   = $request->request->get('password', '');

        if (!filter_var($last_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($userRepository->findOneBy(['email' => $last_email])) {
            $error = 'An account with this email already exists.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            // All checks passed — create the user
            $user = new User();
            $user->setName($last_name);
            $user->setEmail($last_email);
            $user->setPhone($last_phone ?: null);
            $user->setPassword($userPasswordHasher->hashPassword($user, $password));

            $code = (string) random_int(100000, 999999);
            $user->setVerificationCode($code);

            $entityManager->persist($user);
            $entityManager->flush();

            // Send verification email
            $emailMsg = (new Email())
                ->from('yasmine912003@gmail.com')
                ->to($user->getEmail())
                ->subject('Verify your email — FinanceApp')
                ->html(
                    '<div style="font-family:sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #e0e0e0;border-radius:8px">'
                    . '<h2 style="color:#3b82f6">Verify your email</h2>'
                    . '<p>Hello <strong>' . htmlspecialchars($user->getName()) . '</strong>,</p>'
                    . '<p>Enter this code to activate your account:</p>'
                    . '<div style="font-size:2.5rem;font-weight:bold;letter-spacing:12px;text-align:center;padding:20px;background:#f0f9ff;border-radius:6px;color:#1d4ed8">' . $code . '</div>'
                    . '<p style="margin-top:16px;color:#6b7280;font-size:0.9rem">If you did not sign up, ignore this email.</p>'
                    . '</div>'
                );

            $mailer->send($emailMsg);

            $request->getSession()->set('pending_verification_user_id', $user->getId());

            return $this->redirectToRoute('app_verify_email');
        }
    }

    // Renders for both GET and failed POST — variables are always defined
    return $this->render('security/signup.html.twig', [
        'error'      => $error,
        'last_name'  => $last_name,
        'last_email' => $last_email,
        'last_phone' => $last_phone,
        'recaptcha_key' => $this->hcaptchaSiteKey,
    ]);
}

    // ─── FORGOT PASSWORD ──────────────────────────────────────────────────────

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user  = $userRepository->findOneBy(['email' => $email]);

            // Always show the same message to avoid email enumeration
            if ($user) {
                $code = (string) random_int(100000, 999999);
                $user->setVerificationCode($code);
                $em->flush();

                $emailMessage = (new Email())
                    ->from('yasmine912003@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Password Reset Code — FinanceApp')
                    ->html(
                        '<div style="font-family:sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #e0e0e0;border-radius:8px">'
                        . '<h2 style="color:#3b82f6">Password Reset</h2>'
                        . '<p>Hello <strong>' . htmlspecialchars($user->getName()) . '</strong>,</p>'
                        . '<p>Use the code below to reset your password. It is valid for one use only.</p>'
                        . '<div style="font-size:2.5rem;font-weight:bold;letter-spacing:12px;text-align:center;'
                        .     'padding:20px;background:#f0f9ff;border-radius:6px;color:#1d4ed8">' . $code . '</div>'
                        . '<p style="margin-top:16px;color:#6b7280;font-size:0.9rem">If you did not request this, ignore this email.</p>'
                        . '</div>'
                    );

                $mailer->send($emailMessage);
            }

            $this->addFlash('info', 'If that email exists in our system, a reset code has been sent.');
            return $this->redirectToRoute('app_reset_password');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    // ─── RESET PASSWORD ───────────────────────────────────────────────────────

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($request->isMethod('POST')) {
            $code        = trim($request->request->get('code'));
            $newPassword = $request->request->get('password');
            $confirm     = $request->request->get('confirm');

            if ($newPassword !== $confirm) {
                $this->addFlash('danger', 'Passwords do not match.');
                return $this->redirectToRoute('app_reset_password');
            }

            $user = $userRepository->findOneBy(['verificationCode' => $code]);

            if (!$user) {
                $this->addFlash('danger', 'Invalid or expired reset code.');
                return $this->redirectToRoute('app_reset_password');
            }

            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $user->setVerificationCode(null); // invalidate the code
            $em->flush();

            $this->addFlash('success', 'Password reset successfully. You can now sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig');}
       #[Route('/verify-email', name: 'app_verify_email', methods: ['GET', 'POST'])]
       public function verifyEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $userId = $request->getSession()->get('pending_verification_user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_signup');
        }

        $user  = $userRepository->find($userId);
        $error = null;

        if ($request->isMethod('POST')) {
            

            // ── Resend button ──
            if ($request->request->has('resend')) {
                $newCode = (string) random_int(100000, 999999);
                $user->setVerificationCode($newCode);
                $em->flush();

                $mailer->send((new Email())
                    ->from('yasmine912003@gmail.com')
                    ->to($user->getEmail())
                    ->subject('New verification code — FinanceApp')
                    ->html(
                        '<p>Your new verification code: <strong style="font-size:2rem;letter-spacing:8px">'
                        . $newCode .
                        '</strong></p>'
                    )
                );

                $this->addFlash('info', 'A new code was sent to ' . $user->getEmail());
                return $this->redirectToRoute('app_verify_email');
            }

            // ── Verify button ──
            $code = trim($request->request->get('code'));

            if ($code === $user->getVerificationCode()) {
                $user->setIsVerified(true);
                $user->setVerificationCode(null);
                $em->flush();

                $request->getSession()->remove('pending_verification_user_id');
                $this->addFlash('success', 'Email verified! You can now sign in.');
                return $this->redirectToRoute('app_login');
            }

            $error = 'Invalid code. Please try again.';
        }

        return $this->render('security/verify_email.html.twig', [
            'email' => $user?->getEmail(),
            'error' => $error,
        ]);
    }
}
