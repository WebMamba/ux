<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Builder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\BootstrapTable\Model\Table;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
interface TableBuilderInterface
{
    public function createTable(): Table;

    public function addData(array $data): self;

    public function addColumns(array $columns): self;

    public function addOptions(array $options): self;

    public function setSort(string $field, string $direction): self;

    public function setSearch(string $search): self;

    public function enablePagination(): self;

    public function setCurrentPage(int $currentPage): self;

    public function setItemPerPage(int $itemsPerPage): self;

    public function handleRequest(Request $request): self;
}
