<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Core\FieldType\BinaryBase\Value;
use Ibexa\Core\IO\IOServiceInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

use function in_array;
use function sprintf;

/**
 * Extract text from Ibexa file based field types.
 */
final class FileTextExtractor
{
    public function __construct(
        private readonly IOServiceInterface $binaryFileIoService,
        private readonly string $projectDir,
        private readonly string $apacheTikaDir,
        private readonly string $javaDir,
        private readonly array $allowedMimeTypes,
    ) {}

    public function extractFromPersistenceField(Field $field): string
    {
        return isset($field->value->externalData['id'])
            ? $this->extractByFileId($field->value->externalData['id'])
            : '';
    }

    public function extractFromValue(Value $value): string
    {
        return $value->id === null ? '' : $this->extractByFileId($value->id);
    }

    public function extractByFileId(string $fileId): string
    {
        $mimeType = $this->binaryFileIoService->getMimeType($fileId);

        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            return '';
        }

        $file = $this->binaryFileIoService->loadBinaryFile($fileId);

        $process = new Process(
            [
                $this->javaDir,
                '-jar',
                $this->apacheTikaDir,
                '--text',
                sprintf('public%s', $file->uri),
            ],
            $this->projectDir,
        );
        $process->run();
        $exitCode = $process->getExitCode();

        if ($exitCode !== 0) {
            throw new RuntimeException(
                sprintf(
                    'Could not extract text from file with ID "%s": %s',
                    $fileId,
                    $process->getExitCodeText(),
                ),
            );
        }

        return $process->getOutput();
    }
}
