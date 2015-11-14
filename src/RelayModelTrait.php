<?php

namespace Nuwave\Relay;

trait RelayModelTrait
{
    /**
     * ID Attribute mutator.
     *
     * Can be used if your Eloquent model doesn't
     * have an id field.
     *
     * @param  null $value
     * @return integer
     */
    public function getIdAttribute($value)
    {
        return $this->attributes[$this->getKeyName()];
    }
}
