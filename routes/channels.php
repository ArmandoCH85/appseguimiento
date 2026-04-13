<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('gps.tenant.{tenantId}.device.{deviceId}', function ($user, string $tenantId, string $deviceId) {
    return tenancy()->getTenantKey() === $tenantId
        && ($user->hasPermissionTo('devices.view') || $user->hasPermissionTo('reports.view'));
});
