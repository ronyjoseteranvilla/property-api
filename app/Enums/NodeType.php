<?php

namespace App\Enums;

enum NodeType: string
{
    case CORPORATION = 'Corporation';
    case BUILDING = 'Building';
    case PROPERTY = 'Property';
    case TENANCY_PERIOD = 'Tenancy Period';
    case TENANT = 'Tenant';
}
