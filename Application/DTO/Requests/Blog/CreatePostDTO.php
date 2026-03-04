<?php

declare(strict_types=1);

namespace Application\DTO\Requests\Blog;

use Application\Lib\Helpers;
use Application\Models\BlogPost;

readonly class CreatePostDTO
{
    public function __construct(
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
    public static function fromRequest(array $data): self
    {
        $titulo   = trim($data['titulo'] ?? '');
        $conteudo = $data['conteudo'] ?? '';
        $status   = in_array($data['status'] ?? '', ['draft', 'published']) ? $data['status'] : 'draft';

        // Gerar slug a partir do título (se não informado)
        $slugInput = trim($data['slug'] ?? '');
        $slug = !empty($slugInput) ? Helpers::slugify($slugInput) : Helpers::slugify($titulo);

        // Incrementar slug se já existe
        $slug = self::ensureUniqueSlug($slug);

        // Calcular tempo de leitura (palavras / 200 = minutos)
        $plainText    = strip_tags($conteudo);
        $wordCount    = str_word_count($plainText);
        $tempoLeitura = max(1, (int) ceil($wordCount / 200));

        // Se publicando, definir published_at
        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = !empty($data['published_at'])
                ? $data['published_at']
                : date('Y-m-d H:i:s');
        }

        return new self(
            titulo:            $titulo,
            slug:              $slug,
            conteudo:          $conteudo,
            blog_categoria_id: !empty($data['blog_categoria_id']) ? (int) $data['blog_categoria_id'] : null,
            resumo:            !empty($data['resumo']) ? trim($data['resumo']) : null,
            imagem_capa:       !empty($data['imagem_capa']) ? trim($data['imagem_capa']) : null,
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
        $data = [
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

        return $data;
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
