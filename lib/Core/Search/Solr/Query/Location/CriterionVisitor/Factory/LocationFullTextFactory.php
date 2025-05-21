<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Location\CriterionVisitor\Factory;

use Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Content\CriterionVisitor\FullText as ContentFullText;
use Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Location\CriterionVisitor\FullText;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;
use QueryTranslator\Languages\Galach\Generators\ExtendedDisMax;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\Tokenizer;

final class LocationFullTextFactory
{
    public function __construct(
        private readonly Tokenizer $tokenizer,
        private readonly Parser $parser,
        private readonly ExtendedDisMax $generator,
    ) {}

    public function createCriterionVisitor(): CriterionVisitor
    {
        return new FullText(
            new ContentFullText(
                $this->tokenizer,
                $this->parser,
                $this->generator,
            ),
        );
    }
}
