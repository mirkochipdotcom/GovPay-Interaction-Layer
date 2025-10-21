<?php
declare(strict_types=1);
namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;

class RateizzazioneService
{
    /**
     * Split an amount into $n parts: first part carries the rounding remainder so that
     * sum(parts) == total. Algorithm: base = floor((total / n) * 100) / 100;
     * first = round(total - base*(n-1), 2);
     * Returns array of float values.
     *
     * @param float $total
     * @param int $n
     * @return float[]
     */
    public static function splitAmount(float $total, int $n): array
    {
        if ($n <= 1) {
            return [round($total, 2)];
        }
        $base = floor(($total / $n) * 100) / 100.0;
        $sumOthers = $base * ($n - 1);
        $first = round($total - $sumOthers, 2);
        $parts = [$first];
        for ($i = 1; $i < $n; $i++) {
            $parts[] = $base;
        }
        return $parts;
    }

    /**
     * Build parts with default dates. Default: first validity = today +1 month, then +1 month each.
     * Returns array of arrays with keys: indice, importo, dataValidita, dataScadenza
     *
     * @param float $total
     * @param int $n
     * @param DateTimeInterface|null $startDate
     * @return array<int,array{indice:int,importo:float,dataValidita:string,dataScadenza:string}>
     */
    public static function buildPartsWithDates(float $total, int $n, ?DateTimeInterface $startDate = null): array
    {
        $parts = self::splitAmount($total, $n);
        $start = $startDate ? DateTimeImmutable::createFromMutable((new \DateTime())->setTimestamp($startDate->getTimestamp())) : new DateTimeImmutable();
        $result = [];
        for ($i = 0; $i < count($parts); $i++) {
            $valid = $start->modify('+' . ($i + 1) . ' month')->format('Y-m-d');
            $due = $start->modify('+' . ($i + 1) . ' month')->format('Y-m-d');
            $result[] = [
                'indice' => $i + 1,
                'importo' => (float)round($parts[$i], 2),
                'dataValidita' => $valid,
                'dataScadenza' => $due,
            ];
        }
        return $result;
    }
}
