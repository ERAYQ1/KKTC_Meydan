<?php

use Symfony\Component\Translation\MessageCatalogue;

$catalogue = new MessageCatalogue('tr', array (
));

$catalogueEn = new MessageCatalogue('en', array (
));
$catalogue->addFallbackCatalogue($catalogueEn);

return $catalogue;
