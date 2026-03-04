<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Models\BlogCategoria;
use Application\Models\BlogPost;

/**
 * Validador para posts do blog.
 */
class BlogPostValidator
{
    /**
     * Valida dados para criação de post.
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Título
        $titulo = trim($data['titulo'] ?? '');
        if (empty($titulo)) {
            $errors['titulo'] = 'O título é obrigatório.';
        } elseif (mb_strlen($titulo) < 3) {
            $errors['titulo'] = 'O título deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($titulo) > 255) {
            $errors['titulo'] = 'O título não pode ter mais de 255 caracteres.';
        }

        // Conteúdo
        $conteudo = $data['conteudo'] ?? '';
        if (empty(trim(strip_tags($conteudo)))) {
            $errors['conteudo'] = 'O conteúdo é obrigatório.';
        }

        // Categoria (opcional mas deve ser válida se informada)
        if (!empty($data['blog_categoria_id'])) {
            $catId = (int) $data['blog_categoria_id'];
            if (!BlogCategoria::where('id', $catId)->exists()) {
                $errors['blog_categoria_id'] = 'Categoria inválida.';
            }
        }

        // Status
        $status = $data['status'] ?? 'draft';
        if (!in_array($status, ['draft', 'published'])) {
            $errors['status'] = 'Status inválido. Use "draft" ou "published".';
        }

        // Resumo (opcional)
        if (!empty($data['resumo']) && mb_strlen($data['resumo']) > 500) {
            $errors['resumo'] = 'O resumo não pode ter mais de 500 caracteres.';
        }

        // Meta Title (opcional)
        if (!empty($data['meta_title']) && mb_strlen($data['meta_title']) > 255) {
            $errors['meta_title'] = 'O meta title não pode ter mais de 255 caracteres.';
        }

        // Meta Description (opcional)
        if (!empty($data['meta_description']) && mb_strlen($data['meta_description']) > 500) {
            $errors['meta_description'] = 'A meta description não pode ter mais de 500 caracteres.';
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de post.
     */
    public static function validateUpdate(array $data, int $id): array
    {
        // Reutiliza validação de criação (mesmas regras)
        $errors = self::validateCreate($data);

        // Slug: se informado, verificar unicidade excluindo o próprio
        if (!empty($data['slug'])) {
            $exists = BlogPost::where('slug', $data['slug'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                $errors['slug'] = 'Este slug já está em uso por outro artigo.';
            }
        }

        return $errors;
    }
}
