<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BoldSignService
{
    public function __construct(
        private HttpClientInterface  $httpClient,
        private PdfGeneratorService  $pdfGenerator,
        private string $apiKey,
        private string $baseUrl,
        private string $webhookUrl,
    ) {}

    /**
     * Generates the contract PDF and sends it to the signer via BoldSign.
     * Returns the document ID from the BoldSign API.
     */
    public function sendForSignature(
        string             $userName,
        string             $assetReference,
        string             $insurancePackage,
        string             $approvedValue,
        \DateTimeInterface $contractDate,
        string             $signerEmail,
    ): string {
        // ── Step 1: generate PDF ─────────────────────────────────────
        $terms = sprintf(
            'This insurance contract covers the insured asset against all declared risks. '
            . 'The insured party (%s) agrees to the terms set out by the %s package. '
            . 'Any fraudulent claim will void this contract.',
            $userName,
            $insurancePackage
        );

        $pdfPath = $this->pdfGenerator->generateContractPdf(
            $userName,
            $assetReference,
            $insurancePackage,
            $approvedValue,
            $contractDate,
            $terms,
        );

        // ── Step 2: send to BoldSign ─────────────────────────────────
        try {
            $documentId = $this->sendToApi(
                $pdfPath,
                $userName,
                $assetReference,
                $signerEmail,
            );
        } finally {
            @unlink($pdfPath);  // always clean up the temp PDF
        }

        return $documentId;
    }

    // ─────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────

    private function sendToApi(
        string $pdfPath,
        string $userName,
        string $assetReference,
        string $signerEmail,
    ): string {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/v1/document/send', [
            'headers' => [
                'X-API-KEY' => $this->apiKey,
            ],
            'body' => $this->buildMultipartBody(
                $pdfPath,
                $userName,
                $assetReference,
                $signerEmail,
            ),
        ]);

        $statusCode = $response->getStatusCode();
        $body       = $response->getContent(false);  // false = don't throw on 4xx/5xx

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(
                sprintf('BoldSign API error %d: %s', $statusCode, $body)
            );
        }

        $data = json_decode($body, true);
        return $data['documentId'] ?? $body;
    }

    /**
     * Builds the multipart/form-data body manually so we control every field,
     * mirroring the Java implementation exactly.
     */
    private function buildMultipartBody(
        string $pdfPath,
        string $userName,
        string $assetReference,
        string $signerEmail,
    ): array {
        // Symfony HttpClient accepts 'body' as an array of multipart fields
        // when 'Content-Type' is NOT explicitly set — it builds the boundary automatically.
        // We use the array form here for clarity and safety.

        return [
            // ── PDF file ─────────────────────────────────────────────
            [
                'name'     => 'Files',
                'contents' => fopen($pdfPath, 'r'),
                'filename' => 'contract.pdf',
                'headers'  => ['Content-Type' => 'application/pdf'],
            ],

            // ── Document metadata ─────────────────────────────────────
            ['name' => 'Title',   'contents' => 'Insurance Contract - ' . $assetReference],

            // ── Signer ────────────────────────────────────────────────
            ['name' => 'Signers[0].Name',         'contents' => $userName],
            ['name' => 'Signers[0].EmailAddress',  'contents' => $signerEmail],
            ['name' => 'Signers[0].SignerOrder',   'contents' => '1'],

            // ── Signature form field ──────────────────────────────────
            ['name' => 'Signers[0].FormFields[0].FieldType',      'contents' => 'Signature'],
            ['name' => 'Signers[0].FormFields[0].PageNumber',      'contents' => '1'],
            ['name' => 'Signers[0].FormFields[0].Bounds.X',        'contents' => '100'],
            ['name' => 'Signers[0].FormFields[0].Bounds.Y',        'contents' => '600'],
            ['name' => 'Signers[0].FormFields[0].Bounds.Width',    'contents' => '200'],
            ['name' => 'Signers[0].FormFields[0].Bounds.Height',   'contents' => '50'],
            ['name' => 'Signers[0].FormFields[0].IsRequired',      'contents' => 'true'],

            // ── Webhook ───────────────────────────────────────────────
            ['name' => 'WebhookUrl', 'contents' => $this->webhookUrl],
        ];
    }
}