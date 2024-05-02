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
    private $xmlString;

    protected function setUp(): void
    {
        parent::setUp();


        $this->xmlString = file_get_contents('Tests/Testfiles/meitest2.xml');
        $this->subject = XmlDocument::from($this->xmlString);

    }

    /**
     * @test
     */
    public function returnsArray(): void
    {
        self::assertIsArray($this->subject->toArray());
    }

    /**
     * @test
     */
    public function returnsJson(): void
    {
        self::assertIsArray($this->subject->toJson());
    }

    /**
     * @test
     */

    public function xmlStringNotEmpty(): void
    {
        self::assertNotSame('', $this->xmlString);
    }

    /**
     * @test
     */

    public function xmlStringEqualFile() {
        self::assertXmlStringEqualsXmlFile('Tests/Testfiles/meitest2.xml',$this->xmlString);
    }

}

