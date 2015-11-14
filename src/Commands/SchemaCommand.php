<?php

namespace Nuwave\Relay\Commands;

use Illuminate\Console\Command;
use Nuwave\Relay\SchemaGenerator;

class SchemaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relay:schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new GraphQL schema.';

    /**
     * Relay schema generator.
     *
     * @var SchemaGenerator
     */
    protected $generator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SchemaGenerator $generator)
    {
        $this->generator = $generator;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = $this->generator->execute();

        if (!isset($data['data']['__schema'])) {
            $this->error('There was an error when attempting to generate the schema file.');
            $this->line(json_encode($data));
        }

        $this->info('Schema file successfully generated.');
    }
}
