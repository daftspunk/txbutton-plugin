<?php namespace TxButton\App\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use TxButton\App\Classes\RobotWorker;

class ButtonRun extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'button:run';

    /**
     * @var string The console command description.
     */
    protected $description = 'Perform button processing.';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $message = RobotWorker::instance()->process();
        $this->output->writeln($message);
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
