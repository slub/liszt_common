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
    const DOTS = '...';

    const SHOW_CLASS = 'show';
    const HIDE_CLASS = 'hide-mobile';
    const DOTS_CLASS = 'disabled';
    const CURRENT_CLASS = 'current disabled';

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

        if(!$paginationRangeString->isMatch('/^\d* *(, *\d+)*$/')) {
            throw new \Exception('Check the configuration of liszt common. The pagination range needs to be specified in the form "1,2,3..."');
        }

        $this->paginationRange = $paginationRangeString->explode(',')->
            map(function($rangeItem) { return self::getRangeItem($rangeItem); })->
            // we always want the neighboring pages of the current page
            push(1)->
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
            filter()->
            reverse()->
            map(function($page) use ($currentPage) { return self::getPageBefore($page, $currentPage); });
        $pagesAfter = $this->paginationRange->
            filter()->
            map(function($page) use ($currentPage, $totalPages) { return self::getPageAfter($page, $currentPage, $totalPages); });

        return Collection::wrap([])->
            // we include the first page if it is not the current one
            when($this->currentPage != 1,
                function ($collection) { return $collection->push([ 'page' => 1, 'class' => self::SHOW_CLASS ]); }
            )->
            // we include the range pages before the current page (which may be empty)
            concat($pagesBefore)->
            // we include the current page
            push(['page' => $this->currentPage, 'class' => self::CURRENT_CLASS])->
            // we include the range pages after the current page (which may be empty)
            concat($pagesAfter)->
            // we include the last page if it is not the current one
            when($this->currentPage != $totalPages,
                function($collection) use ($totalPages) { return $collection->push([ 'page' => $totalPages, 'class' => self::SHOW_CLASS ]);}
            )->
            // we filter out empty results from the pagesBefore or pagesAfter arrays
            filter()->
            // we introduce dots wherever the distance between two pages is greater than one, so we prepare by adding a dummy
            push(null)->
            // sliding through pairs of pages
            sliding(2)->
            // returning page 1 if the distance is 1 and page 1 and dots elsewise (here we need the dummy)
            mapSpread( function ($page1, $page2) { return self::insertDots($page1, $page2); })->
            // and flatten out everything
            flatten(1)->
            values()->
            all();
    }

    private static function insertDots(?array $page1, ?array $page2): array
    {
        if ($page2 == null) return [ $page1 ];

        if ($page2['page'] - $page1['page'] == 1) {
            return [ $page1 ];
        }

        $dots = [ 'page' => self::DOTS, 'class' => self::DOTS_CLASS ];
        return [ $page1, $dots ];
    }

    private static function getPageBefore(?int $page, int $currentPage): ?array
    {
        $result = $currentPage - $page;

        if ($result < 2) return null;

        if ($page == 1) {
            return [
                'page' => $result,
                'class' => self::SHOW_CLASS
            ];
        }

        return [
            'page' => $result,
            'class' => self::HIDE_CLASS
        ];
    }

    private static function getPageAfter(?int $page, int $currentPage, int $totalPages): ?array
    {
        $result = $currentPage + $page;

        if ($result >= $totalPages) return null;

        if ($page == 1) {
            return [
                'page' => $result,
                'class' => self::SHOW_CLASS
            ];
        }

        return [
            'page' => $result,
            'class' => self::HIDE_CLASS
        ];
    }

    private static function getRangeItem(string $rangeItem): ?int
    {
        if ($rangeItem == '') return null;
        return (int) trim($rangeItem);
    }
}
