## Schema

### Types

Creating a Type:

```
php artisan make:relay:type UserType
```

```php
<?php

namespace App\Http\GraphQL\Type;

use App\Models\User;
use Nuwave\Relay\Support\Definition\RelayType;

class UserType extends RelayType
{
    /**
     * Attributes of Type.
     *
     * @var array
     */
    protected $attributes = [
        'name' => 'User',
        'description' => 'A user of the application.',
    ];

    /**
     * Get user by id.
     *
     * @param  string $id
     * @return User
     */
    public function resolveById($id)
    {
        return User::find($id);
    }

    /**
     * Available fields of Type.
     *
     * @return array
     */
    public function relayFields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'The primary id of the user.'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Full name of user.'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'Email address of user.'
            ]
            // ...
        ]
    }
}
```

### Queries

Create a Query:

```bash
php artisan make:relay:query UserQuery
```

```php
<?php

namespace App\Http\GraphQL\Queries;

use GraphQL;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Nuwave\Relay\Support\Definition\GraphQLQuery;

class ViewerQuery extends GraphQLQuery
{
    /**
     * Type query returns.
     *
     * @return Type
     */
    public function type()
    {
        return GraphQL::type('user');
    }

    /**
     * Available query arguments.
     *
     * @return array
     */
    public function args()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
            ]
        ];
    }

    /**
     * Resolve the query.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @return mixed
     */
    public function resolve($root, array $args)
    {
        return User::find($args['id']);
    }
}

```

### Mutations

Create a mutation:

```bash
php artisan make:relay:mutation
```

```php
<?php

namespace App\Http\GraphQL\Mutations;

use GraphQL;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\InputObjectType;
use Nuwave\Relay\Support\Definition\RelayMutation;

class UpdatePassword extends RelayMutation
{
    /**
     * Name of mutation.
     *
     * @return string
     */
    protected function name()
    {
        return 'UpdatePassword';
    }

    /**
     * Available input fields for mutation.
     *
     * @return array
     */
    public function inputFields()
    {
        return [
            'id' => [
                'type' => Type::string(),
                'rules' => ['required']
            ],
            'password' => [
                'type' => Type::string()
            ]
        ];
    }

    /**
     * Rules for mutation.
     *
     * Note: You can add your rules here or define
     * them in the inputFields
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password' => ['required', 'min:15']
        ];
    }

    /**
     * Fields that will be sent back to client.
     *
     * @return array
     */
    protected function outputFields()
    {
        return [
            'user' => [
                'type' => GraphQL::type('user'),
                'resolve' => function (User $user) {
                    return $user;
                }
            ]
        ];
    }

    /**
     * Perform data mutation.
     *
     * @param  array       $input
     * @param  ResolveInfo $info
     * @return array
     */
    protected function mutateAndGetPayload(array $input, ResolveInfo $info)
    {
        $user = User::find($input['id']);
        $user->password = \Hash::make($input['password']);
        $user->save();

        return $user;
    }
}

```

### Custom Fields

Create a custom field:

```bash
php artisan relay:make:field AvatarField
```

```php
<?php

namespace App\Http\GraphQL\Fields;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Nuwave\Relay\Support\Definition\GraphQLField;
use Nuwave\Relay\Traits\GlobalIdTrait;

class AvatarField extends GraphQLField
{
    /**
     * Field attributes.
     *
     * @var array
     */
    protected $attributes = [
        'description' => 'Avatar of user.'
    ];

    /**
     * The return type of the field.
     *
     * @return Type
     */
    public function type()
    {
        return Type::string();
    }

    /**
     * Available field arguments.
     *
     * @return array
     */
    public function args()
    {
        return [
            'width' => [
                'type' => Type::int(),
                'description' => 'The width of the picture'
            ],
            'height' => [
                'type' => Type::int(),
                'description' => 'The height of the picture'
            ]
        ];
    }

    /**
     * Resolve the field.
     *
     * @param  mixed $root
     * @param  array  $args
     * @return mixed
     */
    public function resolve($root, array $args)
    {
        $width = isset($args['width']) ? $args['width'] : 100;
        $height = isset($args['height']) ? $args['height'] : 100;

        return 'http://placehold.it/'.$root->id.'/'.$width.'x'.$height;
    }
}
```

### Schema File

The ```schema.php``` file you create is similar to Laravel's ```routes.php``` file. It used to declare your Types, Mutations and Queries to be used by GraphQL. Similar to routes, you can group your schema by namespace as well as add middleware to your Queries and Mutations.

*Be sure your file name is located in the ```relay.php``` config file*

```php
// config/relay.php

'schema' => [
    'path'   => 'Http/schema.php',
    'output' => null
],
```

```php
// app/Http/schema.php

Relay::group(['namespace' => 'App\\Http\\GraphQL', 'middleware' => 'auth'], function () {
    Relay::group(['namespace' => 'Mutations'], function () {
        Relay::mutation('createUser', 'CreateUserMutation');
    });

    Relay::group(['namespace' => 'Queries'], function () {
        Relay::query('userQuery', 'UserQuery');
    });

    Relay::group(['namespace' => 'Types'], function () {
        Relay::type('user', 'UserType');
    });
});
```
