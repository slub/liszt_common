<?php

namespace Slub\LisztCommon\Services;

use Elastic\Elasticsearch\Client;
use Slub\LisztCommon\Common\Collection;
use Slub\LisztCommon\Common\ElasticClientBuilder;
use Slub\LisztCommon\Common\QueryParamsBuilder;
use Slub\LisztCommon\Interfaces\ElasticSearchServiceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;



class ElasticSearchService implements ElasticSearchServiceInterface
{

    protected ?Client $client = null;
    protected string $bibIndex;
    protected string $localeIndex;

    protected array $params = [];


    // Todo: Enable Elasticsearch Security: built-in security features are not enabled.
    public function init(): bool
    {
        $this->client = ElasticClientBuilder::getClient();
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('liszt_bibliography');
        $this->bibIndex = $extConf['elasticIndexName'];
        $this->localeIndex = $extConf['elasticLocaleIndexName'];
        return true;
    }

    public function getElasticInfo(): array
    {
        $this->init();
        return ($this->client->info()->asArray());
    }

    public function search(array $searchParams, array $settings): Collection
    {
        $this->init();
        // special query params for single filter with all items (htmx)
        if (!empty($searchParams['filterShowAll'])) {
            $this->params = QueryParamsBuilder::createQueryParamsBuilder($searchParams, $settings)->getSingleFilterQueryParams();
        } else {
            $this->params = QueryParamsBuilder::createQueryParamsBuilder($searchParams, $settings)->getQueryParams();
        }

       //  print_r($this->params);

        $response = $this->client->search($this->params)->asArray();
        $aggs = $response['aggregations'];

        $sortedAggsFromSettings = Collection::wrap($this->params)->
            recursive()->
            get('body')->
            // to retain their order, we retrieve the aggs from the params
            get('aggs')->
            // retrieve their keys
            keys()->
            // and get the respective part from the response aggregations
            mapWithKeys(function ($key) use ($aggs) { return [ $key => $aggs[$key] ]; })->
            all();
        $response['aggregations'] = $sortedAggsFromSettings;

        return new Collection($response);
    }

    public function getDocumentById(string $documentId, array $settings): Collection
    {
        $this->init();
        $params = [
            'index' => $this->bibIndex,
            'id'    => $documentId,
        ];
        // exceptions handled in controller
        $response = $this->client->get($params)->asArray();
        return new Collection($response);
    }



    /**
     * Find next and previous documents using msearch (single elastic request for both queries)
     * Uses minimal query without aggregations for better performance
     * Returns document IDs along with their sort values for further navigation
     */
    public function findNavigationDocuments(array $searchParams, array $settings, array $searchAfter): array
    {
        $this->init();

        try {
            $queryBuilder = QueryParamsBuilder::createQueryParamsBuilder($searchParams, $settings);
            $baseQuery = $queryBuilder->getQueryParams();

            // Create minimal query for navigation - but include ALL filter conditions
            $minimalQuery = [
                'index' => $baseQuery['index'] ?? 'zotero',  // Todo: later add here the name of the "search All" index
                'body' => [
                    'query' => $baseQuery['body']['query'] ?? ['match_all' => (object)[]],
                    '_source' => false, // no _source data, we only need document IDs
                    'size' => 1
                ]
            ];
            if (isset($baseQuery['body']['post_filter'])) {
                $minimalQuery['body']['post_filter'] = $baseQuery['body']['post_filter'];
            }

            // Extract sort configuration from base query
            $sortConfig = $baseQuery['body']['sort'] ?? null;

            // Build sort configurations using only the sort configuration
            $normalSort = $this->buildSortArray($sortConfig, false);
            $reverseSort = $this->buildSortArray($sortConfig, true);


            // Build next document query
            $nextQuery = $minimalQuery;
            $nextQuery['body']['sort'] = $normalSort;
            $nextQuery['body']['search_after'] = $searchAfter;

            // Build previous document query
            $prevQuery = $minimalQuery;
            $prevQuery['body']['sort'] = $reverseSort;
            $prevQuery['body']['search_after'] = $searchAfter;

            // msearch for both queries in one request
            $msearchBody = [];

            $msearchBody[] = ['index' => $nextQuery['index']];
            $msearchBody[] = $nextQuery['body'];

            $msearchBody[] = ['index' => $prevQuery['index']];
            $msearchBody[] = $prevQuery['body'];

            $msearchParams = ['body' => $msearchBody];

            //     echo 'Current document search_after values: ';
            //     print_r($searchAfter);
            //     echo 'Next query with filters: ';
            //     print_r($nextQuery['body']);
            //     echo 'Previous query with filters: ';
            //     print_r($prevQuery['body']);

            $response = $this->client->msearch($msearchParams)->asArray();

            //      echo 'Response: ';
            //      print_r($response);

            $nextDocumentId = null;
            $nextSortValues = null;
            $previousDocumentId = null;
            $previousSortValues = null;

            // Extract results with sort values
            if (isset($response['responses'][0]['hits']['hits'][0])) {
                $nextHit = $response['responses'][0]['hits']['hits'][0];
                $nextDocumentId = $nextHit['_id'];
                $nextSortValues = $nextHit['sort'] ?? [];
            }
            if (isset($response['responses'][1]['hits']['hits'][0])) {
                $prevHit = $response['responses'][1]['hits']['hits'][0];
                $previousDocumentId = $prevHit['_id'];
                $previousSortValues = $prevHit['sort'] ?? [];
            }

            return [
                'nextDocumentId' => $nextDocumentId,
                'nextSortValues' => $nextSortValues,
                'previousDocumentId' => $previousDocumentId,
                'previousSortValues' => $previousSortValues
            ];

        } catch (\Exception $e) {
            error_log('Navigation documents search failed: ' . $e->getMessage());
            return [
                'nextDocumentId' => null,
                'nextSortValues' => null,
                'previousDocumentId' => null,
                'previousSortValues' => null
            ];
        }
    }



    /**
     * Build sort array for prev/next navigation
     */
    private function buildSortArray(?array $sortConfig, bool $reverse = false): array
    {
        // If no sort config provided, return null to let Elasticsearch use its default
        if (empty($sortConfig)) {
            return [];
        }

        $sort = $sortConfig;

        if ($reverse) {
            $sort = array_map(function($sortField) {
                $field = array_key_first($sortField);
                $direction = $sortField[$field]['order'] ?? 'asc';
                $newDirection = $direction === 'asc' ? 'desc' : 'asc';
                return [$field => ['order' => $newDirection]];
            }, $sort);
        }

        return $sort;
    }


}
