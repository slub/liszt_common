<?php

namespace Slub\LisztCommon\Common;

use Slub\LisztCommon\Common\Collection;
use Slub\LisztCommon\Processing\IndexProcessor;
use Illuminate\Support\Str;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueryParamsBuilder
{
    const TYPE_FIELD = 'itemType';
    const HEADER_FIELD = 'tx_lisztcommon_header';
    const FOOTER_FIELD = 'tx_lisztcommon_footer';
    const BODY_FIELD = 'tx_lisztcommon_body';
    const SEARCHABLE_FIELD = 'tx_lisztcommon_searchable';

    protected array $params = [];
    protected array $settings = [];
    protected array $query = [];
    protected string $indexName = '';
    protected bool $searchAll = false;

    public static function createQueryParamsBuilder(array $searchParams, array $settings): QueryParamsBuilder
    {
        $queryParamsBuilder = new QueryParamsBuilder();

        return $queryParamsBuilder->
            setSettings($settings)->
            setSearchParams($searchParams);
    }

    public function setSettings(array $settings): QueryParamsBuilder
    {
        $this->settings = $settings;

        return $this;
    }

    public function setSearchParams($searchParams): QueryParamsBuilder
    {
        if ($this->settings == []) {
            throw new \Exception('Please pass settings to QueryParamsBuilder before setting search parameters.');
        }

        $this->params = $searchParams;

        if (isset($this->params['index'])) {
            $this->searchAll = false;
            $this->indexName = $this->params['index'];
        } else {
            $this->searchAll = true;
            $this->indexName = Collection::wrap($this->settings)->
                recursive()->
                get('entityTypes')->
                pluck('indexName')->
                join(',');
        }

        return $this;
    }

    //Todo: get Config for bibIndex, aggs etc. from extension config?
    public function getQueryParams(): array
    {
        $commonConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('liszt_common');

        $this->query = [
            'size' => $commonConf['itemsPerPage'],
            'body' => [
                '_source' => [
                    IndexProcessor::TYPE_FIELD,
                    IndexProcessor::HEADER_FIELD,
                    IndexProcessor::BODY_FIELD,
                    IndexProcessor::FOOTER_FIELD,
                    IndexProcessor::SEARCHABLE_FIELD
                ],
            ]
        ];

        //if ($this->searchAll == false) {
            $this->query['body']['aggs'] = self::getAggs($this->settings, $this->indexName);
        //}

        $this->setCommonParams();

        // Todo: automate the creation of parameters
        // filter creators name, Todo: its not a filter query because they need 100% match (with spaces from f_creators_name)
        // better would be to build the field 'fullName' at build time with PHP?
        if (isset($this->params['f_creators_name']) && $this->params['f_creators_name'] !== "") {
            $this->query['body']['query']['bool']['must'][] = [
                'nested' => [
                    'path' => 'creators',
                    'query' => [
                        'match' => [
                            'creators.fullName' => $this->params['f_creators_name']
                        ]
                    ]
                ]
            ];
        }
        if (isset($this->params['page']) && $this->params['page'] !== "") {
            $this->query['from'] = ($this->params['page'] - 1) * $commonConf['itemsPerPage'];
        }

        return $this->query;
    }

    public function getCountQueryParams(): array
    {
        $this->query = [ 'body' => [ ] ];

        $this->setCommonParams();

/*
        $params = [
            //'index' => $bibIndex,
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

*/
        return $this->query;
    }
    private function getIndexName(): string
    {
        if (isset($this->params['index'])) {
            return $this->params['index'];
        }
        return Collection::wrap($this->settings)->
            get('entityTypes')->
            pluck('indexName')->
            join(',');
    }

    private static function getAggs(array $settings, string $index): array
    {
        return Collection::wrap($settings)->
            recursive()->
            get('entityTypes')->
            filter(function($entityType) use ($index) {return $entityType->get('indexName') === $index;})->
            values()->
            get(0)->
            get('filters')->
            mapWithKeys(function($entityType) {
                if ($entityType['type'] == 'terms') {
                    return [$entityType['field'] => [
                        'terms' => [
                            'field' => $entityType['field'] . '.keyword'
                        ]
                    ]];
                }
                return [
                    $entityType['field'] => [
                        'nested' => [
                            'path' => $entityType['field']
                        ],
                        'aggs' => [
                            'names' => [
                                'terms' => [
                                    'script' => [
                                        'source' => $entityType['script'],
                                        'lang' => 'painless',
                                    ],
                                    'size' => 15,
                                ]
                            ]
                        ]
                    ]
                ];
            })->
            toArray();
    }

    private static function getFilter(array $field): array
    {
/*
        if (
            isset($field['type']) &&
            $field['type'] == 'terms'
        ) {
*/
            return [
                'term' => [
                    $field['name']. '.keyword' => $field['value']
                ]
            ];
        //}

        return [
            $field['name'] => [
                'nested' => [
                    'path' => $field['name']
                ],
                'aggs' => [
                    'names' => [
                        'terms' => [
                            'script' => [
                                'source' => $field['script'],
                                'lang' => 'painless',
                            ],
                            'size' => 15,
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * sets parameters needed for both search and count queries
     */
    private function setCommonParams(): void
    {
        // set index name
        $this->query['index'] = $this->indexName;

        // set body
        if (!isset($this->params['searchText']) || $this->params['searchText'] == '') {
            $this->query['body']['query'] = [
                'bool' => [
                    'must' => [[
                        'match_all' => new \stdClass()
                    ]]
                ]
            ];
        } else {
            // search in field "fulltext" exakt phrase match boost over all words must contain
            $this->query['body']['query'] = [
                'bool' => [
                    'should' => [
                        [
                            'match_phrase' => [
                                'tx_lisztcommon_searchable' => [
                                    'query' => $this->params['searchText'],
                                    'boost' => 2.0 // boosting for exakt phrases
                                ]
                            ]
                        ],
                        [
                            'query_string' => [
                                'query' => $this->params['searchText'],
                                'fields' => ['fulltext'],
                                'default_operator' => 'AND'
                            ]
                        ]
                    ]
                ]
            ];
        }

        // set filters
        $query = $this->query;
        Collection::wrap($this->params)->
            filter(function($_, $key) { return Str::of($key)->startsWith('f_'); })->
            each(function($value, $key) use (&$query) {
                $field = Str::of($key)->replace('f_', '')->__toString();
                $query['body']['query']['bool']['filter'][] = self::getFilter([
                    'name' => $field,
                    //'type' => $field['type'],
                    'type' => 'terms',
                    'value' => $value
                ]);
            });
        $this->query = $query;

    }

}
