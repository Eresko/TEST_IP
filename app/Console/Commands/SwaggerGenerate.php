<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class SwaggerGenerate extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Generate Swagger JSON';

    public function handle()
    {
        try {
            $generator = new Generator();
            $openapi = $generator->generate([
                app_path('Http/Controllers'),
            ]);
            
            $outputFile = public_path('swagger.json');
            file_put_contents($outputFile, $openapi->toJson());

            $this->info('Swagger JSON generated: ' . $outputFile);
        } catch (\Exception $e) {
            $this->error("Error generating Swagger: " . $e->getMessage());
        }
    }
}