<?php

// PHPStan stubs for Illuminate Database Capsule static methods.
// Declare namespace at top-level and wrap class in `if (false)` so it's ignored at runtime.
namespace Illuminate\Database\Capsule;

if (false) {
    class Manager
    {
        public static function beginTransaction(): void {}
        public static function commit(): void {}
        public static function rollBack(): void {}
        public static function connection($name = null) {}
        public static function table(string $table) {}
        /**
         * @param callable|null $callback
         * @return mixed
         */
        public static function transaction($callback = null) {}
    }
}
