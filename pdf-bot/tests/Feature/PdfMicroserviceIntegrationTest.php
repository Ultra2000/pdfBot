<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\TaskJob;
use App\Services\PdfMicroserviceClient;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class PdfMicroserviceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.pdf_microservice.url' => env('PDF_MICROSERVICE_URL', 'http://localhost:8000'),
            'services.pdf_microservice.enabled' => true,
            'services.pdf_microservice.timeout' => 30,
        ]);
    }

    public function test_microservice_health_check()
    {
        if (!$this->isMicroserviceAvailable()) {
            $this->markTestSkipped('PDF microservice not available');
        }

        $client = app(PdfMicroserviceClient::class);
        $health = $client->healthCheck();
        
        $this->assertEquals('healthy', $health['status']);
        $this->assertArrayHasKey('data', $health);
    }

    public function test_microservice_compress_endpoint()
    {
        if (!$this->isMicroserviceAvailable()) {
            $this->markTestSkipped('PDF microservice not available');
        }

        // Create a simple test PDF content
        $testPdfContent = $this->createTestPdfContent();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pdf_') . '.pdf';
        file_put_contents($tempFile, $testPdfContent);

        try {
            // Test direct microservice call (bypassing S3 for simplicity)
            $response = Http::timeout(30)
                ->attach('file', fopen($tempFile, 'r'), 'test.pdf')
                ->post(config('services.pdf_microservice.url') . '/compress', [
                    'mode' => 'whatsapp',
                    'quality' => 'medium'
                ]);

            $this->assertTrue($response->successful());
            
            // Check if response contains a PDF
            $contentType = $response->header('Content-Type');
            $this->assertStringContainsString('application/', $contentType);

        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function test_microservice_service_info()
    {
        if (!$this->isMicroserviceAvailable()) {
            $this->markTestSkipped('PDF microservice not available');
        }

        $client = app(PdfMicroserviceClient::class);
        $info = $client->getServiceInfo();
        
        $this->assertArrayHasKey('available', $info);
        $this->assertTrue($info['available']);
    }

    public function test_microservice_supported_languages()
    {
        $client = app(PdfMicroserviceClient::class);
        
        $ocrLanguages = $client->getSupportedOcrLanguages();
        $this->assertIsArray($ocrLanguages);
        $this->assertArrayHasKey('eng', $ocrLanguages);
        $this->assertArrayHasKey('fra', $ocrLanguages);
        
        $translationLanguages = $client->getSupportedTranslationLanguages();
        $this->assertIsArray($translationLanguages);
        $this->assertArrayHasKey('en', $translationLanguages);
        $this->assertArrayHasKey('fr', $translationLanguages);
    }

    public function test_end_to_end_compress_with_real_microservice()
    {
        if (!$this->isMicroserviceAvailable()) {
            $this->markTestSkipped('PDF microservice not available - test with placeholder instead');
        }

        // This test would require:
        // 1. A real S3/MinIO setup
        // 2. A running PDF microservice
        // 3. A test PDF file
        
        // For now, we'll test the client configuration
        $client = app(PdfMicroserviceClient::class);
        $this->assertInstanceOf(PdfMicroserviceClient::class, $client);
        
        // Verify the service is reachable
        $health = $client->healthCheck();
        $this->assertEquals('healthy', $health['status']);
    }

    protected function isMicroserviceAvailable(): bool
    {
        try {
            $url = config('services.pdf_microservice.url');
            $response = Http::timeout(5)->get($url . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function createTestPdfContent(): string
    {
        // Minimal PDF content for testing
        return "%PDF-1.4\n" .
               "1 0 obj\n" .
               "<<\n" .
               "/Type /Catalog\n" .
               "/Pages 2 0 R\n" .
               ">>\n" .
               "endobj\n" .
               "\n" .
               "2 0 obj\n" .
               "<<\n" .
               "/Type /Pages\n" .
               "/Kids [3 0 R]\n" .
               "/Count 1\n" .
               ">>\n" .
               "endobj\n" .
               "\n" .
               "3 0 obj\n" .
               "<<\n" .
               "/Type /Page\n" .
               "/Parent 2 0 R\n" .
               "/MediaBox [0 0 612 792]\n" .
               ">>\n" .
               "endobj\n" .
               "\n" .
               "xref\n" .
               "0 4\n" .
               "0000000000 65535 f \n" .
               "0000000009 00000 n \n" .
               "0000000074 00000 n \n" .
               "0000000120 00000 n \n" .
               "trailer\n" .
               "<<\n" .
               "/Size 4\n" .
               "/Root 1 0 R\n" .
               ">>\n" .
               "startxref\n" .
               "193\n" .
               "%%EOF";
    }
}
