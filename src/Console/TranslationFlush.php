<?php

namespace Translation\Console;

use Illuminate\Console\Command;

class TranslationFlush extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translation:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush json file and update base from latest data in DB.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        app('translation.loader')->synchronise();
        $this->info('Translation flushed succesfully.');
    }
}
