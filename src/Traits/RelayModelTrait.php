<?php

namespace Nuwave\Relay\Traits;

trait RelayModelTrait
{
    /**
     * ID Attribute mutator.
     *
     * Can be used if your Eloquent model doesn't
     * have an id field.
     *
     * @return integer
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->getKeyName()];
    }
}
