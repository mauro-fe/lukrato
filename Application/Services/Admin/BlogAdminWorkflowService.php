<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Enums\LogCategory;
use Application\DTO\Requests\Blog\CreatePostDTO;
use Application\DTO\Requests\Blog\UpdatePostDTO;
use Application\Models\BlogCategoria;
use Application\Models\BlogPost;
use Application\Repositories\BlogPostRepository;
use Application\Services\Infrastructure\LogService;
use Application\Validators\BlogPostValidator;
use Throwable;

class BlogAdminWorkflowService
{
    public function __construct(
        private readonly BlogPostRepository $repo = new BlogPostRepository()
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listPosts(array $query): array
    {
        try {
            $filters = [
                'search' => $query['search'] ?? null,
                'status' => $query['status'] ?? null,
                'blog_categoria_id' => $query['blog_categoria_id'] ?? null,
            ];

            $page = max(1, (int) ($query['page'] ?? 1));
            $perPage = max(1, min(50, (int) ($query['per_page'] ?? 15)));

            $result = $this->repo->paginateAdmin($filters, $perPage, $page);
            $stats = $this->repo->countByStatus();

            return $this->success([
                'items' => $result['items']->map(fn (BlogPost $post): array => $this->formatListPost($post)),
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao listar posts.', [
                'action' => 'blog_admin_list_posts',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function showPost(mixed $id): array
    {
        try {
            $post = BlogPost::with('categoria')->find((int) $id);

            if (!$post) {
                return $this->failure('Post nao encontrado', 404);
            }

            return $this->success([
                'post' => [
                    'id' => $post->id,
                    'titulo' => $post->titulo,
                    'slug' => $post->slug,
                    'resumo' => $post->resumo,
                    'conteudo' => $post->conteudo,
                    'imagem_capa' => $post->imagem_capa,
                    'imagem_capa_url' => $post->imagem_capa_url,
                    'blog_categoria_id' => $post->blog_categoria_id,
                    'categoria_nome' => $post->categoria?->nome,
                    'meta_title' => $post->meta_title,
                    'meta_description' => $post->meta_description,
                    'tempo_leitura' => $post->tempo_leitura,
                    'status' => $post->status,
                    'published_at' => $post->published_at?->format('Y-m-d\\TH:i'),
                    'created_at' => $post->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $post->updated_at?->format('d/m/Y H:i'),
                    'url' => $post->url,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao buscar post.', [
                'action' => 'blog_admin_show_post',
                'post_id' => (int) $id,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createPost(array $data): array
    {
        try {
            $errors = BlogPostValidator::validateCreate($data);
            if (!empty($errors)) {
                return $this->failure('Validation failed', 422, $errors);
            }

            $dto = CreatePostDTO::fromRequest($data);
            $post = $this->repo->create($dto->toArray());
            $post->load('categoria');

            return $this->success([
                'message' => 'Artigo criado com sucesso!',
                'post' => [
                    'id' => $post->id,
                    'titulo' => $post->titulo,
                    'slug' => $post->slug,
                    'status' => $post->status,
                    'categoria_nome' => $post->categoria?->nome,
                    'url' => $post->url,
                    'created_at' => $post->created_at?->format('d/m/Y H:i'),
                ],
            ], 201, 'Artigo criado com sucesso!');
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao criar artigo.', [
                'action' => 'blog_admin_create_post',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updatePost(mixed $id, array $data): array
    {
        try {
            $id = (int) $id;
            $post = BlogPost::find($id);

            if (!$post) {
                return $this->failure('Post nao encontrado', 404);
            }

            $errors = BlogPostValidator::validateUpdate($data, $id);
            if (!empty($errors)) {
                return $this->failure('Validation failed', 422, $errors);
            }

            $dto = UpdatePostDTO::fromRequest($data, $id);
            $post->update($dto->toArray());
            $post->load('categoria');

            return $this->success([
                'message' => 'Artigo atualizado com sucesso!',
                'post' => [
                    'id' => $post->id,
                    'titulo' => $post->titulo,
                    'slug' => $post->slug,
                    'status' => $post->status,
                    'categoria_nome' => $post->categoria?->nome,
                    'published_at' => $post->published_at?->format('d/m/Y H:i'),
                    'url' => $post->url,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao atualizar artigo.', [
                'action' => 'blog_admin_update_post',
                'post_id' => (int) $id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function deletePost(mixed $id, string $documentRoot): array
    {
        try {
            $post = BlogPost::find((int) $id);

            if (!$post) {
                return $this->failure('Post nao encontrado', 404);
            }

            if ($post->imagem_capa) {
                $imagePath = rtrim($documentRoot, '/') . '/' . ltrim($post->imagem_capa, '/');
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $titulo = $post->titulo;
            $post->delete();

            return $this->success([
                'message' => "Artigo \"{$titulo}\" excluido com sucesso!",
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao excluir artigo.', [
                'action' => 'blog_admin_delete_post',
                'post_id' => (int) $id,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function uploadImage(array $file, string $documentRoot, string $baseUrl): array
    {
        try {
            if (empty($file) || (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
                return $this->failure('Nenhuma imagem enviada ou erro no upload', 400);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file((string) $file['tmp_name']);

            if (!in_array($mime, $allowedMimes, true)) {
                return $this->failure('Tipo de arquivo nao permitido. Use JPEG, PNG ou WebP.', 400);
            }

            $maxSize = 2 * 1024 * 1024;
            if (($file['size'] ?? 0) > $maxSize) {
                return $this->failure('A imagem nao pode ter mais de 2MB.', 400);
            }

            $uploadDir = rtrim($documentRoot, '/') . '/assets/uploads/blog';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $filename = 'blog_' . uniqid() . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file((string) $file['tmp_name'], $filepath)) {
                return $this->failure('Erro ao salvar imagem', 500);
            }

            $relativePath = 'assets/uploads/blog/' . $filename;

            return $this->success([
                'message' => 'Imagem enviada com sucesso!',
                'path' => $relativePath,
                'url' => rtrim($baseUrl, '/') . '/' . $relativePath,
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao enviar imagem do blog.', [
                'action' => 'blog_admin_upload_image',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function listCategories(): array
    {
        try {
            $categorias = BlogCategoria::ordenadas()->get()->map(static function ($cat): array {
                return [
                    'id' => $cat->id,
                    'nome' => $cat->nome,
                    'slug' => $cat->slug,
                    'icone' => $cat->icone,
                    'ordem' => $cat->ordem,
                ];
            });

            return $this->success(['categorias' => $categorias]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao listar categorias do blog.', [
                'action' => 'blog_admin_list_categories',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function success(array $data, int $status = 200, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $message, int $status, mixed $errors = null): array
    {
        $result = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function internalFailure(Throwable $e, string $publicMessage, array $context = []): array
    {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: $publicMessage,
            context: $context,
            category: LogCategory::GENERAL
        );

        return $this->failure($publicMessage, 500, [
            'error_id' => $errorId,
            'request_id' => $errorId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatListPost(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'titulo' => $post->titulo,
            'slug' => $post->slug,
            'resumo' => $post->resumo,
            'imagem_capa' => $post->imagem_capa,
            'imagem_capa_url' => $post->imagem_capa_url,
            'blog_categoria_id' => $post->blog_categoria_id,
            'categoria_nome' => $post->categoria?->nome,
            'status' => $post->status,
            'tempo_leitura' => $post->tempo_leitura,
            'published_at' => $post->published_at?->format('d/m/Y H:i'),
            'created_at' => $post->created_at?->format('d/m/Y H:i'),
            'updated_at' => $post->updated_at?->format('d/m/Y H:i'),
            'url' => $post->url,
        ];
    }
}
