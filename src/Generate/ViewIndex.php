<?php namespace Brackets\AdminGenerator\Generate;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ViewIndex extends ViewGenerator {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:generate:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an index view template';

    /**
     * Path for view
     *
     * @var string
     */
    protected $view = 'index';

    /**
     * Path for js view
     *
     * @var string
     */
    protected $viewJs = 'listing-js';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $force = $this->option('force');

        //TODO check if exists
        //TODO make global for all generator
        //TODO also with prefix
        if(!empty($template = $this->option('template'))) {
            $this->view = 'templates.'.$template.'.index';
            $this->viewJs = 'templates.'.$template.'.listing-js';
        }

        $viewPath = resource_path('views/admin/'.$this->modelViewsDirectory.'/index.blade.php');
        $listingJsPath = resource_path('assets/js/admin/'.$this->modelJSName.'/Listing.js');
        $bootstrapJsPath = resource_path('assets/js/admin/bootstrap.js');

        if ($this->alreadyExists($viewPath) && !$force) {
            $this->error('File '.$viewPath.' already exists!');
        } else {
            if ($this->alreadyExists($viewPath) && $force) {
                $this->warn('File '.$viewPath.' already exists! File will be deleted.');
                $this->files->delete($viewPath);
            }

            $this->makeDirectory($viewPath);

            $this->files->put($viewPath, $this->buildView());

            $this->info('Generating '.$viewPath.' finished');
        }


        if ($this->alreadyExists($listingJsPath && !$force)) {
            $this->error('File '.$listingJsPath.' already exists!');
        } else {
            if ($this->alreadyExists($listingJsPath) && $force) {
                $this->warn('File '.$listingJsPath.' already exists! File will be deleted.');
                $this->files->delete($listingJsPath);
            }

            $this->makeDirectory($listingJsPath);

            $this->files->put($listingJsPath, $this->buildListingJs());
            $this->info('Generating '.$listingJsPath.' finished');

            if ($this->appendIfNotAlreadyAppended($bootstrapJsPath, "\nrequire('./".$this->modelJSName."/Listing')\n")){
                $this->info('Appending Listing to '.$bootstrapJsPath.' finished');
            };
        }

    }

    protected function buildView() {

        return view('brackets/admin-generator::'.$this->view, [
            'modelBaseName' => $this->modelBaseName,
            'modelRouteAndViewName' => $this->modelRouteAndViewName,
            'modelPlural' => $this->modelPlural,
            'modelViewsDirectory' => $this->modelViewsDirectory,
            'modelJSName' => $this->modelJSName,

            'columns' => $this->readColumnsFromTable($this->tableName)->filter(function($column) {
                return !($column['type'] == 'text' || $column['name'] == "password" || $column['name'] == "remember_token" || $column['name'] == "slug" || $column['name'] == "created_at" || $column['name'] == "updated_at" || $column['name'] == "deleted_at");
                })->map(function($col){

                    $filters = collect([]);
                    $col['switch'] = false;

                    if ($col['type'] == 'date' || $col['type'] == 'time' || $col['type'] == 'datetime') {
                        $filters->push($col['type']);
                    }

                    if ($col['type'] == 'boolean' && ($col['name'] == 'enabled' || $col['name'] == 'activated' || $col['name'] == 'is_published')) {
                        $col['switch'] = true;
                    }

                    $col['filters'] = $filters->isNotEmpty() ? ' | '.implode(' | ', $filters->toArray()) : '';

                    return $col;
                }),
//            'filters' => $this->readColumnsFromTable($tableName)->filter(function($column) {
//                return $column['type'] == 'boolean' || $column['type'] == 'date';
//            }),
        ])->render();
    }

    protected function buildListingJs() {
        return view('brackets/admin-generator::'.$this->viewJs, [
            'modelViewsDirectory' => $this->modelViewsDirectory,
            'modelJSName' => $this->modelJSName,
        ])->render();
    }

    protected function getOptions() {
        return [
            ['model-name', 'm', InputOption::VALUE_OPTIONAL, 'Generates a code for the given model'],
            ['template', 't', InputOption::VALUE_OPTIONAL, 'Specify custom template'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force will delete files before regenerating index'],
        ];
    }

}