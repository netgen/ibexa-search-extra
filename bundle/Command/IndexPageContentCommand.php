<?php

declare(strict_types=1);

namespace Netgen\Bundle\IbexaSearchExtraBundle\Command;

use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;
use Netgen\IbexaSearchExtra\Exception\IndexPageUnavailableException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;

use Symfony\Component\Console\Style\SymfonyStyle;
use function count;
use function explode;

class IndexPageContentCommand extends Command
{
    protected static $defaultName = 'netgen-search-extra:index-page-content';
    private SymfonyStyle $style;

    /**
     * @param array<string, mixed> $sitesConfig
     */
    public function __construct(
        private readonly ContentService $contentService,
        private readonly SearchHandler $searchHandler,
        private readonly PersistenceHandler $persistenceHandler,
        private readonly array $sitesConfig,
    ) {
        parent::__construct($this::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Index content related through layouts')
            ->addOption(
                'content-ids',
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma separated list of content id\'s of content to index.',
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->sitesConfig as $site => $siteConfig) {
            $this->style->info("Indexing for site " . $site);
            $this->indexContent($output, $input, $siteConfig);
        }

        return Command::SUCCESS;
    }

    private function indexContent(OutputInterface $output, InputInterface $input, array $siteConfig): void
    {
        $contentIds = explode(',', $input->getOption('content-ids'));

        $allowedContentTypes = $siteConfig['allowed_content_types'];
        $offset = 0;
        $limit = 50;
        $totalCount = $this->getTotalCount($allowedContentTypes, $contentIds);
        $progressBar = new ProgressBar($output, $totalCount);

        if ($totalCount <= 0) {
            $this->style->info('No content found to index, exiting.');

            return;
        }

        $this->style->info('Found ' . $totalCount . ' content objects...');

        $progressBar->start($totalCount);

        while ($offset < $totalCount) {
            $chunk = $this->getChunk($limit, $offset, $allowedContentTypes, $contentIds);

            $this->processChunk($chunk, $progressBar);

            $offset += $limit;
        }

        $progressBar->finish();

        $output->writeln('');
        $this->style->info('Finished.');

    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getTotalCount(array $allowedContentTypes, array $contentIds): int
    {
        $filter = $this->getFilter($allowedContentTypes, $contentIds);

        $filter
            ->withLimit(0)
            ->withOffset(0);

        return $this->contentService->find($filter)->getTotalCount() ?? 0;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getChunk(int $limit, int $offset, array $allowedContentTypes, array $contentIds): ContentList
    {
        $filter = $this->getFilter($allowedContentTypes, $contentIds);
        $filter
            ->withLimit($limit)
            ->withOffset($offset)
        ;
        return $this->contentService->find($filter);
    }

    private function getFilter(array $allowedContentTypes, array $contentIds = []): Filter
    {
        $filter = new Filter();
        $filter->withCriterion(new Query\Criterion\ContentTypeIdentifier($allowedContentTypes));

        if (count($contentIds) > 0) {
            $filter->andWithCriterion(new Query\Criterion\ContentId($contentIds));
        }
        return $filter;
    }

    private function processChunk(ContentList $contentList, ProgressBar $progressBar): void
    {
        foreach ($contentList->getIterator() as $content) {
            try {
                $this->indexContentWithLocations($content);
                $progressBar->advance();
            } catch (IndexPageUnavailableException $exception) {
                $this->style->error($exception->getMessage());
            }
        }
    }

    private function indexContentWithLocations(Content $content): void
    {
        $this->searchHandler->indexContent(
            $this->persistenceHandler->contentHandler()->load($content->id, $content->versionInfo->versionNo),
        );

        $locations = $this->persistenceHandler->locationHandler()->loadLocationsByContent($content->id);
        foreach ($locations as $location) {
            $this->searchHandler->indexLocation($location);
        }
    }
}
