<?php

namespace KimaiPlugin\OrderUploadBundle\Form;

use KimaiPlugin\OrderUploadBundle\Service\AttachmentStorage;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class OrderFileTransformer implements DataTransformerInterface
{
    private ?string $current = null;

    public function __construct(
        private readonly AttachmentStorage $storage,
        private readonly string $failedMessage,
        private readonly string $tooLargeMessage,
    ) {
    }

    public function remember(?string $current): void
    {
        $this->current = $current;
    }

    public function transform(mixed $value): mixed
    {
        return null;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (!$value instanceof UploadedFile) {
            return $this->current;
        }

        if (!$value->isValid()) {
            throw new TransformationFailedException('Invalid upload', 0, null, $this->failedMessage);
        }

        if ($value->getSize() > AttachmentStorage::MAX_BYTES) {
            throw new TransformationFailedException('Upload too large', 0, null, $this->tooLargeMessage);
        }

        try {
            return $this->storage->store(OrderFileType::SCOPE, $value);
        } catch (\Throwable $exception) {
            throw new TransformationFailedException('Storing failed', 0, $exception, $this->failedMessage);
        }
    }
}
