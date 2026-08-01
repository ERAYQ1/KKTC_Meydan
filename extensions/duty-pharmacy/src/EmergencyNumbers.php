<?php

namespace KktcMeydan\DutyPharmacy;

class EmergencyNumbers
{
    public static function all(): array
    {
        return [
            ['label' => 'Polis İmdat', 'number' => '155', 'phone' => 'tel:155'],
            ['label' => 'Ambulans', 'number' => '112', 'phone' => 'tel:112'],
            ['label' => 'İtfaiye', 'number' => '199', 'phone' => 'tel:199'],
            ['label' => 'Orman Yangını İhbar', 'number' => '177', 'phone' => 'tel:177'],
            ['label' => 'KIB-TEK Elektrik Arıza', 'number' => '188', 'phone' => 'tel:188'],
            ['label' => 'Su Arıza (Lefkoşa Belediyesi)', 'number' => '185', 'phone' => 'tel:185'],
        ];
    }
}
