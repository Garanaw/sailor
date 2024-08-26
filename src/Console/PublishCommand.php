<?php

declare(strict_types=1);

namespace Garanaw\Sailor\Console;

use Laravel\Sail\Console\PublishCommand as SailPublishCommand;

class PublishCommand extends SailPublishCommand
{
    protected $signature = 'sailor:publish';

    public function handle()
    {
        parent::handle();
    }
}
