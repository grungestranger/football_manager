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
        /*if (!($data = Cache::get('match:' . $this->match->id))) {
            
        }*/

        $matchHandler = new MatchHandler($this->match);
        $matchHandler->exec();

/*
        if ($this->match->user1->type == 'man') {
            Predis::publish('user:' . $this->match->user1_id, json_encode($action));
        }
        if ($this->match->user1->type == 'man') {
            Predis::publish('user:' . $this->match->user1_id, json_encode($action));
        }
    /*

        if ($user2->type == 'man') {
                Predis::publish('user:' . $user2->id, json_encode([

                ]));
            }

        $job = (new static($this->match))->delay(10);

        dispatch($job);*/
    }
}
