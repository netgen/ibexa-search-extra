<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler\FieldType;

use Ibexa\FieldTypeRichText\FieldType\RichText\SearchField;
use Netgen\IbexaSearchExtra\Core\FieldType\RichText\Indexable as IndexableRichText;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RichTextIndexablePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $enabled = $container->getParameter('netgen_ibexa_search_extra.indexable_field_type.ezrichtext.enabled');
        $shortTextLimit = $container->getParameter('netgen_ibexa_search_extra.indexable_field_type.ezrichtext.short_text_limit');

        if ($enabled === true) {
            $this->redefineIndexableImplementation($container, $shortTextLimit);
        }
    }

    private function redefineIndexableImplementation(ContainerBuilder $container, $shortTextLimit): void
    {
        $definition = $container->findDefinition(SearchField::class);

        $definition->setClass(IndexableRichText::class);
        $definition->setArgument(0, $shortTextLimit);
        $definition->addTag('ibexa.field_type.indexable', ['alias' => 'ezrichtext']);

        $container->setDefinition(SearchField::class, $definition);
    }
}
