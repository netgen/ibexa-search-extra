<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing;

use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

use function end;
use function implode;
use function is_int;
use function mb_strlen;
use function str_starts_with;

final class AncestorResolver
{
    /**
     * @var array<int, string>
     */
    private array $contentIdContentTypeIdentifierCache = [];

    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly ContentTypeHandler $contentTypeHandler,
        private readonly LocationHandler $locationHandler,
        private readonly AncestorPathGenerator $ancestorPathGenerator,
    ) {}

    public function resolveAncestor(Location $location): ?Location
    {
        $ancestry = [$location];

        do {
            $match = $this->matchPath($ancestry);

            if ($match === true) {
                return end($ancestry);
            }
        } while (is_bool($match) && $this->addToAncestry($ancestry));

        return null;
    }

    public function resolveAncestorForSwapLocation(Location $location, Location $swappedLocation): ?Location
    {
        $contentInfo = $this->contentHandler->loadContentInfo($swappedLocation->contentId);
        $contentType = $this->contentTypeHandler->load($contentInfo->contentTypeId);
        $contentTypeIdentifier = $contentType->identifier;
        $ancestry = [$location];

        do {
            $match = $this->matchPath($ancestry, $contentTypeIdentifier);

            if ($match === true) {
                return end($ancestry);
            }
        } while (is_bool($match) && $this->addToAncestry($ancestry));

        return null;
    }

    /**
     * Return the Location if its content type matches the path parent.
     */
    public function resolveAncestorForParentLocation(Location $location): ?Location
    {
        try {
            $contentTypeIdentifier = $this->getContentTypeIdentifier($location);
        } catch (NotFoundException) {
            return null;
        }

        foreach ($this->ancestorPathGenerator->getPaths() as $path) {
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
    private function matchPath(array $ancestry, ?string $firstContentTypeIdentifier = null): ?bool
    {
        $ancestryPath = $this->getAncestryPath($ancestry, $firstContentTypeIdentifier);

        if ($ancestryPath === null) {
            return false;
        }
        $isPartialMatch = false;

        foreach ($this->ancestorPathGenerator->getPaths() as $path) {
            if (str_starts_with($path, $ancestryPath . '/')) {
                $isPartialMatch = true;
            }

            if ($path === $ancestryPath) {
                return true;
            }
        }

        if ($isPartialMatch) {
            return false;
        }

        return null;
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location[] $ancestry
     */
    private function getAncestryPath(array $ancestry, ?string $firstContentTypeIdentifier = null): ?string
    {
        $pathElements = [];

        foreach ($ancestry as $index => $location) {
            try {
                if ($index === 0 && $firstContentTypeIdentifier !== null) {
                    $pathElements[] = $firstContentTypeIdentifier;
                } else {
                    $pathElements[] = $this->getContentTypeIdentifier($location);
                }
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
}
