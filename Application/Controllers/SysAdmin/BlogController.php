<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Admin\BlogAdminWorkflowService;

class BlogController extends ApiController
{
    private BlogAdminWorkflowService $workflowService;

    public function __construct(?BlogAdminWorkflowService $workflowService = null)
    {
        parent::__construct();

        $this->workflowService = $this->resolveOrCreate($workflowService, BlogAdminWorkflowService::class);
    }

    public function index(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->listPosts([
                'search' => $this->getQuery('search'),
                'status' => $this->getQuery('status'),
                'blog_categoria_id' => $this->getQuery('blog_categoria_id'),
                'page' => $this->getIntQuery('page', 1),
                'per_page' => $this->getIntQuery('per_page', 15),
            ]),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }

    public function show(mixed $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->showPost($id),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }

    public function store(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->createPost($this->getRequestPayload()),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }

    public function update(mixed $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->updatePost($id, $this->getRequestPayload()),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }

    public function delete(mixed $id): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->deletePost($id, (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }

    public function upload(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->uploadImage(
                is_array($_FILES['imagem'] ?? null) ? $_FILES['imagem'] : [],
                (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
                (string) BASE_URL
            ),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }

    public function categorias(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->listCategories(),
            'Erro ao processar operacao do blog.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_blog'],
            true
        );
    }
}
