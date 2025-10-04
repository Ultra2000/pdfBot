<?php

namespace Tests\Feature;

use App\Support\CommandParser;
use Tests\TestCase;

class CommandParserTest extends TestCase
{
    protected CommandParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CommandParser();
    }

    public function test_compress_command_parsing()
    {
        $result = $this->parser->parse('COMPRESS whatsapp');
        
        $this->assertNotNull($result);
        $this->assertEquals('compress', $result['type']);
        $this->assertEquals('whatsapp', $result['parameters']['mode']);
        $this->assertArrayHasKey('job_class', $result);
    }

    public function test_compress_command_with_default_mode()
    {
        $result = $this->parser->parse('COMPRESS');
        
        $this->assertNotNull($result);
        $this->assertEquals('compress', $result['type']);
        $this->assertEquals('whatsapp', $result['parameters']['mode']);
    }

    public function test_convert_command_parsing()
    {
        $result = $this->parser->parse('CONVERT docx');
        
        $this->assertNotNull($result);
        $this->assertEquals('convert', $result['type']);
        $this->assertEquals('docx', $result['parameters']['format']);
    }

    public function test_ocr_command_parsing()
    {
        $result = $this->parser->parse('OCR text');
        
        $this->assertNotNull($result);
        $this->assertEquals('ocr', $result['type']);
        $this->assertEquals('text', $result['parameters']['output_format']);
    }

    public function test_summarize_command_parsing()
    {
        $result = $this->parser->parse('SUMMARIZE medium');
        
        $this->assertNotNull($result);
        $this->assertEquals('summarize', $result['type']);
        $this->assertEquals('medium', $result['parameters']['size']);
    }

    public function test_translate_command_parsing()
    {
        $result = $this->parser->parse('TRANSLATE fr');
        
        $this->assertNotNull($result);
        $this->assertEquals('translate', $result['type']);
        $this->assertEquals('fr', $result['parameters']['target_language']);
    }

    public function test_secure_command_parsing()
    {
        $result = $this->parser->parse('SECURE password');
        
        $this->assertNotNull($result);
        $this->assertEquals('secure', $result['type']);
        $this->assertEquals('password', $result['parameters']['security_type']);
        $this->assertArrayHasKey('password', $result['parameters']);
        $this->assertNotEmpty($result['parameters']['password']);
    }

    public function test_french_commands()
    {
        $result = $this->parser->parse('SUMMARIZE court');
        
        $this->assertNotNull($result);
        $this->assertEquals('short', $result['parameters']['size']);
    }

    public function test_invalid_command()
    {
        $result = $this->parser->parse('INVALID_COMMAND');
        
        $this->assertNull($result);
    }

    public function test_empty_command()
    {
        $result = $this->parser->parse('');
        
        $this->assertNull($result);
    }

    public function test_case_insensitive_parsing()
    {
        $result = $this->parser->parse('compress whatsapp');
        
        $this->assertNotNull($result);
        $this->assertEquals('compress', $result['type']);
    }
}
