<?php
namespace App\Controller;

use App\Entity\Complaint;
use App\Repository\ComplaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $complaint = new Complaint();
            $complaint->setUser($this->getUser());
            $complaint->setSubject((string) $request->request->get('subject'));
            $complaint->setComplaintDate(new \DateTime());
            $complaint->setStatus('pending');

            // Trigger Symfony validation (to enforce the #[NoProfanity] and other constraints)
            $errors = $validator->validate($complaint);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
                return $this->render('complaint/new.html.twig');
            }

            $em->persist($complaint);
            $em->flush();

            $this->addFlash('success', 'Your complaint has been submitted. We will respond shortly.');
            return $this->redirectToRoute('complaint_index');
        }

        return $this->render('complaint/new.html.twig');
    }

    #[Route('/suggest', name: 'suggest', methods: ['POST'])]
    public function suggest(Request $request, HttpClientInterface $httpClient): Response
    {
        $data = json_decode((string) $request->getContent(), true);
        $text = $data['text'] ?? '';

        if (strlen(trim($text)) < 5) {
            return $this->json(['suggestion' => '']);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? '';

        if (empty($apiKey) || $apiKey === 'your_api_key_here') {
            return $this->json(['suggestion' => '']);
        }

        try {
            $response = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey, [
                'json' => [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => "You are a smart autocomplete assistant for a formal Fintech complaint. Finish the user's sentence smoothly and professionally. Only reply with the next 1 to 5 words to complete their sentence. Provide nothing else. Do not use formatting, quotes, or bad words. If the sentence is already complete, return an empty string."]
                        ]
                    ],
                    'contents' => [
                        ['parts' => [['text' => $text]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 15
                    ]
                ]
            ]);

            $content = $response->toArray();
            $suggestion = $content['candidates'][0]['content']['parts'][0]['text'] ?? '';
            // Strip any surrounding quotes if generated
            $suggestion = trim(preg_replace('/^["\']|["\']$/', '', $suggestion));
            
            return $this->json(['suggestion' => $suggestion]);
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            if ($statusCode === 429) {
                return $this->json(['error' => 'Rate limit exceeded. Please wait a moment and try again.'], 429);
            }
            return $this->json(['error' => 'API Error: ' . $statusCode], $statusCode);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'An unexpected error occurred contacting the AI.'], 500);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Complaint $complaint): Response
    {
        if ($complaint->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('complaint/show.html.twig', ['complaint' => $complaint]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Complaint $complaint, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        // Only owner can edit, and ideally we shouldn't edit if it's already resolved/processing, but let's just check owner
        if ($complaint->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $complaint->setSubject((string) $request->request->get('subject'));

            $errors = $validator->validate($complaint);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
                return $this->render('complaint/edit.html.twig', ['complaint' => $complaint]);
            }

            $em->flush();
            $this->addFlash('success', 'Your complaint has been updated successfully.');
            return $this->redirectToRoute('complaint_index');
        }

        return $this->render('complaint/edit.html.twig', [
            'complaint' => $complaint,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Complaint $complaint, EntityManagerInterface $em): Response
    {
        if ($complaint->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_complaint'.$complaint->getId(), (string)$request->request->get('_token'))) {
            $em->remove($complaint);
            $em->flush();
            $this->addFlash('success', 'Complaint deleted successfully.');
        } else {
            $this->addFlash('danger', 'Invalid deletion token.');
        }

        return $this->redirectToRoute('complaint_index');
    }
}
