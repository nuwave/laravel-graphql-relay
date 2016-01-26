<?php

namespace Nuwave\Relay;

use GraphQL\Executor\Executor;
use GraphQL\Error;
use GraphQL\Language\AST\Document;
use GraphQL\Language\AST\Field;
use GraphQL\Language\AST\FragmentDefinition;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\OperationDefinition;
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Schema;
use GraphQL\Type\Definition\AbstractType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Introspection;
use GraphQL\Utils;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Values;
use GraphQL\Executor\ExecutionContext;

class Context extends Executor
{
    private static $UNDEFINED;

    /**
     * Constructs a ExecutionContext object from the arguments passed to
     * execute.
     *
     * (https://github.com/webonyx/graphql-php/blob/master/src/Executor/Executor.php)
     *
     * @param  Schema $schema         [description]
     * @param  $requestString
     * @param  $rootValue
     * @param  $variableValues
     * @param  $operationName
     * @return ExecutionContext
     */
    public static function get(Schema $schema, $requestString, $rootValue = null, $variableValues = null, $operationName = null)
    {
        try {
            $source = new Source($requestString ?: '', 'GraphQL request');
            $documentAST = Parser::parse($source);
            $validationErrors = DocumentValidator::validate($schema, $documentAST);

            if (!empty($validationErrors)) {
                return null;
            } else {
                return self::generate($schema, $documentAST, $rootValue, $variableValues, $operationName);
            }
        } catch (Error $e) {
            return null;
        }
    }

    /**
     * @param  Schema   $schema
     * @param  Document $ast
     * @param  $rootValue
     * @param  $variableValues
     * @param  $operationName
     * @return ExecutionContext
     */
    protected static function generate(Schema $schema, Document $ast, $rootValue = null, $variableValues = null, $operationName = null)
    {
        if (!self::$UNDEFINED) {
            self::$UNDEFINED = new \stdClass();
        }

        if (null !== $variableValues) {
            Utils::invariant(
                is_array($variableValues) || $variableValues instanceof \ArrayAccess,
                "Variable values are expected to be array or instance of ArrayAccess, got " . Utils::getVariableType($variableValues)
            );
        }

        if (null !== $operationName) {
            Utils::invariant(
                is_string($operationName),
                "Operation name is supposed to be string, got " . Utils::getVariableType($operationName)
            );
        }

        return self::buildExecutionContext($schema, $ast, $rootValue, $variableValues, $operationName);
    }

    /**
     * @param  Schema   $schema
     * @param  Document $documentAst
     * @param  $rootValue
     * @param  $rawVariableValues
     * @param  $operationName
     * @return ExecutionContext
     */
    protected static function buildExecutionContext(Schema $schema, Document $documentAst, $rootValue, $rawVariableValues, $operationName = null)
    {
        $errors = [];
        $operations = [];
        $fragments = [];

        foreach ($documentAst->definitions as $statement) {
            switch ($statement->kind) {
                case Node::OPERATION_DEFINITION:
                    $operations[$statement->name ? $statement->name->value : ''] = $statement;
                    break;
                case Node::FRAGMENT_DEFINITION:
                    $fragments[$statement->name->value] = $statement;
                    break;
            }
        }

        if (!$operationName && count($operations) !== 1) {
            throw new Error(
                'Must provide operation name if query contains multiple operations.'
            );
        }

        $opName = $operationName ?: key($operations);

        if (empty($operations[$opName])) {
            throw new Error('Unknown operation named ' . $opName);
        }

        $operation = $operations[$opName];
        $variableValues = Values::getVariableValues($schema, $operation->variableDefinitions ?: [], $rawVariableValues ?: []);
        $exeContext = new ExecutionContext($schema, $fragments, $rootValue, $operation, $variableValues, $errors);

        return $exeContext;
    }
}
