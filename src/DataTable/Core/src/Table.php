<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Core;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @final
 */
class Table implements TableInterface
{
    private $data;
    private $columns;
    private $options;
    private $renderer;

    public function __construct($renderer, $data = [], $columns = [], $options = [])
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->options = $options;
        $this->renderer = $renderer;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setColumns(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getRenderer(): string
    {
        return $this->renderer;
    }

    public function renderView(): array
    {
        return [
            'rows' => $this->data,
            'columns' => $this->columns,
            'options' => $this->options,
        ];
    }
}
