<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Core\Tests\Kernel;

use Symfony\UX\DataTable\Core\AbstractTableBuilder;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class TestDataBuilder extends AbstractTableBuilder
{
    public function getRenderer(): string
    {
        return 'test';
    }
}
