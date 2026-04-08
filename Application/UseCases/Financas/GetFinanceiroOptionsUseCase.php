<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;

class GetFinanceiroOptionsUseCase
{
    private readonly CategoriaRepository $categoriaRepo;
    private readonly ContaRepository $contaRepo;

    public function __construct(
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null
    ) {
        $this->categoriaRepo = ApplicationContainer::resolveOrNew($categoriaRepo, CategoriaRepository::class);
        $this->contaRepo = ApplicationContainer::resolveOrNew($contaRepo, ContaRepository::class);
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
