<?php

namespace Slub\LisztCommon\Common;

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

    public static function createQueryParamsBuilder(array $searchParams, array $settings): self
    {
        $queryParamsBuilder = new self();

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
            $indexNames = Collection::wrap($this->settings)->
                recursive()->
                get('entityTypes')->
                pluck('indexName');
            if ($indexNames->count() == 1) {
                $this->searchAll = false;
            }
            $this->indexName = $indexNames->
                join(',');
        }

        return $this;
    }

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

        if ($this->searchAll == false) {
            $this->query['body']['aggs'] = $this->getAggregations();
        }

        $this->setCommonParams();

        if (isset($this->params['page']) && $this->params['page'] !== "") {
            $this->query['from'] = ($this->params['page'] - 1) * $commonConf['itemsPerPage'];
        }
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

    private function getAggregations(): array
    {
        $settings = $this->settings;
        $index = $this->indexName;
        $filterParams = $this->params['filter'] ?? [];
        $filterTypes = $this->getFilterTypes();
        return  Collection::wrap($settings)->
            recursive()->
            get('entityTypes')->
            filter(function($entityTypes) use ($index) {
                return $entityTypes->get('indexName') === $index;
            })->
            values()->
            get(0)->
            get('filters')->
            mapWithKeys(function ($entityType) use ($filterParams, $filterTypes) {
                return self::retrieveFilterParamsForEntityType($entityType, $filterParams, $filterTypes);
            })->
            toArray();
    }

    private static function retrieveFilterParamsForEntityType(
        Collection $entityType,
        array $filterParams,
        array $filterTypes
    ): array
    {
        $entityField = $entityType['field'];
        $entityTypeKey = $entityType['key'] ?? null;
        $entityTypeMultiselect = isset($entityType['select']) && ($entityType['select'] == 'multi') ?? null; // Todo: remove this an use $filterTypes?
        $entityTypeSize = $entityType['maxSize'] ?? 10;

        // create filter in aggs for filtering aggs (without filtering the current key for multiple selections if multiselect is set)
        $filters = Collection::wrap($filterParams)->
            map(function ($value, $key) use ($entityField, $filterTypes) {
                return self::retrieveFilterParamForEntityField($key, $value, $entityField, $filterTypes);
            })->
            filter()->
            values()->
            toArray();

        // return match_all if filters are empty because elasticsearch throws an error without the filter key
        if (empty($filters)) {
            $filters = [
                ['match_all' => (object) []]
            ];
        }


        // first version of range filter (date)
        if (isset($entityType['select']) && $entityType['select'] === 'range') {
            return [
                $entityField => [
                    'aggs' => [
/*                        $entityField. '_stats' => [  // disable year stats because we get min and max year from  buckets
                            'stats' => [
                                'field' => $entityField,
                            ]
                        ],*/
                        $entityField => [
                            'terms' => [
                                'field' => $entityField,
                                'size' => 300,
                                'order' => ['_key' => 'asc'],
                            ]
                        ]
                    ],
                    'filter' => [
                        'bool' => [
                            'filter' => $filters
                        ]
                    ]
                ]
            ];
        }


        // special aggs for nested fields
        if ($entityType['type'] === 'nested') {

            // basic term options:
            $termsOptions = [
                'field' => $entityField . '.' . $entityTypeKey . '.keyword',
                'size' => $entityTypeSize
            ];

            // sort if sortByKey = 'elastic'
            if (isset($entityType['sortByKey']) && $entityType['sortByKey'] === 'elastic') {
                $termsOptions['order'] = ['_key' => 'asc'];
            }


            return [
                $entityType['field'] => [
                    'filter' => [
                        'bool' => [
                            'filter' => $filters
                        ]
                    ],
                    'aggs' => [
                        'filtered_params' => [
                            'nested' => [
                                'path' => $entityField
                            ],
                            'aggs' => [
                                $entityField => [
                                    'terms' => $termsOptions
                                ]

                            ]
                        ]
                    ]
                ]
            ];

        }

        // all other (not nested fields)

        // basic term options:
        $termsOptions = [
            'field' => $entityField . '.keyword',
            // show docs with count 0 only for multiple select fields
            'min_doc_count' => $entityTypeMultiselect ? 0 : 1,
            'size' => $entityTypeSize,
            //   'include' => 'Slowakisch|.*',

        ];

        // sort if sortByKey = 'elastic'
        if (isset($entityType['sortByKey']) && $entityType['sortByKey'] === 'elastic') {
            $termsOptions['order'] = ['_key' => 'asc'];
        }



        return [
            $entityField => [
                'aggs' => [
                    $entityField => [
                        'terms' => $termsOptions
                    ]
                ],
                'filter' => [
                    'bool' => [
                        'filter' => $filters
                    ]
                ]
            ]
        ];
    }

    private static function retrieveFilterParamForEntityField (
        string $key,
        array $values,
        string $entityField,
        array $filterTypes
    ): ?array
    {
        // exclude current key for multiple selects
        if ($key === $entityField && isset($filterTypes[$key]['multiselect'])) {
            return null;
        }
        // handle nested fields
        if (($filterTypes[$key]['type'] == 'nested') && (isset($filterTypes[$key]['key'])))  {
            return [
                'nested' => [
                    'path' => $key,
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'terms' => [ $key.'.'.$filterTypes[$key]['key'].'.keyword' => array_keys($values)]
                            ]
                        ]
                    ]
                ]
            ];
        }
        // handle range query fields
        if (isset($filterTypes[$key]['range'])) {
            return ['range' => [$key => $values]];
        }
        // handle all other fields (not nested fields)
        return ['terms' => [$key . '.keyword' => array_keys($values)]];
    }


/*    get sort field from search params or from entity */
    private function setSortField(): void {
        $sortField = $this->params['sort'] ?? null; // Todo: the url parameters still have to be adapted to the final params
        $sortDirection = $this->params['order'] ?? 'asc';
        if (!$sortField) {
            $entityTypeDefaultSortBy = Collection::wrap($this->settings)
                ->recursive()
                ->get('entityTypes')
                ->first(function ($entityType) {
                    return isset($entityType['indexName']) && $entityType['indexName'] === $this->indexName;
                });
            $sortField = $entityTypeDefaultSortBy['defaultSortBy'] ?? null;
            $sortDirection = $entityTypeDefaultSortBy['defaultSortDirection'] ?? 'asc';
        }
        if ($sortField) {
            $this->query['body']['sort'] = [
                [
                    $sortField => [
                        'order' => $sortDirection
                    ]
                ]
            ];
        }
    }


    /**
     * sets parameters needed for both search and count queries
     */
    private function setCommonParams(): void
    {
        // set index name
        $index = $this->indexName;
        $this->query['index'] = $index;

        // set body
        if (empty($this->params['searchText'])) {
            // set default sort if no fulltext Search
            $this->setSortField();
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

        $filterTypes = $this->getFilterTypes();
        $query = $this->query;
        Collection::wrap($this->params['filter'] ?? [])
            ->each(function($value, $key) use (&$query, $filterTypes) {
                if (($filterTypes[$key]['type'] == 'nested') && (isset($filterTypes[$key]['key'])))  {
                    $value = array_keys($value);

                // nested filter query (for multiple Names)
                    $query['body']['post_filter']['bool']['filter'][] = [
                        'nested' => [
                            'path' => $key,
                            'query' => [
                                'terms' => [
                                    $key.'.'.$filterTypes[$key]['key'].'.keyword' => $value
                                ]
                            ]
                        ]
                    ];

                } else if (isset($filterTypes[$key]['range'])) {
                   $query['body']['post_filter']['bool']['filter'][] = [
                        'range' => [
                            $key => [
                                $value
                            ]
                        ]
                    ];

                } else  {
                    $value = array_keys($value);
                    // post_filter, runs the search without considering the aggregations, for muliple select aggregations we run the filters again on each agg in getAggregations()
                    $query['body']['post_filter']['bool']['filter'][] = [
                        'terms' => [
                            $key . '.keyword' => $value
                            ]
                        ];
                }
            });
        $this->query = $query;

    }

    /**
     * Retrieves filter types based on the current indexName and settings from extension.
     *
     * @return array
     */
    private function getFilterTypes(): array
    {
        $filters = Collection::wrap($this->settings)
            ->recursive()
            ->get('entityTypes')
            ->filter(function ($entityType) {
                return $entityType->get('indexName') === $this->indexName;
            })
            ->values();
        if ($filters->count() === 0) {
            return [];
        }

        return $filters->get(0)
            ->get('filters')
            ->mapWithKeys(function ($filter) {
                $result = [
                    $filter['field'] => [
                        'type' => $filter['type'],
                        'key' => $filter['key'] ?? '',
                        'multiselect' => isset($filter['select']) && in_array($filter['select'], ['multi', 'range']) ? true : null,
                    ]
                ];

                if (isset($filter['select']) && $filter['select'] == 'range') {
                    $result[$filter['field']]['range'] = 1;
                }

                return $result;
            })
            ->all();

    }

}
