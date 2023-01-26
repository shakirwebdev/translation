<?php

namespace Translation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;
use Translation\Contracts\TranslationFinderContract;

class TranslationFindKeys extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translation:find_keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find translation keys and auto translate them.';

    /**
     * The translation object.
     *
     * @var unknown
     */
    protected $trans;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TranslationFinderContract $trans)
    {
        $this->trans = $trans;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ('database' == Config::get('translation.driver')) {
            $this->error('Database not supported.');

            return;
        }

        $cache = $this->option('nocache') ? false : true;
        $dir = $this->option('directory') ? $this->option('directory') : null;
        $count = $this->trans->find($cache, $dir);
        $this->info("Total of $count translation keys added.");
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['nocache', null, InputOption::VALUE_OPTIONAL, 'Enable cache, default to true.', null],
            ['directory', null, InputOption::VALUE_OPTIONAL, 'Find and translate keys from this directory.', null],
        ];
    }
}
