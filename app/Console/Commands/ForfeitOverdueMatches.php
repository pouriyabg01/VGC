<?php

namespace App\Console\Commands;

use App\Traits\TournamentMatchTrait;
use Illuminate\Console\Command;

/**
 * Settles matches whose players ran out of time to report.
 *
 * Without this a single no-show holds up its whole tournament: the round never
 * completes, so the next one is never drawn and everybody still in the draw
 * waits on one player who has stopped playing.
 */
class ForfeitOverdueMatches extends Command
{
    use TournamentMatchTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:forfeit-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Settle matches past their reporting deadline: one standing report wins, none goes to an admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $result = $this->forfeitOverdueMatches();

        $this->info("Settled {$result['settled']} match(es) on a standing report.");
        $this->info("Sent {$result['disputed']} match(es) to an admin with nothing reported.");

        return self::SUCCESS;
    }
}
