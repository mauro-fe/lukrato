<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Core\Router;

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

        if (isset($this->viewPath)) {
            $relativeViewPath = str_replace(BASE_PATH . '/views/', '', $this->viewPath);
            $viewName = str_replace(['.php', '/', '\\'], ['', '-', '-'], $relativeViewPath);
            $GLOBALS['current_view'] = trim($viewName, '-');
        }

        if ($this->header && file_exists($this->header)) {
            include $this->header;
        }

        extract($this->data);
        $view = $this;

        if (!file_exists($this->viewPath)) {
            throw new \Exception("View file not found: " . $this->viewPath);
        }
        include $this->viewPath;

        if ($this->footer && file_exists($this->footer)) {
            include $this->footer;
        }

        return ob_get_clean();
    }

    public static function renderPage(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): string
    {
        $v = new self($viewPath, $data);
        if ($header) $v->setHeader($header);
        if ($footer) $v->setFooter($footer);
        return $v->render();
    }
}
