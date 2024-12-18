<?php

namespace Slub\LisztCommon\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
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

        // ToDo: handle exceptions!
        $response = $this->client->search($this->params);
        return new Collection($response->asArray());
    }

    public function count(array $searchParams, array $settings): int
    {
        $this->init();
        $this->params = QueryParamsBuilder::createQueryParamsBuilder($searchParams, $settings)->getCountQueryParams();
        $response = $this->client->count($this->params);
        return $response['count'];
    }

}
