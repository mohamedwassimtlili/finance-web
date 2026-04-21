<?php
namespace App\Controller\Admin;

use App\Entity\Loan;
use App\Entity\Repayment;
use App\Repository\LoanRepository;
use App\Repository\RepaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/loans', name: 'admin_loan_')]
class AdminLoanController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(LoanRepository $repo): Response
    {
        return $this->render('admin/loan/index.html.twig', [
            'loans' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/repayments', name: 'repayments', methods: ['GET'])]
    public function repayments(RepaymentRepository $repo): Response
    {
        return $this->render('admin/loan/repayments.html.twig', [
            'repayments' => $repo->findBy([], ['paymentDate' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Loan $loan, RepaymentRepository $repaymentRepo): Response
    {
        $repayments = $repaymentRepo->findBy(['loan' => $loan], ['paymentDate' => 'DESC']);
        $totalPaid  = array_sum(array_map(fn($r) => (float)$r->getAmount(), $repayments));

        return $this->render('admin/loan/show.html.twig', [
            'loan'       => $loan,
            'repayments' => $repayments,
            'totalPaid'  => $totalPaid,
        ]);
    }
}
