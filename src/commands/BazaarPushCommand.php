<?php

namespace Nikapps\BazaarPush;

use Illuminate\Console\Command;

class BazaarPushCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'bazaarpush:sale';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This will fetch info from CafeBazaar and pushes a report to the defined clients.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		//
        $bazaarPush = new BazaarPush();
        $this->info($bazaarPush->exec());
	}

}
