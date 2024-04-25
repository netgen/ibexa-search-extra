<?php

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\ContentPageTextFieldMapper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Ibexa\Elasticsearch\DocumentMapper\DocumentFactoryInterface;


class PageIndexingPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $usePageIndexing = $container->getParameter(
            'netgen_ibexa_search_extra.page_indexing.enabled',
        );

        if ($usePageIndexing !== true) {
            return;
        }

        $container
            ->register(ContentPageTextFieldMapper::class, ContentPageTextFieldMapper::class)
            ->setArguments([
                new Reference('netgen.ibexa_search_extra.page_indexing.page_text_extractor'),
                '%netgen_ibexa_search_extra.page_indexing.allowed_content_types%',
            ])
            ->addTag('ibexa.search.solr.field.mapper.content.translation');
    }
}