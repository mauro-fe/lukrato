<?php

namespace Application\Builders;

use Application\Models\Usuario;
use Application\Models\Sexo;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\EnderecoRepository;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Application\Formatters\DateFormatter;

/**
 * Builder responsável por construir o payload de resposta do perfil.
 */
class PerfilPayloadBuilder
{
    public function __construct(
        private DocumentoRepository $documentoRepo,
        private TelefoneRepository $telefoneRepo,
        private EnderecoRepository $enderecoRepo,
        private DocumentFormatter $documentFormatter,
        private TelefoneFormatter $telefoneFormatter,
        private DateFormatter $dateFormatter
    ) {}

    /**
     * Constrói o array de dados do usuário para ser retornado na API.
     */
    public function build(Usuario $user): array
    {
        // Busca dados relacionados
        $cpf = $this->documentoRepo->getCpf($user->id);
        $telefone = $this->telefoneRepo->getByUserId($user->id);
        $ddd = $this->telefoneRepo->getDddById($telefone?->id_ddd);
        $sexo = $user->id_sexo ? Sexo::find($user->id_sexo) : null;
        $endereco = $this->enderecoRepo->getPrincipal($user->id);

        return [
            'id' => (int) $user->id,
            'nome' => (string) ($user->nome ?? ''),
            'email' => (string) ($user->email ?? ''),
            'avatar' => (string) ($user->avatar ?? ''),
            'data_nascimento' => $this->dateFormatter->normalize($user->data_nascimento),
            'sexo' => $this->mapSexoToOption($sexo?->nm_sexo),
            'cpf' => $this->documentFormatter->formatCpf($cpf),
            'telefone' => $this->telefoneFormatter->format($ddd?->codigo, $telefone?->numero),
            'endereco' => [
                'cep' => (string) $endereco->cep,
                'rua' => (string) $endereco->rua,
                'numero' => (string) $endereco->numero,
                'complemento' => (string) $endereco->complemento,
                'bairro' => (string) $endereco->bairro,
                'cidade' => (string) $endereco->cidade,
                'estado' => (string) $endereco->estado,
            ]
        ];
    }

    /**
     * Mapeia sexo para opção de retorno (M, F, O, N).
     */
    private function mapSexoToOption(?string $value): string
    {
        if (empty(trim($value ?? ''))) {
            return '';
        }

        $normalized = $this->normalizeSexoValue($value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'M',
            'F', 'FEMININO' => 'F',
            'O', 'OUTRO' => 'O',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'PREFIRO NAO INFORMAR' => 'N',
            default => '',
        };
    }

    /**
     * Normaliza string de sexo.
     */
    private function normalizeSexoValue(string $value): string
    {
        $base = strtr($value, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ã' => 'A',
            'Õ' => 'O',
            'Ç' => 'C',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ã' => 'A',
            'õ' => 'O',
            'ç' => 'C',
        ]);

        $base = str_replace(['-', '_', ' '], ' ', $base);
        return strtoupper(trim($base));
    }
}
