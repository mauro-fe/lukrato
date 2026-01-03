<?php
require __DIR__ . '/../bootstrap.php';

use Application\Services\CacheService;

class MockPredis extends Predis\Client
{
    private array $store = [];
    public function __construct() {}
    public function ping()
    {
        return 'PONG';
    }
    public function setex(string $key, int $ttl, string $value)
    {
        $this->store[$key] = ['v' => $value, 'ex' => time() + $ttl];
        return true;
    }
    public function get(string $key)
    {
        if (!isset($this->store[$key])) return null;
        if ($this->store[$key]['ex'] !== 0 && $this->store[$key]['ex'] < time()) {
            unset($this->store[$key]);
            return null;
        }
        return $this->store[$key]['v'];
    }
    public function del(array $keys)
    {
        $c = 0;
        foreach ($keys as $k) {
            if (isset($this->store[$k])) {
                unset($this->store[$k]);
                $c++;
            }
        }
        return $c;
    }
    public function expire(string $key, int $s)
    {
        if (!isset($this->store[$key])) return false;
        $this->store[$key]['ex'] = time() + $s;
        return true;
    }
    public function incr(string $key)
    {
        if (!isset($this->store[$key])) {
            $this->store[$key] = ['v' => '1', 'ex' => 0];
            return 1;
        }
        $val = (int)$this->store[$key]['v'] + 1;
        $this->store[$key]['v'] = (string)$val;
        return $val;
    }
    public function ttl(string $key)
    {
        if (!isset($this->store[$key])) return -2;
        if ($this->store[$key]['ex'] === 0) return -1;
        return max(0, $this->store[$key]['ex'] - time());
    }
    public function flushdb()
    {
        $this->store = [];
        return 'OK';
    }
}

function assertEq($a, $b): bool
{
    if (is_object($a)) $a = json_decode(json_encode($a), true);
    if (is_object($b)) $b = json_decode(json_encode($b), true);
    if (is_array($a) || is_array($b)) return $a == $b;
    return $a === $b;
}

$cache = new CacheService();

// inject in-memory redis
$ref = new ReflectionClass($cache);
$pRedis = $ref->getProperty('redis');
$pRedis->setAccessible(true);
$pRedis->setValue($cache, new MockPredis());
$pEnabled = $ref->getProperty('enabled');
$pEnabled->setAccessible(true);
$pEnabled->setValue($cache, true);

$tests = [
    'string' => 'hello',
    'int' => 123,
    'float' => 1.23,
    'bool' => true,
    'array' => ['a' => 1, 'b' => 'x', 'nested' => ['z' => 2]],
    'object' => (object)['x' => 1, 'y' => 'two'],
    'null' => null,
];

$ok = 0;
$total = count($tests);
foreach ($tests as $k => $v) {
    $cache->set("test_{$k}", $v, 60);
    $got = $cache->get("test_{$k}", '__DEF__');
    $pass = assertEq($v, $got);
    echo "[{$k}] ";
    echo $pass ? "PASS\n" : "FAIL (got=" . var_export($got, true) . ")\n";
    $ok += $pass ? 1 : 0;
}

// test remember
$count = 0;
$res = $cache->remember('remember_test', 60, function () use (&$count) {
    $count++;
    return ['called' => $count];
});
$res2 = $cache->remember('remember_test', 60, function () use (&$count) {
    $count++;
    return ['called' => $count];
});
echo "[remember] first-called={$res['called']} second-called={$res2['called']}\n";
$rememberPass = ($res['called'] === 1 && $res2['called'] === 1);
echo $rememberPass ? "[remember] PASS\n" : "[remember] FAIL\n";

$ok += $rememberPass ? 1 : 0;
$total += 1;

echo "\nSummary: {$ok}/{$total} passed.\n";
exit($ok === $total ? 0 : 2);
