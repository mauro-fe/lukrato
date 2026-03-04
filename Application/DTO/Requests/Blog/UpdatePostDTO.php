<?php

declare(strict_types=1);

namespace Application\DTO\Requests\Blog;

use Application\Lib\Helpers;
use Application\Models\BlogPost;

readonly class UpdatePostDTO
{
    public function __construct(
        public int     $id,
        public string  $titulo,
        public string  $slug,
        public string  $conteudo,
        public ?int    $blog_categoria_id = null,
        public ?string $resumo = null,
        public ?string $imagem_capa = null,
        public ?string $meta_title = null,
        public ?string $meta_description = null,
        public ?int    $tempo_leitura = null,
        public string  $status = 'draft',
        public ?string $published_at = null,
    ) {}

    /**
     * Cria DTO a partir dos dados da requisição.
     */
    public static function fromRequest(array $data, int $id): self
    {
        $titulo   = trim($data['titulo'] ?? '');
        $conteudo = $data['conteudo'] ?? '';
        $status   = in_array($data['status'] ?? '', ['draft', 'published']) ? $data['status'] : 'draft';

        // Gerar slug a partir do título (se não informado)
        $slugInput = trim($data['slug'] ?? '');
        $slug = !empty($slugInput) ? Helpers::slugify($slugInput) : Helpers::slugify($titulo);

        // Incrementar slug se já existe (excluindo o próprio post)
        $slug = self::ensureUniqueSlug($slug, $id);

        // Calcular tempo de leitura
        $plainText    = strip_tags($conteudo);
        $wordCount    = str_word_count($plainText);
        $tempoLeitura = max(1, (int) ceil($wordCount / 200));

        // Se publicando, definir published_at (só se ainda não tem)
        $publishedAt = $data['published_at'] ?? null;
        if ($status === 'published' && empty($publishedAt)) {
            // Verificar se o post já tem published_at
            $existingPost = BlogPost::find($id);
            $publishedAt = $existingPost && $existingPost->published_at
                ? $existingPost->published_at->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s');
        }

        return new self(
            id:                $id,
            titulo:            $titulo,
            slug:              $slug,
            conteudo:          $conteudo,
            blog_categoria_id: !empty($data['blog_categoria_id']) ? (int) $data['blog_categoria_id'] : null,
            resumo:            !empty($data['resumo']) ? trim($data['resumo']) : null,
            imagem_capa:       array_key_exists('imagem_capa', $data) ? ($data['imagem_capa'] ?: null) : null,
            meta_title:        !empty($data['meta_title']) ? trim($data['meta_title']) : null,
            meta_description:  !empty($data['meta_description']) ? trim($data['meta_description']) : null,
            tempo_leitura:     $tempoLeitura,
            status:            $status,
            published_at:      $publishedAt,
        );
    }

    /**
     * Converte DTO para array (para persistência).
     */
    public function toArray(): array
    {
        return [
            'titulo'            => $this->titulo,
            'slug'              => $this->slug,
            'conteudo'          => $this->conteudo,
            'blog_categoria_id' => $this->blog_categoria_id,
            'resumo'            => $this->resumo,
            'imagem_capa'       => $this->imagem_capa,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'tempo_leitura'     => $this->tempo_leitura,
            'status'            => $this->status,
            'published_at'      => $this->published_at,
        ];
    }

    /**
     * Garante slug único incrementando sufixo numérico.
     */
    private static function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $original = $slug;
        $counter  = 1;

        while (true) {
            $query = BlogPost::where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if (!$query->exists()) {
                break;
            }
            $counter++;
            $slug = "{$original}-{$counter}";
        }

        return $slug;
    }
}
