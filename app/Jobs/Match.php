<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\Match as MatchModel;
use App\MatchHandler;

class Match extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $match;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MatchModel $match)
    {
        $this->match = $match;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $matchHandler = new MatchHandler($this->match);
        $matchHandler->exec();
    }
}
