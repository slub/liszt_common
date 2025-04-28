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

    public function indexAction(array $searchParams = []): ResponseInterface
    {
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

        $this->view->assignMultiple([
            'locale'        => $locale,
            'totalItems'    => $totalItems,
            'searchParams'  => $searchParams,
            'searchResults' => $elasticResponse,
            'pagination'    => $pagination,
            'showPagination'=> $showPagination,
            'currentString' => Paginator::CURRENT_PAGE,
            'dots'          => Paginator::DOTS,
            'detailPageId'  => $this->extConf->get('liszt_common', 'detailPageId'),
        ]);

        return $this->htmlResponse();
    }


    public function searchBarAction(array $searchParams = []): ResponseInterface
    {
        $this->view->assign('searchParams', $searchParams);
        return $this->htmlResponse();
    }


    public function detailsHeaderAction(array $searchParams = []): ResponseInterface
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

        $this->view->assign('searchResult', $elasticResponse);
        return $this->htmlResponse();
    }


    public function detailsAction(array $searchParams = []): ResponseInterface
    {
        $documentId = $this->getDocumentIdFromRouting();
        if (!$documentId) {
            return $this->redirectToNotFoundPage();
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

        $this->view->assignMultiple([
            'searchParams' => $searchParams,
            'routingArgs'  => $this->request->getAttribute('routing')->getArguments(),
            'detailId'     => $documentId,
            'searchResult' => $elasticResponse,
            'detailPageId' => $this->extConf->get('liszt_common', 'detailPageId'),
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
}
