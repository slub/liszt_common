<?php

namespace Slub\LisztCommon\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
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
        $this->params = QueryParamsBuilder::createQueryParamsBuilder($searchParams, $settings)->getQueryParams();
//print_r($this->params);
        // ToDo: handle exceptions!
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

    // Count is not needed, we use this parameter from search request
/*    public function count(array $searchParams, array $settings): int
    {
        $this->init();
        $this->params = QueryParamsBuilder::createQueryParamsBuilder($searchParams, $settings)->getCountQueryParams();
        $response = $this->client->count($this->params);
        return $response['count'];
    }*/

}
