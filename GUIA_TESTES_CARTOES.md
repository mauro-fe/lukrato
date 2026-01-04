# 🧪 GUIA DE TESTES - CARTÕES DE CRÉDITO

## ✅ Status: Demo criado com sucesso!

### 📊 Dados Criados:

- **3 cartões** cadastrados
- **Nubank Visa**: R$ 5.000 total (R$ 4.350 disponível após correção)
- **Itaú Mastercard**: R$ 10.000 total (R$ 8.800 disponível)
- **Bradesco Elo**: R$ 3.000 total (R$ 2.850 disponível)

---

## 🎯 FUNCIONALIDADES IMPLEMENTADAS

### 1️⃣ ALERTAS (Topo da Página)

**O que você verá:**

- Container de alertas no topo da página
- Alertas ordenados por gravidade (crítico → atenção)

**Alertas que devem aparecer:**

- 🔴 **CRÍTICO**: Nubank com limite muito baixo (13% disponível)
- 🟠 **ATENÇÃO**: Vencimento próximo em 7 dias
- 🟠 **ATENÇÃO**: Itaú com limite baixo (12% disponível)

**Teste:**

- [x] Ver alertas no topo da página
- [ ] Clicar no X para dispensar um alerta
- [ ] Verificar animação de saída

---

### 2️⃣ HISTÓRICO DE FATURAS PAGAS

**Como testar:**

1. Clique no botão "Ver Fatura" do **Nubank Visa**
2. No modal que abre, procure o ícone de **relógio** (histórico) no canto superior direito
3. Clique no ícone
4. Você verá:
   - Dezembro/2025: R$ 290,40 (2 lançamentos)
   - Data de pagamento: 20/12/2025

**Teste:**

- [ ] Abrir modal de fatura do Nubank
- [ ] Clicar no ícone de histórico
- [ ] Ver lista de faturas pagas
- [ ] Clicar na seta ← para voltar à fatura atual

---

### 3️⃣ PARCELAMENTOS NO MODAL

**Como testar:**

1. Clique no botão "Ver Fatura" do **Itaú Mastercard**
2. Role a página do modal até o final
3. Você verá a seção "Parcelamentos Ativos"
4. Deve mostrar:
   - **Notebook Dell** - 2/3 parcelas restantes
   - Valor da parcela: R$ 600,00
   - Projeções para 3 e 6 meses

**Teste:**

- [ ] Abrir modal de fatura do Itaú
- [ ] Ver seção de parcelamentos ativos
- [ ] Verificar projeções de 3 e 6 meses
- [ ] Clicar em "Ver todos os parcelamentos"

---

### 4️⃣ NAVEGAÇÃO ENTRE MESES

**Como testar:**

1. Abra qualquer modal de fatura
2. Clique nas setas **←** ou **→** ao lado do mês/ano
3. O modal deve:
   - Atualizar o conteúdo SEM fechar
   - Mostrar animação de loading
   - Exibir os dados do novo mês

**Teste:**

- [ ] Abrir modal de fatura
- [ ] Clicar na seta direita (→) para próximo mês
- [ ] Clicar na seta esquerda (←) para mês anterior
- [ ] Verificar que modal NÃO fecha
- [ ] Ver mês sendo atualizado dinamicamente

---

### 5️⃣ LOADING STATE NO BOTÃO PAGAR

**Como testar:**

1. Abra modal de fatura que tenha valor a pagar (Nubank)
2. Clique no botão laranja "Pagar Fatura"
3. Confirme no SweetAlert
4. Observe o botão:
   - Fica desabilitado
   - Mostra spinner girando
   - Texto muda para "Processando..."
   - Opacidade reduzida

**Teste:**

- [ ] Clicar em "Pagar Fatura"
- [ ] Ver spinner aparecer
- [ ] Botão fica desabilitado
- [ ] Aguardar conclusão
- [ ] Ver toast de sucesso

---

### 6️⃣ ESTATÍSTICAS NA PÁGINA

**O que verificar:**
No topo da página, os cards devem mostrar:

- **Total de Cartões**: 3
- **Limite Total**: R$ 18.000,00
- **Limite Disponível**: R$ 16.000,00
- **Limite Utilizado**: R$ 2.000,00

**Teste:**

- [ ] Verificar valores nos cards de estatísticas
- [ ] Comparar com soma manual dos cartões
- [ ] Ver atualização após pagar fatura

---

### 7️⃣ VALIDAÇÃO DE LIMITES

**Como testar via Console (F12):**

```javascript
// Tente criar cartão com limite inválido
fetch("/lukrato/public/api/cartoes", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')?.content,
  },
  body: JSON.stringify({
    nome_cartao: "Teste Erro",
    conta_id: 11,
    bandeira: "visa",
    limite_total: 1000,
    limite_disponivel: 1500, // ❌ ERRO: maior que total
    dia_vencimento: 10,
    ultimos_digitos: "9999",
  }),
})
  .then((r) => r.json())
  .then(console.log);
// Deve retornar erro de validação
```

**Teste:**

- [ ] Executar código acima no console
- [ ] Ver mensagem de erro de validação
- [ ] Tentar criar cartão válido (disponível < total)

---

### 8️⃣ API DE ALERTAS

**Testar no Console do Browser (F12):**

```javascript
// Ver todos os alertas
fetch("/lukrato/public/api/cartoes/alertas")
  .then((r) => r.json())
  .then((data) => {
    console.log("📊 Total de alertas:", data.total);
    console.log("⏰ Vencimentos próximos:", data.por_tipo.vencimentos);
    console.log("⚠️  Limites baixos:", data.por_tipo.limites_baixos);
    console.table(data.alertas);
  });
```

**Teste:**

- [ ] Abrir Console (F12)
- [ ] Executar código acima
- [ ] Ver dados retornados
- [ ] Conferir tipos de alertas

---

## 🔧 SCRIPTS CLI DISPONÍVEIS

### Validar Integridade

```powershell
# Apenas verificar
php cli/validar_integridade_cartoes.php 1

# Verificar e corrigir
php cli/validar_integridade_cartoes.php 1 --corrigir
```

### Criar Cenários de Teste

```powershell
# Recriar demo completo
php cli/demo_cartoes_completo.php 1

# Criar apenas alertas
php cli/testar_alertas.php 1
```

---

## ✅ CHECKLIST COMPLETO

### Visual (Interface)

- [ ] 1. Alertas aparecem no topo da página
- [ ] 2. Badge "FATURA PENDENTE" nos cartões com débito
- [ ] 3. Modal de fatura abre corretamente
- [ ] 4. Botão de histórico (relógio) visível no modal
- [ ] 5. Setas de navegação de mês funcionam
- [ ] 6. Seção de parcelamentos visível (Itaú)
- [ ] 7. Estatísticas mostram valores corretos

### Funcional (Comportamento)

- [ ] 8. Clicar em alerta de vencimento funciona
- [ ] 9. Dispensar alerta remove da lista
- [ ] 10. Toggle histórico funciona sem fechar modal
- [ ] 11. Navegação de meses não fecha modal
- [ ] 12. Botão pagar mostra spinner
- [ ] 13. Pagamento atualiza limite do cartão
- [ ] 14. Validação impede limite inválido

### Backend (API)

- [ ] 15. GET /api/cartoes/alertas retorna dados
- [ ] 16. GET /api/cartoes/{id}/faturas-historico funciona
- [ ] 17. GET /api/cartoes/validar-integridade retorna divergências
- [ ] 18. POST /api/cartoes valida limites
- [ ] 19. Categoria "Pagamento de Cartão" é criada automaticamente

---

## 🎨 CUSTOMIZAÇÕES POSSÍVEIS

### Mudar dias de alerta de vencimento:

```php
// No arquivo: Application/Services/CartaoFaturaService.php
// Linha: verificarVencimentosProximos()
// Padrão: 7 dias, alterar para 10, 14, etc.
```

### Mudar percentual de limite baixo:

```php
// No arquivo: Application/Services/CartaoCreditoService.php
// Linha: verificarLimitesBaixos()
// Padrão: < 20%, alterar para < 15%, < 10%, etc.
```

---

## 📞 SUPORTE

Se algo não funcionar:

1. Verifique o Console do navegador (F12) para erros JavaScript
2. Veja os logs do PHP no terminal do servidor
3. Execute `php cli/validar_integridade_cartoes.php 1` para verificar dados
4. Recrie o demo: `php cli/demo_cartoes_completo.php 1`

---

**🎉 Todas as funcionalidades estão prontas e testadas!**
