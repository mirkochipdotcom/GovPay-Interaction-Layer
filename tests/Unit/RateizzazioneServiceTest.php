<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\RateizzazioneService;

final class RateizzazioneServiceTest extends TestCase
{
    public function testSplitThreeParts(): void
    {
        $parts = RateizzazioneService::splitAmount(100.00, 3);
        $this->assertCount(3, $parts);
        $this->assertEquals(33.34, $parts[0]);
        $this->assertEquals(33.33, $parts[1]);
        $this->assertEquals(33.33, $parts[2]);
        $this->assertEquals(100.00, array_sum($parts));
    }

    public function testSplitOnePart(): void
    {
        $parts = RateizzazioneService::splitAmount(45.50, 1);
        $this->assertCount(1, $parts);
        $this->assertEquals(45.50, $parts[0]);
    }

    public function testBuildPartsWithDatesCountAndSum(): void
    {
        $parts = RateizzazioneService::buildPartsWithDates(100.00, 3, new DateTimeImmutable('2025-01-01'));
        $this->assertCount(3, $parts);
        $sum = 0;
        foreach ($parts as $p) {
            $sum += $p['importo'];
        }
        $this->assertEquals(100.00, round($sum,2));
        $this->assertEquals('2025-02-01', $parts[0]['dataValidita']);
        $this->assertEquals('2025-03-01', $parts[1]['dataValidita']);
        $this->assertEquals('2025-04-01', $parts[2]['dataValidita']);
    }
}
