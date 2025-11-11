<?php

declare(strict_types=1);

namespace Application\Core;

class View
{
    private readonly string $viewPath; // Mantenha readonly
    private array $data; // <-- REMOVA readonly daqui
    private ?string $header = null;
    private ?string $footer = null;

    /**
     * Usa Constructor Property Promotion (PHP 8.0+).
     */
    public function __construct(string $viewPath, array $data = [])
    {
        $this->viewPath = BASE_PATH . '/views/' . trim($viewPath, '/') . '.php';
        $this->data = $data;
    }

    public function setHeader(string $headerPath): self
    {
        $this->header = BASE_PATH . '/views/' . trim($headerPath, '/') . '.php';
        return $this;
    }

    public function setFooter(string $footerPath): self
    {
        $this->footer = BASE_PATH . '/views/' . trim($footerPath, '/') . '.php';
        return $this;
    }

    /**
     * Renderiza a view (header, body, footer) e retorna como string.
     * @throws \Exception Se o arquivo da view principal não for encontrado.
     */
    public function render(): string
    {
        ob_start();
        
        try {
            // Define o identificador da view atual (para CSS/JS)
            $relativeViewPath = str_replace([BASE_PATH . '/views/', '.php'], '', $this->viewPath);
            $viewName = str_replace(['/', '\\'], '-', $relativeViewPath);
            $GLOBALS['current_view'] = trim($viewName, '-');

            // Disponibiliza as variáveis (ex: $pageTitle) para todos os includes
            extract($this->data, EXTR_SKIP);

            // Disponibiliza $view (esta instância) para os partials
            $view = $this;

            // Header
            if ($this->header && file_exists($this->header)) {
                include $this->header;
            }

            // View principal
            if (!file_exists($this->viewPath)) {
                throw new \Exception("View file not found: " . $this->viewPath);
            }
            include $this->viewPath;

            // Footer
            if ($this->footer && file_exists($this->footer)) {
                include $this->footer;
            }
            
            return (string)ob_get_clean();

        } catch (\Throwable $e) {
            // Garante que o buffer seja limpo em caso de erro no include
            ob_end_clean();
            // Re-lança a exceção para o Router tratar
            throw $e;
        }
    }

    /**
     * Helper estático para renderização rápida.
     */
    public static function renderPage(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): string
    {
        $v = new self($viewPath, $data);
        if ($header) $v->setHeader($header);
        if ($footer) $v->setFooter($footer);
        return $v->render();
    }
}