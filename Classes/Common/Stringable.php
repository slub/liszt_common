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

use Illuminate\Support\Stringable as IlluminateStringable;

class Stringable extends IlluminateStringable
{
    public function explode($pattern, $limit = PHP_INT_MAX): Collection
    {
        return Collection::wrap(parent::explode($pattern, $limit)->all());
    }
}
