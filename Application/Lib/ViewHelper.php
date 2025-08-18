<?php

namespace Application\Lib; // <--- CORRIGIDO O NAMESPACE


use Application\Lib\Auth; // <--- ADICIONADO para acessar dados de sessão de forma centralizada

class ViewHelper
{
    /**
     * Coleta dados comuns compartilhados entre as views, especialmente para a área do admin.
     *
     * @return array Um array associativo com os dados compartilhados.
     */
    public static function getSharedData(): array
    {
        // Acessa os dados do admin logado via a classe Auth (fonte única de verdade)
        $admin_id = Auth::id(); // Obtém o ID do admin
        $admin_username = Auth::username(); // Obtém o username do admin
        $admin = Auth::user(); // Obtém o objeto Admin completo (já cacheado na sessão)

        $nome_clinica = $admin->nome_clinica ?? 'Clínica'; // Acessa diretamente do objeto Admin
        // Usa o accessor slug_clinica do Admin, ou Helpers::slugify como fallback
        $slug_clinica = $admin->slug_clinica ?? Helpers::slugify($nome_clinica);


        // Busca o ID da última ficha criada pelo admin
        $ficha_id = null;
        if ($admin) { // Se o admin estiver logado e o objeto estiver disponível
            // Busca o ID da ficha mais recente associada a este admin
            $ficha_id = $admin->fichas() // Acessa o relacionamento fichas() do modelo Admin
                ->orderBy('created_at', 'desc')
                ->value('id'); // Pega apenas o ID
        }

        return [
            'admin_id' => $admin_id,
            'admin_username' => $admin_username,
            'nome_clinica' => $nome_clinica,
            'slug_clinica' => $slug_clinica,
            'ficha_id' => $ficha_id ?? 0, // Garante que seja 0 se não houver ficha
            'base_url' => BASE_URL, // Constante definida em config.php
            'current_year' => date('Y')
        ];
    }

    /**
     * Gera uma URL para a área administrativa.
     *
     * @param string $path O caminho da rota (ex: 'perguntas').
     * @param string|null $username O username do admin (opcional, pega da sessão se null).
     * @return string A URL completa para a área admin.
     */
    public static function adminUrl(string $path = '', ?string $username = null): string
    {
        // Obtém o username do admin logado da sessão via Auth, com fallback 'admin'
        $username = $username ?? (Auth::username() ?? 'admin');
        return BASE_URL . 'admin/' . $username . '/' . ltrim($path, '/');
    }

    /**
     * Gera uma URL pública.
     *
     * @param string $path O caminho da rota (ex: 'contato').
     * @return string A URL pública completa.
     */
    public static function publicUrl(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }

    // public static function url(string $path = ''): string
    // {
    //     return BASE_URL . ltrim($path, '/');
    // }
}