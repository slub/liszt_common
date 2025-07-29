<?php

declare(strict_types=1);

namespace Slub\LisztCommon\Controller;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Slub\LisztCommon\Interfaces\ElasticSearchServiceInterface;
use Slub\LisztCommon\Common\Paginator;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Transport\Exception\RuntimeException;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use Slub\LisztCommon\Common\PageTitleProvider;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;


final class SearchController extends ClientEnabledController
{
    // set resultLimit as intern variable from $this->settings['resultLimit'];
    protected int $resultLimit;
    private FrontendInterface $runtimeCache;


    // Dependency Injection of Repository
    // https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/DependencyInjection/Index.html#Dependency-Injection
    public function __construct(
        private readonly ElasticSearchServiceInterface $elasticSearchService,
        protected ExtensionConfiguration $extConf,
        private readonly PageTitleProvider $titleProvider,
        private readonly AssetCollector $assetCollector,
        CacheManager $cacheManager,
    ) {
        $this->resultLimit = $this->settings['resultLimit'] ?? 25;
        $this->runtimeCache = $cacheManager->getCache('runtime');
    }

    public function indexAction(): ResponseInterface
    {
        $searchParams = $this->getSearchParamsFromRequest();
        $locale = $this->request->getAttribute('language')->getLocale();
        $currentPage = $this->getCurrentPage($searchParams);
        $this->addViewTransitionStyle();

        $elasticResponse = $this->elasticSearchService->search($searchParams, $this->settings);
        $totalItems = 0;
        if (isset($elasticResponse['hits'], $elasticResponse['hits']['total'], $elasticResponse['hits']['total']['value'])) {
            $totalItems = (int)$elasticResponse['hits']['total']['value'];
        }


        $paginator = (new Paginator())
            ->setPage($currentPage)
            ->setTotalItems($totalItems)
            ->setExtensionConfiguration($this->extConf);
        $pagination = $paginator->getPagination();
        $showPagination = $paginator->getTotalPages() > 1;

        $itemsPerPage = $this->getItemsPerPage();
        $navigationBase = $this->createNavigationBase($searchParams, $totalItems, $currentPage, $itemsPerPage);

        if ($elasticResponse instanceof \Slub\LisztCommon\Common\Collection) {
            $hitsContainer = $elasticResponse->get('hits');
            if ($hitsContainer && isset($hitsContainer['hits']) && is_array($hitsContainer['hits'])) {
                $hits = $hitsContainer['hits'];

                // add navigation context to each hit
                foreach ($hits as $index => &$hit) {
                    $currentPosition = $navigationBase['startPosition'] + $index;
                    $hit['_navigationContext'] = json_encode([
                        'searchParams' => $navigationBase['searchParams'],
                        'currentPosition' => $currentPosition,
                        'totalResults' => $navigationBase['totalResults'],
                        'currentPage' => $navigationBase['currentPage'],
                        'itemsPerPage' => $navigationBase['itemsPerPage'],
                        'currentScore' => $hit['_score'] ?? null,
                        'currentSortValues' => $hit['sort'] ?? []
                    ]);
                }

                $hitsContainer['hits'] = $hits;
                $elasticResponse = $elasticResponse->put('hits', $hitsContainer);
            }
        }

        $this->addSearchParamsToBody($searchParams);

        $this->view->assignMultiple([
            'locale'        => $locale,
            'totalItems'    => $totalItems,
            'searchParams'  => $searchParams,
            'searchResults' => $elasticResponse,
            'pagination'    => $pagination,
            'showPagination'=> $showPagination,
            'currentString' => Paginator::CURRENT_PAGE,
            'dots'          => Paginator::DOTS,
            'detailPageId'  => $this->getDetailPageId(),
            'searchPageId'  => $this->getSearchPageId(),
            'navigationBase'   => $navigationBase,
            'itemsPerPage'     => $itemsPerPage,
        ]);

        return $this->htmlResponse();
    }


    public function searchBarAction(): ResponseInterface
    {
        $searchParams = $this->getSearchParamsFromRequest();
        $this->view->assignMultiple([
            'searchParams'  => $searchParams,
            'searchPageId'  => $this->getSearchPageId(),
        ]);
        return $this->htmlResponse();
    }


    public function detailsHeaderAction(): ResponseInterface
    {
        $documentId = $this->getDocumentIdFromRouting();
        if (!$documentId) {
            return $this->redirectToNotFoundPage();
        }

        try {
            $elasticResponse = $this->loadDetailPageFromElastic($documentId);
        } catch (ClientResponseException $e) {
            if ($e->getCode() === 404) {
                return $this->redirectToNotFoundPage();
            }
            throw $e;
        }

        // manage template file for detail view from extension (set in setup.typoscript of the extension)
        if (isset($this->settings['detailHeaderTemplatePath'])) {
            $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->settings['detailHeaderTemplatePath']));
        }

        $this->view->assign('searchResult', $elasticResponse);
        return $this->htmlResponse();
    }


    public function detailsAction(): ResponseInterface
    {
        $documentId = $this->getDocumentIdFromRouting();
        if (!$documentId) {
            return $this->redirectToNotFoundPage();
        }

        // Get search context from POST request if available
        $searchContext = null;

        // Check for POST data with search context
        if ($this->request->getMethod() === 'POST') {
            $postData = $this->request->getParsedBody();
            if (isset($postData['searchContext']) && is_array($postData['searchContext'])) {
                $searchContext = $postData['searchContext'];

                // Process navigation with action if available (from JS), next and prev navigation calculation
                if (isset($searchContext['navigation'])) {
                    $searchContext['navigation'] = $this->calculateNavigationContext(
                        $searchContext['navigation'],
                        $documentId
                    );
                }
            }
        }

        // If we have search context but no complete navigation data, calculate it (if nextDocumentId/previousDocumentId missed)
        if ($searchContext && isset($searchContext['navigation']) &&
            (!isset($searchContext['navigation']['nextDocumentId']) ||
                !isset($searchContext['navigation']['previousDocumentId']))) {
            $searchContext['navigation'] = $this->ensureNavigationDocumentIds(
                $searchContext['navigation'],
                $documentId
            );
        }

        try {
            $elasticResponse = $this->loadDetailPageFromElastic($documentId);
        } catch (ClientResponseException $e) {
            // Handle 404 errors
            if ($e->getCode() === 404) {
                return $this->redirectToNotFoundPage();
            }
            throw $e; // Re-throw for other client errors
        }

        // manage template file for detail view from extension (set in setup.typoscript of the extension)
        if (isset($this->settings['detailTemplatePath'])) {
            $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->settings['detailTemplatePath']));
        }

        // set page title
        $pageTitle = $this->formatPageTitle($elasticResponse['_source']['title'] ?? 'Details');
        $this->titleProvider->setTitle($pageTitle);

        // set meta description
        $metaDescription = '';
        if (isset($elasticResponse['_source'], $elasticResponse['_source']['tx_lisztcommon_searchable'])) {
            $metaDescription = (string)$elasticResponse['_source']['tx_lisztcommon_searchable'];
        }
        $metaTagManager = GeneralUtility::makeInstance(MetaTagManagerRegistry::class)
            ->getManagerForProperty('description');
        $metaTagManager->addProperty('description', $metaDescription);

        $this->addViewTransitionStyle();

        if ($searchContext) {
            $this->addSearchParamsToBody($searchContext);
        }

        $this->view->assignMultiple([
            'routingArgs'  => $this->request->getAttribute('routing')->getArguments(),
            'detailId'     => $documentId,
            'searchResult' => $elasticResponse,
            'detailPageId'  => $this->getDetailPageId(),
            'searchPageId'  => $this->getSearchPageId(),
            'searchContext'    => $searchContext,
        ]);

        return $this->htmlResponse();
    }


    private function getCurrentPage(array $params): int
    {
        return (isset($params['page']) && (int)$params['page'] > 0) ? (int)$params['page'] : 1;
    }


    // add view transition styles as inline style for animation
    private function addViewTransitionStyle(): void
    {
        $this->assetCollector->addInlineStyleSheet(
            'view-transitions-root',
            '@media screen and (prefers-reduced-motion: no-preference) { @view-transition { navigation: auto; } }',
            [],
            ['priority' => true, 'media' => 'screen']
        );
    }

    private function getDocumentIdFromRouting(): ?string
    {
        $routingArgs = $this->request->getAttribute('routing')->getArguments();
        return $routingArgs['tx_lisztcommon_searchdetails']['documentId'] ?? null;
    }

    private function formatPageTitle(string $title): string
    {
        $maxTitleLength = 50;
        return (mb_strlen($title) > $maxTitleLength)
            ? mb_substr($title, 0, $maxTitleLength - 3) . '...'
            : $title;
    }

    private function loadDetailPageFromElastic(string $documentId): Collection
    {
        $cachedResult = $this->runtimeCache->get($documentId);
        if (!$cachedResult instanceof Collection) {
            $result = $this->elasticSearchService->getDocumentById($documentId, []);
            if (!$result instanceof Collection) {
                throw new \UnexpectedValueException("The return value of getDocumentById does not correspond to the expected collection type.");            }
            $this->runtimeCache->set($documentId, $result);
            return $result;
        }
        return $cachedResult;
    }



    public function redirectToNotFoundPage(): ResponseInterface
    {
        // see: https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/Extbase/Reference/Controller/ActionController.html
        // $uri could also be https://example.com/any/uri
        // $this->resourceFactory is injected as part of the `ActionController` inheritance
        return $this->responseFactory->createResponse(301)
            ->withHeader('Location', '/404/');   // the uri of the 404 page from typo installation
    }

    public function loadAllFilterItemsAction(array $searchParams = []): ResponseInterface
    {
        // The loadAllFilterItemsAction method receives its searchParams directly from the controller mapping
        // since it is called via f:uri.action in FilterBlock.html

        // check if this is an  HTMX Request
        if (!$this->request->getHeader('HX-Request')) {
        return $this->responseFactory->createResponse(403)
        ->withHeader('Content-Type', 'text/html')
        ->withBody($this->streamFactory->createStream('Missing Header in Request'));
        }


        $locale = $this->request->getAttribute('language')->getLocale();

        // return 400 if filterShowAll is not set (with HX-Trigger for HTMX Requests)
        if (empty($searchParams['filterShowAll'])) {
            $response = $this->responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'text/html')
                ->withBody($this->streamFactory->createStream('Missing required parameter: filterShowAll'));

            // Add HTMX-specific header for better client-side error handling
            if ($this->request->getHeader('HX-Request')) {
                $response = $response->withHeader('HX-Trigger', '{"showError": "Missing required parameter"}');
            }
            return $response;
        }

        $elasticResponse = $this->elasticSearchService->search($searchParams, $this->settings);

        $this->view->assignMultiple([
            'locale'        => $locale,
            'searchParams'  => $searchParams,
            'searchResults' => $elasticResponse,
        ]);

        // path can be set in an variable like in setup.typoscript of the extension if needed
        $this->view->setTemplatePathAndFilename('EXT:liszt_common/Resources/Private/Templates/Search/HtmxFilterBlock.html');
        $this->view->setLayoutRootPaths([]); // deactivate Layout

        return $this->htmlResponse();
    }

    /**
     * Get search parameters from both old and new namespace for compatibility
     */
    private function getSearchParamsFromRequest(): array
    {
        // Optional: Filter/validate known parameters only?
        $queryParams = $this->request->getQueryParams();

        // Try new short namespace first
        if (isset($queryParams['search']) && is_array($queryParams['search'])) {
            return $queryParams['search'];
        }

        // Fallback to original Extbase namespace
        if (isset($queryParams['tx_liszt_common_searchlisting']) && is_array($queryParams['tx_liszt_common_searchlisting'])) {
            return $queryParams['tx_liszt_common_searchlisting'];
        }

        // Fallback to Extbase request arguments (for backward compatibility)
        return $this->request->getArguments();
    }

    public function addSearchParamsToBody($searchParams): void
    {
        $jsonData = json_encode($searchParams);

        // NUR JSON-Daten, KEINE <script>-Tags
        // AssetCollector fügt automatisch <script>-Tags hinzu
        $this->assetCollector->addInlineJavaScript(
            'search-params-data',
            'window.searchParamsData = ' . $jsonData . ';',
            [],
            ['priority' => false]
        );
    }


    /**
     * Get items per page from extension configuration
     */
    private function getItemsPerPage(): int
    {
        return (int)($this->extConf->get('liszt_common', 'itemsPerPage') ?? 10);
    }

    /**
     * Get detail page ID from extension configuration as integer
     */
    private function getDetailPageId(): int
    {
        return (int)($this->extConf->get('liszt_common', 'detailPageId') ?? 0);
    }

    /**
     * Get search page ID from extension configuration as integer
     */
    private function getSearchPageId(): int
    {
        return (int)($this->extConf->get('liszt_common', 'searchPageId') ?? 0);
    }

    private function getNavigationDocumentIds(string $currentDocumentId, array $searchContext): array
    {
        if (!isset($searchContext['navigation']['currentSortValues']) ||
            empty($searchContext['navigation']['currentSortValues'])) {
            return [
                'nextDocumentId' => null,
                'nextSortValues' => null,
                'previousDocumentId' => null,
                'previousSortValues' => null
            ];
        }

        // Handle both array and JSON string formats
        $currentSortValues = $searchContext['navigation']['currentSortValues'];
        if (is_string($currentSortValues)) {
            $currentSortValues = json_decode($currentSortValues, true);
        }

        if (!is_array($currentSortValues)) {
            echo 'Error: currentSortValues is not array: ';
            print_r($searchContext['navigation']['currentSortValues']);
            return [
                'nextDocumentId' => null,
                'nextSortValues' => null,
                'previousDocumentId' => null,
                'previousSortValues' => null
            ];
        }

        echo 'Current document sort values: ';
        print_r($currentSortValues);

        // Create search parameters without navigation data
        $searchParams = $searchContext;
        unset($searchParams['navigation']);

        try {
            return $this->elasticSearchService->findNavigationDocuments(
                $searchParams,
                $this->settings,
                $currentSortValues
            );
        } catch (\Exception $e) {
            // Log error but don't break the detail page
            error_log('Navigation search failed: ' . $e->getMessage());
            return [
                'nextDocumentId' => null,
                'nextSortValues' => null,
                'previousDocumentId' => null,
                'previousSortValues' => null
            ];
        }
    }



    /**
     * Create navigation base data for search results
     */
    private function createNavigationBase(array $searchParams, int $totalItems, int $currentPage, int $itemsPerPage): array
    {
        $startPosition = ($currentPage - 1) * $itemsPerPage;

        return [
            'searchParams' => $searchParams,
            'totalResults' => $totalItems,
            'currentPage' => $currentPage,
            'itemsPerPage' => $itemsPerPage,
            'startPosition' => $startPosition
        ];
    }


    /**
     * Calculate navigation context based on current navigation data and document ID
     * This method should preserve all search parameters (filter, searchText, sort)
     * and update navigation-specific data
     */
    private function calculateNavigationContext(array $navigationData, string $documentId): array
    {
        // Get the action from navigation data if available
        $action = $navigationData['action'] ?? null;

        if (!$action || !in_array($action, ['next', 'previous'])) {
            return $navigationData;
        }

        // Extract search parameters from the current request/context
        $searchParams = [];

        // Get all search context from POST data if available
        if ($this->request->getMethod() === 'POST') {
            $postData = $this->request->getParsedBody();
            if (isset($postData['searchContext']) && is_array($postData['searchContext'])) {
                $fullContext = $postData['searchContext'];

                // Extract search parameters (filter, searchText, sort)
                $searchParams = array_filter($fullContext, function($key) {
                    return in_array($key, ['filter', 'searchText', 'sort']);
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        $originalSortValues = [];
        if (isset($navigationData['currentSortValues'])) {
            if (is_string($navigationData['currentSortValues'])) {
                $originalSortValues = json_decode($navigationData['currentSortValues'], true) ?: [];
            } else if (is_array($navigationData['currentSortValues'])) {
                $originalSortValues = $navigationData['currentSortValues'];
            }
        }

        // echo "Using ORIGINAL sort values for navigation calculation: ";
        // print_r($originalSortValues);

        // Use ElasticSearchService to find navigation documents based on ORIGINAL document
        $navigationDocuments = $this->elasticSearchService->findNavigationDocuments(
            $searchParams,
            $this->settings,
            $originalSortValues
        );

/*        echo "Navigation documents found based on original position - Next: " .
            ($navigationDocuments['nextDocumentId'] ?? 'none') .
            ", Previous: " . ($navigationDocuments['previousDocumentId'] ?? 'none');*/

        // Update navigation data based on the action
        if ($action === 'previous') {
            // Decrease position by 1
            $navigationData['currentPosition'] = max(0, (int)$navigationData['currentPosition'] - 1);

            // Use the previousDocumentId as our new "next"
            // and the current document (documentId) as the new reference point
            $navigationData['nextDocumentId'] = $navigationDocuments['previousDocumentId']; // The document we came FROM
            $navigationData['nextSortValues'] = $originalSortValues; // Original sort values

            // For the new previous, we need to find what comes before the previous document
            if ($navigationDocuments['previousDocumentId']) {
                $newCurrentSortValues = $navigationDocuments['previousSortValues'] ?? [];
                $secondLevelNavigation = $this->elasticSearchService->findNavigationDocuments(
                    $searchParams,
                    $this->settings,
                    $newCurrentSortValues
                );
                $navigationData['previousDocumentId'] = $secondLevelNavigation['previousDocumentId'];
                $navigationData['previousSortValues'] = $secondLevelNavigation['previousSortValues'];
                $navigationData['currentSortValues'] = $newCurrentSortValues;
            } else {
                // We're at the first document
                $navigationData['previousDocumentId'] = null;
                $navigationData['previousSortValues'] = [];
                // Get the current document's sort values
                try {
                    $currentDocument = $this->elasticSearchService->getDocumentById($documentId, $this->settings);
                    $searchResult = $this->elasticSearchService->search(
                        array_merge($searchParams, ['ids' => [$documentId]]),
                        $this->settings,
                        0,
                        1
                    );
                    if (isset($searchResult['hits']['hits'][0]['sort'])) {
                        $navigationData['currentSortValues'] = $searchResult['hits']['hits'][0]['sort'];
                    }
                } catch (\Exception $e) {
                    error_log('Could not get current document sort values: ' . $e->getMessage());
                }
            }

            // Update has flags
            $navigationData['hasNext'] = $navigationData['nextDocumentId'] !== null;
            $navigationData['hasPrevious'] = $navigationData['previousDocumentId'] !== null;

        } else if ($action === 'next') {
            // Increase position by 1
            $navigationData['currentPosition'] = (int)$navigationData['currentPosition'] + 1;

            // Use the nextDocumentId as our new "previous"
            // and the current document (documentId) as the new reference point
            $navigationData['previousDocumentId'] = $navigationDocuments['nextDocumentId'];
            $navigationData['previousSortValues'] = $originalSortValues;

            if ($navigationDocuments['nextDocumentId']) {
                // Find what comes after the document we just navigated to
                $newCurrentSortValues = $navigationDocuments['nextSortValues'] ?? [];
                $secondLevelNavigation = $this->elasticSearchService->findNavigationDocuments(
                    $searchParams,
                    $this->settings,
                    $newCurrentSortValues
                );
                $navigationData['nextDocumentId'] = $secondLevelNavigation['nextDocumentId'];
                $navigationData['nextSortValues'] = $secondLevelNavigation['nextSortValues'];
                $navigationData['currentSortValues'] = $newCurrentSortValues;
            } else {
                // We're at the last document
                $navigationData['nextDocumentId'] = null;
                $navigationData['nextSortValues'] = [];
                // Get the current document's sort values
                try {
                    $currentDocument = $this->elasticSearchService->getDocumentById($documentId, $this->settings);
                    $searchResult = $this->elasticSearchService->search(
                        array_merge($searchParams, ['ids' => [$documentId]]),
                        $this->settings,
                        0,
                        1
                    );
                    if (isset($searchResult['hits']['hits'][0]['sort'])) {
                        $navigationData['currentSortValues'] = $searchResult['hits']['hits'][0]['sort'];
                    }
                } catch (\Exception $e) {
                    error_log('Could not get current document sort values: ' . $e->getMessage());
                }
            }

            // Update has flags
            $navigationData['hasNext'] = $navigationData['nextDocumentId'] !== null;
            $navigationData['hasPrevious'] = $navigationData['previousDocumentId'] !== null;
        }

        // Recalculate currentPage based on new currentPosition
        $itemsPerPage = $navigationData['itemsPerPage'] ?? $this->getItemsPerPage();
        $newCurrentPage = (int)floor($navigationData['currentPosition'] / $itemsPerPage) + 1;

/*        echo "Recalculating page: Position " . $navigationData['currentPosition'] .
            " with " . $itemsPerPage . " items per page = Page " . $newCurrentPage;*/

        $navigationData['currentPage'] = $newCurrentPage;

        // Position sanity check
        if ($navigationData['currentPosition'] > 0 && !$navigationData['hasPrevious']) {
            echo "Warning: Position suggests hasPrevious should be true, but no previousDocumentId found";
        }

        // Remove the action after processing
        unset($navigationData['action']);

        return $navigationData;
    }


    /**
     * Ensure navigation document IDs are present in the context
     * This is needed when no specific action is provided but we still need the document IDs for buttons
     */
    private function ensureNavigationDocumentIds(array $navigationContext, string $currentDocumentId): array
    {
        // Check if we already have the navigation document IDs
        if (isset($navigationContext['nextDocumentId']) && isset($navigationContext['previousDocumentId'])) {
            return $this->addBasicNavigationHelpers($navigationContext);
        }

        // We need to fetch the navigation document IDs
        // Extract search parameters from POST data
        $searchParams = [];
        $postData = $this->request->getParsedBody();
        if (isset($postData['searchContext']) && is_array($postData['searchContext'])) {
            $fullContext = $postData['searchContext'];

            // Extract search parameters (filter, searchText, sort)
            $searchParams = array_filter($fullContext, function($key) {
                return in_array($key, ['filter', 'searchText', 'sort']);
            }, ARRAY_FILTER_USE_KEY);
        }

        // Get current document's sort values for search_after
        $currentSortValues = [];
        if (isset($navigationContext['currentSortValues'])) {
            if (is_string($navigationContext['currentSortValues'])) {
                $currentSortValues = json_decode($navigationContext['currentSortValues'], true) ?: [];
            } else if (is_array($navigationContext['currentSortValues'])) {
                $currentSortValues = $navigationContext['currentSortValues'];
            }
        }

        if (empty($currentSortValues)) {
            // If no sort values available, we can't determine navigation
            echo 'Warning: No currentSortValues available for navigation calculation';
            return $this->addBasicNavigationHelpers($navigationContext);
        }

        // Use ElasticSearchService to find navigation documents
        try {
            $navigationDocs = $this->elasticSearchService->findNavigationDocuments(
                $searchParams,
                $this->settings,
                $currentSortValues
            );

            // Add the navigation document IDs to the context
            $navigationContext['nextDocumentId'] = $navigationDocs['nextDocumentId'];
            $navigationContext['nextSortValues'] = $navigationDocs['nextSortValues'] ?? [];
            $navigationContext['previousDocumentId'] = $navigationDocs['previousDocumentId'];
            $navigationContext['previousSortValues'] = $navigationDocs['previousSortValues'] ?? [];

            echo 'Navigation documents found - Next: ' . ($navigationDocs['nextDocumentId'] ?? 'none') .
                ', Previous: ' . ($navigationDocs['previousDocumentId'] ?? 'none');

        } catch (\Exception $e) {
            echo 'Error fetching navigation documents: ' . $e->getMessage();
            // Set empty values on error
            $navigationContext['nextDocumentId'] = null;
            $navigationContext['nextSortValues'] = [];
            $navigationContext['previousDocumentId'] = null;
            $navigationContext['previousSortValues'] = [];
        }

        return $this->addBasicNavigationHelpers($navigationContext);
    }

    /**
     * Add basic navigation helpers without action-based calculations
     */
    private function addBasicNavigationHelpers(array $navigationContext): array
    {
        $currentPosition = (int)($navigationContext['currentPosition'] ?? 0);
        $totalResults = (int)($navigationContext['totalResults'] ?? 0);

        // Set navigation flags based on document availability
        $navigationContext['hasNext'] = !empty($navigationContext['nextDocumentId']);
        $navigationContext['hasPrevious'] = !empty($navigationContext['previousDocumentId']);

        // Also check position-based logic as fallback
        if (!$navigationContext['hasNext'] && ($currentPosition + 1) < $totalResults) {
            echo 'Warning: Position suggests hasNext should be true, but no nextDocumentId found';
        }
        if (!$navigationContext['hasPrevious'] && $currentPosition > 0) {
            echo 'Warning: Position suggests hasPrevious should be true, but no previousDocumentId found';
        }

        return $navigationContext;
    }






}


