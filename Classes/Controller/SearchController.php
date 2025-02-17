<?php

declare(strict_types=1);

namespace Slub\LisztCommon\Controller;
use Psr\Http\Message\ResponseInterface;
use Slub\LisztCommon\Interfaces\ElasticSearchServiceInterface;
use Slub\LisztCommon\Common\Paginator;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

// ToDo:
// Organize the transfer of the necessary parameters (index name, fields, etc.) from the other extensions (ExtensionConfiguration?) -> see in ElasticSearchServic
// Elastic Search Index return standardized fields? Standardized search fields or own params from the respective extension?
// process search parameters from the URL query parameters to search

final class SearchController extends ClientEnabledController
{
    // set resultLimit as intern variable from $this->settings['resultLimit'];
    protected int $resultLimit;

    // Dependency Injection of Repository
    // https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/DependencyInjection/Index.html#Dependency-Injection

    public function __construct(
        private readonly ElasticSearchServiceInterface $elasticSearchService,
        protected ExtensionConfiguration $extConf
    )
    {
        $this->resultLimit = $this->settings['resultLimit'] ?? 25;
    }

    public function indexAction(array $searchParams = []): ResponseInterface
    {
        $language = $this->request->getAttribute('language');
        $locale = $language->getLocale();
        if (
            isset($searchParams['page']) &&
            (int) $searchParams['page'] > 0
        ) {
            $currentPage = (int) $searchParams['page'];
        } else {
            $currentPage = 1;
        }

    //  $totalItems = $this->elasticSearchService->count($searchParams, $this->settings);
        //$totalItems = 100;

        $elasticResponse = $this->elasticSearchService->search($searchParams, $this->settings);
        $paginator = (new Paginator())->
            setPage($currentPage)->
            setTotalItems($elasticResponse['hits']['total']['value'])->
            setExtensionConfiguration($this->extConf);
        $pagination = $paginator->getPagination();
        $showPagination = $paginator->getTotalPages() > 1 ? true : false;

        $this->view->assign('locale', $locale);
        $this->view->assign('totalItems', $elasticResponse['hits']['total']['value']);
        $this->view->assign('searchParams', $searchParams);
        $this->view->assign('searchResults', $elasticResponse);
        $this->view->assign('pagination', $pagination);
        $this->view->assign('showPagination', $showPagination);
     //   $this->view->assign('totalItems', $totalItems);
        $this->view->assign('currentString', Paginator::CURRENT_PAGE);
        $this->view->assign('dots', Paginator::DOTS);

        return $this->htmlResponse();
    }

    public function searchBarAction(array $searchParams = []): ResponseInterface
    {
        $this->view->assign('searchParams', $searchParams);
        return $this->htmlResponse();
    }

}
