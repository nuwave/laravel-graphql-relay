## Object Identification

Facebook Relay [Documentation](https://facebook.github.io/relay/docs/graphql-object-identification.html#content)

Facebook GraphQL [Spec](https://facebook.github.io/relay/graphql/objectidentification.htm)

To implement a GraphQL Type that adheres to the Relay Object Identification spec, make sure your type extends ```Nuwave\Relay\Support\Definition\RelayType``` and implements the ```resolveById``` and ```relayFields``` methods.

Example:

```php
<?php

namespace App\GraphQL\Types;

use GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Relay\Support\Definition\RelayType;

class CustomerType extends RelayType
{
    /**
     * Attributes of Type.
     *
     * @var array
     */
    protected $attributes = [
        'name' => 'Customer',
        'description' => 'A Customer model.',
    ];

    /**
     * Get customer by id.
     *
     * When the root 'node' query is called, it will use this method
     * to resolve the type by providing the id.
     *
     * @param  string $id
     * @return Customer
     */
    public function resolveById($id)
    {
        return Customer::find($id);
    }

    /**
     * Available fields of Type.
     *
     * @return array
     */
    public function relayFields()
    {
        return [
            // Note: You may omit the id field as it will be overwritten to adhere to
            // the NodeInterface
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'ID of the customer.'
            ],
            // ...
        ];
    }
}
```

## Connections

Facebook Relay [Documentation](https://facebook.github.io/relay/docs/graphql-connections.html#content)

Facebook GraphQL [Spec](https://facebook.github.io/relay/graphql/connections.htm)

To create a connection, simply use ```GraphQL::connection('typeName', Closure)```. We need to pass back an object that [implements the ```Illuminate\Contract\Pagination\LengthAwarePaginator``` interface](http://laravel.com/api/5.1/Illuminate/Contracts/Pagination/LengthAwarePaginator.html). In this example, we'll add it to our CustomerType we created in the Object Identification section.

*(You can omit the resolve function if you are working with an Eloquent model. The package will use the same code as show below to resolve the connection.)*

Example:

```php
<?php
// ...
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerType extends RelayType
{
    // ...

    /**
     * Available fields of Type.
     *
     * @return array
     */
    public function relayFields()
    {
        return [
            // ...
            'orders' => GraphQL::connection('order', function ($customer, array $args, ResolveInfo $info) {
                // Note: This is just an example. This type of resolve functionality may not make sense for your
                // application so just use what works best for you. However, you will need to pass back an object
                // that implements the LengthAwarePaginator as mentioned above in order for it to work with the
                // Relay connection spec.
                $orders = $customer->orders;

                if (isset($args['first'])) {
                    $total       = $orders->count();
                    $first       = $args['first'];
                    $after       = $this->decodeCursor($args);
                    $currentPage = $first && $after ? floor(($first + $after) / $first) : 1;

                    return new Paginator(
                        $orders->slice($after)->take($first),
                        $total,
                        $first,
                        $currentPage
                    );
                }

                return new Paginator(
                    $orders,
                    $orders->count(),
                    $orders->count()
                );
            }),
            // Alternatively, you can let the package resolve this connection for you
            // by passing the name of the relationship.
            'orders' => GraphQL::connection('order', 'orders')
        ];
    }
}
```
