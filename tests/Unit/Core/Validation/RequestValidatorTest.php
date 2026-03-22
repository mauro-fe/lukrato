<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Validation;

use Application\Core\Exceptions\ValidationException;
use Application\Core\Validation\RequestValidator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    public function testValidateAcceptsValidCpfUsingCompatRuleName(): void
    {
        $validator = new RequestValidator();

        $validated = $validator->validate(
            ['documento' => '529.982.247-25'],
            ['documento' => 'required|cpf_cnpj']
        );

        $this->assertSame('529.982.247-25', $validated['documento'] ?? null);
    }

    public function testValidateRejectsFourteenDigitDocumentForNow(): void
    {
        $validator = new RequestValidator();

        try {
            $validator->validate(
                ['documento' => '12.345.678/0001-95'],
                ['documento' => 'required|cpf_cnpj']
            );

            $this->fail('Era esperada ValidationException para documento com 14 dígitos.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('documento', $e->getErrors());
            $this->assertStringContainsString('CPF', (string) $e->getErrors()['documento']);
        }
    }
}
