<?php
namespace App\Controller\Admin;

use App\Entity\ContractRequest;
use App\Repository\ContractRequestRepository;
use App\Service\BoldSignService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contracts', name: 'admin_contracts_')]
class ContractController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ContractRequestRepository $repo): Response
    {
        return $this->render('admin/contracts/index.html.twig', [
            'requests' => $repo->findBy(['status' => 'APPROVED']),
        ]);
    }

    #[Route('/{id}/send', name: 'send', methods: ['POST'])]
    public function send(
        ContractRequest      $contractRequest,
        BoldSignService      $boldSign,
        EntityManagerInterface $em,
    ): Response {
        if ($contractRequest->getStatus() !== 'APPROVED') {
            $this->addFlash('warning', 'Only approved contracts can be sent for signature.');
            return $this->redirectToRoute('admin_contracts_index');
        }

        try {
            $user    = $contractRequest->getUser();
            $asset   = $contractRequest->getAsset();
            $package = $contractRequest->getPackage();

            $documentId = $boldSign->sendForSignature(
                userName:         $user->getFullName(),
                assetReference:   $asset->getReference(),
                insurancePackage: $package->getName(),
                approvedValue:    $contractRequest->getCalculatedPremium() . ' TND',
                contractDate:     $contractRequest->getCreatedAt() ?? new \DateTime(),
                signerEmail:      $user->getEmail(),
            );

            // Persist the BoldSign document ID for webhook tracking
            $contractRequest->setBoldSignDocumentId($documentId);
            $contractRequest->setStatus('SENT_FOR_SIGNATURE');
            $em->flush();

            $this->addFlash('success', sprintf(
                'Contract sent to %s for signature (document: %s).',
                $user->getEmail(),
                $documentId
            ));

        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Failed to send contract: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_contracts_index');
    }

    // ── Webhook: BoldSign POSTs events here ──────────────────────────
    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(
        \Symfony\Component\HttpFoundation\Request $request,
        ContractRequestRepository $repo,
        EntityManagerInterface $em,
    ): Response {
        $payload = json_decode($request->getContent(), true);
        $event   = $payload['event']['type']       ?? null;
        $docId   = $payload['document']['documentId'] ?? null;

        if (!$docId) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $contractRequest = $repo->findOneBy(['boldSignDocumentId' => $docId]);
        if (!$contractRequest) {
            return new Response('unknown document', Response::HTTP_OK);
        }

        match ($event) {
            'completed' => $contractRequest->setStatus('SIGNED'),
            'declined'  => $contractRequest->setStatus('DECLINED'),
            default     => null,
        };

        $em->flush();
        return new Response('ok', Response::HTTP_OK);
    }
}