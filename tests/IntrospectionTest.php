<?php

namespace Nuwave\Relay\Tests;

class IntrospectionTest extends BaseTest
{
    /**
     * @test
     */
    public function itAcceptsIntrospectionRequestForNodeInterface()
    {
        $query = '{
          __type(name: "Node") {
            name
            kind
            fields {
              name
              type {
                kind
                ofType {
                  name
                  kind
                }
              }
            }
          }
        }';
        $expected = [
            '__type' => [
                'name' => 'Node',
                'kind' => 'INTERFACE',
                'fields' => [[
                    'name' => 'id',
                    'type' => [
                        'kind' => 'NON_NULL',
                        'ofType' => [
                            'name' => 'ID',
                            'kind' => 'SCALAR'
                        ]
                    ]
                ]]
            ]
        ];

        $response = $this->graphqlResponse($query);

        $this->assertEquals($expected, $response['data']);
    }

    /**
     * @test
     */
    public function itAcceptsIntrospectionRequestForNodeQuery()
    {
        $query = '{
          __schema {
            queryType {
              fields {
                name
                type {
                  name
                  kind
                }
                args {
                  name
                  type {
                    kind
                    ofType {
                      name
                      kind
                    }
                  }
                }
              }
            }
          }
        }';
        $response = $this->graphqlResponse($query);
        $fields = array_get($response, 'data.__schema.queryType.fields');

        $nodeField = array_first($fields, function ($key, $value) {
            return $value['name'] == 'node';
        });

        $this->assertEquals([
            'name' => 'node',
            'type' => [
                'name' => 'Node',
                'kind' => 'INTERFACE'
            ],
            'args' => [[
                'name' => 'id',
                'type' => [
                    'kind' => 'NON_NULL',
                    'ofType' => [
                        'name' => 'ID',
                        'kind' => 'SCALAR'
                    ]
                ]
            ]]
        ], $nodeField);
    }
}
