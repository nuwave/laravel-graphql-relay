<?php

namespace Nuwave\Relay\Commands;

use Illuminate\Console\Command;
use Nuwave\Relay\Support\SchemaGenerator;

class CacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relay:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache Eloquent Types.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        app('graphql')->schema();

        $this->info('Eloquent Types successfully cached.');
    }
}
