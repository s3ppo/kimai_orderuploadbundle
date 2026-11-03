<?php

namespace KimaiPlugin\OrderUploadBundle\EventSubscriber;

use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDefinitionEvent;
use KimaiPlugin\OrderUploadBundle\Form\OrderFileType;
use KimaiPlugin\OrderUploadBundle\Service\AttachmentStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderMetaSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProjectMetaDefinitionEvent::class => ['onMetaDefinition', 200],
        ];
    }

    public function onMetaDefinition(ProjectMetaDefinitionEvent $event): void
    {
        $definition = (new ProjectMeta())
            ->setName(OrderFileType::FIELD)
            ->setLabel('Bestellung')
            ->setType(OrderFileType::class)
            ->setIsVisible(false)
            ->setIsRequired(false)
            ->setOptions([
                'required' => false,
                'help' => $this->translator->trans('order_help', ['%max%' => (int) (AttachmentStorage::MAX_BYTES / 1048576)], 'order_upload'),
            ]);

        $event->getEntity()->setMetaField($definition);
    }
}
