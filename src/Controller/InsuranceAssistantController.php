<?php
namespace App\Controller;

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
    public function chat(Request $request, HttpClientInterface $client): Response
    {
        $session  = $request->getSession();
        $chatKey  = 'insurance_chat_history_' . $this->getUser()->getId();
        $history  = $session->get($chatKey, []);

        if ($request->isMethod('POST')) {
            $message = trim((string) $request->request->get('message', ''));

            if ($message !== '') {
                try {
                    $response = $client->request('POST', $this->fastapiUrl . '/insurance/chat', [
                        'json'    => [
                            'user_id' => $this->getUser()->getId(),
                            'message' => $message,
                        ],
                        'timeout' => 30,
                    ]);

                    $data  = $response->toArray();
                    $reply = $data['reply'] ?? 'No response received.';

                    // Keep display history in session (for UI only)
                    $history[] = ['role' => 'user',      'content' => $message];
                    $history[] = ['role' => 'assistant', 'content' => $reply];
                    // Trim to last 20 messages
                    $history = array_slice($history, -20);
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

        // Clear FastAPI server-side memory
        try {
            $client->request('POST', $this->fastapiUrl . '/insurance/reset/' . $userId, [
                'timeout' => 5,
            ]);
        } catch (\Throwable) {
            // non-fatal — server may be down
        }

        // Clear Symfony session display history
        $request->getSession()->remove($chatKey);

        $this->addFlash('success', 'Conversation has been reset.');
        return $this->redirectToRoute('insurance_assistant_chat');
    }
}
