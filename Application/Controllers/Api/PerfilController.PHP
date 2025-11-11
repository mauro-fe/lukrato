<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Application\Models\Usuario;
use Application\Models\Sexo;
use Application\Models\TipoDocumento;
use Application\Models\Documento;
use Application\Models\Telefone;
use Application\Models\Ddd;
use Throwable; // Captura Exceptions e Errors

class PerfilController
{
    // --- Métodos de Utilidade e Limpeza (Tipagem Aprimorada) ---

    /**
     * Remove caracteres não-dígitos de uma string.
     */
    private function digits(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);
        return $digits ?? '';
    }
    
    /**
     * Valida um CPF (algoritmo padrão).
     */
    private function isValidCpf(string $cpf): bool
    {
        $cpf = $this->digits($cpf); // Garantir que só dígitos cheguem aqui
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$t] !== $d) {
                return false;
            }
        }
        return true;
    }

    /**
     * Converte data em formatos variados (DD/MM/YYYY ou string) para YYYY-MM-DD.
     */
    private function parseDate(?string $value): ?string
    {
        if (empty(trim((string)$value))) {
            return null;
        }

        $value = trim((string)$value);

        if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $value)) {
            [$dd, $mm, $yy] = explode('/', $value);
            if (checkdate((int) $mm, (int) $dd, (int) $yy)) {
                return sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
            }
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * Extrai DDD e número local do telefone bruto.
     * @return array{0: string|null, 1: string|null} [DDD, Número Local]
     */
    private function splitPhone(?string $value): array
    {
        $digits = $this->digits((string) $value);
        $len = strlen($digits);

        if ($len < 10 || $len > 11) {
            return [null, null];
        }

        $ddd = substr($digits, 0, 2);
        $local = substr($digits, 2);

        return [$ddd, $local];
    }
    
    /**
     * Normaliza e mapeia o valor de sexo para o Label do Banco de Dados.
     */
    private function mapSexoLabel(?string $value): ?string
    {
        if (empty(trim((string)$value))) {
            return null;
        }
        
        $normalized = $this->normalizeSexoValue((string)$value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'Masculino',
            'F', 'FEMININO'  => 'Feminino',
            'O', 'OUTRO'     => 'Outro',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'NAO INFORMADA', 'PREFIRO NAO INFORMAR' => 'Nao informado',
            default          => null,
        };
    }

    /**
     * Normaliza valor de sexo para a opção de retorno (M, F, O, N).
     */
    private function mapSexoToOption(?string $value): string
    {
        if (empty(trim((string)$value))) {
            return '';
        }

        $normalized = $this->normalizeSexoValue((string)$value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'M',
            'F', 'FEMININO'  => 'F',
            'O', 'OUTRO'     => 'O',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'NAO INFORMADA', 'PREFIRO NAO INFORMAR' => 'N',
            default          => '',
        };
    }

    /**
     * Normaliza a string de sexo (acentos, espaços, caixa alta).
     */
    private function normalizeSexoValue(string $value): string
    {
        // Simplificação: Depende menos do iconv e usa str_replace para os principais acentos
        $base = strtr($value, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ç' => 'C',
            'Ã' => 'A', 'Õ' => 'O',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ç' => 'C',
            'ã' => 'A', 'õ' => 'O',
        ]);
        $base = str_replace(['-', '_', ' '], ' ', $base);

        return strtoupper(trim($base));
    }

    /**
     * Formata CPF no padrão XXX.XXX.XXX-XX.
     */
    private function formatCpf(?string $cpf): string
    {
        $digits = $this->digits((string) $cpf);
        if (strlen($digits) !== 11) {
            return '';
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9, 2)
        );
    }

    /**
     * Formata telefone no padrão (XX) XXXXX-XXXX ou XXXX-XXXX (se DDD for null).
     */
    private function formatTelefone(?string $ddd, ?string $numero): string
    {
        $dddDigits = $this->digits((string) $ddd);
        $numDigits = $this->digits((string) $numero);

        if ($numDigits === '') {
            return '';
        }
        
        // Padroniza para 8 ou 9 dígitos no final
        $numDigits = substr($numDigits, -9); 

        $len = strlen($numDigits);
        if ($len === 9) {
            $masked = substr($numDigits, 0, 5) . '-' . substr($numDigits, 5);
        } elseif ($len === 8) {
            $masked = substr($numDigits, 0, 4) . '-' . substr($numDigits, 4);
        } else {
            $masked = $numDigits;
        }

        return $dddDigits !== ''
            ? sprintf('(%s) %s', $dddDigits, $masked)
            : $masked;
    }

    /**
     * Normaliza data para Y-m-d para payload de retorno.
     */
    private function normalizeDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);
            if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $value)) {
                return $value;
            }
            $timestamp = strtotime($value);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        }
        return '';
    }

    /**
     * Constrói o array de dados do usuário para ser retornado na API.
     */
    private function buildUserPayload(Usuario $user): array
    {
        // Uso do operador nullsafe '->?' (PHP 8.0+)
        
        $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
        $cpfAtual = Documento::where('id_usuario', $user->id)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->value('numero');

        /** @var Telefone|null $tel */
        $tel = Telefone::where('id_usuario', $user->id)->orderBy('id_telefone')->first();
        
        /** @var Ddd|null $ddd */
        $ddd = $tel?->id_ddd ? Ddd::find($tel->id_ddd) : null;

        /** @var Sexo|null $sexo */
        $sexo = $user->id_sexo ? Sexo::find($user->id_sexo) : null;

        return [
            'id'              => (int) $user->id,
            'nome'            => (string) ($user->nome ?? ''),
            'email'           => (string) ($user->email ?? ''),
            'username'        => (string) ($user->username ?? ''),
            'avatar'          => (string) ($user->avatar ?? ''),
            'data_nascimento' => $this->normalizeDate($user->data_nascimento),
            'sexo'            => $this->mapSexoToOption($sexo?->nm_sexo),
            'cpf'             => $this->formatCpf($cpfAtual),
            'telefone'        => $this->formatTelefone($ddd?->codigo, $tel?->numero),
        ];
    }

    // --- Endpoints da API ---
    
    /**
     * Retorna os dados do perfil do usuário autenticado.
     */
    public function show(): void
    {
        try {
            /** @var Usuario|null $sessionUser */
            $sessionUser = Auth::user();
            if (!$sessionUser) {
                Response::error('Nao autenticado', 401);
                return;
            }

            /** @var Usuario|null $user */
            // Uso de find() retorna null, ideal para checagem 404
            $user = Usuario::find((int) $sessionUser->id); 
            if (!$user) {
                Response::error('Usuario nao encontrado', 404);
                return;
            }

            Response::success([
                'user' => $this->buildUserPayload($user),
            ], 'Perfil carregado');
        } catch (Throwable $e) {
            Response::error('Erro interno', 500, ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Atualiza os dados do perfil do usuário.
     */
    public function update(): void
    {
        try {
            /** @var Usuario|null $sessionUser */
            $sessionUser = Auth::user();
            if (!$sessionUser) {
                Response::error('Nao autenticado', 401);
                return;
            }
            
            /** @var Usuario $user */
            $user = Usuario::findOrFail((int) $sessionUser->id); // findOrFail lança exceção se não encontrar

            // Tenta obter dados do POST. Se for PUT/PATCH, precisaria ler o JSON/input stream.
            // Para simplificação, assume-se que os dados vêm de $_POST ou são o valor atual do usuário.
            $nome = trim((string) ($_POST['nome'] ?? $user->nome ?? ''));
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? $user->email ?? '')));
            $username = trim((string) ($_POST['username'] ?? $user->username ?? ''));
            $rawCpf = (string) ($_POST['cpf'] ?? '');
            $rawTel = (string) ($_POST['telefone'] ?? '');
            $rawSexo = (string) ($_POST['sexo'] ?? '');
            $rawNasc = (string) ($_POST['data_nascimento'] ?? '');

            $errors = [];

            // 1. Validação Básica
            if ($nome === '') {
                $errors['nome'] = 'Nome é obrigatório.';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'E-mail inválido.';
            }
            if ($username !== '' && mb_strlen($username) < 3) {
                $errors['username'] = 'Username deve ter no mínimo 3 caracteres.';
            }

            // 2. Validação CPF
            $cpf = $this->digits($rawCpf);
            if ($cpf !== '' && !$this->isValidCpf($cpf)) {
                $errors['cpf'] = 'CPF inválido.';
            }

            // 3. Validação Data de Nascimento
            $nasc = $this->parseDate($rawNasc);
            if (trim($rawNasc) !== '' && $nasc === null) {
                 $errors['data_nascimento'] = 'Data inválida.';
            } elseif ($nasc && strtotime($nasc) > strtotime('today')) {
                $errors['data_nascimento'] = 'Data no futuro não permitida.';
            }

            // 4. Validação Telefone
            [$dddCode, $foneLocal] = $this->splitPhone($rawTel);
            if ($rawTel !== '' && ($dddCode === null || $foneLocal === null)) {
                $errors['telefone'] = 'Telefone inválido. Use DDD + número.';
            }

            // 5. Normalização Sexo
            $sexoLabel = $this->mapSexoLabel($rawSexo);
            
            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            // 6. Validação de Unicidade (Email, Username, CPF)
            
            $q = Usuario::whereRaw('LOWER(email) = ?', [$email])->where('id', '!=', $user->id);
            if ($q->exists()) {
                $errors['email'] = 'Este e-mail já está em uso.';
            }

            $q = Usuario::whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->where('id', '!=', $user->id);
            if ($username !== '' && $q->exists()) {
                $errors['username'] = 'Este username já está em uso.';
            }

            if ($cpf !== '') {
                $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
                $cpfEmUso = Documento::where('numero', $cpf)
                    ->where('id_tipo', $tipoCpf->id_tipo)
                    ->where('id_usuario', '!=', $user->id)
                    ->exists();

                if ($cpfEmUso) {
                    $errors['cpf'] = 'Este CPF já está em uso.';
                }
            }

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            // 7. Persistência (Transação)
            DB::connection()->transaction(function () use (
                $user, $nome, $email, $username, $nasc, $sexoLabel, $cpf, $dddCode, $foneLocal, $rawTel
            ) {
                // Atualiza Usuário
                $user->nome = $nome;
                $user->email = $email;
                if ($username !== '') {
                    $user->username = $username;
                }
                $user->data_nascimento = $nasc;

                if ($sexoLabel !== null) {
                    $sexo = Sexo::firstOrCreate(['nm_sexo' => $sexoLabel]);
                    $user->id_sexo = $sexo->id_sexo;
                } else {
                    $user->id_sexo = null;
                }
                $user->save();
                
                // Atualiza/Cria/Deleta Documento (CPF)
                $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
                if ($cpf !== '') {
                    Documento::updateOrCreate(
                        ['id_usuario' => $user->id, 'id_tipo' => $tipoCpf->id_tipo],
                        ['numero' => $cpf]
                    );
                } else {
                    Documento::where('id_usuario', $user->id)
                        ->where('id_tipo', $tipoCpf->id_tipo)
                        ->delete();
                }

                // Atualiza/Cria/Deleta Telefone
                /** @var Telefone|null $tel */
                $tel = Telefone::where('id_usuario', $user->id)->orderBy('id_telefone')->first();

                if ($rawTel === '') {
                    $tel?->delete();
                } else {
                    $ddd = Ddd::firstOrCreate(['codigo' => $dddCode]);
                    $data = [
                        'numero' => $foneLocal, 
                        'id_ddd' => $ddd->id_ddd, 
                        'tipo' => $tel?->tipo ?? 'celular'
                    ];
                    
                    if ($tel) {
                        $tel->fill($data)->save();
                    } else {
                        Telefone::create(array_merge($data, ['id_usuario' => $user->id]));
                    }
                }
            });

            $user->refresh();

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user'    => $this->buildUserPayload($user),
            ]);
        } catch (Throwable $e) {
            // Captura qualquer exceção ou erro (incluindo o notFound do findOrFail)
            $statusCode = ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) ? 404 : 500;
            Response::error('Erro interno ao atualizar perfil', $statusCode, ['exception' => $e->getMessage()]);
        }
    }
}