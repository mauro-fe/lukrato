<?php

namespace Application\Core; // <--- ADICIONADO O NAMESPACE AQUI

namespace Application\Core;

class View
{
    private string $viewPath;
    private array $data = [];
    private ?string $header = null;
    private ?string $footer = null;

    public function __construct(string $viewPath, array $data = [])
    {
        $this->viewPath = BASE_PATH . '/views/' . trim($viewPath, '/') . '.php';
        $this->data = $data;
    }

    public function setHeader(string $header): self
    {
        $this->header = BASE_PATH . '/views/' . trim($header, '/') . '.php';
        return $this;
    }

    public function setFooter(string $footer): self
    {
        $this->footer = BASE_PATH . '/views/' . trim($footer, '/') . '.php';
        return $this;
    }

    /** Mantém render() como MÉTODO DE INSTÂNCIA (sem parâmetros) */
    public function render(): string
    {
        ob_start();

        // nome da view atual (útil em header/footer)
        if (isset($this->viewPath)) {
            $relative = str_replace(rtrim(BASE_PATH, '/\\') . '/views/', '', $this->viewPath);
            $viewName = str_replace(['.php', '/', '\\'], ['', '-', '-'], $relative);
            $GLOBALS['current_view'] = trim($viewName, '-');
        }

        // Header
        if ($this->header) {
            if (file_exists($this->header)) {
                include $this->header;
            } else if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                echo "<pre style='color:#f66'>Header não encontrado: {$this->header}</pre>";
            }
        }

        // Torna os dados acessíveis na view (sem sobrescrever variáveis internas)
        extract($this->data, EXTR_SKIP);
        $view = $this; // se a view referenciar $view

        // Modo estrito: transforma warnings/notices da view em exceções
        $prevHandler = set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            if (!file_exists($this->viewPath)) {
                throw new \RuntimeException("View não encontrada: {$this->viewPath}");
            }
            include $this->viewPath;
        } finally {
            // restaura handler anterior
            if ($prevHandler !== null) {
                set_error_handler($prevHandler);
            } else {
                restore_error_handler();
            }
        }

        // Footer
        if ($this->footer) {
            if (file_exists($this->footer)) {
                include $this->footer;
            } else if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                echo "<pre style='color:#f66'>Footer não encontrado: {$this->footer}</pre>";
            }
        }

        return ob_get_clean();
    }


    /** Helper estático opcional para quem quiser chamar de uma vez */
    public static function renderPage(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): string
    {
        $v = new self($viewPath, $data);
        if ($header) $v->setHeader($header);
        if ($footer) $v->setFooter($footer);
        return $v->render();
    }
}
