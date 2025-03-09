<?php

namespace Mahfouz\Helpers\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mahfouz:make-service {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service for the specified model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $model = $this->argument('model');
        $serviceName = "{$model}Service";
        $modelClass = "App\\Models\\{$model}";
        $baseServiceClass = "BaseService";
        $servicePath = app_path("Services/{$serviceName}.php");

        if (File::exists($servicePath)) {
            $this->error("Service {$serviceName} already exists!");
            return 1;
        }

        $serviceContent = <<<PHP
            <?php

            namespace App\Services;

            use {$modelClass};

            class {$serviceName} extends {$baseServiceClass}
            {
                protected string \$modelClass = {$model}::class;

                protected function defaultFilters(): array
                {
                    return array_merge(parent::defaultFilters(), []);
                }
            }
            PHP;

        File::ensureDirectoryExists(app_path('Services'));
        File::put($servicePath, $serviceContent);

        $this->info("Service {$serviceName} created successfully at {$servicePath}");
        return 0;
    }
}
