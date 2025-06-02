Fulltext Search Boosting
========================

The Fulltext Search Boost functionality allows fine-tuning of search results by applying configurable boost values to
specific content types, raw fields, and meta-fields. It comes in three parts:

1. **Boosting configuration**

   Boosting configuration is applied during querying the search backend

2. **Meta fields indexing configuration**

   Indexing configuration defines indexing for meta fields that are used in boosting

3. ``FullText`` **criterion**

   A custom criterion implementation that uses boosting configuration on standard and meta fields


The criterion is currently implemented for ``Solr`` search engine only.

.. note::

    Boosting is implemented in a way that will always increate a search hit score **linearly** by the given factor.

Boosting configuration
----------------------

The boosting configuration is defined under the ``netgen_ibexa_search_extra.fulltext.boost`` key in your project's
configuration files. This structure allows you to define multiple named configurations for different use cases. Each
configuration specifies boost values for content types, raw fields, and meta-fields.

The configuration is structured as follows:

.. code-block:: yaml

    netgen_ibexa_search_extra:
        fulltext:
            boost:
                <name>:
                    content_types:
                        <content_type_identifier>: <boost_value>
                    raw_fields:
                        <raw_field_name>: <boost_value>
                    meta_fields:
                        <meta_field_name>: <boost_value>

- ``<name>``: A unique identifier for the configuration. You can define multiple configurations for different scenarios
  (e.g., ``default``, ``custom``, etc.).
- ``content_types``: Specifies boost values for specific content types. The key is the content type identifier, and the
  value is the boost factor.
- ``raw_fields``: Specifies boost values for raw Solr fields. The key is the field name, and the value is the boost
  factor.
- ``meta_fields``: Specifies boost values for meta-fields. The key is the meta-field name, and the value is the boost
  factor.

Below is an example configuration with a name ``default``:

.. code-block:: yaml

    netgen_ibexa_search_extra:
        fulltext:
            boost:
                default:
                    content_types:
                        article: 2.5
                        blog_post: 1.8
                    raw_fields:
                        meta_content__name_t: 2.1
                    meta_fields:
                        title: 3.14

Meta-fields indexing configuration
----------------------------------

Meta-fields are mapped during indexing, from one or multiple Content Fields. The configuration is defined on the same
level as ``boost``. It allows indexing meta-fields from specific ContentType fields or globally, from all ContentTypes.
There are two ways to define the indexed fields:

1. **Per ContentType**: Specify the mapping with content type identifiers and field names. For example:

   .. code-block:: yaml

    netgen_ibexa_search_extra:
        fulltext:
            meta_fields:
                title:
                    - 'article/title'
                    - 'blog_post/title'

   In this example:
   - The ``title`` meta-field is mapped to the ``title`` field of the ``article`` and ``blog_post`` content types.

2. **For all ContentTypes**: Specify just the field name. In this case, the field applies to all content types. For
example:

   .. code-block:: yaml

    netgen_ibexa_search_extra:
        fulltext:
              meta_fields:
                  title:
                    - 'title'

   In this example:
   - The ``title`` meta-field applies to the ``title`` field on any content type.

This flexibility allows you to configure meta-fields either specifically for certain content types or globally across
all content types.

Creating a Criterion
--------------------

The ``ConfiguredFulltextCriterionFactory`` class is responsible for creating ``FullText`` criterion with the specified
boost configuration. When creating a criterion, you can specify the name of the configuration to use. If no name is
provided, the factory defaults to the ``default`` configuration.

To create a ``FullText`` criterion, call the ``create`` method with the search term and the name of the configuration to
use. For example:

.. code-block:: php

    $searchText = trim($request->query->get('searchText', ''));
    $criterion = $configuredFulltextCriterionFactory->create($searchText, 'default');

In this example:

- ``$searchText`` is the user-provided search term.
- ``default`` is the name of the boost configuration to apply.

If the specified configuration name does not exist, an exception will be thrown.

You can also instantiate ``FullText`` criterion manually and set the boosting rules how you see fit:


.. code-block:: php

    use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\FullText;

    $criterion = new FullText();

    $criterion->contentTypeBoost = [
        'article' => 2,
    ];

Integration with Solr
---------------------

The ``FullText`` criterion visitor generates Solr queries using the ``edismax`` query parser. The generated query includes:

- ``qf``: Specifies the fields and their respective boost values.
- ``boost``: Specifies the content type boost logic.
- ``tie``: A tie-breaking multiplier for scoring.

Example Solr Query
~~~~~~~~~~~~~~~~~~

.. code-block:: text

    {!edismax v='search term' qf='meta_content__text_t meta_title__text_t^3.14' boost='if(exists(query({!lucene v="content_type_id_id:42"})),2.5,1)' tie=0.1 uf='-*'}

Service Configuration
---------------------

The ``search_boost`` functionality is integrated into the application via service definitions in YAML files:

1. **Criterion Visitors**: Visitors for ``FullText`` criteria are registered in ``criterion_visitors.yaml``:

   .. code-block:: yaml

      netgen.ibexa_search_extra.solr.query.content.criterion_visitor.full_text:
          class: Netgen\IbexaSearchExtra\Core\Search\Solr\Query\Content\CriterionVisitor\FullText
          factory: [ '@netgen.ibexa_search_extra.solr.query.content.criterion_visitor.full_text_factory', 'createCriterionVisitor' ]
          tags:
              - { name: ibexa.search.solr.query.content.criterion.visitor }

2. **Field Mappers**: The ``FulltextMetaFieldMapper`` is registered in ``field_mappers.yaml``:

   .. code-block:: yaml

      netgen.ibexa_search_extra.solr.field_mapper.content.full_text:
          class: Netgen\IbexaSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation\FulltextMetaFieldMapper
          arguments:
              - '@Ibexa\Contracts\Core\Persistence\Content\Type\Handler'
              - '@Ibexa\Core\Search\Common\FieldRegistry'
              - '%netgen_ibexa_search_extra.fulltext.meta_fields%'
          tags:
              - { name: ibexa.search.solr.field.mapper.content.translation }

Key Points
----------

- Boost values can be configured for content types, raw fields, and meta-fields.
- Multiple configurations can be defined, each identified by a unique name.
- The ``ConfiguredFulltextCriterionFactory`` simplifies the creation of ``FullText`` criteria with boost configurations.
- Boost values are applied during query generation in Solr using the ``edismax`` parser.
