<?php

namespace Files13\Tests\Unit\Models;

use Files13\Models\Phase;
use Carbon\CarbonImmutable;
use Files13\Models\FilePhase;
use PHPUnit\Framework\TestCase;

class FilePhaseCalculationTest extends TestCase
{
    public function test_the_alert_is_the_first_one_above_zero(): void
    {
        $this->assertSame(7, FilePhase::alertOf([0, 7, 3]));
        $this->assertSame(0, FilePhase::alertOf([0, 0]));
        $this->assertSame(0, FilePhase::alertOf([]));
    }

    public function test_the_alert_date_needs_a_start(): void
    {
        $this->assertSame('2026-01-11', FilePhase::alertDateFor(CarbonImmutable::parse('2026-01-01'), 10)?->toDateString());
        $this->assertNull(FilePhase::alertDateFor(null, 10));
    }

    public function test_the_pending_days_need_an_end_and_never_go_below_zero(): void
    {
        $start = CarbonImmutable::parse('2026-01-01');

        $this->assertNull(FilePhase::pendingDaysFor($start, null, 10));
        $this->assertSame(6, FilePhase::pendingDaysFor($start, CarbonImmutable::parse('2026-01-05'), 10));
        $this->assertSame(0, FilePhase::pendingDaysFor($start, CarbonImmutable::parse('2026-01-20'), 10));
    }

    public function test_only_accumulating_phases_of_order_one_and_two_count_days(): void
    {
        $phase = (new Phase)->forceFill(['accumulate_days' => true, 'accumulate_days_order' => 1]);
        $this->assertTrue($phase->accumulatesDays());

        $phase->accumulate_days_order = 3;
        $this->assertFalse($phase->accumulatesDays());

        $phase->forceFill(['accumulate_days' => false, 'accumulate_days_order' => 2]);
        $this->assertFalse($phase->accumulatesDays());
    }
}
