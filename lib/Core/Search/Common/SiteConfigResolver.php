<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Common;

use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use RuntimeException;

class SiteConfigResolver
{

    /**
     * @param array<string, mixed> $sitesConfig
     */
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly LocationHandler $locationHandler,
        private readonly array $sitesConfig
    ) {
    }

    public function getSiteConfigForContent(int $contentId): array
    {
        $contentInfo = $this->contentHandler->loadContentInfo($contentId);

        try {
            $location = $this->locationHandler->load($contentInfo->mainLocationId);
        } catch (NotFoundException) {
            throw new RuntimeException(
                sprintf(
                    'Content #%d does not have a location',
                    $contentInfo->id,
                ),
            );
        }

        $pathString = $location->pathString;
        $pathArray = explode('/', $pathString);

        foreach ($this->sitesConfig as $site => $siteConfig)  {
            if (in_array($siteConfig['tree_root_location_id'], $pathArray, false)) {
                $siteConfig['site']  = $site;
                return $siteConfig;
            }
        }

        throw new RuntimeException(
            sprintf(
                "Failed to match content ID %d to a siteaccess",
                $contentInfo->id
            )
        );

    }
}
