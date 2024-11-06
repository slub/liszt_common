<?php

namespace Slub\LisztCommon\Common;

class QueryParamsBuilder
{

    //Todo: get Config for bibIndex, aggs etc. from extension config?
    public static function createElasticParams(string $bibIndex, array $searchParams): array
    {
        $params = [
            'index' => $bibIndex,
            'body' => [
                'size' => 10,
                '_source' => ['itemType', 'tx_lisztcommon_header', 'tx_lisztcommon_body', 'tx_lisztcommon_footer'],
                'aggs' => [
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
