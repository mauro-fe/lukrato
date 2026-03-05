# Checklist Manual Guiado - Recorrencias

## Preparacao
1. Garantir que o scheduler/cron esteja configurado para rodar as tarefas de recorrencia.
2. Rodar diagnostico de duplicados (dry-run):
   - `php cli/sanear_duplicados_recorrencias.php`
3. Se houver duplicados e voce quiser sanear:
   - `php cli/sanear_duplicados_recorrencias.php --apply`
4. Aplicar migration de blindagem de duplicidade:
   - `php cli/run_unique_recorrencias_migration.php`

## Cenarios Sem Cartao
1. Criar lancamento de despesa recorrente mensal para hoje (ex.: 05/03/2026), sem cartao.
2. Validar que foi criado apenas 1 lancamento inicial (pendente).
3. Validar mensagem de sucesso indicando que os proximos itens serao gerados no proximo ciclo.
4. Marcar o item como pago e validar atualizacao de status.
5. Desmarcar pago e validar retorno para pendente.

## Cenarios Com Cartao
1. Criar item recorrente de cartao mensal (assinatura) com data de hoje.
2. Validar que apenas 1 item inicial foi criado na fatura.
3. Validar que nao foi criado item extra do proximo mes imediatamente.
4. Pagar a fatura e validar fluxo normal de pagamento por fatura.

## Virada de Ciclo (Cron)
1. Executar scheduler completo ou endpoint de geracao de recorrencias.
2. Validar que foi criado no maximo 1 novo item por serie cujo ciclo venceu.
3. Reexecutar scheduler logo em seguida e validar idempotencia (sem novos duplicados).

## Pos-validacao de Duplicidade
1. Re-rodar diagnostico dry-run:
   - `php cli/sanear_duplicados_recorrencias.php`
2. Esperado: `grupos=0` para lancamentos e cartao.

## Observacoes
- Fluxo de cartao continua via pagamento de fatura.
- Fluxo sem cartao permite marcar pago no proprio item.
- A unique key impede que corrida concorrente gere duplicados no mesmo ciclo.
