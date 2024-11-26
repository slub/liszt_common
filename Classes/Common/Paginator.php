<?php

declare(strict_types=1);

/*
 * This file is part of the Liszt Catalog Raisonne project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 */

namespace Slub\LisztCommon\Common;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class Paginator
{
    const CURRENT_PAGE = 'current';

    protected int $itemsPerPage = -1;
    protected int $totalItems = -1;
    protected int $currentPage = -1;
    protected ?Collection $paginationRange = null;

    final public function __construct()
    { }

    public static function createPagination(
        int $page,
        int $totalItems,
        ExtensionConfiguration $extConf
    ): array
    {
        return (new static($page, $totalItems, $extConf))->
            setPage($page)->
            setTotalItems($totalItems)->
            setExtensionConfiguration($extConf)->
            getPagination();
    }

    public function setTotalItems(int $totalItems): Paginator
    {
        $this->totalItems = $totalItems;

        return $this;
    }

    public function setExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): Paginator
    {
        $extConf = $extensionConfiguration->get('liszt_common');
        $this->itemsPerPage = (int) $extConf['itemsPerPage'];
        $paginationRangeString = Str::of($extConf['paginationRange']);

        if($paginationRangeString->match('/([1-9][0-9]*, *)*[1-9][0-9]*/') == "") {
            throw new \Exception('Check the configuration of liszt common. The pagination range needs to be specified in the form "1,2,3..."');
        }

        $this->paginationRange = $paginationRangeString->explode(',')->
            map(function($rangeItem) { return self::getRangeItem($rangeItem); })->
            unique()->
            sort();

        return $this;
    }

    public function setPage(int $page): Paginator
    {
        $this->currentPage = $page;

        return $this;
    }

    public function getPagination(): array
    {
        if (
            $this->totalItems < 0 ||
            $this->currentPage < 0 ||
            $this->itemsPerPage < 0
        ) {
            throw new \Exception('Please specify total items, items per page and current page before retrieving the pagination.');
        }

        $pagination = new Collection();
        $totalPages = (int) ceil($this->totalItems / $this->itemsPerPage);
        $currentPage = $this->currentPage;

        $pagesBefore = $this->paginationRange->
            reverse()->
            map(function($page) use ($currentPage) { return self::getPageBefore($page, $currentPage); });
        $pagesAfter = $this->paginationRange->
            map(function($page) use ($currentPage, $totalPages) { return self::getPageAfter($page, $currentPage, $totalPages); });

        return Collection::wrap([])->
            when($this->currentPage != 1, function ($collection) { return $collection->push(1); })->
            when($pagesBefore->first() > 2, function ($collection) { return $collection->push('...'); })->
            concat($pagesBefore)->
            push(self::CURRENT_PAGE)->
            concat($pagesAfter)->
            when($this->currentPage != $totalPages, function($collection) use ($totalPages) { return $collection->push($totalPages); })->
            filter()->
            values()->
            all();
    }


    private static function getPageBefore(int $page, int $currentPage): ?int
    {
        $result = $currentPage - $page;
        return $result > 1 ? $result : null;
    }

    private static function getPageAfter(int $page, int $currentPage, int $totalPages): ?int
    {
        $result = $currentPage + $page;
        return $result < $totalPages ? $result : null;
    }

    private static function getRangeItem(string $rangeItem): int
    {
        return (int) trim($rangeItem);
    }
}
