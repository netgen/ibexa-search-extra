<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing\Content;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Content\PublishVersion;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class PublishVersionHandler
{
    public function __construct(
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
    ) {}

    public function __invoke(PublishVersion $message): void
    {
        $this->ancestorIndexer->indexMultiple(
            $this->locationHandler->loadLocationsByContent(
                $message->contentId,
            ),
        );
    }
}
