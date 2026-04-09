<?php

/**
 * CONFIGURAÇÃO DE SESSÕES E CACHE COM REDIS
 * 
 * Adicione estas configurações no seu arquivo .env:
 * 
 * REDIS_HOST=127.0.0.1
 * REDIS_PORT=6379
 * REDIS_PASSWORD=
 * REDIS_DATABASE=0
 * 
 * SESSION_DRIVER=redis  # ou 'file' para fallback
 * CACHE_DRIVER=redis    # ou 'file' para fallback
 */

$runtimeConfig = \Application\Container\ApplicationContainer::tryMake(\Application\Config\InfrastructureRuntimeConfig::class)
    ?? new \Application\Config\InfrastructureRuntimeConfig();
$redisRuntimeConfig = \Application\Container\ApplicationContainer::tryMake(\Application\Config\RedisRuntimeConfig::class)
    ?? new \Application\Config\RedisRuntimeConfig();

// Verificar se Redis está disponível
$useRedis = class_exists(Predis\Client::class) && $runtimeConfig->sessionDriver() === 'redis';

if ($useRedis) {
    try {
        $redisClient = new Predis\Client([
            'scheme' => 'tcp',
            'host'   => $redisRuntimeConfig->host(),
            'port'   => $redisRuntimeConfig->port(),
            'password' => $redisRuntimeConfig->password(),
            'database' => $redisRuntimeConfig->database(),
        ]);

        // Testar conexão
        $redisClient->ping();

        // ✅ Configurar PHP para usar Redis como handler de sessão
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', sprintf(
            'tcp://%s:%s?auth=%s&database=%s',
            $redisRuntimeConfig->host(),
            $redisRuntimeConfig->port(),
            (string) ($redisRuntimeConfig->password() ?? ''),
            $redisRuntimeConfig->database()
        ));

        // Configurações otimizadas
        ini_set('session.gc_maxlifetime', 7200); // 2 horas
        ini_set('session.cookie_lifetime', 0);    // Até fechar navegador
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $runtimeConfig->appEnvironment() === 'production' ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');

        echo "✅ Redis configurado para sessões\n";
    } catch (\Throwable $e) {
        // Fallback para file
        echo "⚠️ Redis não disponível, usando sessões em arquivo\n";
        $useRedis = false;
    }
}

if (!$useRedis) {
    // ✅ Configuração para sessões em arquivo (melhorada)
    $sessionPath = BASE_PATH . '/storage/sessions';

    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0700, true);
    }

    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', $sessionPath);
    ini_set('session.gc_maxlifetime', 7200);
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $runtimeConfig->appEnvironment() === 'production' ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    // ✅ Limpar sessões antigas periodicamente (1% de chance a cada request)
    if (rand(1, 100) === 1) {
        $files = glob($sessionPath . '/sess_*');
        if ($files) {
            $now = time();
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file)) > 7200) {
                    @unlink($file);
                }
            }
        }
    }
}

// ✅ Regenerar ID de sessão periodicamente (segurança)
if (session_status() === PHP_SESSION_ACTIVE) {
    $lastRegeneration = $_SESSION['_last_regeneration'] ?? 0;

    if (time() - $lastRegeneration > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
}
