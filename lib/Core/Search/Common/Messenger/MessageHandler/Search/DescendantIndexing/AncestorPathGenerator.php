<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing;

use function array_merge;
use function array_shift;
use function count;
use function explode;
use function implode;
use function is_array;

final class AncestorPathGenerator
{
    /**
     * @var string[]|null
     */
    private ?array $paths = null;

    public function __construct(
        private readonly array $configuration,
    ) {
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        if ($this->paths === null) {
            $normalizedConfiguration = $this->normalizeConfiguration($this->configuration['map'] ?? []);
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
            if ($key === 'indexed' || $key === 'handlers') {
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
