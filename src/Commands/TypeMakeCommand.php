<?php

namespace Nuwave\Relay\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;

class TypeMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:relay:type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a relay type.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Type';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/type.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return config('relay.namespaces.types');
    }
}
