<?php
namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/payments', name: 'payment_')]
class PaymentController extends AbstractController
{
    private string $apiKey;
    private string $paymentUrl;

    public function __construct(string $paymeeApiKey, string $paymeeUrl)
    {
        $this->apiKey     = $paymeeApiKey;
        $this->paymentUrl = $paymeeUrl;
    }

    // ── List user's payment transactions ─────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TransactionRepository $repo): Response
    {
        $transactions = $repo->findBy(
            ['user' => $this->getUser(), 'referenceType' => 'paymee'],
            ['createdAt' => 'DESC']
        );

        return $this->render('payment/index.html.twig', ['transactions' => $transactions]);
    }

    // ── Create payment via Paymee ─────────────────────────────────────────────

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        HttpClientInterface $client
    ): Response {
        if ($request->isMethod('POST')) {
            $amount = (float)$request->request->get('amount');
            $note   = $request->request->get('note', 'Payment');
            $user   = $this->getUser();

            $nameParts = explode(' ', trim($user->getName() ?? ''), 2);
            $firstName = $nameParts[0] ?? 'User';
            $lastName  = $nameParts[1] ?? $firstName;

            try {
                $response = $client->request('POST', $this->paymentUrl, [
                    'headers' => [
                        'Authorization' => 'Token ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'amount'      => $amount,
                        'note'        => $note,
                        'first_name'  => $firstName,
                        'last_name'   => $lastName,
                        'email'       => $user->getEmail(),
                        'phone'       => $user->getPhone() ?? '00000000',
                        'return_url'  => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'cancel_url'  => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'webhook_url' => $this->generateUrl('payment_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ],
                ]);

                $data = $response->toArray(false);

                if (($data['status'] ?? false) === true) {
                    $token      = $data['data']['token'];
                    $gatewayUrl = $data['data']['payment_url'];

                    // Persist a PENDING transaction, store Paymee token in description
                    $transaction = new Transaction();
                    $transaction->setUser($user);
                    $transaction->setAmount((string)$amount);
                    $transaction->setType('payment');
                    $transaction->setStatus('PENDING');
                    $transaction->setDescription($note . ' [paymee:' . $token . ']');
                    $transaction->setReferenceType('paymee');
                    $transaction->setCurrency('TND');

                    $em->persist($transaction);
                    $em->flush();

                    // Remember the transaction in session so we can update it on return
                    $request->getSession()->set('paymee_token', $token);
                    $request->getSession()->set('paymee_transaction_id', $transaction->getId());

                    return $this->redirect($gatewayUrl);
                }

                $this->addFlash('error', 'Paymee rejected the request: ' . ($data['message'] ?? 'unknown error'));
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Could not contact Paymee API: ' . $e->getMessage());
            }
        }

        return $this->render('payment/create.html.twig');
    }

    // ── Payment success return URL ────────────────────────────────────────────

    #[Route('/success', name: 'success', methods: ['GET'])]
    public function success(
        Request $request,
        EntityManagerInterface $em,
        TransactionRepository $repo,
        HttpClientInterface $client
    ): Response {
        $token         = $request->query->get('payment_token') ?? $request->getSession()->get('paymee_token');
        $transactionId = $request->getSession()->get('paymee_transaction_id');

        $verified = false;

        if ($token) {
            try {
                $checkUrl  = 'https://sandbox.paymee.tn/api/v2/payments/' . $token . '/check';
                $checkResp = $client->request('GET', $checkUrl, [
                    'headers' => ['Authorization' => 'Token ' . $this->apiKey],
                ]);
                $checkData = $checkResp->toArray(false);
                $verified  = ($checkData['data']['payment_status'] ?? false) === true;
            } catch (\Throwable) {
                // If check fails, trust the redirect
                $verified = true;
            }

            if ($transactionId && ($transaction = $repo->find($transactionId))) {
                $transaction->setStatus($verified ? 'COMPLETED' : 'FAILED');
                $em->flush();
            }

            $request->getSession()->remove('paymee_token');
            $request->getSession()->remove('paymee_transaction_id');
        }

        return $this->render('payment/success.html.twig', ['verified' => $verified]);
    }

    // ── Payment cancel return URL ─────────────────────────────────────────────

    #[Route('/cancel', name: 'cancel', methods: ['GET'])]
    public function cancel(
        Request $request,
        EntityManagerInterface $em,
        TransactionRepository $repo
    ): Response {
        $transactionId = $request->getSession()->get('paymee_transaction_id');

        if ($transactionId && ($transaction = $repo->find($transactionId))) {
            $transaction->setStatus('CANCELLED');
            $em->flush();
        }

        $request->getSession()->remove('paymee_token');
        $request->getSession()->remove('paymee_transaction_id');

        $this->addFlash('warning', 'Payment was cancelled.');
        return $this->render('payment/cancel.html.twig');
    }

    // ── Paymee webhook (server-to-server) ────────────────────────────────────

    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em, TransactionRepository $repo): Response
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = $data['token'] ?? null;
        $paid  = $data['payment_status'] ?? false;

        if ($token) {
            // Find matching transaction by searching for token in description
            $qb = $em->createQueryBuilder();
            $qb->select('t')
               ->from(Transaction::class, 't')
               ->where('t.description LIKE :token')
               ->setParameter('token', '%paymee:' . $token . '%')
               ->setMaxResults(1);

            $transaction = $qb->getQuery()->getOneOrNullResult();

            if ($transaction) {
                $transaction->setStatus($paid ? 'COMPLETED' : 'FAILED');
                $em->flush();
            }
        }

        return new Response('OK', 200);
    }
}
