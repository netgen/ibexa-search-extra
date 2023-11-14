Asynchronous indexing
=====================

``Asynchronous indexing`` implements asynchronous indexing mechanism for Repository PHP API through Symfony's Messenger
component. This solves a number of use cases where standard synchronous indexing mechanism fails, because it tries to
execute indexing as part of the PHP API call. For example, hiding a large subtree though the Admin UI will fail with
standard synchronous indexing implementation, while with asynchronous indexing enabled it will be processed in the
background, without blocking the UI or causing timeouts.

This also enables sane implementation of various custom use cases, for example indexing file's content, which might also
require more time to execute and hence block the UI or cause timeouts.

In order to enable asynchronous indexing, use the following configuration:

.. code-block:: yaml

    netgen_ibexa_search_extra:
        use_asynchronous_indexing: true

Together with the configuration above, you will need to configure the Messenger component so that messages are handled
through the queue. Otherwise, the Messenger's mechanism will handle them them synchronously. An
`example configuration <https://github.com/netgen/ibexa-search-extra/blob/master/bundle/Resources/config/messenger.yaml>`_
for that is provided. If it fits your use case, use it, otherwise be free to implement your own.

Additionally, you will need to start the consumer to process the queue. For the example configuration, you would do that
with:

.. code-block:: console

    bin/console messenger:consume netgen_ibexa_search_extra_asynchronous_indexing --time-limit=1800 --limit=4096
