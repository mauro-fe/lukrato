<?php

declare(strict_types=1);

namespace Application\Core;

class View
{
    private readonly string $viewPath;
    private array $data;
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

    /**
     * Renderiza a view (header, body, footer) e retorna como string.
     * @throws \Exception Se o arquivo da view principal não for encontrado.
     */
    public function render(): string
    {
        ob_start();

        try {
            $relativeViewPath = str_replace([BASE_PATH . '/views/', '.php'], '', $this->viewPath);
            $fallbackViewPath = trim(str_replace('\\', '/', $relativeViewPath), '/');
            $fallbackViewId = trim(str_replace(['/', '\\'], '-', $relativeViewPath), '-');

            if (!isset($this->data['currentViewPath']) || !is_string($this->data['currentViewPath']) || trim($this->data['currentViewPath']) === '') {
                $this->data['currentViewPath'] = $fallbackViewPath;
            }

            if (!isset($this->data['currentViewId']) || !is_string($this->data['currentViewId']) || trim($this->data['currentViewId']) === '') {
                $this->data['currentViewId'] = $fallbackViewId;
            }

            // Temporary compatibility fallback for legacy helpers.
            $GLOBALS['current_view'] = trim((string) $this->data['currentViewId'], '-');
            $GLOBALS['current_view_path'] = trim((string) $this->data['currentViewPath'], '/');

            // Disponibiliza as variaveis (ex: $pageTitle) para todos os includes.
            extract($this->data, EXTR_SKIP);

            $view = $this;

            if ($this->header && file_exists($this->header)) {
                include $this->header;
            }

            if (!file_exists($this->viewPath)) {
                throw new \Exception('View file not found: ' . $this->viewPath);
            }

            include $this->viewPath;

            if ($this->footer && file_exists($this->footer)) {
                include $this->footer;
            }

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Helper estatico para renderizacao rapida.
     */
    public static function renderPage(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): string
    {
        $v = new self($viewPath, $data);
        if ($header) {
            $v->setHeader($header);
        }
        if ($footer) {
            $v->setFooter($footer);
        }
        return $v->render();
    }
}
