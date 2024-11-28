<?php

declare(strict_types=1);

/*
 * This file is part of the Liszt Catalog Raisonne project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 */

namespace Slub\LisztCommon\Common;

use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class Collection extends IlluminateCollection
{
    public function join($glue, $finalGlue = '')
    {
        return Str::of(parent::join($glue, $finalGlue));
    }

    public function implode($value, $glue = null)
    {
        return Str::of(parent::implode($value, $glue));
    }

    public function recursive(): Collection
    {
        return $this->map( function($item) {
            if (is_array($item)) {
            return Collection::wrap($item)->recursive();
            }
            return $item;
        });
    }
}
