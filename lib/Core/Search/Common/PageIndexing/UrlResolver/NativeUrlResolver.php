<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\UrlResolver;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\ConfigResolver;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\UrlResolver;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class NativeUrlResolver extends UrlResolver
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ConfigResolver $configResolver,
    ) {}

    public function resolveUrl(ContentInfo $contentInfo, string $languageCode): string
    {
        $siteConfig = $this->configResolver->getSiteConfigForContent($contentInfo->id, $languageCode);

        $urlAliasRouteName = 'ibexa.url.alias';

        if ($siteConfig->hasHost()) {
            $relativePath = $this->router->generate(
                $urlAliasRouteName,
                [
                    'locationId' => (int) $contentInfo->mainLocationId,
                    'siteaccess' => $siteConfig->getSiteaccess(),
                ],
                UrlGeneratorInterface::RELATIVE_PATH,
            );

            return $siteConfig->getHost() . $relativePath;
        }

        return $this->router->generate(
            $urlAliasRouteName,
            [
                'locationId' => (int) $contentInfo->mainLocationId,
                'siteaccess' => $siteConfig->getSiteaccess(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
