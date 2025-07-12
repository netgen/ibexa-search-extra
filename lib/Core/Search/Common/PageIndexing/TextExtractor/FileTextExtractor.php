<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Core\FieldType\BinaryBase\Value;
use Ibexa\Core\IO\IOServiceInterface;
use RuntimeException;
use Vaites\ApacheTika\Client as ApacheTikaClient;

use function in_array;
use function sprintf;

/**
 * Extract text from Ibexa file based field types.
 */
final class FileTextExtractor
{
    public function __construct(
        private readonly IOServiceInterface $binaryFileIoService,
        private readonly ApacheTikaClient $apacheTikaClient,
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

        try {
            return $this->apacheTikaClient->getText(sprintf('public%s', $file->uri));
        } catch (\Exception $e) {
            throw new RuntimeException(
                sprintf(
                    'Could not extract text from file with ID "%s": %s (%s)',
                    $fileId,
                    $e->getMessage(),
                    $e->getCode(),
                ),
            );
        }
    }
}
