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
    const EX_LANG_FILE = 'ex-lang-file';
    const EX_DIRECTION = 'asc';

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
            'index' => self::EX_INDEX,
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
                    'languageFile' => self::EX_LANG_FILE,
                    'defaultFilterSize' => 10,
                    'defaultSortBy' => self::EX_FIELD1,
                    'defaultSortDirection' => self::EX_DIRECTION,
                    'sortings' => [
                        0 => [
                            'label' => self::EX_FIELD1,
                            'fields' => [
                                self::EX_FIELD1 => self::EX_DIRECTION
                            ],
                            'default' => true
                        ]
                    ],
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
                    'sortings' => [
                        0 => [
                            'label' => self::EX_FIELD1,
                            'fields' => [
                                self::EX_FIELD1 => 'desc'
                            ],
                            'default' => true
                        ]
                    ],
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
    public function indexParamIsProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self::EX_INDEX
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
                        'aggs' => [
                            self::EX_FIELD1 => [
                                'terms' => [
                                    'field' => self::EX_FIELD1 . '.keyword',
                                    'min_doc_count' => 1,
                                    'size' => 10
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'match_all' => new \StdClass() ]
                                ]
                            ]
                        ]
                    ],
                    self::EX_FIELD2 => [
                        'aggs' => [
                            self::EX_FIELD2 => [
                                'terms' => [
                                    'field' => self::EX_FIELD2 . '.keyword',
                                    'min_doc_count' => 1,
                                    'size' => 10
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'match_all' => new \StdClass() ]
                                ]
                            ]
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'aggs' => [
                            'filtered_params' => [
                                'aggs' => [
                                    self::EX_FIELD3 => [
                                        'terms' => [
                                            'field' => self::EX_FIELD3 . '..keyword',
                                            'size' => 10
                                        ]
                                    ]
                                ],
                                'nested' => [
                                    'path' => self::EX_FIELD3
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'match_all' => new \StdClass() ]
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
                ],
                'sort' => [
                    0 => [
                        self::EX_FIELD1 => [
                            'order' => self::EX_DIRECTION
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
    public function keywordFilterParamIsProcessedCorrectly(): void
    {
        $this->subject->
            setSettings($this->settings)->
            setSearchParams([
                'index' => self::EX_INDEX,
                'filter' => [
                    self::EX_FIELD2 => [
                        self::EX_VAL => 1
                    ]
                ]
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
                        'aggs' => [
                            self::EX_FIELD1 => [
                                'terms' => [
                                    'field' => self::EX_FIELD1 . '.keyword',
                                    'min_doc_count' => 1,
                                    'size' => 10
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'terms' => [
                                        self::EX_FIELD2 . '.keyword' => [ self::EX_VAL ]
                                    ] ]
                                ]
                            ]
                        ]
                    ],
                    self::EX_FIELD2 => [
                        'aggs' => [
                            self::EX_FIELD2 => [
                                'terms' => [
                                    'field' => self::EX_FIELD2 . '.keyword',
                                    'min_doc_count' => 1,
                                    'size' => 10
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'terms' => [
                                        self::EX_FIELD2 . '.keyword' => [ self::EX_VAL ]
                                    ] ]
                                ]
                            ]
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'aggs' => [
                            'filtered_params' => [
                                'aggs' => [
                                    self::EX_FIELD3 => [
                                        'terms' => [
                                            'field' => self::EX_FIELD3 . '..keyword',
                                            'size' => 10
                                        ]
                                    ]
                                ],
                                'nested' => [
                                    'path' => self::EX_FIELD3
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'terms' => [
                                        self::EX_FIELD2 . '.keyword' => [ self::EX_VAL ]
                                    ] ]
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
                ],
                'post_filter' => [
                    'bool' => [
                        'filter' => [ [
                            'terms' => [
                                self::EX_FIELD2 . '.keyword' => [ self::EX_VAL ]
                            ]
                        ] ]
                    ]
                ],
                'sort' => [
                    0 => [
                        self::EX_FIELD1 => [
                            'order' => self::EX_DIRECTION
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
                'index' => self::EX_INDEX,
                'filter' => [
                    self::EX_FIELD3 => [
                        self::EX_VAL => 1
                    ]
                ]
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
                        'aggs' => [
                            self::EX_FIELD1 => [
                                'terms' => [
                                    'field' => self::EX_FIELD1 . '.keyword',
                                    'min_doc_count' => 1,
                                    'size' => 10
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'nested' => [
                                        'path' => self::EX_FIELD3,
                                        'query' => [
                                            'bool' => [
                                                'filter' => [
                                                    'terms' => [
                                                        self::EX_FIELD3 . '..keyword' => [ self::EX_VAL ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ] ]
                                ]
                            ]
                        ]
                    ],
                    self::EX_FIELD2 => [
                        'aggs' => [
                            self::EX_FIELD2 => [
                                'terms' => [
                                    'field' => self::EX_FIELD2 . '.keyword',
                                    'min_doc_count' => 1,
                                    'size' => 10
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'nested' => [
                                        'path' => self::EX_FIELD3,
                                        'query' => [
                                            'bool' => [
                                                'filter' => [
                                                    'terms' => [
                                                        self::EX_FIELD3 . '..keyword' => [ self::EX_VAL ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ] ]
                                ]
                            ]
                        ]
                    ],
                    self::EX_FIELD3 => [
                        'aggs' => [
                            'filtered_params' => [
                                'aggs' => [
                                    self::EX_FIELD3 => [
                                        'terms' => [
                                            'field' => self::EX_FIELD3 . '..keyword',
                                            'size' => 10
                                        ]
                                    ]
                                ],
                                'nested' => [
                                    'path' => self::EX_FIELD3
                                ]
                            ]
                        ],
                        'filter' => [
                            'bool' => [
                                'filter' => [
                                    [ 'nested' => [
                                        'path' => self::EX_FIELD3,
                                        'query' => [
                                            'bool' => [
                                                'filter' => [
                                                    'terms' => [
                                                        self::EX_FIELD3 . '..keyword' => [ self::EX_VAL ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ] ]
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
                ],
                'post_filter' => [
                    'bool' => [
                        'filter' => [ [
                            'nested' => [
                                'path' => self::EX_FIELD3,
                                'query' => [
                                    'terms' => [
                                        self::EX_FIELD3 . '..keyword' => [ self::EX_VAL ]
                                    ]
                                ]
                            ]
                        ] ]
                    ]
                ],
                'sort' => [
                    0 => [
                        self::EX_FIELD1 => [
                            'order' => self::EX_DIRECTION
                        ]
                    ]
                ]
            ]
        ];

        self::assertEquals($expected, $this->subject->getQueryParams());
    }
}
