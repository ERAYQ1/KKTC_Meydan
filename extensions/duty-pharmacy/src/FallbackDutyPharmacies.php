<?php

namespace KktcMeydan\DutyPharmacy;

/**
 * Static safety net for when the KTEB scrape fails or times out. Not real
 * duty data - just the district's central/well-known pharmacy so the UI
 * always has a phone number to call, with a clear "fallback" source flag
 * so the frontend can warn the user it may be stale.
 */
class FallbackDutyPharmacies
{
    public static function forDistrict(string $slug): array
    {
        $entry = self::data()[$slug] ?? null;

        return $entry !== null ? [$entry] : [];
    }

    private static function data(): array
    {
        return [
            'lefkosa' => [
                'name' => 'Lefkoşa Nöbetçi Eczanesi',
                'address' => 'Lefkoşa merkez - güncel nöbetçi eczane için KTEB\'i arayınız',
                'phone' => 'tel:+903922280622',
                'hours' => 'Nöbet saatleri güncellenemedi',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eczane, Lefkoşa, Kıbrıs'),
            ],
            'girne' => [
                'name' => 'Girne Nöbetçi Eczanesi',
                'address' => 'Girne merkez - güncel nöbetçi eczane için KTEB\'i arayınız',
                'phone' => 'tel:+903922280622',
                'hours' => 'Nöbet saatleri güncellenemedi',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eczane, Girne, Kıbrıs'),
            ],
            'gazimagusa' => [
                'name' => 'Gazimağusa Nöbetçi Eczanesi',
                'address' => 'Gazimağusa merkez - güncel nöbetçi eczane için KTEB\'i arayınız',
                'phone' => 'tel:+903922280622',
                'hours' => 'Nöbet saatleri güncellenemedi',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eczane, Gazimağusa, Kıbrıs'),
            ],
            'guzelyurt' => [
                'name' => 'Güzelyurt Nöbetçi Eczanesi',
                'address' => 'Güzelyurt merkez - güncel nöbetçi eczane için KTEB\'i arayınız',
                'phone' => 'tel:+903922280622',
                'hours' => 'Nöbet saatleri güncellenemedi',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eczane, Güzelyurt, Kıbrıs'),
            ],
            'iskele' => [
                'name' => 'İskele Nöbetçi Eczanesi',
                'address' => 'İskele merkez - güncel nöbetçi eczane için KTEB\'i arayınız',
                'phone' => 'tel:+903922280622',
                'hours' => 'Nöbet saatleri güncellenemedi',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eczane, İskele, Kıbrıs'),
            ],
            'lefke' => [
                'name' => 'Lefke Nöbetçi Eczanesi',
                'address' => 'Lefke merkez - güncel nöbetçi eczane için KTEB\'i arayınız',
                'phone' => 'tel:+903922280622',
                'hours' => 'Nöbet saatleri güncellenemedi',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eczane, Lefke, Kıbrıs'),
            ],
        ];
    }
}
