<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class PdfGeneratorService
{
    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {}

    /**
     * Renders the contract template and converts it to a PDF using wkhtmltopdf.
     * Returns the absolute path to the generated PDF file.
     */
    public function generateContractPdf(
        string $userName,
        string $assetReference,
        string $insurancePackage,
        string $approvedValue,
        \DateTimeInterface $contractDate,
        string $terms,
    ): string {
        // ── Render HTML ──────────────────────────────────────────────
        $html = $this->twig->render('contracts/contract.html.twig', [
            'userName'         => $userName,
            'assetReference'   => $assetReference,
            'insurancePackage' => $insurancePackage,
            'approvedValue'    => $approvedValue,
            'contractDate'     => $contractDate->format('Y-m-d'),
            'terms'            => $terms,
        ]);

        // ── Write temp HTML ──────────────────────────────────────────
        $dir      = $this->projectDir . '/var/contracts';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $slug     = preg_replace('/\s+/', '_', $userName);
        $base     = $slug . '_' . time();
        $htmlPath = $dir . '/' . $base . '.html';
        $pdfPath  = $dir . '/' . $base . '.pdf';

        file_put_contents($htmlPath, $html);

        // ── Convert to PDF via wkhtmltopdf ───────────────────────────
        // Install: apt-get install wkhtmltopdf  OR  use knplabs/knp-snappy-bundle
        $cmd = sprintf(
            'wkhtmltopdf --quiet --page-size A4 %s %s 2>&1',
            escapeshellarg($htmlPath),
            escapeshellarg($pdfPath)
        );
        exec($cmd, $output, $exitCode);

        @unlink($htmlPath);   // clean up temp HTML

        if ($exitCode !== 0 || !file_exists($pdfPath)) {
            throw new \RuntimeException(
                'PDF generation failed: ' . implode("\n", $output)
            );
        }

        return $pdfPath;
    }
}