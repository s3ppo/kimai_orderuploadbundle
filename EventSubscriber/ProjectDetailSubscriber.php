<?php

namespace KimaiPlugin\OrderUploadBundle\EventSubscriber;

use App\Event\ProjectDetailControllerEvent;
use KimaiPlugin\OrderUploadBundle\Controller\ProjectAttachmentsController;
use KimaiPlugin\OrderUploadBundle\Controller\ProjectOrderController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProjectDetailSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProjectDetailControllerEvent::class => ['onProjectDetail', 100],
        ];
    }

    public function onProjectDetail(ProjectDetailControllerEvent $event): void
    {
        $event->addController(ProjectOrderController::class . '::boxAction');
        $event->addController(ProjectAttachmentsController::class . '::boxAction');
    }
}
