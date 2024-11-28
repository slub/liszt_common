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

namespace Slub\LisztCommon\Processing;

abstract class IndexProcessor
{

    const TYPE_FIELD = 'itemType';
    const HEADER_FIELD = 'tx_lisztcommon_header';
    const FOOTER_FIELD = 'tx_lisztcommon_footer';
    const BODY_FIELD = 'tx_lisztcommon_body';
    const SEARCHABLE_FIELD = 'tx_lisztcommon_searchable';
    const BOOSTED_FIELD = 'tx_lisztcommon_boosted';
}
