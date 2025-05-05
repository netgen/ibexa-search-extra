<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\Content;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Content\UpdateContentMetadata;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function sprintf;

final class UpdateContentMetadataHandler
{
    public function __construct(
        private readonly ContentHandler $contentHandler,
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(UpdateContentMetadata $message): void
    {
        try {
            $contentInfo = $this->contentHandler->loadContentInfo(
                $message->contentId,
            );
        } catch (NotFoundException) {
            $this->logger->info(
                sprintf(
                    '%s: Content #%d is gone, aborting',
                    $this::class,
                    $message->contentId,
                ),
            );

            return;
        }

        if ($contentInfo->status !== ContentInfo::STATUS_PUBLISHED) {
            return;
        }

        try {
            $location = $this->locationHandler->load(
                $contentInfo->mainLocationId,
            );
        } catch (NotFoundException) {
            $this->logger->info(
                sprintf(
                    '%s: Location #%d is gone, aborting',
                    $this::class,
                    $contentInfo->mainLocationId,
                ),
            );

            return;
        }

        $this->ancestorIndexer->indexSingle($location);
    }
}
