Page indexing
=====================

This feature allows indexing of content by scraping the page using Symfony's HTTP client and indexing its content into document fields.

Configuration
-------------
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

PageTextExtractor
-----------------
The PageTextExtractor is a service that scrapes the page with Symfony's http client.  It contains a cache parameter that
holds the last 10 indexed contents by language. The entire logic is stored in the ``NativePageTextExtractor``, allowing
for new methods of indexing page content to be implemented if needed. This service extends PageTextExtractor so to
implement new logic, extend ``PageTextExtractor`` and implement the new logic.

This service also manages the fields configuration explained above.

Command
-------
As a part of this feature we have implemented the ``IndexPageContentCommand``.

This command is used to perform a complete page index when the feature is new to the project. It goes through all
content types specified in the configuration (``allowed_content_types``) and reindexes all existing content of the specified
types by their pages.

To start the reindex, use the following command::

    netgen-search-extra:index-page-content


The command also has an option ``content-ids``::

    netgen-search-extra:index-page-content --content-ids=38


To index multiple content IDs, add them to the command separated by commas.
