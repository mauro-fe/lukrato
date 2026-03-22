<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Repositories\BlogPostRepository;
use Application\Services\Admin\BlogAdminWorkflowService;

class BlogController extends BaseController
{
    private BlogAdminWorkflowService $workflowService;

    public function __construct(?BlogPostRepository $repo = null)
    {
        parent::__construct();
        $this->workflowService = new BlogAdminWorkflowService($repo ?? new BlogPostRepository());
    }

    public function index(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->listPosts([
            'search' => $this->getQuery('search'),
            'status' => $this->getQuery('status'),
            'blog_categoria_id' => $this->getQuery('blog_categoria_id'),
            'page' => $this->getIntQuery('page', 1),
            'per_page' => $this->getIntQuery('per_page', 15),
        ]));
    }

    public function show(mixed $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->showPost($id));
    }

    public function store(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->createPost($this->getRequestPayload())
        );
    }

    public function update(mixed $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->updatePost($id, $this->getRequestPayload())
        );
    }

    public function delete(mixed $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->deletePost($id, (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''))
        );
    }

    public function upload(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->uploadImage(
                is_array($_FILES['imagem'] ?? null) ? $_FILES['imagem'] : [],
                (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
                (string) BASE_URL
            )
        );
    }

    public function categorias(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->listCategories());
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondWorkflowResult(array $result): Response
    {
        if (!$result['success']) {
            return $this->workflowFailureResponse(
                $result,
                'Erro ao processar operacao do blog.',
                LogCategory::GENERAL,
                ['controller' => 'sysadmin_blog']
            );
        }

        return Response::successResponse(
            $result['data'] ?? null,
            $result['message'] ?? 'Success',
            $result['status'] ?? 200
        );
    }
}
