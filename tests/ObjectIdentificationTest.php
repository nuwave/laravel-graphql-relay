<?php

namespace Nuwave\Relay\Tests;

use Nuwave\Relay\Tests\Assets\Types\HumanType;

class ObjectIdentificationTest extends BaseTest
{
    /**
     * @test
     */
    public function itCanResolveTypeByEncodedId()
    {
        $encodedId = base64_encode(HumanType::class . ':' . '1000');
        $query = '{node(id: "'. $encodedId .'") {id ... on Human {name}}}';
        $response = $this->graphqlResponse($query);

        $this->assertEquals([
            'id' => $encodedId,
            'name' => 'Luke Skywalker'
        ], array_get($response, 'data.node'));
    }

    /**
     * @test
     */
    public function itEncodesIdForResponse()
    {
        $name = 'Darth Vader';
        $encodedId = base64_encode(HumanType::class . ':' . '1001');
        $query = '{humanByName(name: "'. $name .'"){id, name}}';
        $response = $this->graphqlResponse($query);

        $this->assertEquals([
            'id' => $encodedId,
            'name' => $name
        ], array_get($response, 'data.humanByName'));
    }
}
