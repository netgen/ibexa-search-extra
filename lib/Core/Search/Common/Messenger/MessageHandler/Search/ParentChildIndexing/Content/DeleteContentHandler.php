<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing\Content;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Content\DeleteContent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function sprintf;

final class DeleteContentHandler
{
    public function __construct(
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(DeleteContent $message): void
    {
        if ($message->parentLocationIds === []) {
            $this->logger->info(
                sprintf(
                    '%s: Could not find main Location parent Location ID for deleted Content #%d, aborting',
                    $this::class,
                    $message->contentId,
                ),
            );

            return;
        }
        $locations = [];
        foreach ($message->parentLocationIds as $locationId) {
            try {
                $locations[] = $this->locationHandler->load($locationId);
            } catch (NotFoundException) {
                $this->logger->info(
                sprintf(
                    '%s: Location #%d is gone, aborting',
                    $this::class,
                    $locationId,
                ),
            );
            }
        }
        $this->ancestorIndexer->indexMultipleForDeleteContent($locations);
    }
}