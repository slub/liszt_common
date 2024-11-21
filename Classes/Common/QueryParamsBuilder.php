<?php

namespace Slub\LisztCommon\Common;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueryParamsBuilder
{

    //Todo: get Config for bibIndex, aggs etc. from extension config?
    public static function createElasticParams(array $searchParams): array
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('liszt_bibliography');
        $commonConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('liszt_common');
        $bibIndex = $extConf['elasticIndexName'];
        $aggs = []; // TYPOSCRIPT stuff here

        $params = [
            'index' => $bibIndex,
            'body' => [
                'size' => 10,
                '_source' => ['itemType', 'tx_lisztcommon_header', 'tx_lisztcommon_body', 'tx_lisztcommon_footer', 'tx_lisztcommon_searchable'],
                //'aggs' => $aggs
                'aggs' =>
                [
                    'itemType' => [
                        'terms' => [
                            'field' => 'itemType.keyword',
                        ]
                    ],
                    'place' => [
                        'terms' => [
                            'field' => 'place.keyword',
                        ]
                    ],
                    'date' => [
                        'terms' => [
                            'field' => 'date.keyword',
                        ]
                    ],
                    'journalTitle' => [
                        'terms' => [
                            'field' => 'publicationTitle.keyword',
                        ]
                    ],
                    'creators_name' => [
                        'nested' => [
                            'path' => 'creators',
                        ],
                        'aggs' => [
                            'names' => [
                                'terms' => [
                                    'script' => [
                                        'source' => '
                                            String firstName = doc[\'creators.firstName.keyword\'].size() > 0 ? doc[\'creators.firstName.keyword\'].value : \'\';
                                            String lastName = doc[\'creators.lastName.keyword\'].size() > 0 ? doc[\'creators.lastName.keyword\'].value : \'\';

                                            if (firstName == \'\' && lastName == \'\') {
                                                return null;
                                            }

                                            return (firstName + \' \' + lastName).trim();
                                        ',
                                        'lang' => 'painless',
                                    ],
                                    'size' => 15,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        if (!isset($searchParams['searchText']) || $searchParams['searchText'] == '') {
            $params['body']['query'] = [
                'bool' => [
                    'must' => [[
                        'match_all' => new \stdClass()
                    ]]
                ]
            ];
        } else {
            // search in field "fulltext" exakt phrase match boost over all words must contain
            $params['body']['query'] = [
                'bool' => [
                    'should' => [
                        [
                            'match_phrase' => [
                                'tx_lisztcommon_searchable' => [
                                    'query' => $searchParams['searchText'],
                                    'boost' => 2.0 // boosting for exakt phrases
                                ]
                            ]
                        ],
                        [
                            'query_string' => [
                                'query' => $searchParams['searchText'],
                                'fields' => ['fulltext'],
                                'default_operator' => 'AND'
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Todo: automate the creation of parameters
        if (isset($searchParams['f_itemType']) && $searchParams['f_itemType'] !== "") {
            $params['body']['query']['bool']['filter'][] = ['term' => ['itemType.keyword' => $searchParams['f_itemType']]];
        }
        if (isset($searchParams['f_place']) && $searchParams['f_place'] !== "") {
            $params['body']['query']['bool']['filter'][] = ['term' => ['place.keyword' => $searchParams['f_place']]];
        }
        if (isset($searchParams['f_date']) && $searchParams['f_date'] !== "") {
            $params['body']['query']['bool']['filter'][] = ['term' => ['date.keyword' => $searchParams['f_date']]];
        }
        // filter creators name, Todo: its not a filter query because they need 100% match (with spaces from f_creators_name)
        // better would be to build the field 'fullName' at build time with PHP?
        if (isset($searchParams['f_creators_name']) && $searchParams['f_creators_name'] !== "") {
            $params['body']['query']['bool']['must'][] = [
                'nested' => [
                    'path' => 'creators',
                    'query' => [
                        'match' => [
                            'creators.fullName' => $searchParams['f_creators_name']
                        ]
                    ]
                ]
            ];
        }
        if (isset($searchParams['searchParamsPage']) && $searchParams['searchParamsPage'] !== "") {
            $params['from'] = ($searchParams['searchParamsPage'] - 1) * $commonConf['itemsPerPage'];
        }

        return $params;
    }

    public static function createCountParams(array $searchParams): array
    {

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('liszt_bibliography');
        $bibIndex = $extConf['elasticIndexName'];

        $params = [
            'index' => $bibIndex,
            'body' => [ ]
        ];
        if (!isset($searchParams['searchText']) || $searchParams['searchText'] == '') {
            $params['body']['query'] = [
                'bool' => [
                    'must' => [[
                        'match_all' => new \stdClass()
                    ]]
                ]
            ];
        } else {
            // search in field "fulltext" exakt phrase match boost over all words must contain
            $params['body']['query'] = [
                'bool' => [
                    'should' => [
                        [
                            'match_phrase' => [
                                'tx_lisztcommon_searchable' => [
                                    'query' => $searchParams['searchText'],
                                    'boost' => 2.0 // boosting for exakt phrases
                                ]
                            ]
                        ],
                        [
                            'query_string' => [
                                'query' => $searchParams['searchText'],
                                'fields' => ['fulltext'],
                                'default_operator' => 'AND'
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Todo: automate the creation of parameters
        if (isset($searchParams['f_itemType']) && $searchParams['f_itemType'] !== "") {
            $params['body']['query']['bool']['filter'][] = ['term' => ['itemType.keyword' => $searchParams['f_itemType']]];
        }
        if (isset($searchParams['f_place']) && $searchParams['f_place'] !== "") {
            $params['body']['query']['bool']['filter'][] = ['term' => ['place.keyword' => $searchParams['f_place']]];
        }
        if (isset($searchParams['f_date']) && $searchParams['f_date'] !== "") {
            $params['body']['query']['bool']['filter'][] = ['term' => ['date.keyword' => $searchParams['f_date']]];
        }
        // filter creators name, Todo: its not a filter query because they need 100% match (with spaces from f_creators_name)
        // better would be to build the field 'fullName' at build time with PHP?
        if (isset($searchParams['f_creators_name']) && $searchParams['f_creators_name'] !== "") {
            $params['body']['query']['bool']['must'][] = [
                'nested' => [
                    'path' => 'creators',
                    'query' => [
                        'match' => [
                            'creators.fullName' => $searchParams['f_creators_name']
                        ]
                    ]
                ]
            ];
        }

        return $params;
    }
}
