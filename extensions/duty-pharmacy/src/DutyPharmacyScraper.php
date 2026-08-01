<?php

namespace KktcMeydan\DutyPharmacy;

class DutyPharmacyScraper
{
    const SOURCE_URL = 'https://www.kteb.org/dp/?lang=tr';

    const CONNECT_TIMEOUT_SECONDS = 6;
    const TOTAL_TIMEOUT_SECONDS = 10;

    // KTEB renders duty pharmacies into 9 sub-region panels, but the district
    // list we expose only has 6 entries - Ust/Alt Mesarya and Karpaz are rural
    // extensions of Gazimagusa on KTEB's own site map, so they're merged in.
    const PANEL_DISTRICT_MAP = [
        'pnlLefkosa' => 'lefkosa',
        'pnlGirne' => 'girne',
        'pnlMagusa' => 'gazimagusa',
        'pnlUstMesarya' => 'gazimagusa',
        'pnlAltMesarya' => 'gazimagusa',
        'pnlKarpaz' => 'gazimagusa',
        'pnlGuzelyurt' => 'guzelyurt',
        'pnlLefke' => 'lefke',
        'pnlIskele' => 'iskele',
    ];

    const DISTRICTS = [
        'lefkosa' => 'Lefkoşa',
        'girne' => 'Girne',
        'gazimagusa' => 'Gazimağusa',
        'guzelyurt' => 'Güzelyurt',
        'iskele' => 'İskele',
        'lefke' => 'Lefke',
    ];

    /**
     * Fetches and parses live duty-pharmacy data from KTEB. Returns null on
     * any network failure, timeout, or if the parsed result doesn't cover
     * all 6 districts (treated as an unreliable/partial scrape).
     */
    public function scrape(): ?array
    {
        $html = $this->fetch();

        if ($html === null) {
            return null;
        }

        $districts = $this->parseDistricts($html);

        foreach (array_keys(self::DISTRICTS) as $slug) {
            if (empty($districts[$slug])) {
                return null;
            }
        }

        return $districts;
    }

    private function fetch(): ?string
    {
        $ch = curl_init(self::SOURCE_URL);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TOTAL_TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; KktcMeydanDutyPharmacyBot/1.0)',
        ]);

        $body = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errorNumber !== 0 || $statusCode !== 200 || ! is_string($body) || $body === '') {
            return null;
        }

        return $body;
    }

    private function parseDistricts(string $html): array
    {
        $panelOffsets = [];

        foreach (self::PANEL_DISTRICT_MAP as $panelId => $districtSlug) {
            $position = strpos($html, 'id="CpAll_DutyPharmacies_7_'.$panelId.'"');

            if ($position !== false) {
                $panelOffsets[] = ['offset' => $position, 'slug' => $districtSlug];
            }
        }

        usort($panelOffsets, function ($a, $b) {
            return $a['offset'] <=> $b['offset'];
        });

        $districts = [];

        foreach (array_keys(self::DISTRICTS) as $slug) {
            $districts[$slug] = [];
        }

        foreach ($panelOffsets as $index => $panel) {
            $start = $panel['offset'];
            $end = $panelOffsets[$index + 1]['offset'] ?? strlen($html);
            $chunk = substr($html, $start, $end - $start);

            foreach ($this->parsePharmaciesFromChunk($chunk) as $pharmacy) {
                $districts[$panel['slug']][] = $pharmacy;
            }
        }

        return $districts;
    }

    private function parsePharmaciesFromChunk(string $chunk): array
    {
        if (! preg_match_all('#<article>.*?</article>#s', $chunk, $matches)) {
            return [];
        }

        $pharmacies = [];

        foreach ($matches[0] as $articleHtml) {
            $pharmacy = $this->parseArticle($articleHtml);

            if ($pharmacy !== null) {
                $pharmacies[] = $pharmacy;
            }
        }

        return $pharmacies;
    }

    private function parseArticle(string $articleHtml): ?array
    {
        $document = new \DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div>'.$articleHtml.'</div>');
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($document);

        $name = $this->firstDirectText($xpath, '//h1[contains(@class,"title")]');
        $hours = $this->rowValue($xpath, 'icon-clock');
        $address = $this->rowValue($xpath, 'icon-map-marker');
        $phone = $this->firstTelHref($xpath);

        if ($name === null || $address === null) {
            return null;
        }

        $mapQuery = trim($name.', '.$address.', Kıbrıs');

        return [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'hours' => $hours,
            'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($mapQuery),
        ];
    }

    private function firstDirectText(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $textParts = [];

        foreach ($nodes->item(0)->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $textParts[] = $child->textContent;
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', implode(' ', $textParts)));

        return $text !== '' ? $text : null;
    }

    private function rowValue(\DOMXPath $xpath, string $iconClass): ?string
    {
        $nodes = $xpath->query('//i[contains(@class,"'.$iconClass.'")]/parent::td/following-sibling::td[1]');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', $nodes->item(0)->textContent));

        return $text !== '' ? $text : null;
    }

    private function firstTelHref(\DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//a[starts-with(@href,"tel:")]');

        if ($nodes === false) {
            return null;
        }

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $raw = trim(substr($href, strlen('tel:')));

            if ($raw !== '') {
                return self::normalizePhone($raw);
            }
        }

        return null;
    }

    /**
     * KTEB renders numbers as e.g. "(0392) 330 20 22" - strips to a clean
     * tel: href, converting the leading trunk 0 to +90 so it dials
     * correctly from outside Northern Cyprus too.
     */
    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if (strpos($digits, '0') === 0) {
            $digits = '90'.substr($digits, 1);
        }

        return 'tel:+'.$digits;
    }
}
