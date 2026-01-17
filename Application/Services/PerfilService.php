<?php

namespace Application\Services;

use Application\DTO\PerfilUpdateDTO;
use Application\Repositories\UsuarioRepository;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\EnderecoRepository;
use Application\Builders\PerfilPayloadBuilder;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Application\Services\AsaasService;
use Illuminate\Database\Capsule\Manager as DB;

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
        private TelefoneFormatter $telefoneFormatter
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
        return DB::connection()->transaction(function () use ($userId, $dto) {
            // 1. Atualiza dados básicos do usuário
            $user = $this->usuarioRepo->update($userId, [
                'nome' => $dto->nome,
                'email' => $dto->email,
                'username' => $dto->username !== '' ? $dto->username : null,
                'data_nascimento' => $dto->dataNascimento,
                'sexo' => $dto->sexo,
            ]);

            // 2. Atualiza ou remove CPF
            $cpfLimpo = $this->documentFormatter->digits($dto->cpf);

            if ($cpfLimpo !== '') {
                $this->documentoRepo->updateOrCreateCpf($userId, $cpfLimpo);
            } else {
                $this->documentoRepo->deleteCpf($userId);
            }

            // 3. Atualiza ou remove telefone
            if ($dto->telefone !== '') {
                [$ddd, $numero] = $this->telefoneFormatter->split($dto->telefone);
                $this->telefoneRepo->updateOrCreate($userId, $ddd, $numero);
            } else {
                $this->telefoneRepo->delete($userId);
            }

            // 4. Atualiza ou remove endereço
            if ($dto->endereco->isEmpty()) {
                $this->enderecoRepo->deletePrincipal($userId);
            } else {
                $this->enderecoRepo->updateOrCreatePrincipal(
                    $userId,
                    $dto->endereco->toArray()
                );
            }

            // Retorna o perfil atualizado
            return $this->obterPerfil($userId);
        });
    }

    /**
     * Deleta completamente a conta do usuário e todos os seus dados
     */
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
                $asaasService = new AsaasService();
                if ($user->assinatura_id) {
                    try {
                        $asaasService->cancelarAssinatura($user->assinatura_id);
                    } catch (\Exception $e) {
                        error_log("Erro ao cancelar assinatura Asaas: " . $e->getMessage());
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
                    error_log("Tabela {$table} não existe ou erro ao deletar: " . $e->getMessage());
                }
            };

            // Deletar dados relacionados (cascade será feito pelo banco em alguns casos)
            // Mas vamos deletar explicitamente para garantir

            // Lançamentos
            $deleteIfExists('lancamentos');

            // Agendamentos
            $deleteIfExists('agendamentos');

            // Categorias
            $deleteIfExists('categorias');

            // Contas
            $deleteIfExists('contas');

            // Cartões de crédito
            $deleteIfExists('cartoes_credito');

            // Faturas e itens de fatura
            $deleteIfExists('faturas_cartao_itens');
            $deleteIfExists('faturas');

            // Investimentos
            $deleteIfExists('investimentos');

            // Metas
            $deleteIfExists('metas');

            // Gamificação
            $deleteIfExists('user_achievements');
            $deleteIfExists('points_log');

            // Notificações
            $deleteIfExists('notificacoes');

            // Preferências
            $deleteIfExists('preferencias_usuario');

            // Documentos
            $this->documentoRepo->deleteCpf($userId);

            // Telefones
            $this->telefoneRepo->delete($userId);

            // Endereços
            $this->enderecoRepo->deletePrincipal($userId);

            // Por fim, deletar o usuário
            $this->usuarioRepo->delete($userId);
        });
    }
}
