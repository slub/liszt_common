<?php

namespace Slub\LisztCommon\Tests\Unit\Common;

use Slub\LisztCommon\Common\Paginator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * @covers Slub\LisztCommon\Common\Paginator
 */
final class PaginatorTest extends UnitTestCase
{
    const ITEMS_PER_PAGE = 50;
    const PAGE_COUNT = 20;
    const LAST_PAGE_UNDERFLOW = 5;

    private Paginator $subject;
    private ExtensionConfiguration $extConf;
    private array $confArray = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extConf = $this->getAccessibleMock(ExtensionConfiguration::class, ['get'], [], '', false);
        $this->confArray['itemsPerPage'] = self::ITEMS_PER_PAGE;
        $totalItems = self::ITEMS_PER_PAGE * self::PAGE_COUNT - self::LAST_PAGE_UNDERFLOW;

        $this->subject = new Paginator();
        $this->subject->setTotalItems($totalItems);
    }

    /**
     * @test
     */
    public function firstPageGetsCorrectPagination(): void
    {
        $this->confArray['paginationRange'] = '1,2,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $this->subject->setPage(1);
        $this->subject->setExtensionConfiguration($this->extConf);

        $expected = [
            [ 'page' => 1, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => 2, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => 4, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function lastPageGetsCorrectPagination(): void
    {
        $this->confArray['paginationRange'] = '1,2,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $this->subject->setPage(self::PAGE_COUNT);
        $this->subject->setExtensionConfiguration($this->extConf);

        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT - 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => self::PAGE_COUNT - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => self::PAGE_COUNT - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::CURRENT_CLASS ]
        ];
        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function midPageGetsCorrectPagination(): void
    {
        $this->confArray['paginationRange'] = '1,2,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage + 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage + 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function secondPageGetsCorrectPagination()
    {
        $this->confArray['paginationRange'] = '1,2,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $this->subject->setPage(2);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => 2, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => 3, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => 4, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => 5, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function incorrectExtConfLeadsToException()
    {
        $this->confArray['paginationRange'] = 'randomText';
        $this->extConf->method('get')->
            willReturn($this->confArray);

        $this->expectException(\Exception::class);
        $this->subject->setExtensionConfiguration($this->extConf);
    }

    /**
     * @test
     */
    public function mildlyIncorrectExtConfLeadsToException()
    {
        $this->confArray['paginationRange'] = '1,2,a';
        $this->extConf->method('get')->
            willReturn($this->confArray);

        $this->expectException(\Exception::class);
        $this->subject->setExtensionConfiguration($this->extConf);
    }

    /**
     * @test
     */
    public function incorrectSortingInExtConfGetsCorrectPagination()
    {
        $this->confArray['paginationRange'] = '2,1,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage + 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage + 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function nonContiguousConfigInExtConfGetsCorrectPagination()
    {
        $this->confArray['paginationRange'] = '1,2,5';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 5, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage + 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage + 5, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];
    }

    /**
     * @test
     */
    public function paginationRangeMayBeFormattedWithSpaces(): void
    {
        $this->confArray['paginationRange'] = '1, 2, 3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage + 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage + 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function multiplePagesAreReturnedUniquely(): void
    {
        $this->confArray['paginationRange'] = '1,1,2,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage + 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage + 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function neighboringPagesAreAlwaysIncluded(): void
    {
        $this->confArray['paginationRange'] = '2,3';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage + 2, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => $midPage + 3, 'class' => Paginator::HIDE_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

    /**
     * @test
     */
    public function emptyPageRangeLeadsToSensibleResult(): void
    {
        $this->confArray['paginationRange'] = '';
        $this->extConf->method('get')->
            willReturn($this->confArray);
        $midPage = ceil(self::PAGE_COUNT / 2);
        $this->subject->setPage($midPage);
        $this->subject->setExtensionConfiguration($this->extConf);
        $expected = [
            [ 'page' => 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => $midPage - 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => $midPage, 'class' => Paginator::CURRENT_CLASS ],
            [ 'page' => $midPage + 1, 'class' => Paginator::SHOW_CLASS ],
            [ 'page' => Paginator::DOTS, 'class' => Paginator::DOTS_CLASS ],
            [ 'page' => self::PAGE_COUNT, 'class' => Paginator::SHOW_CLASS ]
        ];

        self::assertEquals($expected, $this->subject->getPagination());
    }

}
