<?php

namespace KimaiPlugin\OrderUploadBundle\Form;

use KimaiPlugin\OrderUploadBundle\Service\AttachmentStorage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderFileType extends AbstractType
{
    public const FIELD = 'bestellung';
    public const SCOPE = 'bestellung_dokumente';

    public function __construct(
        private readonly AttachmentStorage $storage,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new OrderFileTransformer(
            $this->storage,
            $this->translator->trans('order_upload_failed', [], 'order_upload'),
            $this->translator->trans('order_too_large', ['%max%' => (int) (AttachmentStorage::MAX_BYTES / 1048576)], 'order_upload'),
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($transformer): void {
            $data = $event->getData();
            $transformer->remember(\is_string($data) && $data !== '' ? $data : null);
        });

        $builder->addModelTransformer($transformer);
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
