<?php

declare(strict_types=1);

namespace Application\Config;

final class AuthRuntimeConfig
{
    public function googleClientId(): string
    {
        return $this->string('GOOGLE_CLIENT_ID', '');
    }

    public function googleClientSecret(): string
    {
        return $this->string('GOOGLE_CLIENT_SECRET', '');
    }

    public function googleRedirectUri(): string
    {
        return $this->string('GOOGLE_REDIRECT_URI', '');
    }

    public function googleCallbackUrl(): string
    {
        $configured = $this->googleRedirectUri();

        return $configured !== '' ? $configured : $this->backendUrl('api/v1/auth/google/callback');
    }

    public function googleConfirmPageUrl(): string
    {
        return $this->url('FRONTEND_GOOGLE_CONFIRM_URL', $this->backendUrl('api/v1/auth/google/confirm-page'));
    }

    public function googlePendingUrl(): string
    {
        return $this->backendUrl('api/v1/auth/google/pending');
    }

    public function googleConfirmUrl(): string
    {
        return $this->backendUrl('api/v1/auth/google/confirm');
    }

    public function googleCancelUrl(): string
    {
        return $this->backendUrl('api/v1/auth/google/cancel');
    }

    public function frontendAppUrl(): string
    {
        return rtrim($this->string('FRONTEND_APP_URL', ''), '/');
    }

    public function loginUrl(): string
    {
        return $this->url('FRONTEND_LOGIN_URL', $this->backendUrl('login'));
    }
    public function hasConfiguredLoginUrl(): bool
    {
        return $this->string('FRONTEND_LOGIN_URL', '') !== '';
    }

    public function forgotPasswordUrl(): string
    {
        return $this->url('FRONTEND_FORGOT_PASSWORD_URL', $this->backendUrl('recuperar-senha'));
    }
    public function hasConfiguredForgotPasswordUrl(): bool
    {
        return $this->string('FRONTEND_FORGOT_PASSWORD_URL', '') !== '';
    }

    public function resetPasswordUrl(string $token = '', string $selector = '', string $validator = ''): string
    {
        $url = $this->url('FRONTEND_RESET_PASSWORD_URL', $this->backendUrl('resetar-senha'));

        if ($selector !== '' && $validator !== '') {
            return $this->appendQueryParams($url, [
                'selector' => $selector,
                'validator' => $validator,
            ]);
        }

        if ($token !== '') {
            return $this->appendQueryParams($url, [
                'token' => $token,
            ]);
        }

        return $url;
    }
    public function hasConfiguredResetPasswordUrl(): bool
    {
        return $this->string('FRONTEND_RESET_PASSWORD_URL', '') !== '';
    }

    public function resetPasswordValidateUrl(): string
    {
        return $this->backendUrl('api/v1/auth/password/reset/validate');
    }

    public function resetPasswordSubmitUrl(): string
    {
        return $this->backendUrl('api/v1/auth/password/reset');
    }

    public function emailNoticeUrl(): string
    {
        return $this->backendUrl('api/v1/auth/email/notice');
    }

    public function emailVerifyUrl(): string
    {
        return $this->backendUrl('api/v1/auth/email/verify');
    }

    public function emailResendUrl(): string
    {
        return $this->backendUrl('api/v1/auth/email/resend');
    }

    public function verifyEmailPageUrl(string $token = '', string $selector = '', string $validator = ''): string
    {
        $url = $this->verifyEmailNoticePageUrl();

        if ($selector !== '' && $validator !== '') {
            return $this->appendQueryParams($url, [
                'selector' => $selector,
                'validator' => $validator,
            ]);
        }

        if ($token !== '') {
            return $this->appendQueryParams($url, [
                'token' => $token,
            ]);
        }

        return $url;
    }

    public function verifyEmailNoticePageUrl(): string
    {
        return $this->url('FRONTEND_VERIFY_EMAIL_NOTICE_URL', $this->backendUrl('verificar-email/aviso'));
    }

    public function hasConfiguredVerifyEmailNoticeUrl(): bool
    {
        return $this->string('FRONTEND_VERIFY_EMAIL_NOTICE_URL', '') !== '';
    }

    public function loginUrlForIntended(string $intended): string
    {
        $path = $this->normalizeRelativePath($intended);
        if ($path === '') {
            return $this->loginUrl();
        }

        return $this->appendQuery($this->loginUrl(), 'intended', $path);
    }

    public function hasConfiguredGoogleConfirmPageUrl(): bool
    {
        return $this->string('FRONTEND_GOOGLE_CONFIRM_URL', '') !== '';
    }

    public function dashboardUrl(): string
    {
        return $this->url('FRONTEND_DASHBOARD_URL', $this->backendUrl('dashboard'));
    }

    public function welcomeUrl(): string
    {
        return $this->url('FRONTEND_WELCOME_URL', $this->backendUrl('dashboard?welcome=1'));
    }

    public function intendedUrl(string $intended, ?string $default = null): string
    {
        $path = $this->normalizeRelativePath($intended);
        if ($path === '') {
            return $default !== null && $default !== '' ? $default : $this->dashboardUrl();
        }

        $frontendAppUrl = $this->frontendAppUrl();
        if ($frontendAppUrl !== '') {
            return $frontendAppUrl . '/' . $path;
        }

        return $this->backendUrl($path);
    }

    public function hasGoogleOauthCredentials(): bool
    {
        return $this->googleClientId() !== ''
            && $this->googleClientSecret() !== ''
            && $this->googleRedirectUri() !== '';
    }

    private function string(string $key, string $default): string
    {
        return trim((string) $this->value($key, $default));
    }

    private function url(string $key, string $default): string
    {
        $value = $this->string($key, '');

        return $value !== '' ? $value : $default;
    }

    private function backendUrl(string $path = ''): string
    {
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        if ($baseUrl === '') {
            return ltrim($path, '/');
        }

        return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
    }

    private function normalizeRelativePath(string $path): string
    {
        $normalized = trim($path, '/ ');
        if ($normalized === '' || !preg_match('#^[a-zA-Z0-9/_\-]+$#', $normalized)) {
            return '';
        }

        return $normalized;
    }

    private function appendQuery(string $url, string $key, string $value): string
    {
        return $this->appendQueryParams($url, [
            $key => $value,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    private function appendQueryParams(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }

    private function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== null) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}
