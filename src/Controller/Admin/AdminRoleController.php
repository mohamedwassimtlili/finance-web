<?php
namespace App\Controller\Admin;

use App\Entity\Role;
use App\Repository\RoleRepository;
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
}
