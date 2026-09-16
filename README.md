Liszt Common
============

[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-orange.svg)](https://get.typo3.org/version/12)
[![CI](https://github.com/slub/liszt_common/actions/workflows/ci.yml/badge.svg)](https://github.com/slub/liszt_common/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/slub/liszt_common)](https://github.com/slub/liszt_common/blob/main/LICENSE)

TYPO3 extension that bundles common functionality for the Liszt Portal
(digital source and works catalog for Franz Liszt): the Elasticsearch
connection, search/facet handling, and XML-to-JSON/array translation.

## Tech Stack

- PHP 8.2, TYPO3 12 (`typo3/cms-core: ^12`)
- Elasticsearch PHP client (`elasticsearch/elasticsearch: ^8`)
- `illuminate/collections` and `illuminate/support` (^11) for collection helpers
- `quellenform/t3x-iconpack` for search-result type icons
- Composer for dependency management
- PHPUnit 9 (unit + functional tests), PHPStan 1 (static analysis)
- TYPO3 `testing-framework` (^7), tests run via TYPO3 core's `Build/Scripts/runTests.sh` in Docker
- GitHub Actions CI

## Install

As a Composer-based TYPO3 extension, require it in your TYPO3 project:

```bash
composer require slub/liszt-common
```

For local development of this extension itself:

```bash
composer install
```

## Configuration

Set the extension configuration in the TYPO3 backend
(`Admin Tools` → `Settings` → `Extension Configuration` → `liszt_common`),
see `ext_conf_template.txt`:

- `elasticHostName` — Elasticsearch host URL
- `elasticCredentialsFilePath`, `elasticPwdFileName`, `elasticCaFileFilePath` — auth/CA file paths
- `paginationRange`, `itemsPerPage` — search hit list paging
- `detailPageId`, `searchPageId` — target page UIDs

To exclude the cache hash from search parameters, add
`^tx_liszt_common_searchlisting[,^search[` under
`Admin Tools` → `Settings` → `Configure Installation-Wide Options` →
`Frontend` → `[FE][cacheHash][excludedParameters]`.

## Build / Static Analysis

Requires Docker (tests run via `Build/Scripts/runTests.sh -b docker`).

```bash
composer ci:install     # install dependencies inside the test container
composer ci:php:stan    # PHPStan
```

## Running Tests

```bash
composer ci:tests:unit         # PHPUnit unit tests (Build/phpunit/UnitTests.xml)
composer ci:tests:functional   # PHPUnit functional tests (Build/phpunit/FunctionalTests.xml)
composer ci:tests              # both
composer ci                    # install + static analysis + tests (full CI pipeline)
```

CI (`.github/workflows/ci.yml`) runs PHPStan, then unit tests, then functional
tests on PHP 8.2, on every push.

## Usage Examples

### Elasticsearch-enabled controller

```php
use Slub\LisztCommon\Controller\ClientEnabledController;

class ActionController extends ClientEnabledController
{
    public function exampleAction()
    {
        $this->initializeClient();
        $params = ...;
        $entity = $this->elasticClient->search($params);
    }
}
```

### XML to array/JSON translation

```php
use Slub\LisztCommon\Common\XmlDocument;

$xmlDocument = XmlDocument::from($xmlString);
$array = $xmlDocument->toArray();
$json = $xmlDocument->toJson();
```

## Maintainer

- [Matthias Richter](https://github.com/dikastes)

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).
