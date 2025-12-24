# Guia de Uso dos Repositories

## 📚 Introdução

Os repositories foram criados para centralizar toda a lógica de acesso a dados, tornando o código mais limpo, testável e manutenível.

---

## 🏗️ Estrutura

### Base
- **RepositoryInterface**: Interface que define o contrato básico
- **BaseRepository**: Implementação abstrata com métodos comuns

### Repositories Disponíveis
- **LancamentoRepository**: Operações com lançamentos
- **ContaRepository**: Operações com contas
- **CategoriaRepository**: Operações com categorias
- **UsuarioRepository**: Operações com usuários (já existente)
- **InvestimentoRepository**: Operações com investimentos (já existente)

---

## 💡 Como Usar

### 1. Injetar no Controller

```php
use Application\Repositories\LancamentoRepository;

class MeuController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
    }
}
```

### 2. Métodos Básicos (Herdados de BaseRepository)

```php
// Buscar por ID
$lancamento = $this->lancamentoRepo->find(1);

// Buscar por ID ou lançar exceção
$lancamento = $this->lancamentoRepo->findOrFail(1);

// Buscar todos
$lancamentos = $this->lancamentoRepo->all();

// Criar
$lancamento = $this->lancamentoRepo->create([
    'user_id' => 1,
    'tipo' => 'receita',
    'valor' => 100.00,
    // ...
]);

// Atualizar
$this->lancamentoRepo->update($id, ['valor' => 150.00]);

// Deletar
$this->lancamentoRepo->delete($id);

// Contar
$total = $this->lancamentoRepo->count();

// Paginação
$lancamentos = $this->lancamentoRepo->paginate(15, 1);

// Buscar com condições
$lancamentos = $this->lancamentoRepo->findWhere([
    'user_id' => 1,
    'tipo' => 'receita'
]);
```

---

## 📋 LancamentoRepository

### Métodos Específicos

#### Por Usuário
```php
// Todos os lançamentos do usuário
$lancamentos = $this->lancamentoRepo->findByUser($userId);

// Por mês
$lancamentos = $this->lancamentoRepo->findByUserAndMonth($userId, '2025-12');

// Por período
$lancamentos = $this->lancamentoRepo->findByPeriod($userId, '2025-01-01', '2025-12-31');
```

#### Por Filtros
```php
use Application\Enums\LancamentoTipo;

// Por conta
$lancamentos = $this->lancamentoRepo->findByAccount($userId, $contaId);

// Por categoria
$lancamentos = $this->lancamentoRepo->findByCategory($userId, $categoriaId);

// Por tipo
$lancamentos = $this->lancamentoRepo->findByType($userId, LancamentoTipo::RECEITA);

// Apenas receitas
$receitas = $this->lancamentoRepo->findReceitas($userId);

// Apenas despesas
$despesas = $this->lancamentoRepo->findDespesas($userId);

// Apenas transferências
$transferencias = $this->lancamentoRepo->findTransferencias($userId);
```

#### Buscar com Segurança de Usuário
```php
// Busca lançamento específico do usuário
$lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);

// Ou lança exceção se não encontrar
$lancamento = $this->lancamentoRepo->findByIdAndUserOrFail($id, $userId);
```

#### Estatísticas
```php
// Contar lançamentos no mês
$count = $this->lancamentoRepo->countByMonth($userId, '2025-12');

// Soma por tipo e período
$total = $this->lancamentoRepo->sumByTypeAndPeriod(
    $userId,
    '2025-01-01',
    '2025-12-31',
    LancamentoTipo::RECEITA
);
```

#### Operações em Massa
```php
// Deletar todos de uma conta
$deleted = $this->lancamentoRepo->deleteByAccount($userId, $contaId);

// Atualizar categoria em massa
$updated = $this->lancamentoRepo->updateCategory($userId, $oldCatId, $newCatId);
```

---

## 🏦 ContaRepository

### Métodos Específicos

#### Buscar
```php
// Contas do usuário
$contas = $this->contaRepo->findByUser($userId);

// Apenas ativas
$contas = $this->contaRepo->findActive($userId);

// Apenas arquivadas
$contas = $this->contaRepo->findArchived($userId);

// Por moeda
$contas = $this->contaRepo->findByMoeda($userId, 'BRL');

// Com lançamentos carregados
$contas = $this->contaRepo->findWithLancamentos($userId);
```

#### Buscar Específica
```php
// Conta específica do usuário
$conta = $this->contaRepo->findByIdAndUser($id, $userId);

// Ou lança exceção
$conta = $this->contaRepo->findByIdAndUserOrFail($id, $userId);
```

#### CRUD Seguro
```php
// Criar para usuário
$conta = $this->contaRepo->createForUser($userId, [
    'nome' => 'Conta Corrente',
    'moeda' => 'BRL',
]);

// Atualizar
$this->contaRepo->updateForUser($id, $userId, ['nome' => 'Novo Nome']);

// Arquivar (soft delete)
$this->contaRepo->archive($id, $userId);

// Restaurar
$this->contaRepo->restore($id, $userId);

// Deletar permanentemente
$this->contaRepo->deleteForUser($id, $userId);
```

#### Validações
```php
// Verifica se pertence ao usuário
if ($this->contaRepo->belongsToUser($id, $userId)) {
    // ...
}

// Verifica nome duplicado
if ($this->contaRepo->hasDuplicateName($userId, $nome)) {
    // ...
}
```

#### Estatísticas
```php
// Contar ativas
$total = $this->contaRepo->countActive($userId);

// Contar todas
$total = $this->contaRepo->countByUser($userId);

// Buscar apenas IDs
$ids = $this->contaRepo->getIdsByUser($userId, true);
```

---

## 📁 CategoriaRepository

### Métodos Específicos

#### Buscar
```php
use Application\Enums\CategoriaTipo;

// Todas (incluindo globais do sistema)
$categorias = $this->categoriaRepo->findByUser($userId);

// Apenas próprias (não globais)
$categorias = $this->categoriaRepo->findOwnByUser($userId);

// Por tipo
$categorias = $this->categoriaRepo->findByType($userId, CategoriaTipo::RECEITA);

// Receitas (inclui AMBAS)
$receitas = $this->categoriaRepo->findReceitas($userId);

// Despesas (inclui AMBAS)
$despesas = $this->categoriaRepo->findDespesas($userId);

// Apenas globais
$globais = $this->categoriaRepo->findGlobal();
```

#### Buscar Específica
```php
// Categoria do usuário (ou global)
$categoria = $this->categoriaRepo->findByIdAndUser($id, $userId);

// Apenas própria (não global)
$categoria = $this->categoriaRepo->findOwnByIdAndUser($id, $userId);
```

#### CRUD Seguro
```php
// Criar para usuário
$categoria = $this->categoriaRepo->createForUser($userId, [
    'nome' => 'Alimentação',
    'tipo' => 'despesa',
]);

// Atualizar (apenas próprias)
$this->categoriaRepo->updateForUser($id, $userId, ['nome' => 'Novo Nome']);

// Deletar (apenas próprias)
$this->categoriaRepo->deleteForUser($id, $userId);
```

#### Validações
```php
// Verifica duplicada
if ($this->categoriaRepo->hasDuplicate($userId, $nome, $tipo)) {
    // ...
}

// Verifica se pertence ao usuário ou é global
if ($this->categoriaRepo->belongsToUser($id, $userId)) {
    // ...
}

// Verifica se é global
if ($this->categoriaRepo->isGlobal($id)) {
    // Não pode editar/deletar
}
```

#### Estatísticas
```php
// Mais usadas
$topCategorias = $this->categoriaRepo->findMostUsed($userId, 10);

// Não usadas (sem lançamentos)
$unused = $this->categoriaRepo->findUnused($userId);

// Contar por tipo
$count = $this->categoriaRepo->countByType($userId, CategoriaTipo::RECEITA);
```

---

## 🎯 Benefícios

### ✅ Antes (Sem Repository)
```php
public function index()
{
    $lancamentos = Lancamento::where('user_id', Auth::id())
        ->whereYear('data', 2025)
        ->whereMonth('data', 12)
        ->where('eh_transferencia', 0)
        ->orderBy('data', 'desc')
        ->get();
    
    Response::success($lancamentos);
}
```

### ✅ Depois (Com Repository)
```php
public function index()
{
    $lancamentos = $this->lancamentoRepo->findByUserAndMonth(
        Auth::id(),
        '2025-12'
    );
    
    Response::success($lancamentos);
}
```

### Vantagens:
- ✅ Código mais limpo e legível
- ✅ Lógica de acesso a dados centralizada
- ✅ Fácil de testar (mock do repository)
- ✅ Reutilização de queries complexas
- ✅ Mudanças no banco impactam apenas o repository
- ✅ Type hints e autocomplete

---

## 🧪 Testando

```php
// Mock do repository em testes
$mockRepo = $this->createMock(LancamentoRepository::class);
$mockRepo->method('findByUser')
    ->willReturn(collect([/* dados fake */]));

// Injetar no controller
$controller->setLancamentoRepo($mockRepo);
```

---

## 🚀 Próximos Passos

1. Refatorar todos os controllers para usar repositories
2. Remover queries diretas dos controllers
3. Adicionar testes unitários para repositories
4. Criar repositories para models restantes
5. Implementar cache nos repositories quando necessário

---

## 📖 Exemplos Completos

### Exemplo 1: Listar Lançamentos com Filtros
```php
public function index(): void
{
    $userId = Auth::id();
    $month = $_GET['month'] ?? date('Y-m');
    $tipo = $_GET['tipo'] ?? null;
    $contaId = $_GET['conta_id'] ?? null;
    
    if ($contaId) {
        $lancamentos = $this->lancamentoRepo->findByAccount($userId, (int)$contaId);
    } elseif ($tipo) {
        $tipoEnum = LancamentoTipo::from($tipo);
        $lancamentos = $this->lancamentoRepo->findByType($userId, $tipoEnum);
    } else {
        $lancamentos = $this->lancamentoRepo->findByUserAndMonth($userId, $month);
    }
    
    Response::success($lancamentos);
}
```

### Exemplo 2: Criar Conta com Validação
```php
public function store(): void
{
    $userId = Auth::id();
    $data = $this->getRequestPayload();
    
    // Validar nome duplicado
    if ($this->contaRepo->hasDuplicateName($userId, $data['nome'])) {
        Response::error('Conta com este nome já existe', 409);
        return;
    }
    
    // Criar
    $conta = $this->contaRepo->createForUser($userId, $data);
    
    Response::success($conta, 'Conta criada com sucesso', 201);
}
```

### Exemplo 3: Deletar com Segurança
```php
public function destroy(int $id): void
{
    $userId = Auth::id();
    
    try {
        // Verifica se pertence ao usuário antes de deletar
        $this->contaRepo->deleteForUser($id, $userId);
        Response::success(['message' => 'Conta deletada']);
    } catch (ModelNotFoundException $e) {
        Response::error('Conta não encontrada', 404);
    }
}
```

---

## 💬 Perguntas Frequentes

**Q: Quando usar Repository vs Model direto?**  
A: Use Repository em Controllers e Services. Model direto apenas em Repositories e Scopes.

**Q: Posso adicionar métodos customizados?**  
A: Sim! Cada repository pode ter métodos específicos para seu domínio.

**Q: E se eu precisar de uma query muito específica?**  
A: Adicione o método no repository específico. Use `$this->query()` para começar.

**Q: Como fazer joins complexos?**  
A: No repository, use Query Builder ou Eloquent relationships.

---

**Documentação atualizada em:** 19/12/2025  
**Versão:** 2.0
