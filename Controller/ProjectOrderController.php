<?php

namespace KimaiPlugin\OrderUploadBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Project\ProjectService;
use App\Repository\ProjectRepository;
use KimaiPlugin\OrderUploadBundle\Form\OrderFileType;
use KimaiPlugin\OrderUploadBundle\Service\AttachmentStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/admin/project')]
final class ProjectOrderController extends AbstractController
{
    private const DOMAIN = 'order_upload';

    public function __construct(
        private readonly AttachmentStorage $storage,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function boxAction(Request $request, ProjectRepository $repository, ProjectService $projectService): Response
    {
        $project = $request->attributes->get('project');

        if (!$project instanceof Project) {
            $project = is_scalar($project) ? $repository->find((int) $project) : null;
        }

        if (!$project instanceof Project || !$this->isGranted('view', $project)) {
            return new Response();
        }

        $projectService->loadMetaFields($project);

        $id = $this->orderId($project);
        $info = $id === null ? null : $this->storage->info(OrderFileType::SCOPE, $id);
        if ($info === null) {
            return new Response();
        }

        return $this->render('@OrderUpload/order_box.html.twig', [
            'project' => $project,
            'order' => $info,
            'can_edit' => $this->isGranted('edit', $project),
        ]);
    }

    #[Route(path: '/{id}/bestellung', name: 'project_order_download', methods: ['GET'])]
    #[IsGranted('view', 'project')]
    public function downloadAction(Project $project, ProjectService $projectService): Response
    {
        $projectService->loadMetaFields($project);

        $id = $this->orderId($project);
        $path = $id === null ? null : $this->storage->resolve(OrderFileType::SCOPE, $id);
        if ($id === null || $path === null) {
            throw $this->createNotFoundException();
        }

        $name = $this->storage->displayName($id);
        $fallback = preg_replace('/[^\x20-\x7e]|%|\/|\\\\/', '_', $name) ?? 'download';

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name, $fallback);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route(path: '/{id}/bestellung/delete', name: 'project_order_delete', methods: ['POST'])]
    #[IsGranted('edit', 'project')]
    public function deleteAction(Project $project, Request $request, ProjectService $projectService): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('project_order_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash_csrf', [], self::DOMAIN));

            return $this->backToProject($project);
        }

        $projectService->loadMetaFields($project);

        $id = $this->orderId($project);
        $meta = $project->getMetaField(OrderFileType::FIELD);
        if ($id === null || $meta === null) {
            $this->addFlash('error', $this->translator->trans('flash_delete_failed', [], self::DOMAIN));

            return $this->backToProject($project);
        }

        $meta->setValue(null);

        try {
            $projectService->saveProject($project);
        } catch (\Throwable) {
            $this->addFlash('error', $this->translator->trans('flash_delete_failed', [], self::DOMAIN));

            return $this->backToProject($project);
        }

        $this->storage->delete(OrderFileType::SCOPE, $id);
        $this->addFlash('success', $this->translator->trans('flash_deleted', [], self::DOMAIN));

        return $this->backToProject($project);
    }

    private function orderId(Project $project): ?string
    {
        $value = $project->getMetaField(OrderFileType::FIELD)?->getValue();

        return \is_string($value) && $value !== '' ? $value : null;
    }

    private function backToProject(Project $project): RedirectResponse
    {
        return $this->redirectToRoute('project_details', ['id' => $project->getId()]);
    }
}
