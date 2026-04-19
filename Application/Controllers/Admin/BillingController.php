<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Models\Plano;
use Application\Models\Usuario;
use Application\Repositories\DocumentoRepository;
use Application\Services\Billing\SubscriptionExpirationService;
use Illuminate\Database\Capsule\Manager as DB;

class BillingController extends WebController
{
    private ?DocumentoRepository $documentoRepo = null;

    public function index(): Response
    {
        $user = $this->requireUser();
        $plans = $this->loadActivePlans();

        return $this->renderAdminResponse(
            'admin/billing/index',
            [
                ...$this->buildBillingViewData($user, $plans),
                'pageTitle' => 'Assinar Pro',
                'subTitle' => 'Assine o pro e tenha acesso a todas as funcionalidades',
            ]
        );
    }

    public function checkout(): Response
    {
        $user = $this->requireUser();
        $plans = $this->loadActivePlans();
        $selectedPlan = $this->resolveCheckoutPlan($plans, $this->getStringQuery('plan', 'pro'));

        if (!$selectedPlan) {
            return $this->buildRedirectResponse('billing');
        }

        $checkoutCycle = $this->resolveCheckoutCycle();

        return $this->renderAdminResponse(
            'admin/billing/checkout',
            [
                ...$this->buildBillingViewData($user, $plans),
                'checkoutPlan' => $this->buildCheckoutPlanData($selectedPlan, $checkoutCycle),
                'checkoutCycle' => $checkoutCycle,
                'pageTitle' => 'Pagamento Seguro',
                'subTitle' => 'Finalize sua assinatura Lukrato Pro',
                'currentPageJsViewId' => 'admin-billing-checkout',
            ]
        );
    }

    private function loadActivePlans()
    {
        return Plano::query()
            ->where('ativo', true)
            ->orderBy('preco_centavos')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param iterable<int, Plano> $plans
     * @return array<string, mixed>
     */
    private function buildBillingViewData(Usuario $user, iterable $plans): array
    {
        $currentPlan = $user->planoAtual();
        $assinatura = $user->assinaturaAtiva()->first();
        $subscriptionStatus = SubscriptionExpirationService::getSubscriptionStatus($assinatura);

        return [
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
            ...$this->resolveBillingData($user),
        ];
    }

    /**
     * @param iterable<int, Plano> $plans
     */
    private function resolveCheckoutPlan(iterable $plans, string $requestedCode): ?Plano
    {
        $requestedCode = strtolower(trim($requestedCode));
        $firstPaidPlan = null;
        $proPlan = null;
        $requestedPaidPlan = null;

        foreach ($plans as $plan) {
            if (((int) $plan->preco_centavos) <= 0) {
                continue;
            }

            $code = strtolower((string) $plan->code);

            if ($code === $requestedCode) {
                $requestedPaidPlan = $plan;
            }

            if ($code === 'pro') {
                $proPlan = $plan;
            }

            $firstPaidPlan ??= $plan;
        }

        // O checkout atual cria assinatura PRO; evita exibir outro plano que a API nao cobraria.
        return $proPlan ?? $requestedPaidPlan ?? $firstPaidPlan;
    }

    /**
     * @return array{cycle:string,months:int,discount:int,label:string,period:string}
     */
    private function resolveCheckoutCycle(): array
    {
        $months = $this->getIntQuery('months', 0);

        if (!in_array($months, [1, 6, 12], true)) {
            $months = match (strtolower($this->getStringQuery('cycle', 'monthly'))) {
                'semiannual' => 6,
                'annual' => 12,
                default => 1,
            };
        }

        return match ($months) {
            6 => [
                'cycle' => 'semiannual',
                'months' => 6,
                'discount' => 10,
                'label' => 'Semestral',
                'period' => 'semestre',
            ],
            12 => [
                'cycle' => 'annual',
                'months' => 12,
                'discount' => 15,
                'label' => 'Anual',
                'period' => 'ano',
            ],
            default => [
                'cycle' => 'monthly',
                'months' => 1,
                'discount' => 0,
                'label' => 'Mensal',
                'period' => 'mes',
            ],
        };
    }

    /**
     * @param array{cycle:string,months:int,discount:int,label:string,period:string} $cycle
     * @return array{planId:string,planCode:string,planName:string,monthlyBase:float,cycle:string,months:int,discount:int,total:float,period:string,label:string}
     */
    private function buildCheckoutPlanData(Plano $plan, array $cycle): array
    {
        $monthlyBase = round(max(0, (int) $plan->preco_centavos) / 100, 2);
        $total = round($monthlyBase * $cycle['months'] * (1 - ($cycle['discount'] / 100)), 2);

        return [
            'planId' => (string) $plan->id,
            'planCode' => (string) $plan->code,
            'planName' => (string) ($plan->nome ?? $plan->code),
            'monthlyBase' => $monthlyBase,
            'cycle' => $cycle['cycle'],
            'months' => $cycle['months'],
            'discount' => $cycle['discount'],
            'total' => $total,
            'period' => $cycle['period'],
            'label' => $cycle['label'],
        ];
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

            $hasTelefonesTable = DB::schema()->hasTable('telefones');
            $hasDddTable = DB::schema()->hasTable('ddd');
            $hasEnderecosTable = DB::schema()->hasTable('enderecos');

            $telRow = null;
            if ($hasTelefonesTable) {
                $telQuery = DB::table('telefones as t')
                    ->where('t.id_usuario', $user->id)
                    ->orderBy('t.id_telefone');

                if ($hasDddTable) {
                    $telQuery->leftJoin('ddd as d', 'd.id_ddd', '=', 't.id_ddd');
                }

                $telRow = $telQuery->first();
            }

            if ($telRow) {
                $ddd = trim((string) ($telRow->codigo ?? ''));
                $num = trim((string) ($telRow->numero ?? ''));
                if ($ddd !== '' && $num !== '') {
                    $telefoneValue = sprintf('(%s) %s', $ddd, $num);
                }
            }

            $endereco = null;
            if ($hasEnderecosTable) {
                $endereco = $user->enderecoPrincipal ?? null;

                if (!$endereco || empty($endereco->cep)) {
                    $endereco = DB::table('enderecos')
                        ->where('user_id', $user->id)
                        ->whereNotNull('cep')
                        ->where('cep', '!=', '')
                        ->first();
                }
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
        return $this->documentoRepo ??= $this->resolveOrCreate(
            null,
            DocumentoRepository::class
        );
    }
}
