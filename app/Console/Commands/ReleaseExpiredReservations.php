<?php

namespace App\Console\Commands;

use App\Services\Marketplace\InventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'marketplace:release-expired-reservations';

    protected $description = 'Release inventory reserved by unpaid orders after checkout expires';

    public function handle(InventoryService $inventory): int
    {
        $released = $inventory->releaseExpiredReservations();
        $this->info("Released {$released} expired stock reservation(s).");

        return self::SUCCESS;
    }
}
