<?php

declare(strict_types=1);

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

    public function render(): string
    {
        ob_start();
        $prevHandler = set_error_handler(fn($s, $m, $f, $l) => throw new \ErrorException($m, 0, $s, $f, $l));

        try {
            extract($this->data, EXTR_SKIP);

            if ($this->header && file_exists($this->header)) include $this->header;
            if (!file_exists($this->viewPath)) throw new \RuntimeException("View não encontrada: {$this->viewPath}");
            include $this->viewPath;
            if ($this->footer && file_exists($this->footer)) include $this->footer;

            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean(); // Limpa o buffer quebrado
            restore_error_handler(); // Restaura o handler antes de sair
            Router::handleException($e, null); // Reutiliza o handler de exceção do Router
            return '';
        } finally {
            restore_error_handler(); // Garante que o handler sempre será restaurado
        }
    }
}
