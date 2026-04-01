<?php

declare(strict_types=1);

namespace WhatsApp\Media\Enum;

enum MediaType: string
{
    case IMAGE    = 'WhatsApp Image Keys';
    case VIDEO    = 'WhatsApp Video Keys';
    case AUDIO    = 'WhatsApp Audio Keys';
    case DOCUMENT = 'WhatsApp Document Keys';
}
