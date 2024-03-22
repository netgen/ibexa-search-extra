<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing;

use Netgen\IbexaSearchExtra\Core\Search\Solr\ParentChildReindexAncestorResolver;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Search\VersatileHandler;

final class AncestorIndexer
{
    public function __construct(
        private readonly VersatileHandler $searchHandler,
        private readonly ContentHandler $contentHandler,
        private readonly ParentChildReindexAncestorResolver $ancestorResolver,
    ) {}

    public function indexSingle(Location $location): void
    {
        $ancestor = $this->ancestorResolver->resolveAncestor($location);


        if ($ancestor === null) {
            return;
        }

        try {
            $content = $this->contentHandler->load($ancestor->contentId);
        } catch (NotFoundException) {
            return;
        }

        $this->searchHandler->indexContent($content);
        $this->searchHandler->indexLocation($ancestor);
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location $location
     */
    public function indexSingleForDeleteContent(Location $location): void
    {
        $ancestor = $this->ancestorResolver->resolveAncestorForDeleteContent($location);

        if ($ancestor === null) {
            return;
        }

        try {
            $content = $this->contentHandler->load($ancestor->contentId);
        } catch (NotFoundException) {
            return;
        }

        $this->searchHandler->indexContent($content);
        $this->searchHandler->indexLocation($ancestor);
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location[] $locations
     */
    public function indexMultiple(array $locations): void
    {
        foreach ($locations as $location) {
            $this->indexSingle($location);
        }
    }

    /***
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location[] $locations
     */
    public function indexMultipleForDeleteContent(array $locations): void
    {
        $this->indexMultiple($locations);

        foreach ($locations as $location) {
            $this->indexSingleForDeleteContent($location);
        }
    }
}
