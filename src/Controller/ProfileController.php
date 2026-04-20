<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profile')]
class ProfileController extends AbstractController
{
    // ─── View own profile ──────────────────────────────────────────────────────
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    // ─── Edit name + phone ─────────────────────────────────────────────────────
    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $error = null;

        if ($request->isMethod('POST')) {
            $name  = trim($request->request->get('name', ''));
            $phone = trim($request->request->get('phone', ''));

            if (empty($name)) {
                $error = 'Name is required.';
            } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
                $error = 'Name can only contain letters and spaces.';
            } elseif ($phone !== '' && !preg_match('/^[0-9]{8}$/', $phone)) {
                $error = 'Phone must be exactly 8 digits.';
            } else {
                $user->setName($name);
                $user->setPhone($phone !== '' ? $phone : null);
                $em->flush();

                $this->addFlash('success', 'Profile updated successfully!');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user'  => $user,
            'error' => $error,
        ]);
    }

    // ─── Change password ───────────────────────────────────────────────────────
    #[Route('/change-password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var \App\Entity\User $user */
        $user       = $this->getUser();
        $currentPwd = $request->request->get('current_password', '');
        $newPwd     = $request->request->get('new_password', '');
        $confirmPwd = $request->request->get('confirm_password', '');

        // Cannot change password for Google-only accounts
        if ($user->getGoogleAccount() && empty($user->getPasswordHash())) {
            $this->addFlash('danger', 'Google accounts do not use a password.');
            return $this->redirectToRoute('app_profile');
        }

        if (!$hasher->isPasswordValid($user, $currentPwd)) {
            $this->addFlash('danger', 'Your current password is incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        if (strlen($newPwd) < 6) {
            $this->addFlash('danger', 'New password must be at least 6 characters.');
            return $this->redirectToRoute('app_profile');
        }

        if ($newPwd !== $confirmPwd) {
            $this->addFlash('danger', 'New passwords do not match.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($hasher->hashPassword($user, $newPwd));
        $em->flush();

        $this->addFlash('success', 'Password changed successfully!');
        return $this->redirectToRoute('app_profile');
    }
}
