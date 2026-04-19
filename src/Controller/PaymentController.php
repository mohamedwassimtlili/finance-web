<?php
namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/payments', name: 'payment_')]
class PaymentController extends AbstractController
{
    // ── List all user payments ────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TransactionRepository $repo): Response
    {
        $transactions = $repo->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('payment/index.html.twig', ['transactions' => $transactions]);
    }

    // ── Create a new payment ──────────────────────────────────────────────────

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $amount      = $request->request->get('amount');
            $description = trim($request->request->get('description', ''));
            $type        = $request->request->get('type', 'payment');
            $status      = $request->request->get('status', 'COMPLETED');
            $currency    = $request->request->get('currency', 'TND');

            if (!$amount || (float)$amount <= 0) {
                $this->addFlash('error', 'Amount must be a positive number.');
                return $this->render('payment/new.html.twig');
            }

            $transaction = new Transaction();
            $transaction->setUser($this->getUser());
            $transaction->setAmount((string)(float)$amount);
            $transaction->setType($type);
            $transaction->setStatus($status);
            $transaction->setDescription($description ?: null);
            $transaction->setCurrency($currency);
            $transaction->setReferenceType('manual');

            $em->persist($transaction);
            $em->flush();

            $this->addFlash('success', 'Payment recorded successfully.');
            return $this->redirectToRoute('payment_index');
        }

        return $this->render('payment/new.html.twig');
    }

    // ── Show a single payment ─────────────────────────────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Transaction $transaction): Response
    {
        if ($transaction->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('payment/show.html.twig', ['transaction' => $transaction]);
    }

    // ── Edit a payment ────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Transaction $transaction, EntityManagerInterface $em): Response
    {
        if ($transaction->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $amount      = $request->request->get('amount');
            $description = trim($request->request->get('description', ''));
            $type        = $request->request->get('type', 'payment');
            $status      = $request->request->get('status', 'COMPLETED');
            $currency    = $request->request->get('currency', 'TND');

            if (!$amount || (float)$amount <= 0) {
                $this->addFlash('error', 'Amount must be a positive number.');
                return $this->render('payment/edit.html.twig', ['transaction' => $transaction]);
            }

            $transaction->setAmount((string)(float)$amount);
            $transaction->setType($type);
            $transaction->setStatus($status);
            $transaction->setDescription($description ?: null);
            $transaction->setCurrency($currency);

            $em->flush();

            $this->addFlash('success', 'Payment updated successfully.');
            return $this->redirectToRoute('payment_show', ['id' => $transaction->getId()]);
        }

        return $this->render('payment/edit.html.twig', ['transaction' => $transaction]);
    }

    // ── Delete a payment ──────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Transaction $transaction, EntityManagerInterface $em): Response
    {
        if ($transaction->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_payment_' . $transaction->getId(), $request->request->get('_token'))) {
            $em->remove($transaction);
            $em->flush();
            $this->addFlash('success', 'Payment deleted.');
        }

        return $this->redirectToRoute('payment_index');
    }
}
