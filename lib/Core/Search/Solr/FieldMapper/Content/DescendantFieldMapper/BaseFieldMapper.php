<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Content\DescendantFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Solr\FieldMapper\ContentFieldMapper;

abstract class BaseFieldMapper extends ContentFieldMapper
{
    public function __construct(
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly array $configuration,
    ) {}

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    final public function accept(Content $content): bool
    {
        return $this->internalAccept($content) && $this->doAccept($content);
    }

    abstract public function doAccept(Content $content): bool;

    abstract public function getIdentifier(): string;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function internalAccept(Content $content): bool
    {
        $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);

        $map = $this->configuration['map'] ?? [];
        $handlers = $this->configuration['handlers'] ?? [];

        return array_key_exists($contentType->identifier, $map)
            && in_array($this->getIdentifier(), $handlers, true);
    }
}
