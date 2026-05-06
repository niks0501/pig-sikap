<?php

/**
 * Centralized navigation configuration for the sidebar and quick actions.
 *
 * Each menu item defines:
 *  - label: Display text
 *  - route: Named route for href and route generation
 *  - route_pattern: Pattern for request()->routeIs() active-state matching (defaults to route + '*')
 *  - icon: Icon key mapped to SVG in the sidebar blade template
 *  - color: Color scheme key (green, amber, blue, violet, emerald, gray)
 *  - roles: Array of role slugs allowed to see this item; ['*'] means all authenticated users
 *  - gate: Optional Gate ability name for extra authorization check
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Color Schemes
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'green' => [
            'bg' => 'bg-[#0c6d57]/10',
            'text' => 'text-[#0c6d57]',
            'icon' => 'text-[#0c6d57]',
        ],
        'amber' => [
            'bg' => 'bg-amber-100',
            'text' => 'text-amber-700',
            'icon' => 'text-amber-700',
        ],
        'blue' => [
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-700',
            'icon' => 'text-blue-700',
        ],
        'violet' => [
            'bg' => 'bg-violet-100',
            'text' => 'text-violet-700',
            'icon' => 'text-violet-700',
        ],
        'emerald' => [
            'bg' => 'bg-emerald-100',
            'text' => 'text-emerald-700',
            'icon' => 'text-emerald-700',
        ],
        'gray' => [
            'bg' => 'bg-gray-100',
            'text' => 'text-gray-700',
            'icon' => 'text-gray-700',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quick Action Color Schemes (compact icon backgrounds)
    |--------------------------------------------------------------------------
    */
    'quick_action_colors' => [
        'emerald' => 'bg-emerald-50 text-[#0c6d57]',
        'violet' => 'bg-violet-50 text-violet-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'amber' => 'bg-amber-50 text-amber-600',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Sections
    |--------------------------------------------------------------------------
    */
    'sections' => [

        'main' => [
            'label' => 'Main Menu',
            'items' => [

                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'route_pattern' => 'dashboard',
                    'icon' => 'dashboard',
                    'color' => 'green',
                    'roles' => ['*'],
                ],

                [
                    'label' => 'Cycles',
                    'route' => 'cycles.index',
                    'route_pattern' => 'cycles.*',
                    'icon' => 'cycles',
                    'color' => 'green',
                    'roles' => ['president'],
                ],

                [
                    'label' => 'Health & Treatments',
                    'route' => 'health.index',
                    'route_pattern' => 'health.*',
                    'icon' => 'health',
                    'color' => 'amber',
                    'roles' => ['president', 'caretaker'],
                ],

                [
                    'label' => 'Sales Log',
                    'route' => 'sales.index',
                    'route_pattern' => 'sales.*',
                    'icon' => 'sales',
                    'color' => 'blue',
                    'roles' => ['president', 'treasurer'],
                ],

                [
                    'label' => 'Expenses',
                    'route' => 'expenses.index',
                    'route_pattern' => 'expenses.*',
                    'icon' => 'expenses',
                    'color' => 'violet',
                    'roles' => ['president', 'treasurer'],
                ],

                [
                    'label' => 'Profitability',
                    'route' => 'profitability.index',
                    'route_pattern' => 'profitability.*,profit-sharing*',
                    'icon' => 'profitability',
                    'color' => 'emerald',
                    'roles' => ['president', 'treasurer'],
                ],

                [
                    'label' => 'Members',
                    'route' => 'membership.how-to-join',
                    'route_pattern' => 'membership.*',
                    'icon' => 'members',
                    'color' => 'green',
                    'roles' => ['*'],
                ],

                [
                    'label' => 'Reports',
                    'route' => 'reports.index',
                    'route_pattern' => 'reports.*',
                    'icon' => 'reports',
                    'color' => 'gray',
                    'roles' => ['president', 'treasurer', 'secretary'],
                ],

                [
                    'label' => 'Meetings',
                    'route' => 'workflow.meetings.index',
                    'route_pattern' => 'workflow.meetings*',
                    'icon' => 'meetings',
                    'color' => 'gray',
                    'roles' => ['president', 'secretary'],
                ],

                [
                    'label' => 'Resolutions',
                    'route' => 'workflow.resolutions.index',
                    'route_pattern' => 'workflow.resolutions*',
                    'icon' => 'resolutions',
                    'color' => 'gray',
                    'roles' => ['president', 'secretary', 'treasurer'],
                ],

                [
                    'label' => 'Documents',
                    'route' => 'workflow.documents.page.upload',
                    'route_pattern' => 'workflow.documents.*',
                    'icon' => 'documents',
                    'color' => 'gray',
                    'roles' => ['president', 'secretary', 'treasurer'],
                ],

                [
                    'label' => 'Canvassing',
                    'route' => 'workflow.canvasses.index',
                    'route_pattern' => 'workflow.canvasses*',
                    'icon' => 'canvassing',
                    'color' => 'emerald',
                    'roles' => ['president', 'secretary', 'treasurer', 'canvasser'],
                ],

                [
                    'label' => 'Suppliers',
                    'route' => 'workflow.suppliers.index',
                    'route_pattern' => 'workflow.suppliers*',
                    'icon' => 'suppliers',
                    'color' => 'emerald',
                    'roles' => ['president', 'secretary', 'treasurer', 'canvasser'],
                ],

                [
                    'label' => 'Penalties',
                    'route' => 'workflow.penalties.index',
                    'route_pattern' => 'workflow.penalties*',
                    'icon' => 'penalties',
                    'color' => 'amber',
                    'roles' => ['president', 'secretary', 'treasurer'],
                ],

                [
                    'label' => 'Policy Settings',
                    'route' => 'workflow.settings.index',
                    'route_pattern' => 'workflow.settings.*',
                    'icon' => 'settings',
                    'color' => 'gray',
                    'roles' => ['president', 'system_admin'],
                ],

                [
                    'label' => 'Audit Trails',
                    'route' => 'audit-trails.index',
                    'route_pattern' => 'audit-trails.*',
                    'icon' => 'audit',
                    'color' => 'gray',
                    'roles' => ['president'],
                ],

            ],
        ],

        'quick_actions' => [
            'label' => 'Quick Actions',
            'items' => [

                [
                    'label' => 'New Cycle',
                    'route' => 'cycles.create',
                    'color' => 'emerald',
                    'roles' => ['president'],
                ],

                [
                    'label' => 'Add Expense',
                    'route' => 'expenses.create',
                    'color' => 'violet',
                    'roles' => ['president', 'treasurer'],
                ],

                [
                    'label' => 'Record Sale',
                    'route' => 'sales.create',
                    'color' => 'blue',
                    'roles' => ['president', 'treasurer'],
                ],

                [
                    'label' => 'Health Incident',
                    'route' => 'health.create',
                    'color' => 'amber',
                    'roles' => ['president', 'caretaker'],
                ],

                [
                    'label' => 'New Meeting',
                    'route' => 'workflow.meetings.create',
                    'color' => 'emerald',
                    'roles' => ['president', 'secretary'],
                ],

                [
                    'label' => 'New Resolution',
                    'route' => 'workflow.resolutions.create',
                    'color' => 'emerald',
                    'roles' => ['president', 'secretary'],
                ],

                [
                    'label' => 'New Canvass',
                    'route' => 'workflow.canvasses.create',
                    'color' => 'emerald',
                    'roles' => ['president', 'secretary', 'treasurer', 'canvasser'],
                ],

            ],
        ],

    ],
];
