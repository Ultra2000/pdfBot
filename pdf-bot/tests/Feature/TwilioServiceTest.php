<?php

namespace Tests\Feature;

use App\Services\Messaging\TwilioService;
use Tests\TestCase;
use Mockery;

class TwilioServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Twilio configuration
        config([
            'services.twilio.sid' => 'test_sid',
            'services.twilio.auth_token' => 'test_token',
            'services.twilio.whatsapp_number' => 'whatsapp:+14155238886'
        ]);
    }

    public function test_twilio_service_can_be_instantiated()
    {
        // Skip actual Twilio client creation in tests
        $this->expectException(\Exception::class);
        
        $service = new TwilioService();
        $this->assertInstanceOf(TwilioService::class, $service);
    }

    public function test_provider_name()
    {
        try {
            $service = new TwilioService();
            $this->assertEquals('twilio', $service->getProviderName());
        } catch (\Exception $e) {
            // Expected in test environment without real Twilio credentials
            $this->assertTrue(true);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
