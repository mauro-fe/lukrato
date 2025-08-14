<?php

namespace Application\Services;

use Application\Models\Ficha;
use Application\Models\Resposta;
use Application\Models\Admin;
use Application\Lib\Helpers;
use Illuminate\Support\Collection;
use Application\Services\LogService;

class DashboardService
{
    public function carregarDadosDashboard(int $adminId, Admin $admin, int $page = 1, int $perPage = 10): array
    {
        $hoje = now();
        $inicioDia = $hoje->copy()->startOfDay();
        $inicioSemana = $hoje->copy()->startOfWeek();

        $total = Ficha::where('admin_id', $adminId)
            ->where('status', '!=', 'excluida')
            ->count();

        $fichasHoje = Ficha::where('admin_id', $adminId)
            ->where('status', '!=', 'excluida')
            ->where('created_at', '>=', $inicioDia)
            ->count();

        $fichasSemana = Ficha::where('admin_id', $adminId)
            ->where('status', '!=', 'excluida')
            ->where('created_at', '>=', $inicioSemana)
            ->count();

        $fichas = Ficha::with(['respostas.pergunta'])
            ->where('admin_id', $adminId)
            ->where('status', '!=', 'excluida')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        foreach ($fichas as $ficha) {
            $ficha->slug_clinica = $admin->slug_clinica ?? Helpers::slugify($admin->nome_clinica ?? 'clinica');
            $ficha->slug_cliente = Helpers::slugify($ficha->nome_completo ?? 'cliente');
            $ficha->data_criacao = $ficha->created_at;
        }

        return [
            'fichas' => $fichas,
            'totalFichas' => $total,
            'fichasHoje' => $fichasHoje,
            'fichasSemana' => $fichasSemana,
            'ficha_id' => $fichas[0]->id ?? 0,
            'slug_clinica' => $admin->slug_clinica ?? Helpers::slugify($admin->nome_clinica ?? 'clinica'),
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
        ];
    }


    public function excluirFicha(int $adminId, int $fichaId): void
    {
        $ficha = Ficha::findOrFail($fichaId);

        if ($ficha->admin_id !== $adminId) {
            throw new \Exception('Acesso negado.');
        }

        // Ao invés de deletar fisicamente, marcamos como excluída
        $ficha->status = 'excluida';
        $ficha->save();
    }


    public function getFichaCompleta(int $fichaId, int $adminId): ?array
    {
        $ficha = Ficha::where('id', $fichaId)
            ->where('admin_id', $adminId)
            ->first();

        if (!$ficha) {
            LogService::warning("Ficha não encontrada ou não pertence ao admin", [
                'ficha_id' => $fichaId,
                'admin_id' => $adminId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            return null;
        }

        $respostas = Resposta::where('ficha_id', $fichaId)
            ->where('admin_id', $adminId)
            ->with('pergunta')
            ->orderBy('pergunta_id')
            ->get();

        return [
            'ficha' => $ficha,
            'respostas' => $respostas
        ];
    }

    public function gerarHTMLParaDownload(Ficha $ficha, Collection $respostas, Admin $admin): string
    {
        $html = '
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .clinica { font-size: 18px; font-weight: bold; color: #2c5aa0; }
            .titulo { font-size: 16px; margin: 20px 0; }
            .resposta { margin: 15px 0; page-break-inside: avoid; }
            .pergunta { font-weight: bold; color: #333; margin-bottom: 5px; }
            .resposta-texto { margin-left: 10px; padding: 8px; background-color: #f8f9fa; border-left: 3px solid #2c5aa0; }
            .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        </style>

        <div class="header">
            <div class="clinica">' . Helpers::escapeHtml($admin->nome_clinica) . '</div>
            <div>' . Helpers::escapeHtml($admin->razao_social ?? '') . '</div>
        </div>

        <h2 class="titulo">Ficha de Anamnese</h2>

        <div class="resposta">
            <div class="pergunta">Data de Preenchimento:</div>
            <div class="resposta-texto">' . ($ficha->created_at ? $ficha->created_at->format('d/m/Y H:i') : 'N/A') . '</div>
        </div>';

        foreach ($respostas as $resposta) {
            $respostaDisplay = $resposta->getRespostaFormatadaAttribute();

            $html .= '
            <div class="resposta">
                <div class="pergunta">' . Helpers::escapeHtml($resposta->pergunta->pergunta ?? '') . '</div>
                <div class="resposta-texto">' . nl2br(Helpers::escapeHtml($respostaDisplay)) . '</div>
            </div>';
        }

        if ($ficha->observacoes) {
            $html .= '
            <div class="resposta">
                <div class="pergunta">Observações:</div>
                <div class="resposta-texto">' . nl2br(Helpers::escapeHtml($ficha->observacoes)) . '</div>
            </div>';
        }

        $html .= '
        <div class="footer">
            <p>Documento gerado em ' . date('d/m/Y H:i') . ' pelo Sistema de Anamnese</p>
            <p>' . Helpers::escapeHtml($admin->nome_clinica) . '</p>
        </div>';

        return $html;
    }
}
