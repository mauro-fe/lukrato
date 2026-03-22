<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testQueryAndInputRemainSeparated(): void
    {
        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            query: ['month' => '2026-03'],
            rawInput: '{"month":"2026-04","active":"1"}'
        );

        $this->assertSame('2026-03', $request->query('month'));
        $this->assertSame('2026-04', $request->input('month'));
        $this->assertTrue($request->boolValue('active'));
    }

    public function testInvalidJsonIsTrackedExplicitly(): void
    {
        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            rawInput: '{invalid-json'
        );

        $this->assertTrue($request->hasJsonError());
        $this->assertSame('JSON invalido na requisicao.', $request->jsonError());
        $this->assertNull($request->json());
    }

    public function testTypedAccessorsCastValuesSafely(): void
    {
        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            rawInput: json_encode([
                'name' => ' Lukrato ',
                'count' => '42',
                'enabled' => 'sim',
                'items' => ['a', 'b'],
                'invalid_array' => 'x',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame('Lukrato', $request->stringValue('name'));
        $this->assertSame(42, $request->intValue('count'));
        $this->assertTrue($request->boolValue('enabled'));
        $this->assertSame(['a', 'b'], $request->arrayValue('items'));
        $this->assertSame([], $request->arrayValue('invalid_array'));
    }

    public function testBagSpecificTypedAccessorsPreserveSourceBag(): void
    {
        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            query: [
                'month' => ' 2026-03 ',
                'page' => '7',
                'enabled' => '1',
                'items' => ['query-item'],
            ],
            rawInput: json_encode([
                'month' => '2026-04',
                'page' => '11',
                'enabled' => '0',
                'items' => ['body-item'],
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame('2026-03', $request->queryString('month'));
        $this->assertSame(7, $request->queryInt('page'));
        $this->assertTrue($request->queryBool('enabled'));
        $this->assertSame(['query-item'], $request->queryArray('items'));

        $this->assertSame('2026-04', $request->inputString('month'));
        $this->assertSame(11, $request->inputInt('page'));
        $this->assertFalse($request->inputBool('enabled'));
        $this->assertSame(['body-item'], $request->inputArray('items'));
    }

    public function testIpUsesInjectedServerState(): void
    {
        $request = new Request(server: [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '10.0.0.1',
        ]);

        $this->assertSame('10.0.0.1', $request->ip());
    }

    public function testValidateDelegatesAndAcceptsValidCpfThroughCompatRule(): void
    {
        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            post: [
                'documento' => '529.982.247-25',
            ]
        );

        $validated = $request->validate([
            'documento' => 'required|cpf_cnpj',
        ]);

        $this->assertSame('529.982.247-25', $validated['documento'] ?? null);
    }

    public function testValidateRejectsCnpjLengthEvenWithCompatRuleName(): void
    {
        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            post: [
                'documento' => '12.345.678/0001-95',
            ]
        );

        try {
            $request->validate([
                'documento' => 'required|cpf_cnpj',
            ]);

            $this->fail('Era esperada ValidationException para documento com 14 dígitos.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('documento', $e->getErrors());
            $this->assertStringContainsString('CPF', (string) $e->getErrors()['documento']);
        }
    }
}
