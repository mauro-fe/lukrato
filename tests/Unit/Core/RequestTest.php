<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Validation\RequestValidator;
use Illuminate\Container\Container as IlluminateContainer;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        parent::tearDown();
    }

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

    public function testIpUsesCloudflareHeaderWhenRequestComesFromTrustedProxy(): void
    {
        $hadTrustedProxies = array_key_exists('TRUSTED_PROXIES', $_ENV);
        $originalTrustedProxies = $_ENV['TRUSTED_PROXIES'] ?? null;
        $_ENV['TRUSTED_PROXIES'] = '127.0.0.10';

        try {
            $request = new Request(server: [
                'REQUEST_METHOD' => 'GET',
                'REMOTE_ADDR' => '127.0.0.10',
                'HTTP_CF_CONNECTING_IP' => '8.8.8.8',
                'HTTP_X_REAL_IP' => '9.9.9.9',
            ]);

            $this->assertSame('8.8.8.8', $request->ip());
        } finally {
            if ($hadTrustedProxies) {
                $_ENV['TRUSTED_PROXIES'] = $originalTrustedProxies;
            } else {
                unset($_ENV['TRUSTED_PROXIES']);
            }
        }
    }

    public function testIpUsesXRealIpWhenTrustedProxyDoesNotExposeCloudflareHeader(): void
    {
        $hadTrustedProxies = array_key_exists('TRUSTED_PROXIES', $_ENV);
        $originalTrustedProxies = $_ENV['TRUSTED_PROXIES'] ?? null;
        $_ENV['TRUSTED_PROXIES'] = '127.0.0.10';

        try {
            $request = new Request(server: [
                'REQUEST_METHOD' => 'GET',
                'REMOTE_ADDR' => '127.0.0.10',
                'HTTP_X_REAL_IP' => '9.9.9.9',
                'HTTP_X_FORWARDED_FOR' => '8.8.4.4, 10.0.0.1',
            ]);

            $this->assertSame('9.9.9.9', $request->ip());
        } finally {
            if ($hadTrustedProxies) {
                $_ENV['TRUSTED_PROXIES'] = $originalTrustedProxies;
            } else {
                unset($_ENV['TRUSTED_PROXIES']);
            }
        }
    }

    public function testIpFallsBackToFirstValidPrivateForwardedIpWhenTrustedProxyHasNoPublicClientIp(): void
    {
        $hadTrustedProxies = array_key_exists('TRUSTED_PROXIES', $_ENV);
        $originalTrustedProxies = $_ENV['TRUSTED_PROXIES'] ?? null;
        $_ENV['TRUSTED_PROXIES'] = '127.0.0.10';

        try {
            $request = new Request(server: [
                'REQUEST_METHOD' => 'GET',
                'REMOTE_ADDR' => '127.0.0.10',
                'HTTP_X_FORWARDED_FOR' => '10.8.0.5, 10.8.0.6',
            ]);

            $this->assertSame('10.8.0.5', $request->ip());
        } finally {
            if ($hadTrustedProxies) {
                $_ENV['TRUSTED_PROXIES'] = $originalTrustedProxies;
            } else {
                unset($_ENV['TRUSTED_PROXIES']);
            }
        }
    }

    public function testServerAndUriExposeInjectedServerState(): void
    {
        $request = new Request(server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/blog/upload?draft=1',
            'DOCUMENT_ROOT' => 'C:/xampp/htdocs/lukrato/public',
        ]);

        $this->assertSame('/api/blog/upload?draft=1', $request->uri());
        $this->assertSame('C:/xampp/htdocs/lukrato/public', $request->server('DOCUMENT_ROOT'));
        $this->assertSame('fallback', $request->server('MISSING_KEY', 'fallback'));
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

    public function testValidateUsesContainerValidatorWhenAvailable(): void
    {
        $validator = new class extends RequestValidator {
            public function validate(array $data, array $rules, array $filters = []): array
            {
                return [
                    'source' => 'container',
                    'rules' => $rules,
                    'data' => $data,
                ];
            }
        };

        $container = new IlluminateContainer();
        $container->instance(RequestValidator::class, $validator);
        ApplicationContainer::setInstance($container);

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

        $this->assertSame('container', $validated['source']);
        $this->assertSame(['documento' => 'required|cpf_cnpj'], $validated['rules']);
        $this->assertSame(['documento' => '529.982.247-25'], $validated['data']);
    }
}
