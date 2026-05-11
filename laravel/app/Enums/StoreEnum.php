<?php

namespace App\Enums;

enum StoreEnum: string
{
    case Emag = 'emag';
    case Altex = 'altex';
    case Flanco = 'flanco';
    case Evomag = 'evomag';
    case Vexio = 'vexio';
    case Istyle = 'istyle';
    case Pcgarage = 'pcgarage';

    public static function requiredProxyDomains(): array
    {
        return [
            'emag.ro',
            'www.emag.ro',
            'altex.ro',
            'www.altex.ro'
        ];
    }
}

