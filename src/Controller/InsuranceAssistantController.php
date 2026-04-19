<?php
namespace App\Controller;

use App\Repository\{ContractRequestRepository, InsurancePackageRepository, InsuredAssetRepository};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/insurance/assistant', name: 'insurance_assistant_')]
class InsuranceAssistantController extends AbstractController
{
    public function __construct(private string $fastapiUrl) {}

    #[Route('', name: 'chat', methods: ['GET', 'POST'])]
    public function chat(
        Request $request,
        HttpClientInterface $client,
        InsuredAssetRepository $assetRepo,
        InsurancePackageRepository $packageRepo,
        ContractRequestRepository $requestRepo,
    ): Response {
        $session = $request->getSession();
        $chatKey = 'insurance_chat_history_' . $this->getUser()->getId();
        $history = $session->get($chatKey, []);

        if ($request->isMethod('POST')) {
            $message = trim((string) $request->request->get('message', ''));

            if ($message !== '') {
                // ── Build context directly from repositories ──────────────
                $user = $this->getUser();

                $contextData = [
                    'user' => [
                        'id'    => $user->getId(),
                        'name'  => $user->getName(),
                        'email' => $user->getEmail(),
                        'phone' => $user->getPhone(),
                    ],
                    'assets' => array_map(fn($a) => [
                        'id'               => $a->getId(),
                        'reference'        => $a->getReference(),
                        'type'             => $a->getType(),
                        'description'      => $a->getDescription(),
                        'location'         => $a->getLocation(),
                        'brand'            => $a->getBrand(),
                        'declared_value'   => $a->getDeclaredValue(),
                        'manufacture_date' => $a->getManufactureDate()?->format('Y-m-d'),
                    ], $assetRepo->findBy(['user' => $user])),

                    'packages' => array_map(fn($p) => [
                        'id'              => $p->getId(),
                        'name'            => $p->getName(),
                        'asset_type'      => $p->getAssetType(),
                        'base_price'      => $p->getBasePrice(),
                        'risk_multiplier' => $p->getRiskMultiplier(),
                        'coverage'        => $p->getCoverageDetails(),
                    ], $packageRepo->findBy(['isActive' => true])),

                    'requests' => array_map(fn($r) => [
                        'id'         => $r->getId(),
                        'asset'      => $r->getAsset()->getReference(),
                        'package'    => $r->getPackage()->getName(),
                        'premium'    => $r->getCalculatedPremium(),
                        'status'     => $r->getStatus(),
                        'created_at' => $r->getCreatedAt()?->format('Y-m-d H:i'),
                    ], $requestRepo->findBy(['user' => $user])),
                ];

                // ── Forward message + context to FastAPI ──────────────────
                try {
                    $response = $client->request('POST', $this->fastapiUrl . '/insurance/chat', [
                        'json' => [
                            'user_id' => $user->getId(),
                            'message' => $message,
                            'context' => $contextData,
                        ],
                        'timeout' => 30,
                    ]);

                    $data  = $response->toArray();
                    $reply = $data['reply'] ?? 'No response received.';

                    $history[] = ['role' => 'user',      'content' => $message];
                    $history[] = ['role' => 'assistant', 'content' => $reply];
                    $history   = array_slice($history, -20);
                    $session->set($chatKey, $history);

                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Assistant unavailable: ' . $e->getMessage());
                }
            }

            return $this->redirectToRoute('insurance_assistant_chat');
        }

        return $this->render('insurance_assistant/chat.html.twig', [
            'history' => $history,
        ]);
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(Request $request, HttpClientInterface $client): Response
    {
        $userId  = $this->getUser()->getId();
        $chatKey = 'insurance_chat_history_' . $userId;

        try {
            $client->request('POST', $this->fastapiUrl . '/insurance/reset/' . $userId, [
                'timeout' => 5,
            ]);
        } catch (\Throwable) {}

        $request->getSession()->remove($chatKey);
        $this->addFlash('success', 'Conversation has been reset.');
        return $this->redirectToRoute('insurance_assistant_chat');
    }
}