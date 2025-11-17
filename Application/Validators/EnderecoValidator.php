<?php

namespace Application\Validators;

use Application\DTO\EnderecoDTO;
use Application\Formatters\DocumentFormatter;

/**
 * Validator responsável por validar dados de endereço.
 */
class EnderecoValidator
{
    public function __construct(
        private DocumentFormatter $formatter
    ) {}

    /**
     * Valida os dados do endereço.
     * 
     * @return array Array de erros com chave no formato "endereco.campo"
     */
    public function validate(EnderecoDTO $endereco): array
    {
        $errors = [];

        // CEP
        $cepLimpo = $this->formatter->digits($endereco->cep);
        if ($endereco->cep !== '' && strlen($cepLimpo) !== 8) {
            $errors['endereco.cep'] = 'CEP inválido.';
        }

        // Rua
        if ($endereco->rua === '') {
            $errors['endereco.rua'] = 'Rua é obrigatória.';
        }

        // Número
        if ($endereco->numero === '') {
            $errors['endereco.numero'] = 'Número é obrigatório.';
        }

        // Bairro
        if ($endereco->bairro === '') {
            $errors['endereco.bairro'] = 'Bairro é obrigatório.';
        }

        // Cidade
        if ($endereco->cidade === '') {
            $errors['endereco.cidade'] = 'Cidade é obrigatória.';
        }

        // Estado (UF)
        if ($endereco->estado === '') {
            $errors['endereco.estado'] = 'Estado (UF) é obrigatório.';
        } elseif (strlen($endereco->estado) !== 2) {
            $errors['endereco.estado'] = 'Estado (UF) deve ter 2 letras.';
        }

        return $errors;
    }
}