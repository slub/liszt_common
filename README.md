Common Tools for the Liszt Portal
=================================

[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
[![CC-BY](https://img.shields.io/github/license/dikastes/liszt_common)](https://github.com/dikastes/liszt_common/blob/main/LICENSE)

This package bundles common functionality for the Liszt Portal.
This comprises the elasticsearch connection and translation of file formats.

# Features

## ClientEnabledController

You can obtain a Controller with easy access to elasticsearch by inheriting from ClientEnabledController.

    use Slub\LisztCommon\Controller\ClientEnabledController;

    class ActionController extends ClientEnabledController
    {
    
      public function ExampleAction()
      {
        $this->initializeClient();
        $params = ...
        $entity = $this->elasticClient->search($params);
        ...
        
      }

    }

# Maintainer

If you have any questions or encounter any problems, please do not hesitate to contact me.
- [Matthias Richter](https://github.com/dikastes)
