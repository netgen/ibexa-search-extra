<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\PageTextExtractor;

use DOMDocument;
use DOMNode;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Netgen\IbexaSearchExtra\Exception\IndexPageUnavailableException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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

class PageTextExtractor extends \Netgen\IbexaSearchExtra\Core\Search\Common\PageTextExtractor
{
    /** @var array<int, array<string, array<string, array<int, string>|string>>> */
    private array $cache = [];

    private LoggerInterface $logger;

    /**
     * @param array<string, mixed> $sitesConfig
     */
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly LocationHandler $locationHandler,
        private readonly RouterInterface $router,
        private readonly array $sitesConfig
    ) {
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param int $contentId
     * @param string $languageCode
     *
     * @return array<string, array<int, string>|string>
     */
    public function extractPageText(int $contentId, string $languageCode): array
    {
        $siteConfig = $this->getSiteConfigForContent($contentId);

        if (isset($this->cache[$contentId][$languageCode])) {
            return $this->cache[$contentId][$languageCode];
        }

        if (count($this->cache) > 10) {
            $this->cache = [];
        }


        try {
            $html = $this->fetchPageSource($contentId, $languageCode, $siteConfig);
        } catch (IndexPageUnavailableException|RuntimeException $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $textArray = $this->extractTextArray($html, $contentId);

        $this->cache[$contentId][$languageCode] = $textArray;

        return $textArray;
    }

    /**
     * @param string $languageCode
     * @param int $contentId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return string
     */
    private function generateUrl(string $languageCode, int $contentId, array $siteConfig): string
    {
        $contentInfo = $this->contentHandler->loadContentInfo($contentId);
        $siteAccess = $this->resolveSiteAccess($contentInfo, $languageCode);

        if (isset($siteConfig['host'])) {
            $relativePath = $this->router->generate(
                'ibexa.url.alias',
                [
                    'locationId' => (int) $contentInfo->mainLocationId,
                    'siteaccess' => $siteAccess,
                ],
                UrlGeneratorInterface::RELATIVE_PATH,
            );

            return $siteConfig['host'] . $relativePath;
        }

        return $this->router->generate(
            'ibexa.url.alias',
            [
                'locationId' => (int) $contentInfo->mainLocationId,
                'siteaccess' => $siteAccess,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

    }

    private function resolveSiteAccess(ContentInfo $contentInfo, string $languageCode): string
    {
        $siteConfig = $this->getSiteConfigForContent($contentInfo->id);

        if (!isset($siteConfig['languages_siteaccess_map'][$languageCode])) {
            throw new RuntimeException(
                sprintf(
                    "Language not supported for matched siteaccess group %s",
                    $siteConfig['site']
                )
            );
        }

        return $siteConfig['languages_siteaccess_map'][$languageCode];

    }

    /**
     * @param \DOMNode $node
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
        $siteConfig = $this->getSiteConfigForContent($contentId);
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
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws \RuntimeException
     */
    private function fetchPageSource(int $contentId, string $languageCode, array $siteConfig): string
    {
        $url = $this->generateUrl($languageCode, $contentId, $siteConfig);

        $httpClient = HttpClient::create(
        );

        $response = $httpClient->request(
            'GET',
            $url
        );

        $html = $response->getContent();

        if ($response->getStatusCode() !== 200) {
            throw new IndexPageUnavailableException(
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
     * @param string $html
     *
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

    public function getSiteConfigForContent(int $contentId): array
    {
        $contentInfo = $this->contentHandler->loadContentInfo($contentId);

        try {
            $location = $this->locationHandler->load($contentInfo->mainLocationId);
        } catch (NotFoundException) {
            throw new RuntimeException(
                sprintf(
                    'Content #%d does not have a location',
                    $contentInfo->id,
                ),
            );
        }

        $pathString = $location->pathString;
        $pathArray = explode('/', $pathString);

        foreach ($this->sitesConfig as $site => $siteConfig)  {
            if (in_array($siteConfig['tree_root_location_id'], $pathArray, false)) {
                $siteConfig['site']  = $site;
                return $siteConfig;
            }
        }

        throw new RuntimeException(
            sprintf(
                "Failed to match content ID %d to a siteaccess",
                $contentInfo->id
            )
        );

    }
}
