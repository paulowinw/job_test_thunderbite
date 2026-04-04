<?php

namespace Tests\Unit;

use App\Models\Prize;
use App\Services\WeightedPrizePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class WeightedPrizePickerMysqlOrderTest extends TestCase
{
    /**
     * MySQL/MariaDB use ORDER BY -LOG(RAND()) / weight DESC (Gumbel-max trick); this guards the exact expression used in {@see WeightedPrizePicker::pickFromQuery()}.
     */
    public function test_mysql_driver_applies_log_rand_over_weight_desc_order(): void
    {
        $fakePrize = new Prize(['id' => 1]);

        $cloneForEmptyCheck = Mockery::mock(Builder::class);
        $cloneForEmptyCheck->shouldReceive('doesntExist')->once()->andReturn(false);

        $cloneForPick = Mockery::mock(Builder::class);
        $cloneForPick->shouldReceive('orderByRaw')
            ->once()
            ->with('-LOG(RAND()) / weight DESC')
            ->andReturnSelf();
        $cloneForPick->shouldReceive('limit')->once()->with(1)->andReturnSelf();
        $cloneForPick->shouldReceive('first')->once()->andReturn($fakePrize);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('clone')->twice()->andReturn($cloneForEmptyCheck, $cloneForPick);

        $connection = Mockery::mock(\Illuminate\Database\Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');

        DB::shouldReceive('connection')->once()->withNoArgs()->andReturn($connection);

        $picker = new WeightedPrizePicker;
        $result = $picker->pickFromQuery($query);

        $this->assertSame($fakePrize, $result);
    }
}
