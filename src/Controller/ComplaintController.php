<?php
namespace App\Controller;

use App\Entity\Complaint;
use App\Repository\ComplaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/complaints', name: 'complaint_')]
class ComplaintController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ComplaintRepository $repo): Response
    {
        return $this->render('complaint/index.html.twig', [
            'complaints' => $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $complaint = new Complaint();
            $complaint->setUser($this->getUser());
            $complaint->setSubject($request->request->get('subject'));
            $complaint->setComplaintDate(new \DateTime());
            $complaint->setStatus('pending');

            $em->persist($complaint);
            $em->flush();

            $this->addFlash('success', 'Your complaint has been submitted. We will respond shortly.');
            return $this->redirectToRoute('complaint_index');
        }

        return $this->render('complaint/new.html.twig');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Complaint $complaint): Response
    {
        if ($complaint->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('complaint/show.html.twig', ['complaint' => $complaint]);
    }
}
