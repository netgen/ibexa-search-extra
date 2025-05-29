<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageTextExtractor;

use DOMDocument;
use DOMNode;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Netgen\IbexaSearchExtra\Core\Search\Common\PageTextExtractor;
use Netgen\IbexaSearchExtra\Core\Search\Common\SiteConfigResolver;
use Netgen\IbexaSearchExtra\Exception\PageUnavailableException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
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

class NativePageTextExtractor extends PageTextExtractor
{
    /** @var array<int, array<string, array<string, array<int, string>|string>>> */
    private array $cache = [];

    private LoggerInterface $logger;

    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly RouterInterface $router,
        private readonly SiteConfigResolver $siteConfigResolver,
    ) {
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function extractPageText(int $contentId, string $languageCode): array
    {
        if (isset($this->cache[$contentId][$languageCode])) {
            return $this->cache[$contentId][$languageCode];
        }

        if (count($this->cache) > 10) {
            $this->cache = [];
        }

        $siteConfig = $this->siteConfigResolver->getSiteConfigForContent($contentId);

        try {
            $html = $this->fetchPageSource($contentId, $languageCode, $siteConfig);
        } catch (PageUnavailableException|HttpClientException $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $textArray = $this->extractTextArray($html, $contentId);

        $this->cache[$contentId][$languageCode] = $textArray;

        return $textArray;
    }

    private function generateUrl(string $languageCode, int $contentId, array $siteConfig): string
    {
        $contentInfo = $this->contentHandler->loadContentInfo($contentId);
        $siteAccess = $this->resolveSiteAccess($contentInfo, $languageCode);
        $urlAliasRouteName = 'ibexa.url.alias';

        if (isset($siteConfig['host'])) {
            $relativePath = $this->router->generate(
                $urlAliasRouteName,
                [
                    'locationId' => (int) $contentInfo->mainLocationId,
                    'siteaccess' => $siteAccess,
                ],
                UrlGeneratorInterface::RELATIVE_PATH,
            );

            return $siteConfig['host'] . $relativePath;
        }

        return $this->router->generate(
            $urlAliasRouteName,
            [
                'locationId' => (int) $contentInfo->mainLocationId,
                'siteaccess' => $siteAccess,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    private function resolveSiteAccess(ContentInfo $contentInfo, string $languageCode): string
    {
        $siteConfig = $this->siteConfigResolver->getSiteConfigForContent($contentInfo->id);

        if (!isset($siteConfig['languages_siteaccess_map'][$languageCode])) {
            throw new RuntimeException(
                sprintf(
                    'Language not supported for matched siteaccess group %s',
                    $siteConfig['site'],
                ),
            );
        }

        return $siteConfig['languages_siteaccess_map'][$languageCode];
    }

    /**
     * @param array<string, array<int, string>> $textArray
     *
     * @return array<string, array<int, string>>
     */
    private function recursiveExtractTextArray(DOMNode $node, array &$textArray, int $contentId): array
    {
        if ($node->nodeType === XML_ELEMENT_NODE || $node->nodeType === XML_HTML_DOCUMENT_NODE) {
            $fieldLevel = $this->getFieldName($node, $contentId);

            if ($fieldLevel !== null) {
                $textArray[$fieldLevel][] = $node->textContent;

                return $textArray;
            }

            foreach ($node->childNodes as $childNode) {
                $this->recursiveExtractTextArray($childNode, $textArray, $contentId);
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

    private function getFieldName(DOMNode $node, int $contentId): null|string
    {
        $siteConfig = $this->siteConfigResolver->getSiteConfigForContent($contentId);
        $fields = $siteConfig['fields'];

        foreach ($fields as $level => $tags) {
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

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function fetchPageSource(int $contentId, string $languageCode, array $siteConfig): string
    {
        $url = $this->generateUrl($languageCode, $contentId, $siteConfig);

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
    private function extractTextArray(string $html, int $contentId): array
    {
        $startTag = '<!--begin page content-->';
        $endTag = '<!--end page content-->';

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
            $textArray = $this->recursiveExtractTextArray($doc, $textArray, $contentId);
        }

        return $textArray;
    }
}
