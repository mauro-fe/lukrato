<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\PromptBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Testa PromptBuilder: conteúdo, formato, versioning e edge cases.
 */
class PromptBuilderTest extends TestCase
{
    // ─── chatSystem ──────────────────────────────────────────────

    public function testChatSystemContainsBaseInstructions(): void
    {
        $prompt = PromptBuilder::chatSystem();
        $this->assertStringContainsString('Lukrato', $prompt);
        $this->assertStringContainsString('REGRAS:', $prompt);
        $this->assertStringContainsString('ÁREAS DE ACESSO:', $prompt);
    }

    public function testChatSystemWithContextAppendsData(): void
    {
        $prompt = PromptBuilder::chatSystem(['total_usuarios' => 150]);
        $this->assertStringContainsString('150', $prompt);
        $this->assertStringContainsString('DADOS DO SISTEMA', $prompt);
    }

    public function testChatSystemUserModeRedirects(): void
    {
        $prompt = PromptBuilder::chatSystem(['_user_mode' => true]);
        $this->assertStringContainsString('Lukra', $prompt);
        $this->assertStringContainsString('PERSONALIDADE:', $prompt);
    }

    // ─── userChatSystem ──────────────────────────────────────────

    public function testUserChatSystemContainsPersona(): void
    {
        $prompt = PromptBuilder::userChatSystem();
        $this->assertStringContainsString('Lukra', $prompt);
        $this->assertStringContainsString('PERSONALIDADE:', $prompt);
        $this->assertStringContainsString('CAPACIDADES', $prompt);
        $this->assertStringContainsString('UX CONVERSACIONAL', $prompt);
        $this->assertStringContainsString('uma pergunta por vez', $prompt);
    }

    public function testUserChatSystemAvoidsFakeConfirmationFlows(): void
    {
        $prompt = PromptBuilder::userChatSystem();
        $this->assertStringContainsString('nunca diga "responda sim/nao"', $prompt);
        $this->assertStringContainsString('nao mande o usuario seguir passo a passo pelo site', $prompt);
    }

    public function testUserChatSystemIncludesUserName(): void
    {
        $prompt = PromptBuilder::userChatSystem(['usuario_nome' => 'João']);
        $this->assertStringContainsString('João', $prompt);
    }

    public function testUserChatSystemIncludesDate(): void
    {
        $prompt = PromptBuilder::userChatSystem();
        $this->assertStringContainsString(date('d/m/Y'), $prompt);
    }

    public function testUserChatSystemAppendsHistory(): void
    {
        $prompt = PromptBuilder::userChatSystem([
            'conversation_history' => [
                ['role' => 'user', 'content' => 'Oi'],
                ['role' => 'assistant', 'content' => 'Olá!'],
            ],
        ]);
        $this->assertStringContainsString('HISTÓRICO', $prompt);
        $this->assertStringContainsString('Usr: Oi', $prompt);
        $this->assertStringContainsString('IA: Olá!', $prompt);
    }

    // ─── categorySystem / categoryUser ───────────────────────────

    public function testCategorySystemIsShort(): void
    {
        $prompt = PromptBuilder::categorySystem();
        $this->assertLessThan(200, strlen($prompt), 'Category system prompt should be concise');
    }

    public function testCategoryUserContainsDescription(): void
    {
        $prompt = PromptBuilder::categoryUser('Mercado Livre', ['Alimentação', 'Compras']);
        $this->assertStringContainsString('Mercado Livre', $prompt);
        $this->assertStringContainsString('Alimentação', $prompt);
    }

    // ─── analysisSystem / analysisUser ───────────────────────────

    public function testAnalysisUserContainsData(): void
    {
        $data = ['total_despesas' => 5000, 'total_receitas' => 8000];
        $prompt = PromptBuilder::analysisUser($data, 'março 2026');
        $this->assertStringContainsString('5000', $prompt);
        $this->assertStringContainsString('março 2026', $prompt);
        $this->assertStringContainsString('JSON', $prompt);
    }

    // ─── transactionExtractionSystem ─────────────────────────────

    public function testTransactionExtractionContainsRules(): void
    {
        $prompt = PromptBuilder::transactionExtractionSystem();
        $this->assertStringContainsString('REGRAS DE VALOR', $prompt);
        $this->assertStringContainsString('REGRAS DE TIPO', $prompt);
        $this->assertStringContainsString('REGRAS DE DATA', $prompt);
        $this->assertStringContainsString('frase inteira', $prompt);
        $this->assertStringContainsString('produto de limpeza', $prompt);
        $this->assertStringContainsString('function calling', $prompt);
        $this->assertStringContainsString(date('Y-m-d'), $prompt);
    }

    // ─── quickQuerySystem ────────────────────────────────────────

    public function testQuickQuerySystemIsShort(): void
    {
        $prompt = PromptBuilder::quickQuerySystem();
        $this->assertLessThan(200, strlen($prompt));
    }

    // ─── defaultCategories ───────────────────────────────────────

    public function testDefaultCategoriesNotEmpty(): void
    {
        $categories = PromptBuilder::defaultCategories();
        $this->assertNotEmpty($categories);
        $this->assertContains('Alimentação', $categories);
        $this->assertContains('Transporte', $categories);
        $this->assertContains('Salário', $categories);
    }

    // ─── Prompt versioning ───────────────────────────────────────

    public function testVersionRegistryExists(): void
    {
        $versions = PromptBuilder::getVersions();
        $this->assertIsArray($versions);
        $this->assertNotEmpty($versions);
    }

    public function testVersionForKnownPrompts(): void
    {
        $versions = PromptBuilder::getVersions();
        $this->assertArrayHasKey('chat_system', $versions);
        $this->assertArrayHasKey('user_chat_system', $versions);
        $this->assertArrayHasKey('transaction_extraction', $versions);
        $this->assertArrayHasKey('category_system', $versions);
    }

    public function testGetVersionReturnsString(): void
    {
        $version = PromptBuilder::getVersion('chat_system');
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
}
