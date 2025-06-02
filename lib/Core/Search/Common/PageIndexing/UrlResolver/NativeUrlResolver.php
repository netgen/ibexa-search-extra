<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\UrlResolver;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\ConfigResolver;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\UrlResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class NativeUrlResolver extends UrlResolver
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ConfigResolver $configResolver,
    ) {}

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function resolveUrl(ContentInfo $contentInfo, string $languageCode): string
    {
        $config = $this->configResolver->resolveConfig($contentInfo->id, $languageCode);

        $urlAliasRouteName = 'ibexa.url.alias';

        if ($config->hasHost()) {
            $relativePath = $this->router->generate(
                $urlAliasRouteName,
                [
                    'locationId' => (int) $contentInfo->mainLocationId,
                    'siteaccess' => $config->getSiteaccess(),
                ],
                UrlGeneratorInterface::RELATIVE_PATH,
            );

            return $config->getHost() . $relativePath;
        }

        return $this->router->generate(
            $urlAliasRouteName,
            [
                'locationId' => (int) $contentInfo->mainLocationId,
                'siteaccess' => $config->getSiteaccess(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
