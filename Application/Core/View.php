<?php

namespace Application\Core; // <--- ADICIONADO O NAMESPACE AQUI

class View
{
    private $viewPath;
    private $data = [];
    private $header;
    private $footer;

    public function __construct($viewPath, $data = [])
    {
        // Usa BASE_PATH (definido em public/index.php) para construir o caminho absoluto
        $this->viewPath = BASE_PATH . '/views/' . $viewPath . '.php';
        $this->data = $data;
    }

    public function setHeader($headerPath)
    {

        $this->header = BASE_PATH . '/views/' . $headerPath . '.php';
        return $this; // Adicionado para permitir encadeamento, se desejar
    }

    public function setFooter($footerPath)
    {
        $this->footer = BASE_PATH . '/views/' . $footerPath . '.php';
        return $this; // Adicionado para permitir encadeamento, se desejar
    }

    public function render()
    {
        ob_start();

        // ✅ Salva o nome da view corretamente baseado no caminho
        if (isset($this->viewPath)) {
            $relativeViewPath = str_replace(BASE_PATH . '/views/', '', $this->viewPath);
            $viewName = str_replace(['.php', '/', '\\'], ['', '-', '-'], $relativeViewPath);
            $GLOBALS['current_view'] = trim($viewName, '-'); // Exemplo: admin-home-dashboard
        }

        if ($this->header && file_exists($this->header)) {
            include $this->header;
        }

        extract($this->data);
        $view = $this;

        if (file_exists($this->viewPath)) {
            include $this->viewPath;
        } else {
            throw new \Exception("View file not found: " . $this->viewPath);
        }

        if ($this->footer && file_exists($this->footer)) {
            include $this->footer;
        }

        return ob_get_clean();
    }


    // Método mágico para acessar dados como propriedades ($view->titulo)
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        // Opcional: lançar erro ou retornar null se a propriedade não existir
        // trigger_error("Undefined property: " . $name . " in view data.", E_USER_NOTICE);
        return null;
    }

    // Opcional: Adicionar um método para acessar dados mais complexos
    public function getData($key = null)
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }
}
