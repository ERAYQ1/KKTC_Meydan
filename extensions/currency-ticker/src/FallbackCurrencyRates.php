<?php

namespace KktcMeydan\CurrencyTicker;

class FallbackCurrencyRates
{
    /**
     * Static defaults used only when the live source is unreachable.
     *
     * @return array{GBP: float, EUR: float, USD: float}
     */
    public static function rates(): array
    {
        return [
            'GBP' => 43.50,
            'EUR' => 37.20,
            'USD' => 34.10,
        ];
    }
}
