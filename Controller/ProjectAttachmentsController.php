<?php

namespace KimaiPlugin\OrderUploadBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use KimaiPlugin\OrderUploadBundle\Service\AttachmentStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/admin/project')]
final class ProjectAttachmentsController extends AbstractController
{
    private const DOMAIN = 'order_upload';

    public function __construct(
        private readonly AttachmentStorage $storage,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function boxAction(Request $request, ProjectRepository $repository): Response
    {
        $project = $request->attributes->get('project');

        if (!$project instanceof Project) {
            $project = is_scalar($project) ? $repository->find((int) $project) : null;
        }

        if (!$project instanceof Project || !$this->isGranted('view', $project)) {
            return new Response();
        }

        return $this->render('@OrderUpload/box.html.twig', [
            'project' => $project,
            'attachments' => $this->storage->list((int) $project->getId()),
            'can_edit' => $this->isGranted('edit', $project),
            'max_mb' => (int) (AttachmentStorage::MAX_BYTES / 1048576),
        ]);
    }

    #[Route(path: '/{id}/attachments/upload', name: 'project_attachments_upload', methods: ['POST'])]
    #[IsGranted('edit', 'project')]
    public function uploadAction(Project $project, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid($this->tokenId($project), (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash_csrf', [], self::DOMAIN));

            return $this->backToProject($project);
        }

        $submitted = $request->files->get('attachments');
        if ($submitted instanceof UploadedFile) {
            $submitted = [$submitted];
        }
        if (!\is_array($submitted)) {
            $submitted = [];
        }

        $stored = 0;
        $failed = 0;

        foreach ($submitted as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (!$file->isValid() || $file->getSize() > AttachmentStorage::MAX_BYTES) {
                $failed++;
                continue;
            }

            try {
                $this->storage->store((int) $project->getId(), $file);
                $stored++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        if ($stored === 0 && $failed === 0) {
            $this->addFlash('warning', $this->translator->trans('flash_none', [], self::DOMAIN));
        }

        if ($stored > 0) {
            $this->addFlash('success', $this->translator->trans('flash_uploaded', ['%count%' => $stored], self::DOMAIN));
        }

        if ($failed > 0) {
            $this->addFlash('error', $this->translator->trans('flash_failed', ['%count%' => $failed], self::DOMAIN));
        }

        return $this->backToProject($project);
    }

    #[Route(path: '/{id}/attachments/{file}', name: 'project_attachments_download', requirements: ['file' => '[^/]+'], methods: ['GET'])]
    #[IsGranted('view', 'project')]
    public function downloadAction(Project $project, string $file): Response
    {
        $path = $this->storage->resolve((int) $project->getId(), $file);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        $name = $this->storage->displayName($file);
        $fallback = preg_replace('/[^\x20-\x7e]|%|\/|\\\\/', '_', $name) ?? 'download';

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name, $fallback);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route(path: '/{id}/attachments/{file}/delete', name: 'project_attachments_delete', requirements: ['file' => '[^/]+'], methods: ['POST'])]
    #[IsGranted('edit', 'project')]
    public function deleteAction(Project $project, string $file, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid($this->tokenId($project), (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash_csrf', [], self::DOMAIN));

            return $this->backToProject($project);
        }

        if ($this->storage->delete((int) $project->getId(), $file)) {
            $this->addFlash('success', $this->translator->trans('flash_deleted', [], self::DOMAIN));
        } else {
            $this->addFlash('error', $this->translator->trans('flash_delete_failed', [], self::DOMAIN));
        }

        return $this->backToProject($project);
    }

    private function tokenId(Project $project): string
    {
        return 'project_attachments_' . $project->getId();
    }

    private function backToProject(Project $project): RedirectResponse
    {
        return $this->redirectToRoute('project_details', ['id' => $project->getId()]);
    }
}
