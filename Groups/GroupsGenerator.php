<?php

namespace App\Console\Commands\Groups;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Stringable;

class GroupsGenerator extends Command
{
    protected $signature = 'make:group {name}';
    private $files;
    private $stubsDirectory = '';
    private $baseGroupDirectory = '';
    private $baseNamespace = '';

    public function __construct(Filesystem $files, Application $laravel)
    {
        parent::__construct();

        $this->files = $files;
        $this->laravel = $laravel;
        $this->stubsDirectory = $this->laravel->basePath('app/Console/Commands/Groups/stubs');
        $this->baseGroupDirectory = $this->laravel->basePath('app/Groups');
        $this->baseNamespace = $this->laravel->getNamespace() . 'Groups';
    }

    public function handle() {
        $groupName = $this->argument('name');

        if(is_null($groupName)) {
            $groupName = $this->ask('Add the group name');
        }

        $groupName = ucfirst($groupName);
        $this->baseNamespace .= "\\$groupName";
        $groupPath = $this->baseGroupDirectory . '/' . $groupName;

        $this->files->ensureDirectoryExists($groupPath);

        $this->makeController($groupName, $groupPath);
        $this->makeModel($groupName, $groupPath);
    }

    private function makeController(string $groupName, string $groupPath)
    {
        if(!$this->confirm("Do you want a Controller")) {
            return;
        }

        $stub = $this->files->get($this->stubsDirectory . '/controller.stub');
        $controllerName = $groupName . 'Controller';
        $controllerContent = '';

        if($this->confirm('Do you wan to create controller content? Choose type none to stop')) {
            while(true) {
                $type = $this->choice('Controller content', ['invoke', 'custom', 'none']);

                if($type === 'none') {
                    break;
                }

                $contentStub = match ($type) {
                    'invoke' => $this->files->get($this->stubsDirectory . '/controller_invoke.stub'),
                    'custom' => $this->files->get($this->stubsDirectory . '/class_method.stub'),
                };

                if($type === 'invoke') {
                    $controllerContent = $contentStub;
                    break;
                }

                $methodName = $this->ask('method name');

                $controllerContent .= "\n" . str_replace([
                            '{{ name }}',
                            '{{ arguments }}'
                        ], [
                            $methodName,
                            'Request $request'
                        ], $contentStub);
            }
        }

        $class = str_replace([
            '{{ namespace }}',
            '{{ class }}',
            '{{ controllerContent }}'
        ],[
            $this->baseNamespace,
            $controllerName,
            $controllerContent
        ], $stub);

        $this->files->put($groupPath . '/' . $controllerName . '.php', $class);
    }

    private function makeModel(string $groupName, string $groupPath)
    {
        if(!$this->confirm("Do you want a Controller")) {
            return;
        }

        $stub = $this->files->get($this->stubsDirectory . '/model.stub');
        $modelName = $groupName . 'Model';
        $fillable = $this->ask('fillable fields');

        if(!empty($fillable)) {
            $fillable = 'protected $fillable = [' . $fillable . '];';
        }

        $class = str_replace([
            '{{ namespace }}',
            '{{ class }}',
            '{{ fillable }}'
        ],[
            $this->baseNamespace,
            $modelName,
            $fillable,
        ], $stub);

        $this->files->put($groupPath . '/' . $modelName . '.php', $class);

        if($this->confirm('Create repository')) {

            $repositoryContent = '';
            $repositoryInterfaceContent = '';
            $repositoryMethodStub = $this->files->get($this->stubsDirectory . '/class_method.stub');
            $repositoryMethodInterfaceStub = $this->files->get($this->stubsDirectory . '/interface_method.stub');

            while(true) {
                $type = $this->choice('Repository content', ['custom', 'none']);

                if($type === 'none') {
                    break;
                }

                $name = $this->ask('name');
                $arguments = $this->ask('arguments');
                $rms = $repositoryMethodStub;
                $rmis = $repositoryMethodInterfaceStub;

                $repositoryContent .= "\n" .  str_replace([
                        '{{ name }}',
                        '{{ arguments }}',
                    ],[
                       $name,
                       $arguments,
                    ], $rms);

                $repositoryInterfaceContent .= "\n" . str_replace([
                        '{{ name }}',
                        '{{ arguments }}',
                    ],[
                        $name,
                        $arguments,
                    ], $rmis);
            }

            $repositoryName = $groupName . 'Repository';
            $repositoryInterfaceName = $groupName . 'RepositoryInterface';
            $repositoryStub = $this->files->get($this->stubsDirectory . '/repository.stub');
            $repositoryInterfaceStub = $this->files->get($this->stubsDirectory . '/repositoryInterface.stub');

            $repository = str_replace([
               '{{ namespace }}',
               '{{ name }}',
               '{{ content }}',
            ],[
               $this->baseNamespace,
               $repositoryName,
               $repositoryContent
            ], $repositoryStub);

            $this->files->put($groupPath . '/' . $repositoryName . '.php', $repository);

            $repositoryInterface = str_replace([
                '{{ namespace }}',
                '{{ name }}',
                '{{ content }}',
            ],[
                $this->baseNamespace,
                $repositoryInterfaceName,
                $repositoryInterfaceContent,
            ], $repositoryInterfaceStub);

            $this->files->put($groupPath . '/' . $repositoryInterfaceName . '.php', $repositoryInterface);

            $serviceProviderName = 'RepositoryServiceProvider';
            $serviceProviderPath = $this->laravel->basePath('app/Providers');

            if(!$this->files->exists($serviceProviderPath . '/' . $serviceProviderName . '.php')) {
                $serviceProviderStub = $this->files->get($this->stubsDirectory . '/provider.stub');

                $class = str_replace([
                    '{{ namespace }}',
                    '{{ class }}',
                ], [
                    $this->laravel->getNamespace() . 'Providers',
                    $serviceProviderName
                ], $serviceProviderStub);

                $this->line('Please add App\Provider\\'. $serviceProviderName. '::class to the providers in config/app.php.');
            }

            $this->line('Add $this->app->bind(App\Groups\\'.$groupName.'\\'.$repositoryInterfaceName.'::class, App\Groups\\'.$groupName.'\\'.$repositoryName.'::class); to the app/Providers/'.$serviceProviderName.'.php');
        }
    }
}
