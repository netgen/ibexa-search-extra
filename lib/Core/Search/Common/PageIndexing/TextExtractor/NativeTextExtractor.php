<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor;

use DOMDocument;
use DOMNode;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\Config;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\ConfigResolver;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageIndexing\TextExtractor;
use Netgen\IbexaSearchExtra\Exception\PageUnavailableException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;

use function count;
use function explode;
use function in_array;
use function libxml_use_internal_errors;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function sprintf;
use function trim;

use const XML_ELEMENT_NODE;
use const XML_HTML_DOCUMENT_NODE;
use const XML_TEXT_NODE;

class NativeTextExtractor extends TextExtractor
{
    /** @var array<int, array<string, array<string, array<int, string>|string>>> */
    private array $cache = [];

    private LoggerInterface $logger;

    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly RouterInterface $router,
        private readonly ConfigResolver $configResolver,
    ) {
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function extractPageText(int $contentId, string $languageCode): array
    {
        if (isset($this->cache[$contentId][$languageCode])) {
            return $this->cache[$contentId][$languageCode];
        }

        if (count($this->cache) > 10) {
            $this->cache = [];
        }

        try {
            $html = $this->fetchPageSource($contentId, $languageCode);
        } catch (PageUnavailableException|HttpClientException $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $textArray = $this->extractTextArray($html, $contentId, $languageCode);

        $this->cache[$contentId][$languageCode] = $textArray;

        return $textArray;
    }

    private function generateUrl(string $languageCode, int $contentId): string
    {
        $siteConfig = $this->configResolver->getSiteConfigForContent($contentId, $languageCode);

        $contentInfo = $this->contentHandler->loadContentInfo($contentId);
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

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function fetchPageSource(int $contentId, string $languageCode): string
    {
        $url = $this->generateUrl($languageCode, $contentId);

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

    /**
     * @return array<string, array<int, string>>
     */
    private function extractTextArray(string $html, int $contentId, string $languageCode): array
    {
        $startTag = '<!--begin page content-->';
        $endTag = '<!--end page content-->';
        $config = $this->configResolver->getSiteConfigForContent($contentId, $languageCode);

        $startPos = mb_strpos($html, $startTag);
        $endPos = mb_strpos($html, $endTag);

        $textArray = [];

        if ($startPos !== false && $endPos !== false) {
            $startPos += mb_strlen($startTag);
            $extractedContent = mb_substr($html, $startPos, $endPos - $startPos);

            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML($extractedContent);
            libxml_use_internal_errors(false);

            $textArray = $this->recursiveExtractTextArray($doc, $textArray, $config);
        }

        return $textArray;
    }

    /**
     * @param array<string, array<int, string>> $textArray
     *
     * @return array<string, array<int, string>>
     */
    private function recursiveExtractTextArray(DOMNode $node, array &$textArray, Config $config): array
    {
        if ($node->nodeType === XML_ELEMENT_NODE || $node->nodeType === XML_HTML_DOCUMENT_NODE) {
            $fieldLevel = $this->getFieldName($node, $config);

            if ($fieldLevel !== null) {
                $textArray[$fieldLevel][] = $node->textContent;

                return $textArray;
            }

            foreach ($node->childNodes as $childNode) {
                $this->recursiveExtractTextArray($childNode, $textArray, $config);
            }
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            $textContent = trim($node->textContent);

            if ($textContent !== '') {
                $textArray['other'][] = $textContent;
            }
        }

        return $textArray;
    }

    private function getFieldName(DOMNode $node, Config $config): null|string
    {
        foreach ($config->getFields() as $level => $tags) {
            foreach ($tags as $tag) {
                $tagParts = explode('.', $tag); // Split tag and class if present
                $tagName = $tagParts[0]; // Get the tag name
                $class = $tagParts[1] ?? null; // Get the class if exists

                if ($node->nodeName !== $tagName) {
                    continue;
                }

                if ($class !== null && !$this->hasClass($node, $class)) {
                    continue;
                }

                return $level;
            }
        }

        return null;
    }

    private function hasClass(DOMNode $node, string $className): bool
    {
        /** @var \DOMElement $node */
        $classes = explode(' ', $node->getAttribute('class'));

        return in_array($className, $classes, true);
    }
}
