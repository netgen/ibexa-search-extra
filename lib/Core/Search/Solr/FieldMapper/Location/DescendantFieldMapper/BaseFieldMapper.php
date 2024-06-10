<?php

declare(strict_types=1);


namespace Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\Location\DescendantFieldMapper;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Solr\FieldMapper\LocationFieldMapper;

abstract class BaseFieldMapper extends LocationFieldMapper
{
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly array $configuration,
    ) {}

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    final public function accept(Location $location): bool
    {
        return $this->internalAccept($location) && $this->doAccept($location);
    }

    abstract public function doAccept(Location $location): bool;

    abstract public function getIdentifier(): string;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function internalAccept(Location $location): bool
    {
        $contentInfo = $this->contentHandler->loadContentInfo($location->contentId);
        $contentType = $this->contentTypeHandler->load($contentInfo->contentTypeId);

        $map = $this->configuration['map'] ?? [];
        $handlers = $this->configuration['handlers'] ?? [];

        return array_key_exists($contentType->identifier, $map)
            && in_array($this->getIdentifier(), $handlers, true);
    }
}
