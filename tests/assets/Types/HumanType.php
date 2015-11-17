<?php

namespace Nuwave\Relay\Tests\Assets\Types;

use Nuwave\Relay\Types\RelayType;
use Nuwave\Relay\Tests\Assets\Data\StarWarsData;
use GraphQL\Type\Definition\Type;

class HumanType extends RelayType
{
    protected $attributes = [
        'name' => 'Human'
    ];

    /**
     * Get the identifier of the type.
     *
     * @param  mixed $obj
     * @return mixed
     */
    public function getIdentifier($obj)
    {
        return $obj['id'];
    }

    /**
     * Fetch type data by id.
     *
     * @param  string $id
     * @return mixed
     */
    public function resolveById($id)
    {
        return StarWarsData::getCharacter($id);
    }

    /**
     * Get list of available fields for type.
     *
     * @return array
     */
    protected function relayFields()
    {
        return [
            'name' => [
                'type' => Type::string()
            ],
            'homePlanet' => [
                'type' => Type::string()
            ]
        ];
    }

    /**
     * Available connections for type.
     *
     * @return array
     */
    protected function connections()
    {
        return [];
    }
}
