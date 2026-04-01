<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Models\Plano;
use Application\Repositories\DocumentoRepository;
use Application\Services\Billing\SubscriptionExpirationService;
use Illuminate\Database\Capsule\Manager as DB;

class BillingController extends WebController
{
    private ?DocumentoRepository $documentoRepo = null;

    public function index(): Response
    {
        $user = $this->requireUser();
        $plans = Plano::query()
            ->where('ativo', true)
            ->orderBy('preco_centavos')
            ->orderBy('id')
            ->get();
        $currentPlan = $user?->planoAtual();
        $assinatura = $user?->assinaturaAtiva()->first();
        $subscriptionStatus = SubscriptionExpirationService::getSubscriptionStatus($assinatura);
        $billingData = $this->resolveBillingData($user);

        return $this->renderAdminResponse(
            'admin/billing/index',
            [
                'user' => $user,
                'plans' => $plans,
                'currentPlanCode' => $currentPlan?->code,
                'assinatura' => $assinatura,
                'subscriptionStatus' => $subscriptionStatus,
                'isCanceled' => $subscriptionStatus['is_canceled'],
                'isInGrace' => $subscriptionStatus['is_in_grace'],
                'isExpired' => $subscriptionStatus['is_expired'],
                'accessUntil' => $subscriptionStatus['access_until'],
                'graceDaysRemaining' => $subscriptionStatus['grace_days_remaining'],
                'graceHoursRemaining' => $subscriptionStatus['grace_hours_remaining'],
                'shouldShowRenew' => $subscriptionStatus['should_show_renew'],
                'alertMessage' => $subscriptionStatus['alert_message'],
                'statusLabel' => $subscriptionStatus['status_label'],
                'statusColor' => $subscriptionStatus['status_color'],
                'actionLabel' => $subscriptionStatus['action_label'],
                'pageTitle' => 'Assinar Pro',
                'subTitle' => 'Assine o pro e tenha acesso a todas as funcionalidades',
                ...$billingData,
            ]
        );
    }

    /**
     * @param \Application\Models\Usuario|null $user
     * @return array{cpfValue:string,telefoneValue:string,cepValue:string,enderecoValue:string,cpfDigits:string,phoneDigits:string,cepDigits:string,pixDataComplete:bool,boletoDataComplete:bool}
     */
    private function resolveBillingData(?\Application\Models\Usuario $user): array
    {
        $cpfValue = '';
        $telefoneValue = '';
        $cepValue = '';
        $enderecoValue = '';

        if ($user) {
            $cpfValue = $this->getDocumentoRepo()->getCpf($user->id) ?? '';

            $telRow = DB::table('telefones as t')
                ->leftJoin('ddd as d', 'd.id_ddd', '=', 't.id_ddd')
                ->where('t.id_usuario', $user->id)
                ->orderBy('t.id_telefone')
                ->first();

            if ($telRow) {
                $ddd = trim((string) ($telRow->codigo ?? ''));
                $num = trim((string) ($telRow->numero ?? ''));
                if ($ddd !== '' && $num !== '') {
                    $telefoneValue = sprintf('(%s) %s', $ddd, $num);
                }
            }

            $endereco = $user->enderecoPrincipal ?? null;

            if (!$endereco || empty($endereco->cep)) {
                $endereco = DB::table('enderecos')
                    ->where('user_id', $user->id)
                    ->whereNotNull('cep')
                    ->where('cep', '!=', '')
                    ->first();
            }

            if ($endereco && !empty($endereco->cep)) {
                $cepValue = $endereco->cep;
                $enderecoValue = ($endereco->logradouro ?? '') . ($endereco->numero ? ', ' . $endereco->numero : '');
            }
        }

        $cpfDigits = preg_replace('/\D/', '', $cpfValue);
        $phoneDigits = preg_replace('/\D/', '', $telefoneValue);
        $cepDigits = preg_replace('/\D/', '', $cepValue);

        return [
            'cpfValue' => $cpfValue,
            'telefoneValue' => $telefoneValue,
            'cepValue' => $cepValue,
            'enderecoValue' => $enderecoValue,
            'cpfDigits' => $cpfDigits,
            'phoneDigits' => $phoneDigits,
            'cepDigits' => $cepDigits,
            'pixDataComplete' => strlen($cpfDigits) === 11,
            'boletoDataComplete' => strlen($cpfDigits) === 11 && strlen($cepDigits) === 8,
        ];
    }

    private function getDocumentoRepo(): DocumentoRepository
    {
        return $this->documentoRepo ??= new DocumentoRepository();
    }
}
