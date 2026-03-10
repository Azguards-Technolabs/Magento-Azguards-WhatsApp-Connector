<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Test\Unit\Model\Service;

use Azguards\WhatsAppConnect\Model\Service\Validator;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;

class ValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidateValidData()
    {
        $data = [
            'name' => 'test_template',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'type' => 'TEXT',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}', 'example' => ['body_text' => ['Sample']]]
            ]
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testValidateInvalidName()
    {
        $this->expectException(LocalizedException::class);
        $data = [
            'name' => 'Invalid-Name',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'type' => 'TEXT'
        ];
        $this->validator->validate($data);
    }

    public function testValidateTextLimit()
    {
        $this->expectException(LocalizedException::class);
        $data = [
            'name' => 'test_template',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'type' => 'TEXT',
            'components' => [
                ['type' => 'HEADER', 'format' => 'TEXT', 'text' => str_repeat('a', 61)]
            ]
        ];
        $this->validator->validate($data);
    }

    public function testValidateSequentialPlaceholders()
    {
        $this->expectException(LocalizedException::class);
        $data = [
            'name' => 'test_template',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'type' => 'TEXT',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{2}}', 'example' => ['body_text' => ['Sample']]]
            ]
        ];
        $this->validator->validate($data);
    }
}
