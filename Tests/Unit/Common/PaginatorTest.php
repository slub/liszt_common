<?php

namespace Slub\LisztCommon\Tests\Unit\Common;

use Slub\LisztCommon\Common\Paginator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * @covers Slub\LisztCommon\Common\XmlDocument
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

        self::assertEquals($this->subject->getPagination(), [ Paginator::CURRENT_PAGE, 2, 3, 4, self::PAGE_COUNT]);
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

        self::assertEquals($this->subject->getPagination(), [1, self::PAGE_COUNT - 3, self::PAGE_COUNT - 2, self::PAGE_COUNT - 1, Paginator::CURRENT_PAGE]);
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
            1,
            $midPage - 3,
            $midPage - 2,
            $midPage - 1,
            Paginator::CURRENT_PAGE,
            $midPage + 1,
            $midPage + 2,
            $midPage + 3,
            self::PAGE_COUNT
        ];

        self::assertEquals($this->subject->getPagination(), $expected);
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

        self::assertEquals($this->subject->getPagination(), [ 1, Paginator::CURRENT_PAGE, 3, 4, 5, self::PAGE_COUNT ]);
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
        $this->confArray['paginationRange'] = 'randomText';
        $this->extConf->method('get')->
            willReturn($this->confArray);

        $this->expectException(\Exception::class);
        $this->subject->setExtensionConfiguration($this->extConf);
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
            1,
            $midPage - 3,
            $midPage - 2,
            $midPage - 1,
            Paginator::CURRENT_PAGE,
            $midPage + 1,
            $midPage + 2,
            $midPage + 3,
            self::PAGE_COUNT
        ];

        self::assertEquals($this->subject->getPagination(), $expected);
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
            1,
            $midPage - 5,
            $midPage - 2,
            $midPage - 1,
            Paginator::CURRENT_PAGE,
            $midPage + 1,
            $midPage + 2,
            $midPage + 5,
            self::PAGE_COUNT
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
            1,
            $midPage - 3,
            $midPage - 2,
            $midPage - 1,
            Paginator::CURRENT_PAGE,
            $midPage + 1,
            $midPage + 2,
            $midPage + 3,
            self::PAGE_COUNT
        ];

        self::assertEquals($this->subject->getPagination(), $expected);
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
            1,
            $midPage - 3,
            $midPage - 2,
            $midPage - 1,
            Paginator::CURRENT_PAGE,
            $midPage + 1,
            $midPage + 2,
            $midPage + 3,
            self::PAGE_COUNT
        ];

        self::assertEquals($this->subject->getPagination(), $expected);
    }

}
