<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\DTO\ServiceResultDTO;
use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;

class GetFinanceiroOptionsUseCase
{
    public function __construct(
        private readonly CategoriaRepository $categoriaRepo = new CategoriaRepository(),
        private readonly ContaRepository $contaRepo = new ContaRepository()
    ) {
    }

    public function execute(int $userId): ServiceResultDTO
    {
        $catsReceita = $this->categoriaRepo->findReceitas($userId);
        $catsDespesa = $this->categoriaRepo->findDespesas($userId);
        $contas = $this->contaRepo->findActive($userId);

        return new ServiceResultDTO(
            success: true,
            message: 'Success',
            data: [
                'categorias' => [
                    'receitas' => $catsReceita->map(fn(Categoria $c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
                    'despesas' => $catsDespesa->map(fn(Categoria $c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
                ],
                'contas' => $contas->map(fn(Conta $c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
            ],
            httpCode: 200
        );
    }
}
