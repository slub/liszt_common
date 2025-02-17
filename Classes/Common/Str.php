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

use Illuminate\Support\Str as IlluminateStr;

class Str extends IlluminateStr
{
    public static function of($string): Stringable
    {
        return new Stringable($string);
    }
}
