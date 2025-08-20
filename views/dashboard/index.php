<?php

use Application\Lib\Helpers;
use Carbon\Carbon;

// Formatter de dinheiro (fallback se $fmt não vier do controller)
$fmt = $fmt ?? fn($v) => Helpers::formatMoneyBRL((float)($v ?? 0));

// Formatter de data dd/mm/yyyy (fallback se $fmtDate não vier do controller)
$fmtDate = $fmtDate ?? function ($date) {
    if ($date instanceof \DateTimeInterface) return $date->format('d/m/Y');
    if (!$date) return '—';
    try {
        return Carbon::parse($date)->format('d/m/Y');
    } catch (\Throwable $e) {
        return '—';
    }
};

// Garante arrays pro gráfico (evita erros no json_encode/array_map)
$labels = is_array($labels ?? null) ? array_values($labels) : [];
$data   = is_array($data   ?? null) ? array_map('floatval', $data) : [];
?>



<main class="main-content max-height-vh-100 h-100 border-radius-lg">
    <div class="navbar navbar-main d-flex f">
        <div class="container" style="padding:20px;color:#eaeaea;">
            <h2 style="margin-bottom:20px;">Dashboard</h2>

            <!-- KPIs -->
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:24px;">
                <div style="background:#1f2937;padding:16px;border-radius:12px;">
                    <div style="opacity:.7;font-size:14px;">Receitas (mês)</div>
                    <div style="font-size:24px;margin-top:6px;"><?= $fmt($receitasMes) ?></div>
                </div>
                <div style="background:#1f2937;padding:16px;border-radius:12px;">
                    <div style="opacity:.7;font-size:14px;">Despesas (mês)</div>
                    <div style="font-size:24px;margin-top:6px;"><?= $fmt($despesasMes) ?></div>
                </div>
                <div style="background:#1f2937;padding:16px;border-radius:12px;">
                    <div style="opacity:.7;font-size:14px;">Saldo total</div>
                    <div style="font-size:24px;margin-top:6px;"><?= $fmt($saldoTotal) ?></div>
                </div>
            </div>

            <!-- (Opcional) dados prontos pro gráfico -->
            <script>
                window.dashboardChart = {
                    labels: <?= json_encode(array_values($labels), JSON_UNESCAPED_UNICODE) ?>,
                    data: <?= json_encode(array_map('floatval', $data), JSON_UNESCAPED_UNICODE) ?>
                };
            </script>

            <!-- Últimos lançamentos -->
            <div style="background:#111827;padding:16px;border-radius:12px;">
                <h3 style="margin:0 0 12px;">Últimos lançamentos</h3>
                <div style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:left;background:#1f2937;">
                                <th style="padding:10px;border-bottom:1px solid #374151;">Data</th>
                                <th style="padding:10px;border-bottom:1px solid #374151;">Tipo</th>
                                <th style="padding:10px;border-bottom:1px solid #374151;">Categoria</th>
                                <th style="padding:10px;border-bottom:1px solid #374151;">Valor</th>
                                <th style="padding:10px;border-bottom:1px solid #374151;">Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ultimos) && count($ultimos)): ?>
                                <?php foreach ($ultimos as $l): ?>
                                    <tr>
                                        <td style="padding:10px;border-bottom:1px solid #1f2937;">
                                            <?= htmlspecialchars($fmtDate($l->data)) ?>
                                        </td>
                                        <td style="padding:10px;border-bottom:1px solid #1f2937;">
                                            <?= htmlspecialchars(($l->tipo === 'receita') ? 'Receita' : 'Despesa') ?>
                                        </td>
                                        <td style="padding:10px;border-bottom:1px solid #1f2937;">
                                            <?= htmlspecialchars($l->categoria->nome ?? '—') ?>
                                        </td>
                                        <td style="padding:10px;border-bottom:1px solid #1f2937;">
                                            <?= $fmt($l->valor) ?>
                                        </td>
                                        <td style="padding:10px;border-bottom:1px solid #1f2937;">
                                            <?= htmlspecialchars($l->descricao ?? '—') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="padding:10px;">Nenhum lançamento encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>