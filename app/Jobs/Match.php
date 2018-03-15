<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Match as MatchModel;
use App\Match1 as MatchClass;
use Predis;

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
    {/*
        sleep(10);

        if ($user2->type == 'man') {
                Predis::publish('user:' . $user2->id, json_encode([

                ]));
            }

        $job = (new static($this->match))->delay(10);

        dispatch($job);*/
    }
}
