<?php

namespace Nuwave\Relay\Commands;

use Symfony\Component\Console\Input\InputOption;

class QueryMakeCommand extends MakeCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:relay:query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a GraphQL/Relay query.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Query';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/query.stub';
    }

    /**
     * Get the fallback namespace from config
     *
     * @return string
     */
    public function getConfigNamespace() {
        return config('relay.namespaces.queries');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', null, InputOption::VALUE_OPTIONAL, 'Generate a Eloquent GraphQL type.'],
            ['packaged', null, InputOption::VALUE_OPTIONAL, '(Boolean) Should the Model package\'s namespace\GraphQL\Types be used?'],
        ];
    }

}
