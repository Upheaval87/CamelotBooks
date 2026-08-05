<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Connection Routing
    |--------------------------------------------------------------------------
    |
    | The application's default connection stays the CENTRAL database. Tenant
    | models resolve to a dynamically-registered `tenant` connection at request
    | time via the TenantScoped trait + TenantConnectionResolver.
    |
    */

    'routing' => [

        /*
         * Base connection whose host/port/username/password are reused when the
         * company record does not override them. The tenant `database` is always
         * the company's `db_name`.
         */
        'base_connection' => env('TENANT_ROUTING_BASE_CONNECTION', 'mysql'),

        /*
         * When set, tenant models resolve to this connection INSTEAD of the
         * dynamically-registered `tenant` connection and no runtime connection
         * is registered. Used by tests to keep every tenant query on the default
         * (sqlite) database. Leave null in production.
         */
        'connection_override' => env('TENANT_ROUTING_OVERRIDE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Access Audit
    |--------------------------------------------------------------------------
    |
    | Every entry into a company (auto-select at login, explicit switch, and
    | super-admin support access) is recorded in the central company_access_logs
    | table. Super admins are logged with action = 'support'.
    |
    */

    'audit' => [
        'enabled' => env('TENANT_ACCESS_AUDIT', true),
    ],
];
