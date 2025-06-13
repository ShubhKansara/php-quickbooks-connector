<?php

namespace ShubhKansara\PhpQuickbooksConnector\Contracts;

interface QuickBooksProviderInterface
{
    /**
     * Fetch everything your sync job expects, as an array of associative-data.
     * You can pass filters (e.g. modified-since) if you like.
     */
    public function fetchItems(array $filters = []): array;

    public function fetchCustomers(array $filters = []): array;

    public function fetchAttachables(array $filters = []): array;

    // …and any other “entity” your app syncs…
}
