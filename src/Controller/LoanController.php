<?php
namespace App\Controller;

use App\Entity\Loan;
use App\Entity\Repayment;
use App\Repository\LoanRepository;
use App\Repository\RepaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/loans', name: 'loan_')]
class LoanController extends AbstractController
{
    // ─── LOANS ────────────────────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(LoanRepository $repo): Response
    {
        $loans = $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('loan/index.html.twig', ['loans' => $loans]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $loan = new Loan();
            $loan->setUser($this->getUser());
            $loan->setAmount($request->request->get('amount'));
            $loan->setInterestRate($request->request->get('interest_rate'));
            $loan->setStartDate(new \DateTime($request->request->get('start_date')));
            $loan->setEndDate(new \DateTime($request->request->get('end_date')));
            $loan->setStatus('active');

            $em->persist($loan);
            $em->flush();

            $this->addFlash('success', 'Loan created successfully.');
            return $this->redirectToRoute('loan_index');
        }

        return $this->render('loan/new.html.twig');
    }

    #[Route('/repayments', name: 'repayments', methods: ['GET'])]
    public function repayments(LoanRepository $loanRepo): Response
    {
        $loans = $loanRepo->findBy(['user' => $this->getUser()]);
        $repayments = [];
        foreach ($loans as $loan) {
            foreach ($loan->getRepayments() as $r) {
                $repayments[] = $r;
            }
        }
        usort($repayments, fn($a, $b) => $b->getPaymentDate() <=> $a->getPaymentDate());

        return $this->render('loan/repayments.html.twig', ['repayments' => $repayments]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Loan $loan, RepaymentRepository $repaymentRepo): Response
    {
        if ($loan->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $repayments = $repaymentRepo->findBy(['loan' => $loan], ['paymentDate' => 'DESC']);
        $totalPaid  = array_sum(array_map(fn($r) => (float)$r->getAmount(), $repayments));

        return $this->render('loan/show.html.twig', [
            'loan'       => $loan,
            'repayments' => $repayments,
            'totalPaid'  => $totalPaid,
        ]);
    }

    // ─── REPAYMENTS ───────────────────────────────────────────────────────────

    #[Route('/{id}/repayments/new', name: 'repayment_new', methods: ['GET', 'POST'])]
    public function newRepayment(Loan $loan, Request $request, EntityManagerInterface $em): Response
    {
        if ($loan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $repayment = new Repayment();
            $repayment->setLoan($loan);
            $repayment->setAmount($request->request->get('amount'));
            $repayment->setPaymentDate(new \DateTime($request->request->get('payment_date')));
            $repayment->setPaymentType($request->request->get('payment_type'));
            $repayment->setMonthlyPayment($request->request->get('monthly_payment') ?: null);
            $repayment->setStatus('pending');

            $em->persist($repayment);
            $em->flush();

            $this->addFlash('success', 'Repayment recorded successfully.');
            return $this->redirectToRoute('loan_show', ['id' => $loan->getId()]);
        }

        return $this->render('loan/repayment_new.html.twig', ['loan' => $loan]);
    }

    #[Route('/repayments/{id}/confirm', name: 'repayment_confirm', methods: ['POST'])]
    public function confirmRepayment(Repayment $repayment, EntityManagerInterface $em): Response
    {
        if ($repayment->getLoan()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $repayment->setStatus('paid');
        $em->flush();

        $this->addFlash('success', 'Repayment marked as paid.');
        return $this->redirectToRoute('loan_show', ['id' => $repayment->getLoan()->getId()]);
    }
}
