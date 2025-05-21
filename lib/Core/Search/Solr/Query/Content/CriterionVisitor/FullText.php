<?php

namespace Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Content\CriterionVisitor;

use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\FullText as FullTextCriterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;
use QueryTranslator\Languages\Galach\Generators\ExtendedDisMax;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\Tokenizer;

use function array_keys;
use function array_map;
use function count;
use function implode;
use function sprintf;
use function str_repeat;

class FullText extends CriterionVisitor
{
    public function __construct(
        private readonly Tokenizer $tokenizer,
        private readonly Parser $parser,
        private readonly ExtendedDisMax $generator,
    ) {}

    public function canVisit(Criterion $criterion): bool
    {
        return $criterion instanceof FullTextCriterion;
    }

    public function visit(Criterion $criterion, ?CriterionVisitor $subVisitor = null): string
    {
        /** @var FullTextCriterion $criterion */
        /** @var string $value */
        $value = $criterion->value;

        $tokenSequence = $this->tokenizer->tokenize($value);
        $syntaxTree = $this->parser->parse($tokenSequence);

        $options = [];

        if ($criterion->fuzziness < 1) {
            $options['fuzziness'] = $criterion->fuzziness;
        }

        $queryString = $this->generator->generate($syntaxTree, $options);
        $queryStringEscaped = $this->escapeQuote($queryString);
        $queryFields = $this->getQueryFields($criterion);

        $boost = $this->getBoostParameter($criterion);

        $queryParams = [
            'v' => $queryStringEscaped,
            'qf' => $queryFields,
            'tie' => 0.1,
            'uf' => '-*',
            'boost' => $boost,
        ];

        $queryParamsString = implode(
            ' ',
            array_map(
                static fn ($key, $value) => sprintf("%s='%s'", $key, $value),
                array_keys($queryParams),
                $queryParams,
            ),
        );

        return sprintf('{!edismax %s}', $queryParamsString);
    }

    /**
     * @param FullTextCriterion $criterion
     */
    private function getBoostParameter(Criterion $criterion): string
    {
        $function = '';

        foreach ($criterion->contentTypeBoost as $contentTypeIdentifier) {
            $function .= 'if(exists(query({!lucene v=\"content_type_id_id:' . $contentTypeIdentifier['id'] . '\"})),' . $contentTypeIdentifier['boost_value'] . ',';
        }

        $function .= '1' . str_repeat(')', count($criterion->contentTypeBoost));

        return $function;
    }

    /**
     * @param FullTextCriterion $criterion
     */
    private function getQueryFields(Criterion $criterion): string
    {
        $queryFields = ['meta_content__text_t'];

        foreach ($criterion->solrFieldsBoost as $field => $boost) {
            $queryFields[] = sprintf('%s^%s', $field, $boost);
        }

        foreach ($criterion->metaFieldsBoost as $fieldKey => $boost) {
            $queryFields[] = sprintf('meta_%s__text_t^%s', $fieldKey, $boost);
        }

        return implode(' ', $queryFields);
    }
}
