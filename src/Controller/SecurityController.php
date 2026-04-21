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
    public function signup(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            $user->setPhone($request->request->get('phone'));

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $request->request->get('password')
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/signup.html.twig');
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

        return $this->render('security/reset_password.html.twig');
    }
}
