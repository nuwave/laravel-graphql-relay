<?php

namespace Nuwave\Relay\Schema;

use Illuminate\Support\Collection;

class FieldCollection extends Collection
{
    /**
     * Get configuration formatted items.
     *
     * @return array
     */
    public function config()
    {
        return $this->map(function ($field, $key) {
            return [$key => $field['namespace']];
        })
        ->collapse()
        ->all();
    }
}
