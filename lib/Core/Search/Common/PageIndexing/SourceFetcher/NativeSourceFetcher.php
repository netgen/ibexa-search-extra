<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\SourceFetcher;

use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\SourceFetcher;
use Netgen\IbexaSearchExtra\Exception\PageUnavailableException;
use Symfony\Component\HttpClient\HttpClient;

use function sprintf;

final class NativeSourceFetcher extends SourceFetcher
{
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchSource(string $url): string
    {
        $response = HttpClient::create()->request('GET', $url);

        $html = $response->getContent();

        if ($response->getStatusCode() !== 200) {
            throw new PageUnavailableException(
                sprintf(
                    'Could not fetch URL "%s": %s',
                    $url,
                    $response->getInfo()['error'],
                ),
            );
        }

        return $html;
    }
}
