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
    const EX_FIELD3 = 'ex-field3';
    const EX_PAGE = 3;
    const EX_SCRIPT = 'ex-script';
    const EX_PATH = 'ex-path';

    private QueryParamsBuilder $subject;
    private array $settings = [];
    private array $params = [];
    private ?ExtensionConfiguration $extConf = null;

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
        $this->extConf = $this->getAccessibleMock(ExtensionConfiguration::class, ['get'], [], '', false);
        $this->extConf->method('get')->
            willReturn($confArray);

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
                            'type' => 'keyword',
                        ],
                        2 => [
                            'field' => self::EX_FIELD3,
                            'type' => 'nested',
                            'script' => self::EX_SCRIPT,
                            'path' => self::EX_PATH
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
                            'type' => 'keyword'
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
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extConf);

        $expected = [
            'index' => self::EX_INDEX . ',' . self::EX_INDEX2,
            'size' => PaginatorTest::ITEMS_PER_PAGE,
            'body' => [
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

        self::assertEquals($expected, $this->subject->getQueryParams());
    }

    /**
     * @test
     */
    public function IndexParamIsProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self:: EX_INDEX
            ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extConf);

        $expected = [
            'index' => self::EX_INDEX,
            'size' => PaginatorTest::ITEMS_PER_PAGE,
            'body' => [
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
                            'field' => self::EX_FIELD2
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'nested' => [
                            'path' => self::EX_FIELD3
                        ],
                        'aggs' => [
                            'names' => [
                                'terms' => [
                                    'script' => [
                                        'source' => self::EX_SCRIPT,
                                        'lang' => 'painless'
                                    ],
                                    'size' => 15
                                ]
                            ]
                        ]
                    ]
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

        self::assertEquals($expected, $this->subject->getQueryParams());
    }

    /**
     * @test
     */
    public function pageParamIsProcessedCorrectly(): void
    {

        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'page' => self::EX_PAGE
            ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extConf);

        $expected = [
            'index' => self::EX_INDEX . ',' . self::EX_INDEX2,
            'size' => PaginatorTest::ITEMS_PER_PAGE,
            'from' => PaginatorTest::ITEMS_PER_PAGE * (self::EX_PAGE - 1),
            'body' => [
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

        self::assertEquals($expected, $this->subject->getQueryParams());
    }

    /**
     * @test
     */
    public function termsFilterParamIsProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self:: EX_INDEX,
                'f_' . self::EX_FIELD1 => self::EX_VAL
            ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extConf);

        $expected = [
            'index' => self::EX_INDEX,
            'size' => PaginatorTest::ITEMS_PER_PAGE,
            'body' => [
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
                            'field' => self::EX_FIELD2
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'nested' => [
                            'path' => self::EX_FIELD3
                        ],
                        'aggs' => [
                            'names' => [
                                'terms' => [
                                    'script' => [
                                        'source' => self::EX_SCRIPT,
                                        'lang' => 'painless'
                                    ],
                                    'size' => 15
                                ]
                            ]
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
                                    self::EX_FIELD1 . '.keyword' => self::EX_VAL
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($expected, $this->subject->getQueryParams());
    }

    /**
     * @test
     */
    public function keywordFilterParamIsProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self:: EX_INDEX,
                'f_' . self::EX_FIELD2 => self::EX_VAL
            ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extConf);

        $expected = [
            'index' => self::EX_INDEX,
            'size' => PaginatorTest::ITEMS_PER_PAGE,
            'body' => [
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
                            'field' => self::EX_FIELD2
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'nested' => [
                            'path' => self::EX_FIELD3
                        ],
                        'aggs' => [
                            'names' => [
                                'terms' => [
                                    'script' => [
                                        'source' => self::EX_SCRIPT,
                                        'lang' => 'painless'
                                    ],
                                    'size' => 15
                                ]
                            ]
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
                                    self::EX_FIELD2 => self::EX_VAL
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($expected, $this->subject->getQueryParams());
    }

    /**
     * @test
     */
    public function nestedFilterParamIsProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self:: EX_INDEX,
                'f_' . self::EX_FIELD3 => self::EX_VAL
            ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extConf);

        $expected = [
            'index' => self::EX_INDEX,
            'size' => PaginatorTest::ITEMS_PER_PAGE,
            'body' => [
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
                            'field' => self::EX_FIELD2
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'nested' => [
                            'path' => self::EX_FIELD3
                        ],
                        'aggs' => [
                            'names' => [
                                'terms' => [
                                    'script' => [
                                        'source' => self::EX_SCRIPT,
                                        'lang' => 'painless'
                                    ],
                                    'size' => 15
                                ]
                            ]
                        ]
                    ]
                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            [ 'match_all' => new \StdClass() ]
                        ],
                        'filter' => [
                            [ 'nested' => [
                                    'path' => self::EX_FIELD3,
                                    'query' => [
                                        'match' => [
                                            self::EX_FIELD3 . '.' . self::EX_PATH => self::EX_VAL
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($expected, $this->subject->getQueryParams());
    }

    /**
     * @test
     */
    public function countQueryIsBuiltCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self:: EX_INDEX,
                'f_' . self::EX_FIELD1 => self::EX_VAL
            ]);

        $expected = [
            'index' => self::EX_INDEX,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [ 'match_all' => new \StdClass() ]
                        ],
                        'filter' => [
                            [ 'term' => [
                                    self::EX_FIELD1 . '.keyword' => self::EX_VAL
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($expected, $this->subject->getCountQueryParams());
    }
}
