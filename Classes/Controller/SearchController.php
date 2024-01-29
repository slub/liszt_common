<?php

declare(strict_types=1);

namespace Slub\LisztCommon\Controller;

class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public function indexAction(): void
    {
        $this->view->assign('foo', 'bar');
    }
}