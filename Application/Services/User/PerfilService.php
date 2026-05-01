<?php

declare(strict_types=1);

namespace Application\Services\User;

use Application\Container\ApplicationContainer;
use Application\DTO\PerfilUpdateDTO;
use Application\Repositories\UsuarioRepository;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\EnderecoRepository;
use Application\Builders\PerfilPayloadBuilder;
use Application\Enums\LogCategory;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Billing\AsaasService;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\Infrastructure\LogService;

/**
 * Service responsável pela lógica de negócio do perfil.
 */
class PerfilService
{
    public function __construct(
        private UsuarioRepository $usuarioRepo,
        private DocumentoRepository $documentoRepo,
        private TelefoneRepository $telefoneRepo,
        private EnderecoRepository $enderecoRepo,
        private PerfilPayloadBuilder $payloadBuilder,
        private DocumentFormatter $documentFormatter,
        private TelefoneFormatter $telefoneFormatter,
        private ?EmailVerificationService $emailVerificationService = null,
        private ?AsaasService $asaasService = null
    ) {}

    /**
     * Obtém os dados completos do perfil do usuário.
     */
    public function obterPerfil(int $userId): ?array
    {
        $user = $this->usuarioRepo->findById($userId);

        if (!$user) {
            return null;
        }

        return $this->payloadBuilder->build($user);
    }

    /**
     * Atualiza o perfil completo do usuário.
     */
    public function atualizarPerfil(int $userId, PerfilUpdateDTO $dto): array
    {
        $result = DB::connection()->transaction(function () use ($userId, $dto) {
            $currentUser = $this->usuarioRepo->findById($userId);
            if (!$currentUser) {
                throw new \RuntimeException('Usuário não encontrado');
            }

            $currentEmail = mb_strtolower(trim((string) $currentUser->email));
            $pendingEmail = mb_strtolower(trim((string) ($currentUser->pending_email ?? '')));
            $requestedEmail = mb_strtolower(trim($dto->email));
            $shouldCreateNewPending = $requestedEmail !== '' && $requestedEmail !== $currentEmail && $requestedEmail !== $pendingEmail;

            if ($shouldCreateNewPending) {
                $currentUser->forceFill([
                    'pending_email' => $requestedEmail,
                    'email_verification_selector' => null,
                    'email_verification_token_hash' => null,
                    'email_verification_expires_at' => null,
                    'email_verification_sent_at' => null,
                    'email_verification_reminder_sent_at' => null,
                ])->save();
            }

            // Mantem o email atual ate confirmar o novo endereco.
            $this->usuarioRepo->update($userId, [
                'nome' => $dto->nome,
                'email' => $currentEmail,
                'data_nascimento' => $dto->dataNascimento,
                'sexo' => $dto->sexo,
            ]);

            $cpfLimpo = $this->documentFormatter->digits($dto->cpf);
            if ($cpfLimpo !== '') {
                $this->documentoRepo->updateOrCreateCpf($userId, $cpfLimpo);
            } else {
                $this->documentoRepo->deleteCpf($userId);
            }

            if ($dto->telefone !== '') {
                [$ddd, $numero] = $this->telefoneFormatter->split($dto->telefone);
                $this->telefoneRepo->updateOrCreate($userId, $ddd, $numero);
            } else {
                $this->telefoneRepo->delete($userId);
            }

            if ($dto->endereco->isEmpty()) {
                $this->enderecoRepo->deletePrincipal($userId);
            } else {
                $this->enderecoRepo->updateOrCreatePrincipal($userId, $dto->endereco->toArray());
            }

            $freshUser = $this->usuarioRepo->findById($userId);
            $hasPendingEmail = $this->hasPendingEmailChange($freshUser?->email, $freshUser?->pending_email);

            return [
                'user' => $this->obterPerfil($userId),
                'email_change_pending' => $hasPendingEmail,
                'should_send_verification' => $shouldCreateNewPending,
            ];
        });

        $verificationSent = false;
        if ($result['should_send_verification'] === true) {
            $userToVerify = $this->usuarioRepo->findById($userId);
            if ($userToVerify) {
                $verificationSent = $this->verificationService()->sendVerificationEmail($userToVerify);
            }
        }

        return [
            'user' => $result['user'] ?? $this->obterPerfil($userId),
            'email_change_pending' => (bool) $result['email_change_pending'],
            'email_verification_sent' => $verificationSent,
        ];
    }

    private function hasPendingEmailChange(?string $email, ?string $pendingEmail): bool
    {
        $current = mb_strtolower(trim((string) $email));
        $pending = mb_strtolower(trim((string) $pendingEmail));

        return $pending !== '' && $pending !== $current;
    }

    private function verificationService(): EmailVerificationService
    {
        return $this->emailVerificationService ??= ApplicationContainer::resolveOrNew(null, EmailVerificationService::class);
    }

    private function asaasService(): AsaasService
    {
        return $this->asaasService ??= ApplicationContainer::resolveOrNew(null, AsaasService::class);
    }

    public function deletarConta(int $userId): void
    {
        DB::connection()->transaction(function () use ($userId) {
            // Verificar se o usuário existe
            $user = $this->usuarioRepo->findById($userId);
            if (!$user) {
                throw new \Exception('Usuário não encontrado');
            }

            // Cancelar assinatura PRO se existir
            if ($user->assinatura_plano === 'pro' && $user->assinatura_ativa) {
                if ($user->assinatura_id) {
                    try {
                        $this->asaasService()->cancelSubscription($user->assinatura_id);
                    } catch (\Exception $e) {
                        LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                            'action' => 'cancelar_assinatura_ao_deletar_conta',
                            'user_id' => $userId,
                            'assinatura_id' => $user->assinatura_id,
                        ]);
                        // Continua com a exclusão mesmo se falhar o cancelamento
                    }
                }
            }

            // Helper para deletar de tabela se ela existir
            $deleteIfExists = function (string $table) use ($userId) {
                try {
                    DB::table($table)->where('user_id', $userId)->delete();
                } catch (\Exception $e) {
                    // Tabela não existe, ignorar
                    LogService::warning("Tabela {$table} não existe ou erro ao deletar: " . $e->getMessage());
                }
            };

            // Deletar dados relacionados (cascade será feito pelo banco em alguns casos)
            // Mas vamos deletar explicitamente para garantir

            // Lançamentos
            $deleteIfExists('lancamentos');

            // Agendamentos
            $deleteIfExists('agendamentos');

            // Orçamentos
            $deleteIfExists('orcamentos_categoria');

            // Parcelamentos
            $deleteIfExists('parcelamentos');

            // Categorias
            $deleteIfExists('categorias');

            // Contas
            $deleteIfExists('contas');

            // Cartões de crédito
            $deleteIfExists('cartoes_credito');

            // Faturas e itens de fatura
            $deleteIfExists('faturas_cartao_itens');
            $deleteIfExists('faturas');

            // Investimentos (transações e proventos primeiro)
            try {
                $investimentoIds = DB::table('investimentos')->where('user_id', $userId)->pluck('id');
                if ($investimentoIds->isNotEmpty()) {
                    DB::table('transacoes_investimento')->whereIn('investimento_id', $investimentoIds)->delete();
                    DB::table('proventos')->whereIn('investimento_id', $investimentoIds)->delete();
                }
            } catch (\Exception $e) {
                LogService::warning("Erro ao deletar transações/proventos de investimentos: " . $e->getMessage());
            }
            $deleteIfExists('investimentos');

            // Metas
            $deleteIfExists('metas');

            // Gamificação
            $deleteIfExists('user_achievements');
            $deleteIfExists('points_log');
            $deleteIfExists('user_progress');

            // Notificações
            $deleteIfExists('notificacoes');
            $deleteIfExists('notifications');

            // Preferências
            $deleteIfExists('preferencias_usuario');

            // Assinatura
            $deleteIfExists('assinaturas_usuarios');

            // Indicações (pode ser referrer ou referred)
            try {
                DB::table('indicacoes')
                    ->where('referrer_id', $userId)
                    ->orWhere('referred_id', $userId)
                    ->delete();
            } catch (\Exception $e) {
                LogService::warning("Tabela indicacoes não existe ou erro ao deletar: " . $e->getMessage());
            }

            // Cupons usados (usa usuario_id, não user_id)
            try {
                DB::table('cupons_usados')->where('usuario_id', $userId)->delete();
            } catch (\Exception $e) {
                LogService::warning("Tabela cupons_usados não existe ou erro ao deletar: " . $e->getMessage());
            }

            // Documentos
            $this->documentoRepo->deleteCpf($userId);

            // Telefones
            $this->telefoneRepo->delete($userId);

            // Endereços
            $this->enderecoRepo->deletePrincipal($userId);

            // Deletar arquivo de avatar se existir
            if ($user->avatar) {
                $publicRoot = defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public';
                $avatarPath = $publicRoot . '/' . $user->avatar;
                if (is_file($avatarPath)) {
                    @unlink($avatarPath);
                }
            }

            // Por fim, deletar o usuário
            $this->usuarioRepo->delete($userId);
        });
    }

    /**
     * Salva dados do checkout no perfil (CPF, telefone, CEP) se estiverem faltando
     * Usado após pagamento bem-sucedido para completar o perfil automaticamente
     */
    public function salvarDadosCheckout(int $userId, array $dados): void
    {
        DB::connection()->transaction(function () use ($userId, $dados) {
            $cpf = $dados['cpf'] ?? '';
            $phone = $dados['phone'] ?? '';
            $cep = $dados['cep'] ?? '';
            $endereco = $dados['endereco'] ?? '';

            // CPF - só salva se não existe ainda
            if ($cpf !== '') {
                $cpfLimpo = preg_replace('/\D/', '', $cpf);
                if (strlen($cpfLimpo) === 11) {
                    $existeCpf = $this->documentoRepo->hasCpf($userId);

                    if (!$existeCpf) {
                        $this->documentoRepo->updateOrCreateCpf($userId, $cpfLimpo);
                    }
                }
            }

            // Telefone - só salva se não existe ainda
            if ($phone !== '') {
                $phoneLimpo = preg_replace('/\D/', '', $phone);
                if (strlen($phoneLimpo) >= 10) {
                    $existeTel = DB::table('telefones')
                        ->where('id_usuario', $userId)
                        ->exists();

                    if (!$existeTel) {
                        [$ddd, $numero] = $this->telefoneFormatter->split($phone);
                        $this->telefoneRepo->updateOrCreate($userId, $ddd, $numero);
                    }
                }
            }

            // CEP e Endereço - só salva se não existe endereço ainda
            if ($cep !== '') {
                $cepLimpo = preg_replace('/\D/', '', $cep);
                if (strlen($cepLimpo) === 8) {
                    $existeEndereco = DB::table('enderecos')
                        ->where('user_id', $userId)
                        ->exists();

                    if (!$existeEndereco) {
                        // Tentar separar logradouro e número do endereço
                        $logradouro = $endereco;
                        $numero = null;

                        if (!empty($endereco) && preg_match('/^(.+?),\s*(\d+.*)$/', $endereco, $matches)) {
                            $logradouro = trim($matches[1]);
                            $numero = trim($matches[2]);
                        }

                        $this->enderecoRepo->updateOrCreatePrincipal($userId, [
                            'cep' => $cepLimpo,
                            'rua' => $logradouro ?: null,
                            'numero' => $numero,
                        ]);
                    }
                }
            }
        });
    }
}
