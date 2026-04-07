<?php
namespace App\Controller;

use App\Entity\ContractRequest;
use App\Entity\InsuredAsset;
use App\Repository\ContractRequestRepository;
use App\Repository\InsurancePackageRepository;
use App\Repository\InsuredAssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/insurance', name: 'insurance_')]
class InsuranceController extends AbstractController
{
    // ─── ASSETS ───────────────────────────────────────────────────────────────

    #[Route('/assets', name: 'assets', methods: ['GET'])]
    public function assets(InsuredAssetRepository $repo): Response
    {
        $assets = $repo->findBy(['user' => $this->getUser()]);

        return $this->render('insurance/assets/index.html.twig', [
            'assets' => $assets,
        ]);
    }

    #[Route('/assets/new', name: 'asset_new', methods: ['GET', 'POST'])]
    public function newAsset(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $asset = new InsuredAsset();
            $asset->setUser($this->getUser());
            $asset->setReference($request->request->get('reference'));
            $asset->setType($request->request->get('type'));
            $asset->setDescription($request->request->get('description'));
            $asset->setLocation($request->request->get('location'));
            $asset->setBrand($request->request->get('brand'));
            $asset->setDeclaredValue($request->request->get('declared_value'));
            $asset->setManufactureDate(new \DateTime($request->request->get('manufacture_date')));

            $em->persist($asset);
            $em->flush();

            $this->addFlash('success', 'Asset registered successfully.');
            return $this->redirectToRoute('insurance_assets');
        }

        return $this->render('insurance/assets/new.html.twig');
    }

    #[Route('/assets/{id}/edit', name: 'asset_edit', methods: ['GET', 'POST'])]
    public function editAsset(InsuredAsset $asset, Request $request, EntityManagerInterface $em): Response
    {
        if ($asset->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $asset->setReference($request->request->get('reference'));
            $asset->setType($request->request->get('type'));
            $asset->setDescription($request->request->get('description'));
            $asset->setLocation($request->request->get('location'));
            $asset->setBrand($request->request->get('brand'));
            $asset->setDeclaredValue($request->request->get('declared_value'));
            $asset->setManufactureDate(new \DateTime($request->request->get('manufacture_date')));

            $em->flush();

            $this->addFlash('success', 'Asset updated successfully.');
            return $this->redirectToRoute('insurance_assets');
        }

        return $this->render('insurance/assets/edit.html.twig', ['asset' => $asset]);
    }

    #[Route('/assets/{id}/delete', name: 'asset_delete', methods: ['POST'])]
    public function deleteAsset(InsuredAsset $asset, EntityManagerInterface $em): Response
    {
        if ($asset->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($asset);
        $em->flush();

        $this->addFlash('success', 'Asset deleted.');
        return $this->redirectToRoute('insurance_assets');
    }

    // ─── PACKAGES ─────────────────────────────────────────────────────────────

    #[Route('/packages', name: 'packages', methods: ['GET'])]
    public function packages(InsurancePackageRepository $repo): Response
    {
        $packages = $repo->findBy(['isActive' => true]);

        return $this->render('insurance/packages/index.html.twig', [
            'packages' => $packages,
        ]);
    }

    // ─── CONTRACT REQUESTS ────────────────────────────────────────────────────

    #[Route('/requests', name: 'requests', methods: ['GET'])]
    public function requests(ContractRequestRepository $repo): Response
    {
        $requests = $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('insurance/requests/index.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/requests/new', name: 'request_new', methods: ['GET', 'POST'])]
    public function newRequest(
        Request $request,
        InsuredAssetRepository $assetRepo,
        InsurancePackageRepository $packageRepo,
        EntityManagerInterface $em
    ): Response {
        $assets   = $assetRepo->findBy(['user' => $this->getUser()]);
        $packages = $packageRepo->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $asset   = $assetRepo->find($request->request->get('asset_id'));
            $package = $packageRepo->find($request->request->get('package_id'));

            if (!$asset || $asset->getUser() !== $this->getUser()) {
                $this->addFlash('danger', 'Invalid asset selected.');
                return $this->redirectToRoute('insurance_request_new');
            }

            // Simple premium calculation: base_price * risk_multiplier
            $premium = round((float)$package->getBasePrice() * (float)$package->getRiskMultiplier(), 2);

            $contractRequest = new ContractRequest();
            $contractRequest->setUser($this->getUser());
            $contractRequest->setAsset($asset);
            $contractRequest->setPackage($package);
            $contractRequest->setCalculatedPremium((string)$premium);
            $contractRequest->setStatus('PENDING');

            $em->persist($contractRequest);
            $em->flush();

            $this->addFlash('success', 'Contract request submitted successfully.');
            return $this->redirectToRoute('insurance_requests');
        }

        return $this->render('insurance/requests/new.html.twig', [
            'assets'   => $assets,
            'packages' => $packages,
        ]);
    }

    #[Route('/requests/{id}/cancel', name: 'request_cancel', methods: ['POST'])]
    public function cancelRequest(ContractRequest $contractRequest, EntityManagerInterface $em): Response
    {
        if ($contractRequest->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($contractRequest->getStatus() === 'PENDING') {
            $contractRequest->setStatus('CANCELLED');
            $em->flush();
            $this->addFlash('success', 'Request cancelled.');
        } else {
            $this->addFlash('warning', 'Only pending requests can be cancelled.');
        }

        return $this->redirectToRoute('insurance_requests');
    }
}
