<?php

namespace KimaiPlugin\OrderUploadBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AttachmentStorage
{
    public const MAX_BYTES = 26214400;

    private const ID_PATTERN = '/^[a-f0-9]{16}__[^\/\\\\\x00]{1,200}$/';

    private readonly Filesystem $filesystem;

    public function __construct(#[Autowire('%kimai.data_dir%')] private readonly string $dataDir)
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @return array<int, array{id: string, name: string, size: string, bytes: int, modified: \DateTimeImmutable}>
     */
    public function list(int|string $scope): array
    {
        $directory = $this->getDirectory($scope);
        if (!is_dir($directory)) {
            return [];
        }

        $items = [];
        $entries = scandir($directory);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            $item = $this->info($scope, $entry);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        usort($items, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);

        return $items;
    }

    /**
     * @return array{id: string, name: string, size: string, bytes: int, modified: \DateTimeImmutable}|null
     */
    public function info(int|string $scope, string $id): ?array
    {
        $path = $this->resolve($scope, $id);
        if ($path === null) {
            return null;
        }

        $bytes = (int) filesize($path);

        return [
            'id' => $id,
            'name' => $this->displayName($id),
            'size' => $this->formatBytes($bytes),
            'bytes' => $bytes,
            'modified' => (new \DateTimeImmutable())->setTimestamp((int) filemtime($path)),
        ];
    }

    public function store(int|string $scope, UploadedFile $file): string
    {
        $directory = $this->getDirectory($scope);
        $this->filesystem->mkdir($directory, 0750);

        $id = bin2hex(random_bytes(8)) . '__' . $this->sanitizeName($file->getClientOriginalName());
        $file->move($directory, $id);

        return $id;
    }

    public function resolve(int|string $scope, string $id): ?string
    {
        if (!$this->isValidId($id)) {
            return null;
        }

        $directory = realpath($this->getDirectory($scope));
        if ($directory === false) {
            return null;
        }

        $path = realpath($directory . DIRECTORY_SEPARATOR . $id);
        if ($path === false || !is_file($path) || dirname($path) !== $directory) {
            return null;
        }

        return $path;
    }

    public function delete(int|string $scope, string $id): bool
    {
        $path = $this->resolve($scope, $id);
        if ($path === null) {
            return false;
        }

        $this->filesystem->remove($path);

        return true;
    }

    public function displayName(string $id): string
    {
        return substr($id, 18);
    }

    private function getDirectory(int|string $scope): string
    {
        return rtrim($this->dataDir, '/\\') . DIRECTORY_SEPARATOR . 'project_attachments' . DIRECTORY_SEPARATOR . $scope;
    }

    private function isValidId(string $id): bool
    {
        return preg_match(self::ID_PATTERN, $id) === 1;
    }

    private function sanitizeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N}._ \-()]/u', '_', $name) ?? '';
        $name = trim($name, " .\t\n\r");

        if ($name === '') {
            $name = 'datei';
        }

        if (mb_strlen($name) > 150) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $extension = mb_substr($extension, 0, 20);
            $base = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 120);
            $name = $extension !== '' ? $base . '.' . $extension : $base;
        }

        return $name;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
}
