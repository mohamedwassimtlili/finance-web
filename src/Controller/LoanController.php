<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Entity\Repayment;
use App\Repository\LoanRepository;
use App\Repository\RepaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use OpenAI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/loans', name: 'loan_')]
class LoanController extends AbstractController
{
    /**
     * Helper method to check if loan belongs to current user
     * This handles different possible method names
     */
    private function isLoanOwner(Loan $loan): bool
    {
        $user = $this->getUser();
        
        // Try different possible getter methods
        if (method_exists($loan, 'getBorrower')) {
            return $loan->getBorrower() === $user;
        }
        if (method_exists($loan, 'getUser')) {
            return $loan->getUser() === $user;
        }
        if (method_exists($loan, 'getOwner')) {
            return $loan->getOwner() === $user;
        }
        
        // If no method exists, try property access via reflection
        try {
            $reflection = new \ReflectionProperty($loan, 'borrower');
            $reflection->setAccessible(true);
            return $reflection->getValue($loan) === $user;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 1. THE ADVANCED DASHBOARD
     * Includes: News API, Currency Conversion, Status Metrics, and Progress Table
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(LoanRepository $loanRepo, HttpClientInterface $client): Response
    {
        // Get loans - use the correct field name 'borrower'
        $loans = $loanRepo->findBy(['borrower' => $this->getUser()]);
        
        $totalLoans = count($loans);
        $totalBorrowed = 0;
        $totalRepaid = 0;
        $statusCounts = ['active' => 0, 'paid' => 0, 'defaulted' => 0];
        $loanProgress = [];
        $monthlyData = [];

        foreach ($loans as $loan) {
            $totalBorrowed += (float)$loan->getAmount();
            $status = $loan->getStatus();
            if (array_key_exists($status, $statusCounts)) $statusCounts[$status]++;

            $loanPaid = 0;
            foreach ($loan->getRepayments() as $repayment) {
                $loanPaid += (float)$repayment->getAmount();
                
                // Group repayments by month for chart
                $month = $repayment->getPaymentDate()->format('F Y');
                if (!isset($monthlyData[$month])) {
                    $monthlyData[$month] = 0;
                }
                $monthlyData[$month] += (float)$repayment->getAmount();
            }
            $totalRepaid += $loanPaid;

            $remainingAmount = $loan->getAmount() - $loanPaid;
            $percentValue = $loan->getAmount() > 0 ? ($loanPaid / $loan->getAmount()) * 100 : 0;

            $loanProgress[] = [
                'loan' => $loan,
                'paid' => $loanPaid,
                'percent' => $percentValue,
                'pct' => $percentValue,
                'remaining' => $remainingAmount
            ];
        }

        // Sort monthly data by date
        ksort($monthlyData);

        // --- ECONOMIC NEWS API (NewsData.io) ---
        $economicNews = [];
        try {
            // Free API - No API key required for basic usage
            $newsResponse = $client->request('GET', 'https://newsdata.io/api/1/news', [
                'query' => [
                    'country' => 'us,gb,fr,de',
                    'category' => 'business,economy',
                    'language' => 'en',
                    'size' => 6,
                ],
                'timeout' => 5,
            ]);
            
            if ($newsResponse->getStatusCode() === 200) {
                $newsData = $newsResponse->toArray();
                if (isset($newsData['results']) && !empty($newsData['results'])) {
                    foreach ($newsData['results'] as $item) {
                        $economicNews[] = [
                            'title' => $item['title'] ?? 'Economic Update',
                            'description' => substr($item['description'] ?? $item['content'] ?? '', 0, 120) . '...',
                            'link' => $item['link'] ?? '#',
                            'source' => $item['source_id'] ?? 'Financial News',
                            'image' => $item['image_url'] ?? null,
                            'pubDate' => isset($item['pubDate']) ? date('H:i, M d', strtotime($item['pubDate'])) : date('H:i, M d'),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // If API fails, use fallback news
            $economicNews = [
                [
                    'title' => 'Global Markets Show Resilience',
                    'description' => 'Major indices recover as investors digest economic data...',
                    'link' => '#',
                    'source' => 'Market Watch',
                    'image' => null,
                    'pubDate' => date('H:i, M d'),
                ],
                [
                    'title' => 'Central Banks Signal Rate Decisions',
                    'description' => 'Federal Reserve and ECB maintain cautious stance on monetary policy...',
                    'link' => '#',
                    'source' => 'Financial Times',
                    'image' => null,
                    'pubDate' => date('H:i, M d'),
                ],
                [
                    'title' => 'Tech Sector Leads Market Gains',
                    'description' => 'Technology stocks rally on AI optimism and strong earnings...',
                    'link' => '#',
                    'source' => 'Bloomberg',
                    'image' => null,
                    'pubDate' => date('H:i, M d'),
                ],
                [
                    'title' => 'Oil Prices Fluctuate on Supply Concerns',
                    'description' => 'Crude oil markets volatile amid geopolitical tensions...',
                    'link' => '#',
                    'source' => 'Reuters',
                    'image' => null,
                    'pubDate' => date('H:i, M d'),
                ],
                [
                    'title' => 'Emerging Markets Attract Foreign Investment',
                    'description' => 'Developing economies see increased capital inflows...',
                    'link' => '#',
                    'source' => 'CNBC',
                    'image' => null,
                    'pubDate' => date('H:i, M d'),
                ],
                [
                    'title' => 'Inflation Trends Show Mixed Signals',
                    'description' => 'Core inflation remains sticky while headline numbers improve...',
                    'link' => '#',
                    'source' => 'Wall Street Journal',
                    'image' => null,
                    'pubDate' => date('H:i, M d'),
                ],
            ];
        }

        // --- CURRENCY CONVERSION LOGIC ---
        $eurRate = 0.30;
        $usdRate = 0.32;
        try {
            $currencyData = $client->request('GET', 'https://api.exchangerate-api.com/v4/latest/TND', [
                'timeout' => 3,
            ])->toArray();
            $eurRate = $currencyData['rates']['EUR'] ?? 0.30;
            $usdRate = $currencyData['rates']['USD'] ?? 0.32;
        } catch (\Exception $e) { }

        // Financial tips fallback
        $financialTips = [
            "💰 Smart Tip: Pay more than the minimum payment to reduce interest charges.",
            "📊 Financial Health: Keep your debt-to-income ratio below 36%.",
            "🎯 Goal Setting: Set up automatic payments to avoid late fees.",
            "💡 Emergency Fund: Build savings while paying off debt.",
            "✅ Credit Score: Check your credit report annually for errors.",
        ];
        $newsTip = $financialTips[array_rand($financialTips)];

        return $this->render('loan/dashboard.html.twig', [
            'totalLoans' => $totalLoans,
            'totalBorrowed' => $totalBorrowed,
            'totalRepaid' => $totalRepaid,
            'remaining' => $totalBorrowed - $totalRepaid,
            'statusCounts' => $statusCounts,
            'loanProgress' => $loanProgress,
            'monthlyData' => $monthlyData,
            'economicNews' => $economicNews,
            'newsTip' => $newsTip,
            'eurRate' => $eurRate,
            'usdRate' => $usdRate,
        ]);
    }

    /**
     * 2. CRUD: INDEX
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(LoanRepository $repo): Response
    {
        return $this->render('loan/index.html.twig', [
            'loans' => $repo->findBy(['borrower' => $this->getUser()], ['createdAt' => 'DESC'])
        ]);
    }

    /**
     * 3. CRUD: CREATE
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $loan = new Loan();
            
            // Try different setter methods
            if (method_exists($loan, 'setBorrower')) {
                $loan->setBorrower($this->getUser());
            } elseif (method_exists($loan, 'setUser')) {
                $loan->setUser($this->getUser());
            } else {
                // Try direct property access
                $loan->borrower = $this->getUser();
            }
            
            $loan->setAmount($request->request->get('amount'));
            $loan->setInterestRate($request->request->get('interest_rate'));
            $loan->setStartDate(new \DateTime($request->request->get('start_date')));
            $loan->setEndDate(new \DateTime($request->request->get('end_date')));
            $loan->setStatus('active');
            $em->persist($loan);
            $em->flush();
            $this->addFlash('success', 'Loan created successfully!');
            return $this->redirectToRoute('loan_index');
        }
        return $this->render('loan/new.html.twig');
    }

    /**
     * 4. CRUD: SHOW (Enhanced with Remaining Calculations)
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Loan $loan, RepaymentRepository $repaymentRepo): Response
    {
        // Use the helper method for security check
        if (!$this->isLoanOwner($loan)) {
            throw $this->createAccessDeniedException('You do not have access to this loan.');
        }
        
        $repayments = $repaymentRepo->findBy(['loan' => $loan], ['paymentDate' => 'DESC']);
        $totalPaid = array_sum(array_map(fn($r) => (float)$r->getAmount(), $repayments));

        return $this->render('loan/show.html.twig', [
            'loan' => $loan,
            'repayments' => $repayments,
            'totalPaid' => $totalPaid,
            'remaining' => max(0, $loan->getAmount() - $totalPaid)
        ]);
    }

    /**
     * 5. AJAX LOAN SIMULATOR
     */
    #[Route('/simulate', name: 'simulate', methods: ['POST'])]
    public function simulate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $remaining = $data['remaining'] ?? 0;
        $monthly = $data['monthly_payment'] ?? 1;
        $monthsNeeded = ($monthly > 0) ? ceil($remaining / $monthly) : 0;
        
        // Simple AI message based on months needed
        $aiMessage = '';
        $risk = '';
        $riskColor = '';
        
        if ($monthsNeeded <= 12) {
            $aiMessage = "Great! You'll be debt-free in less than a year. Keep up the good work!";
            $risk = "Low Risk";
            $riskColor = "success";
        } elseif ($monthsNeeded <= 36) {
            $aiMessage = "Good progress! Consistent payments will clear your debt in {$monthsNeeded} months.";
            $risk = "Medium Risk";
            $riskColor = "warning";
        } else {
            $aiMessage = "Consider increasing payments to reduce interest and clear debt faster.";
            $risk = "High Risk";
            $riskColor = "danger";
        }
        
        // Generate timeline for chart
        $timeline = [];
        $balance = $remaining;
        for ($i = 1; $i <= min($monthsNeeded, 12); $i++) {
            $balance = max(0, $balance - $monthly);
            $timeline[] = ['month' => "Month {$i}", 'balance' => $balance];
        }
        
        return new JsonResponse([
            'months_needed' => $monthsNeeded,
            'ai_message' => $aiMessage,
            'risk' => $risk,
            'risk_color' => $riskColor,
            'timeline' => $timeline
        ]);
    }

    /**
     * 6. SIMPLE ADD REPAYMENT (No form class needed)
     */
    #[Route('/{id}/repayment/new', name: 'repayment_new', methods: ['GET', 'POST'])]
    public function addRepayment(Loan $loan, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isLoanOwner($loan)) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $amount = $request->request->get('amount');
            $paymentDate = $request->request->get('payment_date');
            $paymentType = $request->request->get('payment_type', 'monthly');
            
            if ($amount && $paymentDate) {
                $repayment = new Repayment();
                $repayment->setLoan($loan);
                $repayment->setAmount($amount);
                $repayment->setPaymentDate(new \DateTime($paymentDate));
                $repayment->setPaymentType($paymentType);
                $repayment->setStatus('paid');
                
                $em->persist($repayment);
                $em->flush();
                
                $this->addFlash('success', 'Repayment recorded successfully!');
                return $this->redirectToRoute('loan_show', ['id' => $loan->getId()]);
            } else {
                $this->addFlash('error', 'Please fill in amount and payment date.');
            }
        }
        
        // FIXED: Changed from 'repayment/simple_new.html.twig' to 'loan/simple_new.html.twig'
        return $this->render('loan/simple_new.html.twig', [
            'loan' => $loan
        ]);
    }

    /**
     * 7. AI RISK ANALYSIS (OpenAI Integration)
     */
    #[Route('/{id}/analyse', name: 'analyse', methods: ['GET'])]
    public function analyseRisk(Loan $loan, RepaymentRepository $repaymentRepo): Response
    {
        if (!$this->isLoanOwner($loan)) {
            throw $this->createAccessDeniedException();
        }
        
        $repayments = $repaymentRepo->findBy(['loan' => $loan]);
        $totalPaid = array_sum(array_map(fn($r) => (float)$r->getAmount(), $repayments));
        $remaining = max(0, (float)$loan->getAmount() - $totalPaid);
        $pct = $loan->getAmount() > 0 ? round(($totalPaid / $loan->getAmount()) * 100, 1) : 0;
        
        $analysis = ['level' => 'N/A', 'advice' => 'AI Analysis is currently unavailable.'];
        try {
            $apiKey = $this->getParameter('kernel.openai_key');
            $client = OpenAI::client($apiKey);
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a financial risk advisor.'],
                    ['role' => 'user', 'content' => "Analyse this loan: Total {$loan->getAmount()}, Paid {$totalPaid}, Remaining {$remaining}. Provide risk level and one tip."]
                ],
            ]);
            $analysis = ['level' => 'Active', 'advice' => $result->choices[0]->message->content];
        } catch (\Exception $e) { }

        return $this->render('loan/analyse.html.twig', [
            'loan' => $loan, 
            'analysis' => $analysis, 
            'totalPaid' => $totalPaid, 
            'remaining' => $remaining,
            'pct' => $pct
        ]);
    }

    /**
     * 8. PDF INVOICE & QR CODE GENERATION
     */
    #[Route('/{id}/invoice', name: 'invoice', methods: ['GET'])]
    public function downloadInvoice(Loan $loan, RepaymentRepository $repaymentRepo): Response
    {
        if (!$this->isLoanOwner($loan)) {
            throw $this->createAccessDeniedException();
        }
        
        $repayments = $repaymentRepo->findBy(['loan' => $loan]);
        $totalPaid = array_sum(array_map(fn($r) => (float)$r->getAmount(), $repayments));
        
        $qrCode = new QrCode('LoanID:' . $loan->getId() . '|Remaining:' . ($loan->getAmount() - $totalPaid));
        $writer = new PngWriter();
        $qrBase64 = base64_encode($writer->write($qrCode)->getString());
        
        $html = $this->renderView('loan/invoice.html.twig', [
            'loan' => $loan, 
            'repayments' => $repayments, 
            'totalPaid' => $totalPaid, 
            'qrCode' => $qrBase64
        ]);
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice_'.$loan->getId().'.pdf"'
        ]);
    }

    /**
     * 9. CRUD: DELETE
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Loan $loan, EntityManagerInterface $em): Response
    {
        if (!$this->isLoanOwner($loan)) {
            throw $this->createAccessDeniedException();
        }
        
        if ($this->isCsrfTokenValid('delete_loan_' . $loan->getId(), $request->request->get('_token'))) {
            $em->remove($loan);
            $em->flush();
            $this->addFlash('success', 'Loan deleted successfully!');
        }
        return $this->redirectToRoute('loan_index');
    }

    /**
     * 10. CRUD: EDIT
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Loan $loan, EntityManagerInterface $em): Response
    {
        if (!$this->isLoanOwner($loan)) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $loan->setAmount($request->request->get('amount'));
            $loan->setInterestRate($request->request->get('interest_rate'));
            $loan->setStartDate(new \DateTime($request->request->get('start_date')));
            $loan->setEndDate(new \DateTime($request->request->get('end_date')));
            $loan->setStatus($request->request->get('status'));
            $em->flush();
            $this->addFlash('success', 'Loan updated successfully!');
            return $this->redirectToRoute('loan_index');
        }

        return $this->render('loan/edit.html.twig', [
            'loan' => $loan
        ]);
    }
}