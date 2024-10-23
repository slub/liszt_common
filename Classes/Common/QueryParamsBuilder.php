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
                '_source' => ['itemType', 'title', 'creators', 'pages', 'date', 'language', 'localizedCitations'],
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
                    'must' => [[
                        'query_string' => [
                            'query' => $searchParams['searchText']
                        ]
                    ]]
                ]
            ];
        }
        return $params;
    }


}
