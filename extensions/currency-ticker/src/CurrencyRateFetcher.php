<?php

namespace KktcMeydan\CurrencyTicker;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class CurrencyRateFetcher
{
    const SOURCE_URL = 'https://open.er-api.com/v6/latest/GBP';

    const CONNECT_TIMEOUT_SECONDS = 5;
    const TOTAL_TIMEOUT_SECONDS = 8;

    /**
     * @var Client
     */
    private $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?: new Client;
    }

    /**
     * Fetches live GBP-based rates and derives GBP/EUR/USD -> TRY.
     * Returns null on any network failure, timeout, or malformed response.
     *
     * @return array{GBP: float, EUR: float, USD: float}|null
     */
    public function fetch(): ?array
    {
        try {
            $response = $this->client->request('GET', self::SOURCE_URL, [
                'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
                'timeout' => self::TOTAL_TIMEOUT_SECONDS,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; KktcMeydanCurrencyTickerBot/1.0)',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $decoded = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            return null;
        }

        if (
            ! is_array($decoded)
            || ($decoded['result'] ?? null) !== 'success'
            || ! isset($decoded['rates']['TRY'], $decoded['rates']['EUR'], $decoded['rates']['USD'])
        ) {
            return null;
        }

        $tryPerGbp = (float) $decoded['rates']['TRY'];
        $eurPerGbp = (float) $decoded['rates']['EUR'];
        $usdPerGbp = (float) $decoded['rates']['USD'];

        if ($tryPerGbp <= 0 || $eurPerGbp <= 0 || $usdPerGbp <= 0) {
            return null;
        }

        return [
            'GBP' => $tryPerGbp,
            'EUR' => $tryPerGbp / $eurPerGbp,
            'USD' => $tryPerGbp / $usdPerGbp,
        ];
    }
}
