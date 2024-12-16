<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\DescendantFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Solr\FieldMapper\ContentTranslationFieldMapper;

abstract class BaseFieldMapper extends ContentTranslationFieldMapper
{
    public function __construct(
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly array $configuration,
    ) {}

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    final public function accept(Content $content, $languageCode): bool
    {
        return $this->internalAccept($content) && $this->doAccept($content, $languageCode);
    }

    abstract public function doAccept(Content $content, string $languageCode): bool;

    abstract public function getIdentifier(): string;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function internalAccept(Content $content): bool
    {
        $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);

        $map = $this->configuration['map'] ?? [];

        $handlers = $map[$contentType->identifier]['handlers'] ?? [];

        return array_key_exists($contentType->identifier, $map)
            && in_array($this->getIdentifier(), $handlers, true);
    }
}
