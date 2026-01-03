<?php

// PHPStan stubs for Illuminate Database Capsule static methods.
// Wrapped in `if (false)` so code is ignored at runtime but parsed by static analyzers.+
namespace Illuminate\Database\Capsule;

if (false) {

    class Manager
    {
        public static function beginTransaction(): void {}
        public static function commit(): void {}
        public static function rollBack(): void {}
        /**
         * @param callable|null $callback
         * @return mixed
         */
        public static function transaction($callback = null) {}
    }
}
