<?php

namespace Slub\LisztCommon\Common;

/*
 * This file is part of the Liszt Catalog Raisonne project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 */

use Illuminate\Support\Collection;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ElasticClientBuilder extends ClientBuilder {

    protected string $caFilePath;
    protected Collection $extConf;
    protected array $hosts;
    protected string $password;

    public static function getClient(): Client
    {
        return parent::create()->
            initialize()->
            autoconfig()->
            build();
    }

    protected function initialize(): ElasticClientBuilder
    {
        $this->extConf = new Collection(
            GeneralUtility::makeInstance(ExtensionConfiguration::class)->
                get('liszt_common'));

		$this->hosts = [ $this->extConf->get('elasticHostName') ];
		$this->setCaFilePath();
		$this->setPassword();

        return $this;
    }

	protected function autoconfig (): ElasticClientBuilder {
		$this->sethosts($this->hosts);
		if ($this->password) {
			$this->setBasicAuthentication('elastic', $this->password);
		}
		if ($this->caFilePath) {
			$this->setCABundle($this->caFilePath);
		}

		return $this;
	}

    private function setCaFilePath(): void
    {
        if ($this->extConf->get('elasticCaFileFilePath') == '') {
            $this->caFilePath = '';
            return;
        }

        $this->caFilePath = $this->extConf->
            sortKeysDesc()->
            only('elasticCredentialsFilePath', 'elasticCaFileFilePath')->
            implode('/');
    }

    private function setPassword(): void
    {
		if ($this->extConf->get('elasticPwdFileName') == '') {
            $this->password = '';
            return;
		}

        $passwordFilePath = $this->extConf->
            sortKeys()->
            only('elasticCredentialsFilePath', 'elasticPwdFileName')->
            implode('/');
        $passwordFile = fopen($passwordFilePath, 'r') or
            die($passwordFilePath . ' not found. Check your extension\'s configuration');
        $size = filesize($passwordFilePath);

        $this->password = trim(fread($passwordFile, $size));
    }
}
