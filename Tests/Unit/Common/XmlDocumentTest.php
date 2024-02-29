<?php

namespace Slub\LisztCommon\Tests\Unit\Common;

use Slub\LisztCommon\Common\XmlDocument;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers Slub\LisztCommon\Common\XmlDocument
 */
final class XmlDocumentTest extends UnitTestCase
{
    private XmlDocument $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new XmlDocument('');
    }

    /**
     * @test
     */
    public function returnsArray(): void
    {
        self::assertSame([], $this->subject->toArray());
    }

    /**
     * @test
     */
    public function returnsJson(): void
    {
        self::assertSame('', $this->subject->toJson());
    }
}
