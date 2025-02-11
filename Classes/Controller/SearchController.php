<?php

declare(strict_types=1);

namespace Slub\LisztCommon\Controller;
use Psr\Http\Message\ResponseInterface;
use Slub\LisztCommon\Interfaces\ElasticSearchServiceInterface;
use Slub\LisztCommon\Common\Paginator;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Transport\Exception\RuntimeException;

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

    public function detailsAction(array $searchParams = []): ResponseInterface
    {

        $routing = $this->request->getAttribute('routing');
        $routingArgs = $routing->getArguments();

        // Check if 'tx_lisztcommon_searchdetails' exists and if 'detailId' has a valid value.
        $documentId = null;
        if (!empty($routingArgs['tx_lisztcommon_searchdetails']['documentId'])) {
            $documentId = $routingArgs['tx_lisztcommon_searchdetails']['documentId'];
        } else {
            return $this->redirectToNotFoundPage();
        }

        try {
            $elasticResponse = $this->elasticSearchService->getDocumentById($documentId, []);
        } catch (ClientResponseException $e) {
            // Handle 404 errors
            if ($e->getCode() === 404) {
                return $this->redirectToNotFoundPage();
            }
            throw $e; // Re-throw for other client errors

        }

        $this->view->assign('searchParams', $searchParams);
        $this->view->assign('routingArgs', $routingArgs);
        $this->view->assign('detailId', $documentId);
        $this->view->assign('searchResult', $elasticResponse);

        return $this->htmlResponse();


    }


    public function redirectToNotFoundPage(): ResponseInterface
    {
    // see: https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/Extbase/Reference/Controller/ActionController.html
        // $uri could also be https://example.com/any/uri
        // $this->resourceFactory is injected as part of the `ActionController` inheritance
        return $this->responseFactory->createResponse(301)
            ->withHeader('Location', '/404/');   // the uri of the 404 page from typo installation
    }

}
