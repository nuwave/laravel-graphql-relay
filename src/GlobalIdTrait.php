<?php

namespace Nuwave\Relay;

trait GlobalIdTrait
{
    /**
     * Create global id.
     *
     * @param  string $type
     * @param  string|integer $id
     * @return string
     */
    public function encodeGlobalId($type, $id)
    {
        return base64_encode($type . ':' . $id);
    }

    /**
     * Decode the global id.
     *
     * @param  string $id
     * @return array
     */
    public function decodeGlobalId($id)
    {
        return explode(":", base64_decode($id));
    }
}
