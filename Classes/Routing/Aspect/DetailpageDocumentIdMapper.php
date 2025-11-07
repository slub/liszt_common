<?php

declare(strict_types=1);

namespace Slub\LisztCommon\Routing\Aspect;


use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

class DetailpageDocumentIdMapper implements StaticMappableAspectInterface
{


    public function generate(string $value): ?string
    {
        // check 8 Digits Zotero id, return not found if id not match
       if (preg_match('/^[A-Z0-9]{8}$/', $value)) {
            return $value;
        }
        return null;
    }

    public function resolve(string $value): ?string
    {
        return $this->generate($value);
    }
}
