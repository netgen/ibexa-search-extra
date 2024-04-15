<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common;

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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use function count;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function explode;
use function in_array;
use function is_string;
use function libxml_use_internal_errors;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function sprintf;
use function trim;
use const CURLINFO_HTTP_CODE;
use const CURLOPT_RETURNTRANSFER;
use const XML_ELEMENT_NODE;
use const XML_HTML_DOCUMENT_NODE;
use const XML_TEXT_NODE;

class PageTextExtractor
{
    /** @var array<int, array<string, array<string, array<int, string>|string>>> */
    private array $cache = [];

    private LoggerInterface $logger;

    /**
     * @param array<string, int> $siteRoots
     * @param array<string, array<string, string>> $languageAccessibility
     * @param array<array<int, string>> $pageTextConfig
     */
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly LocationHandler $locationHandler,
        private readonly RouterInterface $router,
        private readonly array $siteRoots,
        private readonly array $languageAccessibility,
        private readonly string $pageIndexingHost,
        private readonly array $pageTextConfig,
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
        if (isset($this->cache[$contentId][$languageCode])) {
            return $this->cache[$contentId][$languageCode];
        }

        if (count($this->cache) > 10) {
            $this->cache = [];
        }

        try {
            $html = $this->fetchPageSource($contentId, $languageCode);
        } catch (IndexPageUnavailableException|RuntimeException $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $textArray = $this->extractTextArray($html);

        $this->cache[$contentId][$languageCode] = $textArray;

        return $textArray;
    }

    /**
     * @param string $languageCode
     * @param int $contentId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @return string
     */
    private function generateUrl(string $languageCode, int $contentId): string
    {
        $contentInfo = $this->contentHandler->loadContentInfo($contentId);
        $siteAccess = $this->resolveSiteAccess($contentInfo, $languageCode);

        $relativePath = $this->router->generate(
            'ibexa.url.alias',
            [
                'locationId' => (int) $contentInfo->mainLocationId,
                'siteaccess' => $siteAccess,
            ],
            UrlGeneratorInterface::RELATIVE_PATH,
        );

        return $this->pageIndexingHost . $relativePath;
    }

    private function resolveSiteAccess(ContentInfo $contentInfo, string $languageCode): string
    {
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

        $pathArray = explode('/', $location->pathString);

        foreach ($this->siteRoots as $site => $siteRoot) {
            if (!in_array((string) $siteRoot, $pathArray, true)) {
                continue;
            }

            if (!isset($this->languageAccessibility[$site][$languageCode])) {
                throw new RuntimeException("Language not supported for matched siteaccess group '{$site}'");
            }

            return $this->languageAccessibility[$site][$languageCode];
        }

        throw new RuntimeException("Failed to match content ID '{$contentInfo->id}' to a siteaccess");
    }

    /**
     * @param \DOMNode $node
     * @param array<string, array<int, string>> $textArray
     *
     * @return array<string, array<int, string>>
     */
    private function recursiveExtractTextArray(DOMNode $node, array &$textArray): array
    {
        if ($node->nodeType === XML_ELEMENT_NODE || $node->nodeType === XML_HTML_DOCUMENT_NODE) {
            $fieldLevel = $this->getFieldName($node);

            if ($fieldLevel !== null) {
                $textArray[$fieldLevel][] = $node->textContent;
            } else {
                foreach ($node->childNodes as $childNode) {
                    $this->recursiveExtractTextArray($childNode, $textArray);
                }
            }
        } elseif ($node->nodeType === XML_TEXT_NODE) {
            $textContent = trim($node->textContent);
            if ($textContent !== '') {
                $textArray['other'][] = $textContent;
            }
        }

        return $textArray;
    }

    private function getFieldName(DOMNode $node): null|string
    {
        foreach ($this->pageTextConfig as $level => $tags) {
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
    private function fetchPageSource(int $contentId, string $languageCode): string
    {
        $url = $this->generateUrl($languageCode, $contentId);
        $curlHandle = curl_init($url);

        if ($curlHandle === false) {
            throw new RuntimeException('There was an error initializing a cURL session');
        }

        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);

        $html = curl_exec($curlHandle);
        if (!is_string($html)) {
            throw new RuntimeException('curl_exec could not fetch url');
        }

        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new IndexPageUnavailableException(
                sprintf(
                    'Could not fetch URL "%s": %s',
                    $url,
                    curl_error($curlHandle),
                ),
            );
        }

        curl_close($curlHandle);

        return $html;
    }

    /**
     * @param string $html
     *
     * @return array<string, array<int, string>>
     */
    private function extractTextArray(string $html): array
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
            $textArray = $this->recursiveExtractTextArray($doc, $textArray);
        }

        return $textArray;
    }
}
