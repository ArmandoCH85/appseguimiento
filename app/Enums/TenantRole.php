<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantRole: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Operator = 'operator';
}
