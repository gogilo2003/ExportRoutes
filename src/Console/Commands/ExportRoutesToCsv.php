<?php

namespace Ogilo\ExportRoutes\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use SplFileObject;

class ExportRoutesToCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routes:csv {search? : URI to search for} {--path= : The file path to export the CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export selected routes with method, URI, action, and permissions to a CSV file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get the search term and the file path
        $search = $this->argument('search');
        $filePath = $this->option('path') ?? storage_path('routes.csv');

        // Get the list of routes, and filter by search term if provided
        $routes = collect(Route::getRoutes())->filter(function ($route) use ($search) {
            // If search term is provided, only return URIs that contain the search term
            if ($search) {
                return str_contains($route->uri(), $search);
            }

            // Return all routes if no search term is provided
            return true;
        })->map(function ($route) {
            // Get the route action, which includes the controller and middleware
            $action = $route->getAction();
            $permissions = $this->getPermissionsFromMiddleware($action['middleware'] ?? []);

            return [
                'method'      => implode('|', $route->methods()),
                'uri'         => $route->uri(),
                'action'      => $route->getActionName(),
                'permissions' => $permissions,
            ];
        });

        // Open or create the CSV file
        $file = new SplFileObject($filePath, 'w');

        // Add CSV header
        $file->fputcsv(['Method', 'URI', 'Action', 'Permissions']);

        // Write routes to the CSV
        foreach ($routes as $route) {
            $file->fputcsv([$route['method'], $route['uri'], $route['action'], $route['permissions']]);
        }

        // Display a message when the process is complete
        $this->info("Routes exported successfully to: {$filePath}");
    }

    /**
     * Extract permission-related middleware.
     *
     * @param  mixed  $middlewares
     * @return string
     */
    protected function getPermissionsFromMiddleware($middlewares)
    {
        // Ensure the $middlewares is an array, convert to array if it's a string
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        // Filter the middlewares for those that contain 'checkPermission'
        $permissions = collect($middlewares)->filter(function ($middleware) {
            return str_contains($middleware, 'checkPermission');
        });

        // Return the permissions as a comma-separated string
        return $permissions->implode(', ');
    }
}
