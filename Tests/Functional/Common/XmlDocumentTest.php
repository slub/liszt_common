<?php
namespace Slub\LisztCommon\Tests\Functional\Common;

use Slub\LisztCommon\Common\XmlDocument;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 *
 * @author Matthias Richter <matthias.richter@slub-dresden.de>
 */
class PublishedItemComposerNameTest extends FunctionalTestCase
{
    const PATH = __DIR__ . '/Fixtures/';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function minimalXmlDocumentIsTranslatedCorrectly()
    {
        $xmlFilename = 'minimal.xml';
        $handle = fopen(self::PATH . $xmlFilename, 'r');
        $xmlString = fread($handle, filesize(self::PATH . $xmlFilename));
        $result = implode(" ",XmlDocument::from($xmlString)->toJson());

        $jsonFilename = 'minimal.json';
        $handle = fopen(self::PATH . $jsonFilename, 'r');
        $jsonString = fread($handle, filesize(self::PATH . $jsonFilename));

        self::assertsame(trim($jsonString), $result);
    }
}
