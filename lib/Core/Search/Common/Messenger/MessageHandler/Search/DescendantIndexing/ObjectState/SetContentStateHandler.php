<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\ObjectState;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\ObjectState\SetContentState;

final class SetContentStateHandler
{
    public function __construct(
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
    ) {}

    public function __invoke(SetContentState $message): void
    {
        $this->ancestorIndexer->indexMultiple(
            $this->locationHandler->loadLocationsByContent(
                $message->contentId,
            ),
        );
    }
}
