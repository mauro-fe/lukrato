<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\Requests\Blog\CreatePostDTO;
use Application\DTO\Requests\Blog\UpdatePostDTO;
use Application\Lib\Auth;
use Application\Models\BlogCategoria;
use Application\Models\BlogPost;
use Application\Repositories\BlogPostRepository;
use Application\Validators\BlogPostValidator;

class BlogController extends BaseController
{
    private BlogPostRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new BlogPostRepository();
    }

    /**
     * Verifica se o usuário é admin.
     */
    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_admin == 1;
    }

    /**
     * Obtém o corpo da requisição JSON.
     */
    private function getRequestBody(): array
    {
        return $this->getJson() ?? [];
    }

    // ─── CRUD ───────────────────────────────────────────

    /**
     * Lista posts com filtros e paginação.
     */
    public function index(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $filters = [
                'search'            => $_GET['search'] ?? null,
                'status'            => $_GET['status'] ?? null,
                'blog_categoria_id' => $_GET['blog_categoria_id'] ?? null,
            ];

            $page    = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 15)));

            $result = $this->repo->paginateAdmin($filters, $perPage, $page);

            $items = $result['items']->map(function (BlogPost $post) {
                return [
                    'id'                => $post->id,
                    'titulo'            => $post->titulo,
                    'slug'              => $post->slug,
                    'resumo'            => $post->resumo,
                    'imagem_capa'       => $post->imagem_capa,
                    'imagem_capa_url'   => $post->imagem_capa_url,
                    'blog_categoria_id' => $post->blog_categoria_id,
                    'categoria_nome'    => $post->categoria?->nome,
                    'status'            => $post->status,
                    'tempo_leitura'     => $post->tempo_leitura,
                    'published_at'      => $post->published_at?->format('d/m/Y H:i'),
                    'created_at'        => $post->created_at?->format('d/m/Y H:i'),
                    'updated_at'        => $post->updated_at?->format('d/m/Y H:i'),
                    'url'               => $post->url,
                ];
            });

            $stats = $this->repo->countByStatus();

            Response::success([
                'items'   => $items,
                'total'   => $result['total'],
                'page'    => $result['page'],
                'perPage' => $result['perPage'],
                'stats'   => $stats,
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao listar posts do blog: " . $e->getMessage());
            Response::error('Erro ao listar posts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Retorna um post por ID (para edição).
     */
    public function show($id): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $post = BlogPost::with('categoria')->find((int) $id);

            if (!$post) {
                Response::notFound('Post não encontrado');
                return;
            }

            Response::success([
                'post' => [
                    'id'                => $post->id,
                    'titulo'            => $post->titulo,
                    'slug'              => $post->slug,
                    'resumo'            => $post->resumo,
                    'conteudo'          => $post->conteudo,
                    'imagem_capa'       => $post->imagem_capa,
                    'imagem_capa_url'   => $post->imagem_capa_url,
                    'blog_categoria_id' => $post->blog_categoria_id,
                    'categoria_nome'    => $post->categoria?->nome,
                    'meta_title'        => $post->meta_title,
                    'meta_description'  => $post->meta_description,
                    'tempo_leitura'     => $post->tempo_leitura,
                    'status'            => $post->status,
                    'published_at'      => $post->published_at?->format('Y-m-d\TH:i'),
                    'created_at'        => $post->created_at?->format('d/m/Y H:i'),
                    'updated_at'        => $post->updated_at?->format('d/m/Y H:i'),
                    'url'               => $post->url,
                ],
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao buscar post: " . $e->getMessage());
            Response::error('Erro ao buscar post: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cria um novo post.
     */
    public function store(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $data = $this->getRequestBody();

            // Validar
            $errors = BlogPostValidator::validateCreate($data);
            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            // Criar DTO (gera slug, calcula tempo de leitura, etc.)
            $dto = CreatePostDTO::fromRequest($data);

            // Persistir
            $post = $this->repo->create($dto->toArray());
            $post->load('categoria');

            Response::success([
                'message' => 'Artigo criado com sucesso!',
                'post'    => [
                    'id'             => $post->id,
                    'titulo'         => $post->titulo,
                    'slug'           => $post->slug,
                    'status'         => $post->status,
                    'categoria_nome' => $post->categoria?->nome,
                    'url'            => $post->url,
                    'created_at'     => $post->created_at?->format('d/m/Y H:i'),
                ],
            ], 'Artigo criado com sucesso!', 201);
        } catch (\Exception $e) {
            error_log("Erro ao criar post do blog: " . $e->getMessage());
            Response::error('Erro ao criar artigo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza um post existente.
     */
    public function update($id): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $id   = (int) $id;
            $post = BlogPost::find($id);

            if (!$post) {
                Response::notFound('Post não encontrado');
                return;
            }

            $data = $this->getRequestBody();

            // Validar
            $errors = BlogPostValidator::validateUpdate($data, $id);
            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            // Criar DTO (re-gera slug se necessário, recalcula tempo de leitura)
            $dto = UpdatePostDTO::fromRequest($data, $id);

            // Atualizar
            $post->update($dto->toArray());
            $post->load('categoria');

            Response::success([
                'message' => 'Artigo atualizado com sucesso!',
                'post'    => [
                    'id'             => $post->id,
                    'titulo'         => $post->titulo,
                    'slug'           => $post->slug,
                    'status'         => $post->status,
                    'categoria_nome' => $post->categoria?->nome,
                    'published_at'   => $post->published_at?->format('d/m/Y H:i'),
                    'url'            => $post->url,
                ],
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao atualizar post do blog: " . $e->getMessage());
            Response::error('Erro ao atualizar artigo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Exclui um post.
     */
    public function delete($id): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $id   = (int) $id;
            $post = BlogPost::find($id);

            if (!$post) {
                Response::notFound('Post não encontrado');
                return;
            }

            // Excluir imagem de capa se existir
            if ($post->imagem_capa) {
                $imagePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($post->imagem_capa, '/');
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $titulo = $post->titulo;
            $post->delete();

            Response::success([
                'message' => "Artigo \"{$titulo}\" excluído com sucesso!",
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao excluir post do blog: " . $e->getMessage());
            Response::error('Erro ao excluir artigo: ' . $e->getMessage(), 500);
        }
    }

    // ─── Upload ─────────────────────────────────────────

    /**
     * Upload de imagem de capa.
     */
    public function upload(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
                Response::error('Nenhuma imagem enviada ou erro no upload', 400);
                return;
            }

            $file = $_FILES['imagem'];

            // Validar tipo MIME
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            if (!in_array($mime, $allowedMimes)) {
                Response::error('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP.', 400);
                return;
            }

            // Validar tamanho (max 2MB)
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                Response::error('A imagem não pode ter mais de 2MB.', 400);
                return;
            }

            // Criar diretório se não existe
            $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/uploads/blog';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Gerar nome único
            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
            $filename = 'blog_' . uniqid() . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                Response::error('Erro ao salvar imagem', 500);
                return;
            }

            $relativePath = 'assets/uploads/blog/' . $filename;

            Response::success([
                'message' => 'Imagem enviada com sucesso!',
                'path'    => $relativePath,
                'url'     => rtrim(BASE_URL, '/') . '/' . $relativePath,
            ]);
        } catch (\Exception $e) {
            error_log("Erro no upload de imagem do blog: " . $e->getMessage());
            Response::error('Erro no upload: ' . $e->getMessage(), 500);
        }
    }

    // ─── Categorias ─────────────────────────────────────

    /**
     * Lista categorias do blog (para select no form).
     */
    public function categorias(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $categorias = BlogCategoria::ordenadas()->get()->map(function ($cat) {
                return [
                    'id'    => $cat->id,
                    'nome'  => $cat->nome,
                    'slug'  => $cat->slug,
                    'icone' => $cat->icone,
                    'ordem' => $cat->ordem,
                ];
            });

            Response::success(['categorias' => $categorias]);
        } catch (\Exception $e) {
            error_log("Erro ao listar categorias do blog: " . $e->getMessage());
            Response::error('Erro ao listar categorias: ' . $e->getMessage(), 500);
        }
    }
}
