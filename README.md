Common Tools for the Liszt Portal
=================================

[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
[![CC-BY](https://img.shields.io/github/license/dikastes/liszt_common)](https://github.com/dikastes/liszt_common/blob/main/LICENSE)

This package bundles common functionality for the Liszt Portal.
This comprises the elasticsearch connection and translation of file formats.

# Features

## ClientEnabledController

You can obtain a Controller with easy access to elasticsearch by inheriting from ClientEnabledController.

```php
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
```

## Elasticsearch Configuration

In the extension configuration, you need to specify the elasticsearch host,
password file, credentials path and HTTP CA certificate path.

## Search Hit List Configuration

In the extension configuration, you may specify how many search results are
shown in one page. For the pagination, you may set which pages before and after
the current one are shown. The first and last page are always shown. Specify a
comma separated list of numbers, which will be added and subtracted respectively
from the current page.

## Translation between XML and JSON

You can read in an XML document and translate it to a PHP array or JSON.

```php
use Slub\LisztCommon\Common\XmlDocument;
...

$xmlDocument = XmlDocument::from($xmlString);
$array = $xmlDocument->toArray();
$json = $xmlDocument->toJson();
```

# Maintainer

If you have any questions or encounter any problems, please do not hesitate to contact me.
- [Matthias Richter](https://github.com/dikastes)
