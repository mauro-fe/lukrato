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
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Título
        $titulo = trim((string) ($data['titulo'] ?? ''));
        if (empty($titulo)) {
            $errors['titulo'] = 'O título é obrigatório.';
        } elseif (mb_strlen($titulo) < 3) {
            $errors['titulo'] = 'O título deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($titulo) > 255) {
            $errors['titulo'] = 'O título não pode ter mais de 255 caracteres.';
        }

        // Conteúdo
        $conteudo = (string) ($data['conteudo'] ?? '');
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
        $status = (string) ($data['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published'], true)) {
            $errors['status'] = 'Status inválido. Use "draft" ou "published".';
        }

        // Resumo (opcional)
        $resumo = (string) ($data['resumo'] ?? '');
        if ($resumo !== '' && mb_strlen($resumo) > 500) {
            $errors['resumo'] = 'O resumo não pode ter mais de 500 caracteres.';
        }

        // Meta Title (opcional)
        $metaTitle = (string) ($data['meta_title'] ?? '');
        if ($metaTitle !== '' && mb_strlen($metaTitle) > 255) {
            $errors['meta_title'] = 'O meta title não pode ter mais de 255 caracteres.';
        }

        // Meta Description (opcional)
        $metaDescription = (string) ($data['meta_description'] ?? '');
        if ($metaDescription !== '' && mb_strlen($metaDescription) > 500) {
            $errors['meta_description'] = 'A meta description não pode ter mais de 500 caracteres.';
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de post.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateUpdate(array $data, int $id): array
    {
        // Reutiliza validação de criação (mesmas regras)
        $errors = self::validateCreate($data);

        // Slug: se informado, verificar unicidade excluindo o próprio
        if (!empty($data['slug'])) {
            $exists = BlogPost::where('slug', (string) $data['slug'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                $errors['slug'] = 'Este slug já está em uso por outro artigo.';
            }
        }

        return $errors;
    }
}
