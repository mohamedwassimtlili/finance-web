<?php
namespace App\Controller\Admin;

use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/payments', name: 'admin_payment_')]
class AdminPaymentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TransactionRepository $repo): Response
    {
        return $this->render('admin/payment/index.html.twig', [
            'transactions' => $repo->findBy(['referenceType' => 'paymee'], ['createdAt' => 'DESC']),
        ]);
    }
}
