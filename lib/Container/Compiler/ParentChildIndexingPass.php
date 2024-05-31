<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Container\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParentChildIndexingPass implements CompilerPassInterface
{
    private const ParentChildIndexerTag = 'netgen.parent_child_indexer.message_handler';
    private const MessageHandlerTag = 'messenger.message_handler';

    public function process(ContainerBuilder $container): void
    {
        $useParentChildIndexing = $container->getParameter(
            'netgen_ibexa_search_extra.use_parent_child_indexing',
        );

        if ($useParentChildIndexing !== true) {
            return;
        }

        $serviceIds =$container->findTaggedServiceIds(self::ParentChildIndexerTag);

        foreach ($serviceIds as $serviceId => $tag) {
            $definition = $container->getDefinition($serviceId);
            $definition->addTag(self::MessageHandlerTag);
        }
    }
}
