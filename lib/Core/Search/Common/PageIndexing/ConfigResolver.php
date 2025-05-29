<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing;

use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use LogicException;
use RuntimeException;

use function explode;
use function in_array;
use function sprintf;

class ConfigResolver
{
    /**
     * @var array<int, array<string, Config>>
     */
    private array $cache = [];

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly LocationHandler $locationHandler,
        private readonly array $configuration,
    ) {}

    public function getSiteConfigForContent(int $contentId, string $languageCode): Config
    {
        if (isset($this->cache[$contentId][$languageCode])) {
            return $this->cache[$contentId][$languageCode];
        }

        $contentInfo = $this->contentHandler->loadContentInfo($contentId);
        $content = $this->contentHandler->load($contentId, $contentInfo->currentVersionNo, [$languageCode]);

        try {
            $location = $this->locationHandler->load($contentInfo->mainLocationId);
        } catch (NotFoundException) {
            throw new RuntimeException(
                sprintf(
                    'Content #%d does not have a Location',
                    $contentInfo->id,
                ),
            );
        }

        $pathString = $location->pathString;
        $pathArray = array_map('intval', explode('/', $pathString));

        foreach ($this->configuration as $siteConfiguration) {
            if (!in_array($siteConfiguration['tree_root_location_id'], $pathArray, true)) {
                continue;
            }

            $languageSiteaccessMap = $siteConfiguration['language_siteaccess_map'] ?? [];
            $siteaccess = $this->resolveSiteaccessForLanguage($languageCode, $languageSiteaccessMap);

            if ($siteaccess === null) {
                continue;
            }

            $configObject = $this->mapConfig($siteaccess, $siteConfiguration);

            $this->cache[$contentId][$languageCode] = $configObject;

            return $configObject;
        }

        throw new LogicException(
            sprintf(
                'Failed to match Content #%d to a siteaccess',
                $contentInfo->id,
            ),
        );
    }

    private function resolveSiteaccessForLanguage(string $languageCode, array $languageSiteaccessMap): ?string
    {
        foreach ($languageSiteaccessMap as $mappedLanguageCode => $siteaccess) {
            if ($languageCode === $mappedLanguageCode) {
                return $siteaccess;
            }
        }

        return null;
    }

    private function mapConfig(string $siteaccess, array $siteConfiguration): Config
    {
        return new Config(
            $siteaccess,
            $siteConfiguration['allowed_content_types'],
            $siteConfiguration['fields'],
            $siteConfiguration['host'],
        );
    }
}
