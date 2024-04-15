<?php

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\DocumentMapper\DocumentFactory;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\Query\CriterionVisitor\Content\VisibilityVisitor as ContentVisibilityVisitor;
use Netgen\IbexaSearchExtra\Core\Search\Elasticsearch\Query\CriterionVisitor\Location\VisibilityVisitor as LocationVisibilityVisitor;

use Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\ContentPageTextFieldMapper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Ibexa\Elasticsearch\DocumentMapper\DocumentFactoryInterface;
use Ibexa\Contracts\Core\Persistence\Content\Handler;


class LayoutsPageIndexingPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $usePageIndexing = $container->getParameter(
            'netgen_ibexa_search_extra.use_page_indexing',
        );


        if ($usePageIndexing !== true) {
            return;
        }

        $container
            ->register(DocumentFactory::class, DocumentFactory::class)
            ->setDecoratedService(DocumentFactoryInterface::class)
            ->setArguments([
                new Reference('.inner'),
                new Reference(Handler::class),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.content.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.location.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.content_translation.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.location_translation.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.block.aggregate'),
                new Reference('netgen.ibexa_search_extra.elasticsearch.field_mapper.block_translation.aggregate'),
            ]);

        $container
            ->register(ContentVisibilityVisitor::class, ContentVisibilityVisitor::class)
            ->addTag('ibexa.search.elasticsearch.query.content.criterion.visitor');

        $container
            ->register(LocationVisibilityVisitor::class, LocationVisibilityVisitor::class)
            ->addTag('ibexa.search.elasticsearch.query.location.criterion.visitor');

        $container
            ->register(ContentPageTextFieldMapper::class, ContentPageTextFieldMapper::class)
            ->setArguments([
                new Reference('netgen.ibexa_search_extra.page_indexing.page_text_extractor'),
                '%netgen.ibexa_search_extra.page_indexing.allowed_content_types%',
            ])
            ->addTag('ibexa.search.solr.field.mapper.content.translation');
    }
}