<?php
namespace Slub\LisztCommon\Controller;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use Slub\LisztCommon\Common\ElasticClientBuilder;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/***
 *
 * This file is part of the "Publisher Database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *	(c) 2020 Matthias Richter <matthias.richter@slub-dresden.de>, SLUB Dresden
 *
 ***/
/**
 * ClientEnabledController
 */
abstract class ClientEnabledController extends ActionController
{

    /**
     * elasticClient
     * @var Client
     */
    protected $elasticClient = null;

    /**
     * initialize show action: firing up client
     */
    public function initializeClient()
    {
        $this->elasticClient = ElasticClientBuilder::create()->
            autoconfig()->
            build();
    }

}
