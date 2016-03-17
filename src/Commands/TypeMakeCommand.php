<?php

namespace Nuwave\Relay\Commands;

use ReflectionClass;
use Nuwave\Relay\Support\Definition\EloquentType;
use Symfony\Component\Console\Input\InputOption;

class TypeMakeCommand extends MakeCommandBase
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
    protected $description = 'Generate a GraphQL/Relay type.';

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
        $modifiedNamespace = parent::getDefaultNamespace($rootNamespace);
        if($modifiedNamespace) return $modifiedNamespace;
        else return config('relay.namespaces.types');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($model = $this->option('model')) {
            $this->setViewPath();
            $stub = $this->getEloquentStub($model);
        } else {
            $stub = $this->files->get($this->getStub());
        }

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
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

    /**
     * Set config view paths.
     *
     * @return void
     */
    protected function setViewPath()
    {
        $paths = config('view.paths');
        $paths[] = realpath(__DIR__.'/stubs');

        config(['view.paths' => $paths]);
    }

    /**
     * Generate stub from eloquent type.
     *
     * @param  string $model
     * @return string
     */
    protected function getEloquentStub($model)
    {
        $shortName = $model;
        $rootNamespace = $this->laravel->getNamespace();

        if (starts_with($model, $rootNamespace) || substr($model, 0, 1) == "\\") {
            $shortName = (new ReflectionClass($model))->getShortName();
        } else {
            $model = config('relay.model_path') . "\\" . $model;
        }

        $fields = $this->getTypeFields($model);

        return "<?php\n\n" . view('eloquent', compact('model', 'shortName', 'fields'))->render();
    }

    /**
     * Generate fields for type.
     *
     * @param  string $class
     * @return array
     */
    protected function getTypeFields($class)
    {
        $model = app($class);

        return (new EloquentType($model))->rawFields();
    }

}
