Descendant indexing
=====================

This feature helps in indexing hierarchical content structures. It allows the children of a content item to be indexed
within the same document as the parent if both are configured for descendant indexing.

''Configuration''

To enable this feature, set up the descendant indexing configuration:

.. code-block:: yaml
    hierarchical_indexing:
        descendant_indexing:
            enabled: false
            map:
                content_type_identifier:
                    handlers:
                        - handler_identifier_1
                        - handler_identifier_2
                    children:
                        content_type_identifier:
                            indexed: true

The ``enabled`` field must be set to true to activate descendant indexing services by registering them in the container.
In the array parameter ``map`` we define the structure of content to be included in descendant indexing by content types.
Any structure in the content tree that matches the configuration will be part of descendant indexing. Content can be
part of the structure but not included in the index. To index the content in the parent document, set the ``indexed```
parameter to ``true``.

This feature is automatically triggered during indexing when configured correctly.

Depending on what we want to index, we use different handlers. They represent the field mappers used to index the content.
If you want to index content to the full text fields, you should use the 'ng_descendant_indexing_fulltext' handler:

.. code-block:: yaml
    hierarchical_indexing:
        descendant_indexing:
            enabled: true
            map:
                content_type_identifier:
                    handlers:
                        - ng_descendant_indexing_fulltext
                    children:
                        content_type_identifier:
                            indexed: true

To index something other than full text fields (e.g., location information or content metadata), implement new field
mappers by extending the corresponding ``BaseFieldMapper`` and registering the field mapper as a service with needed tag.
The ``getIdentifier()`` method returns a string of handler identifier which should match the handler
identifier defined in the configuration.

.. code-block:: php
    public function getIdentifier(): string
    {
        return 'ng_descendant_indexing_fulltext';
    }

''AncestorIndexer''

AncestorIndexer is a service that ensures descendant indexing is considered during reindexing. For example, if we edit
content that is part of the descendant indexing map, the descendant content in which it is indexed should also be
reindexed.

The service contains methods ``indexSingle()`` and ``indexMultiple()``, which are called in handlers for any content
changes (e.g., ``CopyContentHandler``, ``DeleteContentHandler``). These methods use the AncestorResolver service to
resolve the ancestor to be reindexed. If no ancestor matches the configuration map structure, the ``resolveAncestor()``
method returns null.

The AncestorResolver service uses AncestorPathGenerator service to read from the configuration and return an array of
strings representing all of the paths matching the given configuration in order to be able to find a match with any of
the paths.
