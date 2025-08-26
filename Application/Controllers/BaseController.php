<?php

namespace Application\Controllers;

// Importa as classes necessárias
use Application\Core\View;     // Classe para renderizar templates/views
use Application\Lib\Auth;      // Classe para gerenciar autenticação
use Application\Core\Response; // Classe para gerenciar respostas HTTP
use Application\Core\Request;  // Classe para gerenciar requisições HTTP

/**
 * CLASSE BASE CONTROLLER
 * 
 * Esta é a classe "pai" de todos os outros controladores do sistema.
 * Ela contém funcionalidades comuns que todos os controladores precisam,
 * como renderizar páginas, redirecionar, autenticar usuários, etc.
 * 
 * É uma classe "abstract" = não pode ser instanciada diretamente,
 * apenas herdada por outros controladores.
 */
abstract class BaseController
{
    // PROPRIEDADES PROTEGIDAS (acessíveis apenas por esta classe e suas filhas)

    protected View $view;                    // Objeto para renderizar templates
    protected Auth $auth;                    // Objeto para gerenciar autenticação
    protected ?int $adminId = null;          // ID do admin logado (null se não logado)
    protected ?string $adminUsername = null; // Username do admin logado (null se não logado)
    protected Request $request;              // Objeto para gerenciar dados da requisição
    protected Response $response;            // Objeto para gerenciar respostas HTTP

    /**
     * CONSTRUTOR - Executado sempre que um controlador é criado
     * Inicializa todas as dependências necessárias
     */
    public function __construct()
    {
        // NOTA: session_start() já é chamado em public/index.php
        // Por isso não precisa inicializar a sessão aqui

        $this->auth = new Auth();           // Cria objeto para gerenciar autenticação
        $this->request = new Request();     // Cria objeto para gerenciar requisições
        $this->response = new Response();   // Cria objeto para gerenciar respostas
    }

    /**
     * MÉTODO DE AUTENTICAÇÃO OBRIGATÓRIA
     * 
     * Este método força que o usuário esteja logado para acessar determinadas páginas.
     * Se não estiver logado, redireciona para a página de login.
     * 
     * NOTA: Este método está marcado como "redundante" porque idealmente
     * a autenticação deveria ser tratada por um Middleware no Router,
     * mas foi mantido para evitar quebras no código existente.
     */
    protected function requireAuth(): void
    {
        // Verifica se o usuário está logado usando a classe Auth
        if (!Auth::isLoggedIn()) {
            // Se não estiver logado, redireciona para login
            $this->redirect('admin/login');
        }

        // Se chegou até aqui, o usuário está logado
        // Popula as propriedades com os dados da sessão
        $this->adminId = $_SESSION['admin_id'] ?? null;
        $this->adminUsername = $_SESSION['admin_username'] ?? null;

        // VALIDAÇÃO DE SEGURANÇA: Verifica se os dados da sessão estão consistentes
        // Se o usuário está "logado" mas não tem ID ou username, há algo errado
        if (is_null($this->adminId) || is_null($this->adminUsername)) {
            $this->auth->logout();           // Força logout para limpar sessão inconsistente
            $this->redirect('admin/login');  // Redireciona para login
        }
    }

    /**
     * MÉTODO PARA VERIFICAR SE ESTÁ AUTENTICADO
     * 
     * Retorna true/false se o usuário está logado.
     * É mais simples que requireAuth() porque não força redirecionamento.
     */
    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn(); // Delega para a classe Auth
    }

    /**
     * MÉTODO PRINCIPAL PARA RENDERIZAR PÁGINAS
     * 
     * Este é o método mais importante para exibir conteúdo ao usuário.
     * Ele junta header + conteúdo + footer e exibe a página completa.
     * 
     * @param string $viewPath - Caminho da view (ex: 'admin/home/dashboard')
     * @param array $data - Dados para passar para a view (ex: ['nome' => 'João'])
     * @param string|null $header - Caminho do header (ex: 'admin/header')
     * @param string|null $footer - Caminho do footer (ex: 'admin/footer')
     */
    // Em Application/Controllers/BaseController.php

    protected function render(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): void
    {

        // Cria objeto View com o caminho e dados
        $view = new View($viewPath, $data);


        // Se foi especificado um header, adiciona
        if ($header) {
            $view->setHeader($header);
        }

        // Se foi especificado um footer, adiciona
        if ($footer) {
            $view->setFooter($footer);
        }


        echo $view->render();
    }
    /**
     * MÉTODO ESPECIALIZADO PARA RENDERIZAR PÁGINAS DO ADMIN
     * 
     * É um atalho para renderizar páginas da área administrativa
     * com header e footer padrão do admin.
     * 
     * @param string $viewPath - Caminho da view
     * @param array $data - Dados para a view
     */
    protected function renderAdmin(string $viewPath, array $data = []): void
    {
        $this->render($viewPath, $data, 'admin/home/header', 'admin/footer');
    }


    /**
     * MÉTODO PARA REDIRECIONAR USUÁRIO
     * 
     * Envia o usuário para outra página.
     * 
     * @param string $path - Caminho para onde redirecionar
     */
    protected function redirect(string $path): void
    {
        // Verifica se o path é uma URL completa ou relativa
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path                           // Se é URL completa, usa como está
            : BASE_URL . ltrim($path, '/');   // Se é relativa, adiciona BASE_URL

        // Usa a classe Response para fazer o redirecionamento
        $this->response->redirect($url)->send();
    }

    /**
     * MÉTODO PARA RETORNAR DADOS EM JSON
     * 
     * Usado para APIs ou requisições AJAX.
     * Retorna dados no formato JSON para JavaScript processar.
     * 
     * @param array $data - Dados para retornar
     * @param int $statusCode - Código de status HTTP (200, 404, 500, etc.)
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        $this->response->json($data, $statusCode)->send();
    }

    /**
     * MÉTODO PARA RETORNAR ERRO EM JSON
     * 
     * Usado quando algo dá errado e precisa informar o JavaScript.
     * 
     * @param string $message - Mensagem de erro
     * @param int $statusCode - Código de status HTTP
     * @param array $errors - Detalhes específicos dos erros
     */
    protected function jsonError(string $message, int $code = 400, array $errors = []): void
    {
        $this->response->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code)->send(); // ← ESSENCIAL
        exit;
    }

    /**
     * MÉTODO PARA RETORNAR SUCESSO EM JSON
     * 
     * Usado quando uma operação é bem-sucedida e precisa informar o JavaScript.
     * 
     * @param string $message - Mensagem de sucesso
     * @param array $data - Dados adicionais
     * @param int $status - Código de status HTTP
     */
    protected function jsonSuccess(string $message, array $data = []): void
    {
        $this->response->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ])->send(); // ← ESSENCIAL
        exit;
    }

    /**
     * MÉTODO PARA PEGAR DADOS DO POST
     * 
     * Obtém dados enviados via formulário POST (método HTTP POST).
     * Delega para a classe Request que já sanitiza os dados.
     * 
     * @param string $key - Nome do campo
     * @param mixed $default - Valor padrão se não encontrar
     */
    protected function getPost(string $key, $default = null)
    {
        // NOTA: Request->get() normalmente busca em GET e POST
        // Se precisar APENAS de POST, a classe Request precisaria de um método post() dedicado
        return $this->request->get($key, $default);
    }

    /**
     * MÉTODO PARA PEGAR DADOS DO GET
     * 
     * Obtém dados enviados via URL (parâmetros GET).
     * 
     * @param string $key - Nome do parâmetro
     * @param mixed $default - Valor padrão se não encontrar
     */
    protected function getQuery(string $key, $default = null)
    {
        // NOTA: Request->get() normalmente busca em GET e POST
        // Se precisar APENAS de GET, a classe Request precisaria de um método query() dedicado
        return $this->request->get($key, $default);
    }

    /**
     * MÉTODO PARA SANITIZAR DADOS
     * 
     * Remove caracteres perigosos que poderiam causar ataques XSS.
     * 
     * ATENÇÃO: A sanitização para XSS deve ser feita NA SAÍDA (na view),
     * não na entrada. Este método pode ser removido ou reavaliado.
     * 
     * @param mixed $value - Valor a ser sanitizado
     * @return string - Valor sanitizado
     */
    protected function sanitize($value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * MÉTODO PARA VALIDAR SE A REQUISIÇÃO É POST
     * 
     * Alguns métodos só devem ser acessados via POST (como envio de formulários).
     * Este método força que a requisição seja POST.
     * 
     * @throws \Exception Se não for POST
     */
    protected function requirePost(): void
    {
        if (!$this->request->isPost()) {
            $this->jsonError('Método não permitido', 405);
            exit; // Para a execução após enviar o erro
        }
    }

    // MÉTODOS PARA FLASH MESSAGES
    // Flash messages são mensagens que aparecem uma vez e depois desaparecem
    // Muito úteis para mostrar "Salvo com sucesso!" ou "Erro ao salvar!"

    /**
     * DEFINE MENSAGEM DE ERRO NA SESSÃO
     * 
     * Armazena uma mensagem de erro que será exibida na próxima página.
     * 
     * @param string $message - Mensagem de erro
     */
    protected function setError(string $message): void
    {
        $_SESSION['error'] = $message;
    }

    /**
     * DEFINE MENSAGEM DE SUCESSO NA SESSÃO
     * 
     * Armazena uma mensagem de sucesso que será exibida na próxima página.
     * 
     * @param string $message - Mensagem de sucesso
     */
    protected function setSuccess(string $message): void
    {
        $_SESSION['success'] = $message;
    }

    /**
     * PEGA E LIMPA MENSAGEM DE ERRO DA SESSÃO
     * 
     * Obtém a mensagem de erro armazenada e a remove da sessão
     * (para que não apareça novamente na próxima página).
     * 
     * @return string|null - Mensagem de erro ou null se não houver
     */
    protected function getError(): ?string
    {
        $error = $_SESSION['error'] ?? null;  // Pega a mensagem
        unset($_SESSION['error']);            // Remove da sessão
        return $error;                        // Retorna a mensagem
    }

    /**
     * PEGA E LIMPA MENSAGEM DE SUCESSO DA SESSÃO
     * 
     * Obtém a mensagem de sucesso armazenada e a remove da sessão
     * (para que não apareça novamente na próxima página).
     * 
     * @return string|null - Mensagem de sucesso ou null se não houver
     */
    protected function getSuccess(): ?string
    {
        $success = $_SESSION['success'] ?? null;  // Pega a mensagem
        unset($_SESSION['success']);              // Remove da sessão
        return $success;                          // Retorna a mensagem
    }
}
