<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr;

use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

use function array_merge;
use function array_shift;
use function count;
use function end;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function mb_strlen;
use function str_starts_with;

final class ParentChildReindexAncestorResolver
{
    /**
     * @var string[]|null
     */
    private ?array $paths = null;

    /**
     * @var array<int, string>
     */
    private array $contentIdContentTypeIdentifierCache = [];

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly LocationHandler $locationHandler,
        private readonly array $configuration,
    ) {}

    public function resolveAncestor(Location $location): ?Location
    {
        $ancestry = [$location];

        do {
            $match = $this->matchPath($ancestry);

            if ($match === 0) {
                return end($ancestry);
            }
        } while (is_int($match) && $this->addToAncestry($ancestry));

        return null;
    }

    /**
     * Return the location if its content type matches the path parent.
     */
    public function resolveAncestorForParentLocation(Location $location): ?Location
    {
        try {
            $contentTypeIdentifier = $this->getContentTypeIdentifier($location);
        } catch (NotFoundException) {
            return null;
        }

        foreach ($this->getPaths() as $path) {
            if (str_ends_with($path, $contentTypeIdentifier)) {
                return $location;
            }
        }

        return null;
    }

    /**
     * Return remaining string length if the path matches (if zero, the match is complete), false otherwise.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location[] $ancestry
     */
    private function matchPath(array $ancestry): false|int
    {
        $ancestryPath = $this->getAncestryPath($ancestry);
        if ($ancestryPath === null) {
            return false;
        }
        foreach ($this->getPaths() as $path) {
            if (str_starts_with($path, $ancestryPath)) {
                return mb_strlen($path) - mb_strlen($ancestryPath);
            }
        }

        return false;
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location[] $ancestry
     */
    private function getAncestryPath(array $ancestry): ?string
    {
        $pathElements = [];

        foreach ($ancestry as $location) {
            try {
                $pathElements[] = $this->getContentTypeIdentifier($location);
            } catch (NotFoundException) {
                return null;
            }
        }

        return implode('/', $pathElements);
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location[] $ancestry
     */
    private function addToAncestry(array &$ancestry): bool
    {
        /** @var \Ibexa\Contracts\Core\Persistence\Content\Location $last */
        $last = end($ancestry);

        if ($last->depth <= 1) {
            return false;
        }

        try {
            $ancestry[] = $this->getParentLocation($last);
        } catch (NotFoundException) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getParentLocation(Location $location): Location
    {
        return $this->locationHandler->load($location->parentId);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getContentTypeIdentifier(Location $location): string
    {
        /** @var int $contentId */
        $contentId = $location->contentId;

        if (!isset($this->contentIdContentTypeIdentifierCache[$contentId])) {
            $contentInfo = $this->contentHandler->loadContentInfo($contentId);
            $contentTypeId = $contentInfo->contentTypeId;
            $contentType = $this->contentTypeHandler->load($contentTypeId);

            $this->contentIdContentTypeIdentifierCache[$contentId] = $contentType->identifier;
        }

        return $this->contentIdContentTypeIdentifierCache[$contentId];
    }

    /**
     * @return string[]
     */
    private function getPaths(): array
    {
        if ($this->paths === null) {
            $normalizedConfiguration = $this->normalizeConfiguration($this->configuration);
            $paths = $this->recursiveFlattenPaths($normalizedConfiguration);
            $this->paths = $this->expandPaths($paths);
        }

        return $this->paths;
    }

    /**
     * @param string[] $paths
     *
     * @return string[]
     */
    private function expandPaths(array $paths): array
    {
        $expandedPathsGrouped = [[]];

        foreach ($paths as $path) {
            $expandedPathsGrouped[] = $this->recursiveExpandPath(explode('/', $path));
        }

        return array_merge(...$expandedPathsGrouped);
    }

    /**
     * @param string[] $pathElements
     *
     * @return string[]
     */
    private function recursiveExpandPath(array $pathElements): array
    {
        $expandedPaths = [];

        if (count($pathElements) > 1) {
            $path = implode('/', $pathElements);
            array_shift($pathElements);

            $expandedPaths = [
                $path,
                ...$expandedPaths,
                ...$this->recursiveExpandPath($pathElements),
            ];
        }

        return $expandedPaths;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return string[]
     */
    private function recursiveFlattenPaths(array $config, string $path = ''): array
    {
        $paths = [];

        foreach ($config as $key => $value) {
            if (is_array($value) && count($value) > 0) {
                $paths = [
                    ...$paths,
                    ...$this->recursiveFlattenPaths($value, '/' . $key . $path),
                ];

                continue;
            }

            $paths[] = $key . $path;
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function normalizeConfiguration(array $config): array
    {
        $normalizedConfig = [];

        foreach ($config as $key => $value) {
            $normalizedConfig[$key] = $this->recursiveNormalizeConfiguration($value);
        }

        return $normalizedConfig;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function recursiveNormalizeConfiguration(array $config): array
    {
        $normalizedConfig = [];

        foreach ($config as $key => $value) {
            if ($key === 'indexed') {
                continue;
            }

            if ($key === 'children') {
                return $this->recursiveNormalizeConfiguration($value);
            }

            $normalizedConfig[$key] = $this->recursiveNormalizeConfiguration($value);
        }

        return $normalizedConfig;
    }
}
