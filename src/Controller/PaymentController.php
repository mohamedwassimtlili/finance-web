<?php
namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/payments', name: 'payment_')]
class PaymentController extends AbstractController
{
    private string $apiKey;
    private string $paymentUrl;
    private string $mailerSender;
    private MailerInterface $mailer;

    public function __construct(string $paymeeApiKey, string $paymeeUrl, string $mailerSender, MailerInterface $mailer)
    {
        $this->apiKey       = $paymeeApiKey;
        $this->paymentUrl   = $paymeeUrl;
        $this->mailerSender = $mailerSender;
        $this->mailer       = $mailer;
    }

    // ── Send confirmation email ───────────────────────────────────────────────

    private function sendConfirmationEmail(Transaction $transaction): void
    {
        try {
            $html = $this->renderView('email/payment_confirmation.html.twig', [
                'transaction' => $transaction,
            ]);

            $email = (new Email())
                ->from($this->mailerSender)
                ->to($transaction->getUser()->getEmail())
                ->subject(sprintf(
                    '[MyApp] Payment %s — Receipt #%d',
                    $transaction->getStatus(),
                    $transaction->getId()
                ))
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Log email failure but don't block the user flow
            // (visible as a warning flash on the next page)
            $this->addFlash('warning', 'Email could not be sent: ' . $e->getMessage());
        }
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

    // ── Test email (dev only) ─────────────────────────────────────────────────

    #[Route('/test-email', name: 'test_email', methods: ['GET'])]
    public function testEmail(): Response
    {
        $user = $this->getUser();
        $userEmail = $user->getEmail();

        try {
            $email = (new Email())
                ->from($this->mailerSender)
                ->to($userEmail)
                ->subject('[MyApp] Test Email - SMTP Check')
                ->html('<h2>✅ SMTP is working!</h2><p>This is a test email sent to: <strong>' . htmlspecialchars((string)$userEmail) . '</strong></p>');

            $this->mailer->send($email);

            return new Response('<h2 style="color:green">✅ Email sent successfully exactly to: ' . htmlspecialchars((string)$userEmail) . '</h2>');
        } catch (\Throwable $e) {
            return new Response('<h2 style="color:red">❌ Email failed for ' . htmlspecialchars((string)$userEmail) . ':</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
        }
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

            // Server-side guard: amount must be positive
            if ($amount <= 0) {
                $this->addFlash('error', 'Amount must be greater than 0.');
                return $this->render('payment/create.html.twig');
            }

            $nameParts = explode(' ', trim($user->getName() ?? ''), 2);
            $firstName = $nameParts[0] ?? 'User';
            $lastName  = $nameParts[1] ?? $firstName;

            // Normalize phone to Paymee format: +216XXXXXXXX
            $rawPhone  = $user->getPhone() ?? '';
            $digits    = preg_replace('/\D/', '', $rawPhone); // strip non-digits
            if (str_starts_with($digits, '216')) {
                $digits = substr($digits, 3); // remove country code if already present
            }
            $phone = '+216' . ($digits !== '' ? $digits : '00000000');

            // Paymee requires all callback URLs to start with https://
            $forceHttps = static fn(string $url): string => preg_replace('#^http://#', 'https://', $url);

            $payload = [
                'amount'      => $amount,
                'note'        => $note,
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'email'       => $user->getEmail(),
                'phone'       => $phone,
                'return_url'  => $forceHttps($this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL)),
                'cancel_url'  => $forceHttps($this->generateUrl('payment_cancel',  [], UrlGeneratorInterface::ABSOLUTE_URL)),
                'webhook_url' => $forceHttps($this->generateUrl('payment_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)),
            ];

            try {
                $response = $client->request('POST', $this->paymentUrl, [
                    'verify_peer' => false,
                    'verify_host' => false,
                    'headers' => [
                        'Authorization' => 'Token ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
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

                    // Send email notification about the initiated payment
                    $this->sendConfirmationEmail($transaction);

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
                    'verify_peer' => false,
                    'verify_host' => false,
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

                // Feature 1 — auto-send email receipt
                $this->sendConfirmationEmail($transaction);
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

                // Feature 1 — auto-send email receipt via webhook
                $this->sendConfirmationEmail($transaction);
            }
        }

        return new Response('OK', 200);
    }

    // ── Download transaction PDF ──────────────────────────────────────────────

    #[Route('/{id}/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(Transaction $transaction): Response
    {
        // Security: only the owner can download their receipt
        if ($transaction->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $html = $this->renderView('payment/pdf.html.twig', [
            'transaction' => $transaction,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('receipt-%d-%s.pdf', $transaction->getId(), date('Ymd'));

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
