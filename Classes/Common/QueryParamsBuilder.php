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
        $bibIndex = $extConf['elasticIndexName'];
        $aggs = []; // TYPOSCRIPT stuff here

        $params = [
            'index' => $bibIndex,
            'body' => [
                'size' => 10,
                '_source' => ['itemType', 'tx_lisztcommon_header', 'tx_lisztcommon_body', 'tx_lisztcommon_footer'],
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
                    'language' => [
                        'terms' => [
                            'field' => 'language.keyword',
                        ]
                    ],
                    'journalTitle' => [
                        'terms' => [
                            'field' => 'publicationTitle.keyword',
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
            $params['body']['query'] = [
                'bool' => [
                    'must' => [
                            ['query_string' => ['query' => $searchParams['searchText']]],
                    ]
                ]
            ];
        }

        // Todo: automate the creation of parameters
        if (isset($searchParams['f_itemType']) && $searchParams['f_itemType'] !== "") {
            $params['body']['query']['bool']['filter']['term']['itemType.keyword'] = $searchParams['f_itemType'];
        }
        if (isset($searchParams['f_place']) && $searchParams['f_place'] !== "") {
            $params['body']['query']['bool']['filter']['term']['place.keyword'] = $searchParams['f_place'];
        }
        if (isset($searchParams['f_date']) && $searchParams['f_date'] !== "") {
            $params['body']['query']['bool']['filter']['term']['date.keyword'] = $searchParams['f_date'];
        }
        return $params;
    }


}
