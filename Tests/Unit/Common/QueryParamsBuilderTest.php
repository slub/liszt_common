<?php

namespace Slub\LisztCommon\Tests\Unit\Common;

use Slub\LisztCommon\Common\QueryParamsBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers Slub\LisztCommon\Common\QueryParamsBuilder
 */
final class QueryParamsBuilderTest extends UnitTestCase
{

    const EX_INDEX = 'ex-index';
    const EX_INDEX2 = 'ex-index2';
    const EX_VAL = 'ex-val';
    const EX_LABEL_KEY = 'ex-label-key';
    const EX_LABEL_KEY2 = 'ex-label-key2';
    const EX_EXTENSION = 'ex-extension';
    const EX_EXTENSION2 = 'ex-extension2';
    const EX_FIELD1 = 'ex-field1';
    const EX_FIELD2 = 'ex-field2';

    private QueryParamsBuilder $subject;
    private array $settings = [];
    private array $params = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new QueryParamsBuilder();
        $this->settings = [];
        $this->params = [
            'index' => self:: EX_INDEX,
            'page' => 3,
            'f_filter' => self::EX_VAL
        ];

        $confArray = [];
        $confArray['itemsPerPage'] = PaginatorTest::ITEMS_PER_PAGE;
        $extConf = $this->getAccessibleMock(ExtensionConfiguration::class, ['get'], [], '', false);
        $extConf->method('get')->
            willReturn($confArray);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extConf);

        $this->settings = [
            'entityTypes' => [
                0 => [
                    'labelKey' => self::EX_LABEL_KEY,
                    'extensionName' => self::EX_EXTENSION,
                    'indexName' => self::EX_INDEX,
                    'filters' => [
                        0 => [
                            'field' => self::EX_FIELD1,
                            'type' => 'terms'
                        ],
                        1 => [
                            'field' => self::EX_FIELD2,
                            'type' => 'nested',
                            'script' => 'example'
                        ]
                    ]
                ],
                1 => [
                    'labelKey' => self::EX_LABEL_KEY2,
                    'extensionName' => self::EX_EXTENSION2,
                    'indexName' => self::EX_INDEX2,
                    'filters' => [
                        0 => [
                            'field' => self::EX_FIELD1,
                            'type' => 'terms'
                        ],
                        1 => [
                            'field' => self::EX_FIELD2,
                            'type' => 'nested',
                            'script' => 'example'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     */
    public function emptySearchParamsAreProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([]);
        $expected = [
            'index' => self::EX_INDEX . ',' . self::EX_INDEX2,
            'body' => [
                'size' => PaginatorTest::ITEMS_PER_PAGE,
                '_source' => [
                    QueryParamsBuilder::TYPE_FIELD,
                    QueryParamsBuilder::HEADER_FIELD,
                    QueryParamsBuilder::BODY_FIELD,
                    QueryParamsBuilder::FOOTER_FIELD,
                    QueryParamsBuilder::SEARCHABLE_FIELD

                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            [ 'match_all' => new \StdClass() ]
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($this->subject->getQueryParams(), $expected);
    }
    /**
     * @test
     */
    public function matchAllQueryParamsAreCalculatedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams($this->params);
        $expected = [
            'index' => self::EX_INDEX,
            'body' => [
                'size' => PaginatorTest::ITEMS_PER_PAGE,
                '_source' => [
                    QueryParamsBuilder::TYPE_FIELD,
                    QueryParamsBuilder::HEADER_FIELD,
                    QueryParamsBuilder::BODY_FIELD,
                    QueryParamsBuilder::FOOTER_FIELD,
                    QueryParamsBuilder::SEARCHABLE_FIELD

                ],
                'aggs' => [
                    self::EX_FIELD1 => [
                        'terms' => [
                            'field' => self::EX_FIELD1 . '.keyword'
                        ]
                    ],
                    self::EX_FIELD2 => [
                        'terms' => [
                            'field' => self::EX_FIELD2 . '.keyword'
                        ]
                    ]
                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            [ 'match_all' => new \StdClass() ]
                        ],
                        'filter' => [
                            [ 'term' => [
                                    'filter.keyword' => self::EX_VAL
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($this->subject->getQueryParams(), $expected);
    }
}
