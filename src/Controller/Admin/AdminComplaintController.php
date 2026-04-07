<?php
namespace App\Controller\Admin;

use App\Entity\Complaint;
use App\Repository\ComplaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/complaints', name: 'admin_complaint_')]
class AdminComplaintController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ComplaintRepository $repo): Response
    {
        return $this->render('admin/complaint/index.html.twig', [
            'complaints' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function show(Complaint $complaint, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $complaint->setResponse($request->request->get('response'));
            $complaint->setStatus($request->request->get('status', $complaint->getStatus()));
            $em->flush();

            $this->addFlash('success', 'Response saved.');
            return $this->redirectToRoute('admin_complaint_show', ['id' => $complaint->getId()]);
        }

        return $this->render('admin/complaint/show.html.twig', ['complaint' => $complaint]);
    }
}
