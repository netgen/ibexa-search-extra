<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use LogicException;

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
        private readonly LocationHandler $locationHandler,
        private readonly array $configuration,
    ) {}

    /**
     * @throws \LogicException If configuration could not be resolved
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If Content's main Location is not found
     */
    public function resolveConfig(ContentInfo $contentInfo, string $languageCode): Config
    {
        $contentId = $contentInfo->id;

        if (isset($this->cache[$contentId][$languageCode])) {
            return $this->cache[$contentId][$languageCode];
        }

        $location = $this->locationHandler->load($contentInfo->mainLocationId);

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

            $config = $this->mapConfig($siteaccess, $siteConfiguration);

            $this->cache[$contentId][$languageCode] = $config;

            return $config;
        }

        throw new LogicException(
            sprintf(
                'Failed to match Content #%d to a siteaccess',
                $contentId,
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
