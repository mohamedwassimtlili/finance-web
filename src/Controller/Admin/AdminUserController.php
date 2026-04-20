<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    // ─── List + inline create form ─────────────────────────────────────────────
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $q     = $request->query->get('q');
        $users = $q ? $userRepository->searchByNameOrEmail($q) : $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users'       => $users,
            'search_term' => $q,
        ]);
    }

    // ─── Create ────────────────────────────────────────────────────────────────
    #[Route('/new', name: 'admin_user_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository
    ): Response {
        $name     = trim($request->request->get('name', ''));
        $email    = trim($request->request->get('email', ''));
        $phone    = trim($request->request->get('phone', ''));
        $password = $request->request->get('password', '');
        $roleId   = (int) $request->request->get('roleId', 2);

        // Validation
        $errors = $this->validateUserInput($name, $email, $phone, $password);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error);
            }
            return $this->redirectToRoute('admin_user_index');
        }

        // Duplicate email check
        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('danger', 'A user with this email already exists.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPhone($phone !== '' ? $phone : null);
        $user->setRoleId($roleId);
        $user->setIsVerified(true); // Admin-created users are pre-verified
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', "User '{$name}' created successfully.");
        return $this->redirectToRoute('admin_user_index');
    }

    // ─── Update ────────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $name   = trim($request->request->get('name', ''));
            $email  = trim($request->request->get('email', ''));
            $phone  = trim($request->request->get('phone', ''));
            $roleId = (int) $request->request->get('roleId', 2);
            $newPwd = $request->request->get('password', '');

            // Basic validation (password optional on edit)
            $errors = $this->validateUserInput($name, $email, $phone, $newPwd === '' ? null : $newPwd);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
                return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
            }

            // Email uniqueness (excluding self)
            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Another user already uses this email.');
                return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
            }

            $user->setName($name);
            $user->setEmail($email);
            $user->setPhone($phone !== '' ? $phone : null);
            $user->setRoleId($roleId);

            if ($newPwd !== '') {
                $user->setPassword($hasher->hashPassword($user, $newPwd));
            }

            $em->flush();
            $this->addFlash('success', "User '{$name}' updated successfully.");
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', ['user' => $user]);
    }

    // ─── Delete ────────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            // Prevent self-deletion
            if ($user === $this->getUser()) {
                $this->addFlash('danger', 'You cannot delete your own account.');
                return $this->redirectToRoute('admin_user_index');
            }
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', "User '{$user->getName()}' deleted.");
        }

        return $this->redirectToRoute('admin_user_index');
    }

    // ─── Toggle active/inactive ────────────────────────────────────────────────
    #[Route('/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle-' . $user->getId(), $request->request->get('_token'))) {
            if ($user === $this->getUser()) {
                $this->addFlash('danger', 'You cannot deactivate your own account.');
                return $this->redirectToRoute('admin_user_index');
            }
            $user->setIsActive(!$user->isActive());
            $em->flush();
            $status = $user->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', "User '{$user->getName()}' {$status}.");
        }

        return $this->redirectToRoute('admin_user_index');
    }

    // ─── Reset password (admin sets a new one directly) ────────────────────────
    #[Route('/{id}/reset-password', name: 'admin_user_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($this->isCsrfTokenValid('reset-pwd-' . $user->getId(), $request->request->get('_token'))) {
            $newPassword = $request->request->get('new_password', '');
            if (strlen($newPassword) < 6) {
                $this->addFlash('danger', 'Password must be at least 6 characters.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
                $em->flush();
                $this->addFlash('success', "Password for '{$user->getName()}' has been reset.");
            }
        }

        return $this->redirectToRoute('admin_user_index');
    }

    // ─── Validation helper ─────────────────────────────────────────────────────
    private function validateUserInput(string $name, string $email, string $phone, ?string $password): array
    {
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
            $errors[] = 'Name can only contain letters and spaces.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if ($phone !== '' && !preg_match('/^[0-9]{8}$/', $phone)) {
            $errors[] = 'Phone must be exactly 8 digits.';
        }

        if ($password !== null && strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        return $errors;
    }
}
