<?php

namespace Amrnn\CursorPaginator;

class Macro
{
    protected $requestData;
    protected $currentCursor;
    protected $perPage;
    protected $options;

    public function __construct(array $requestData, $perPage, $options = [])
    {
        $this->setRequestData($requestData);
        $this->setPerPage($perPage);
        $this->options = $options;
    }

    public function process($query)
    {
        list($currentItems, $nextItem) = $this->resolveQuery($query);
        $meta = $this->meta($query, $currentItems, $nextItem);

        return new CursorPaginator($currentItems, $this->perPage, $meta);
    }

    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
        $this->setCurrentCursor();
        return $this;
    }

    public function setPerPage($perPage)
    {
        $this->perPage = $perPage ?? config('cursor_paginator.per_page');
        return $this;
    }

    protected function setCurrentCursor()
    {
        $this->currentCursor = Cursor::fromRequest($this->requestData);
    }

    protected function resolveQuery($query)
    {
        return $this->currentCursor->paginate($query, $this->perPage, $this->options);
    }

    protected function meta($query, $items, $nextItem)
    {
        $metaHasTotal = config('cursor_paginator.meta.total', true);
        $hasFirstAndLast = config('cursor_paginator.meta.hasFirstAndLast', true);
        $targetsManager = new TargetsManager($query, $this->options);
        return (new Query\QueryMeta($query, $items, $this->currentCursor, $targetsManager, $nextItem, $metaHasTotal, $hasFirstAndLast))
            ->meta();
    }
}
