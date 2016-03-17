<?php

namespace Nuwave\Relay\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeCommandBase extends GeneratorCommand
{
    /**
     * The parse Name to overwrite generators parsename
     *
     * @var string
     */
    protected $customParseName = null;

    /**
     * The path to overwrite generators parsename
     *
     * @var string
     */
    protected $customPath = null;

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub() {

    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        $model = $this->option('model');
        if ($model && $this->option('packaged')) {
            $this->setPackageRoots($model, $this->getNameInput());
            return "/";
        } else if (substr($this->getNameInput(), 0, 1) == "\\") {
            $this->setCustomNamespaceRoots($this->getNameInput());
            return "/";
        } else {
            return false;
        }
    }

    /**
     * Parse the name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function parseName($name)
    {
        if($this->customParseName) {
            return $this->customParseName;
        }

        return parent::parseName($name);
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        if($this->customPath) {
            return $this->customPath;
        }

        return parent::getPath($name);
    }

    /**
     * set new path and parse name to overwrite generator commands methods
     * with custom directories outside of app/ dir
     *
     * @param  string $model
     * @param  string $name
     * @return string
     */
    protected function setPackageRoots($model, $name)
    {
        $rootNamespaceArr = $this->getNamespaceComposerPath($model, '\\GraphQL\\'. str_plural($this->type) .'\\'. $name);

        $this->customPath = $rootNamespaceArr['path'] .'.php';
        $this->customParseName = $rootNamespaceArr['namespaceFull'];
    }

    /**
     * set new path and parse name to overwrite generator commands methods
     * with custom directories outside of app/ dir
     *
     * @param  string $name
     * @return string
     */
    protected function setCustomNamespaceRoots($name)
    {
        $rootNamespaceArr = $this->getNamespaceComposerPath($name, false);
        $rootNamespace = $rootNamespaceArr['path'];

        if($rootNamespace) {
            $this->customPath = $rootNamespace .'.php';
            $this->customParseName = trim($name, "\\");
        } else {
            $this->info('namespace for '. $name .' was not found.');
        }
    }

    /**
     * get the composer base dir for a given namespace
     *
     * @param  string $namespace
     * @param  bool $overwriteSubPath the subparts of the namespace after root that will be overwriten
     * @return array
     */
    protected function getNamespaceComposerPath($namespace, $overwriteSubPath = false)
    {
        $namespaceArr = explode("\\", trim($namespace, "\\"));
        $countLevels = count($namespaceArr);
        $namespacePath = false;
        $namespaceFull = false;
        $subSpaces = [];
        $namespaceScopeStr = '';

        $vendorMap = require(base_path('vendor/composer/autoload_psr4.php'));

        for($i = 0; $i < $countLevels; $i++) {
            $namespaceScopeStr = implode("\\", $namespaceArr) ."\\";
            if(isset($vendorMap[$namespaceScopeStr])) {
                $namespacePath = reset($vendorMap[$namespaceScopeStr]);
                break;
            } else {
                $subSpaces[] = array_pop($namespaceArr);
            }
        }

        if($overwriteSubPath !== false) {
            $namespacePath .= "/". str_replace("\\", "/", trim($overwriteSubPath, "\\"));
            $namespaceFull = $namespaceScopeStr . trim($overwriteSubPath, "\\");
        } else {
            krsort($subSpaces);
            $namespacePath .= "/". implode("/", $subSpaces);
            $namespaceFull = $namespace;
        }

        return [
            'path' => $namespacePath,
            'baseNamespace' => $namespaceScopeStr,
            'namespaceFull' => $namespaceFull
        ];
    }

}
