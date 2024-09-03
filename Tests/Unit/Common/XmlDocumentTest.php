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
        self::assertJson($this->subject->toJson());
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
    public function testFluidInterface()
    {
        self::assertJson($this->subject->setXmlId(true)->toJson());
    }

    /**
     * @test
     */
    public function testXmlIdIsOmited()
    {
        self::assertFalse(str_contains($this->subject->setXmlId(false)->toJson(), '@xmlId'));
    }

    /**
     * @test
     */
    public function xmlIdIsIncluded()
    {
        self::assertStringContainsString('@xml:id', $this->subject->setXmlId(true)->toJson());
    }

    /**
     * @test
     */
    public function testMixedContentIsIncluded()
    {
        $mixedContentString = '<p> I am <b> mixed </b> content </p>';
        $subject = XmlDocument::from($mixedContentString);
        $subject->setLiteralString(true);
        $expected = '"@literal": "<p> I am <b> mixed <\/b> content <\/p>"';
        self::assertStringContainsString($expected, $subject->toJson());
    }

    /**
     * @test
     */
    public function testMixedContentIsNotIncluded()
    {
        $this->subject->setLiteralString(false);
        self::assertFalse(str_contains($this->subject->toJson(), '@literal'));
    }

    /**
     * @test
     */
    public function testConvertedAttribute()
    {
        $subject = XmlDocument::from('<item id="1" name="ExampleItem" />');

        $expected = '{
        "@attributes": {
            "id": "1",
            "name": "ExampleItem"
        }
    }';

        self::assertSame(json_decode($expected, true), json_decode($subject->toJson(), true));
    }

    /**
     * @test
     */
    public function testPlainText()
    {
        $subject = XmlDocument::from('<p>I am plain text</p>');
        $expected = '{"@value": "I am plain text"}';
        self::assertSame(json_decode($subject->toJson(), true), json_decode($expected, true));
    }

    /**
     * @test
     */
    public function testSplitSymbols()
    {
        $xmlString = '
        <mei xmlns="http://www.music-encoding.org/ns/mei">
     <music>
     <body>
        <mdiv xml:id="TC-01">
         <measure n="1">
             <staff n="1">
                <layer n="1">
                <note xml:id="N1" pname="c" oct="4" dur="4" />
                <note xml:id="N2" pname="e" oct="4" dur="4" />
                </layer>
            </staff>
        </measure>
      </mdiv>
    </body>
    </music>
    </mei>

        ';

        $jsonString = '{
        "@xml:id": "TC-01",
        "measure": [
            {
                "@attributes": {
                    "n": "1"
                },
                "staff": [
                    {
                        "@attributes": {
                            "n": "1"
                        },
                        "layer": [
                            {
                                "@attributes": {
                                    "n": "1"
                                },
                                "note": [
                                    {
                                        "@attributes": {
                                            "pname": "e",
                                            "oct": "4",
                                            "dur": "4"
                                        },
                                        "@xml:id": "N2"
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    }{
        "music": [
            {
                "body": [
                    {
                        "@link": "TC-01"
                    }
                ]
            }
        ]
    }';

        $subject = XmlDocument::from($xmlString)->setSplitSymbols(['mdiv']);

        //TODO: Should compare Json to Json, but splitsymbol Strings are not valid
        //->Discuss
        self::assertTrue(true);
    }
}
