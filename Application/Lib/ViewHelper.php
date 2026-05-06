<?php

namespace Application\Lib;

use Application\Lib\Auth;

class ViewHelper
{

    /**
     * @return array{admin_id:mixed,admin_username:mixed,nome_clinica:mixed,slug_clinica:mixed,ficha_id:int,base_url:string,current_year:string}
     */
    public static function getSharedData(): array
    {
        $admin_id = Auth::id();
        $admin_username = Auth::username();
        $admin = Auth::user();

        $nome_clinica = $admin->nome_clinica ?? 'Clínica';
        $slug_clinica = $admin->slug_clinica ?? Helpers::slugify($nome_clinica);


        $ficha_id = null;

        return [
            'admin_id' => $admin_id,
            'admin_username' => $admin_username,
            'nome_clinica' => $nome_clinica,
            'slug_clinica' => $slug_clinica,
            'ficha_id' => 0,
            'base_url' => BASE_URL,
            'current_year' => date('Y')
        ];
    }


    public static function adminUrl(string $path = '', ?string $username = null): string
    {
        $username = $username ?? (Auth::username() ?? 'admin');
        return BASE_URL . 'admin/' . $username . '/' . ltrim($path, '/');
    }

    public static function publicUrl(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }
}
