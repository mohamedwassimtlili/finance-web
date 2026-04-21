<?php
namespace App\Controller\Admin;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/roles', name: 'admin_role_')]
class AdminRoleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(RoleRepository $repo): Response
    {
        return $this->render('admin/role/index.html.twig', [
            'roles' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $role = new Role();
            $role->setRoleName($request->request->get('role_name'));
            $role->setPermissions($request->request->get('permissions') ?: null);

            $em->persist($role);
            $em->flush();

            $this->addFlash('success', 'Role created successfully.');
            return $this->redirectToRoute('admin_role_index');
        }

        return $this->render('admin/role/form.html.twig', ['role' => null]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Role $role, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $role->setRoleName($request->request->get('role_name'));
            $role->setPermissions($request->request->get('permissions') ?: null);

            $em->flush();

            $this->addFlash('success', 'Role updated successfully.');
            return $this->redirectToRoute('admin_role_index');
        }

        return $this->render('admin/role/form.html.twig', ['role' => $role]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Role $role, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_role_' . $role->getId(), $request->request->get('_token'))) {
            $em->remove($role);
            $em->flush();
            $this->addFlash('success', 'Role deleted.');
        }

        return $this->redirectToRoute('admin_role_index');
    }

    // ── User ↔ Role assignment ────────────────────────────────────────────────

    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(UserRepository $userRepo, RoleRepository $roleRepo): Response
    {
        return $this->render('admin/role/users.html.twig', [
            'users' => $userRepo->findBy([], ['name' => 'ASC']),
            'roles' => $roleRepo->findAll(),
        ]);
    }

    #[Route('/users/{id}/assign', name: 'assign', methods: ['POST'])]
    public function assign(User $user, Request $request, EntityManagerInterface $em, RoleRepository $roleRepo): Response
    {
        if (!$this->isCsrfTokenValid('assign_role_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_role_users');
        }

        $roleId = (int) $request->request->get('role_id');
        $role   = $roleRepo->find($roleId);

        if (!$role) {
            $this->addFlash('error', 'Role not found.');
            return $this->redirectToRoute('admin_role_users');
        }

        $user->setRoleId($roleId);
        $em->flush();

        $this->addFlash('success', sprintf('Role "%s" assigned to %s.', $role->getRoleName(), $user->getName()));
        return $this->redirectToRoute('admin_role_users');
    }
}
