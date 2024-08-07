Page indexing
=====================

This feature allows indexing of content by scraping the page using Symfony's HTTP client and indexing its content into document fields.

''Config''
To enable this feature, set up the page indexing configuration:

.. code-block:: yaml

    netgen_ibexa_search_extra:
        page_indexing:
            enabled: true
            sites:
                site1:
                    tree_root_location_id: '%site1.locations.tree_root.id%'
                    languages_siteaccess_map:
                        cro-HR: cro
                        eng-GB: eng
                    fields:
                        level_1:
                            - h1
                        level_2:
                            - h2
                            - h3
                            - div.short
                        level_3:
                            - div.item-short
                    allowed_content_types:
                        - ng_article
                        - ng_frontpage
                    host: "%env(PAGE_INDEXING_HOST)%"
                site2:
                    tree_root_location_id: '%site2.locations.tree_root.id%'
                    languages_siteaccess_map:
                        cro-HR: cro
                        eng-GB: eng
                    fields:
                        level_1:
                            - h1
                        level_2:
                            - h2
                            - h3
                            - div.short
                        level_3:
                            - div.item-short
                    allowed_content_types:
                        - ng_landing_page
                    host: "%env(PAGE_INDEXING_HOST)%"

To activate the feateure, set the ``enabled`` parameter to true. Define the individual page sites under the ``sites``
array parameter. In this example we have ``site1`` and ``site2``. For each site configuration, specify
``tree_root_location_id``, ``languages_siteaccess_map``, ``fields``, ``allowed_content_types`` and ``host``.

``tree_root_location_id``: is an integer defining the root location of the site we are configuring.

``languages_siteaccess_map``: define all languages present on the site to determine which document should be indexed based on the language.

``fields``: Defines the importance of text by HTML tags. Only the text under the specified HTML tags will be indexed.
Importance is indicated by listing tags under the desired level. You can also specify content importance by CSS class by
following the HTML tag with a class name as shown in the example.

``allowed_content_types``: Only content types listed here will be indexed with additional fields from the page indexer.

``host`` Define this parameter in the .env file. It's used by the Symfony HTTP client to resolve the page URL.


''DocumentFactory''
DocumentFactory is an implementation of field mappers for Elasticsearch modeled after the Solr implementation using the
template method pattern. It implements the Elasticsearch ``DocumentFactoryInterface`` and its methods ``fromContent()``
and ``fromLocation()`` add fields to document. These methods index the fields from the suitable field mappers.

The ``DocumentFactory`` service uses all base field mapper services to index content into the correct document
(content, location, or translation-dependent document):

.. code-block:: php
    ContentFieldMapper
    LocationFieldMapper
    ContentTranslationFieldMapper
    LocationTranslationFieldMapper
    BlockFieldMapper
    BlockTranslationFieldMapper

These services are abstract classes containing methods ``accept()`` and ``mapFields()`` which are implemented by new
field mappers as needed.

To add a new field mapper, create a class that extends one of the base field mappers above, implements its methods, and
registers the service with one of the following tags, depending on the base field mapper:

.. code-block:: yaml
    netgen.ibexa_search_extra.elasticsearch.field_mapper.content
    netgen.ibexa_search_extra.elasticsearch.field_mapper.location
    netgen.ibexa_search_extra.elasticsearch.field_mapper.content_translation
    netgen.ibexa_search_extra.elasticsearch.field_mapper.location_translation
    netgen.ibexa_search_extra.elasticsearch.field_mapper.block_translation


''PageTextExtractor''
The PageTextExtractor is a service that scrapes the page with Symfony's http client.  It contains a cache parameter that
holds the last 10 indexed contents by language. The entire logic is stored in the ``NativePageTextExtractor``, allowing
for new methods of indexing page content to be implemented if needed. This service extends PageTextExtractor so to
implement new logic, extend ``PageTextExtractor`` and implement the new logic.

This service also manages the fields configuration explained above.

''Command''
As a part of this feature we have implemented the ``IndexPageContentCommand``.

This command is used to perform a complete page index when the feature is new to the project. It goes through all
content types specified in the configuration (``allowed_content_types``) and reindexes all existing content of the specified
types by their pages.

To start the reindex, use the following command:

.. code-block:: command
    netgen-search-extra:index-page-content

The command also has an option ``content-ids``:

.. code-block:: command
    netgen-search-extra:index-page-content --content-ids=38

To index multiple content IDs, add them to the command separated by commas.
