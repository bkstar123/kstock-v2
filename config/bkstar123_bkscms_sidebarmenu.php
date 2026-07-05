<?php
/**
 * Menu array
 * Each link component consists of 'name', 'path', 'icon', 'children' keys
 * 'name', 'path', 'icon' are of string type, 'children' is of array type
 * 'path' for an expandable link should be '#'
 */
return [
    [
        'name' => 'Settings',
        'path' => '/cms/settings',
        'icon' => 'fa fa-cog',
    ],
    [
        'name' => 'Admin Managment',
        'path' => '#',
        'icon' => 'far fa-user',
        'children' => [
            [
                'name' => 'Admins',
                'path' => '/cms/admins',
                'icon' => 'fa fa-users',
            ],
            [
                'name' => 'Create Admin',
                'path' => '/cms/admins/create',
                'icon' => 'fa fa-user-plus',
            ]
        ]
    ],

    [
        'name' => 'Role Managment',
        'path' => '#',
        'icon' => 'fa fa-certificate',
        'children' => [
            [
                'name' => 'Roles',
                'path' => '/cms/roles',
                'icon' => 'fa fa-user-circle',
            ],
            [
                'name' => 'Create Role',
                'path' => '/cms/roles/create',
                'icon' => 'fa fa-plus',
            ]
        ]
    ],

    [
        'name' => 'Permission Managment',
        'path' => '#',
        'icon' => 'fa fa-universal-access',
        'children' => [
            [
                'name' => 'Permissions',
                'path' => '/cms/permissions',
                'icon' => 'fa fa-ship',
            ],
            [
                'name' => 'Create Permission',
                'path' => '/cms/permissions/create',
                'icon' => 'fa fa-plus',
            ]
        ]
    ],

    [
        'name' => 'Companies',
        'path' => '#',
        'icon' => 'fa fa-building',
        'children' => [
            [
                'name' => 'Symbol Directory',
                'path' => '/cms/companies',
                'icon' => 'fa fa-list',
            ],
            [
                'name' => 'My Watchlist',
                'path' => '/cms/watchlist',
                'icon' => 'fa fa-star',
            ]
        ]
    ],

    [
        'name' => 'Securities Symbols',
        'path' => '#',
        'icon' => 'fa fa-rss-square',
        'children' => [
            [
                'name' => 'Finalcial Statements',
                'path' => '/cms/financial-statements',
                'icon' => 'fa fa-tint',
            ],
            [
                'name' => 'Pull Financial Statement',
                'path' => '/cms/financial-statement/pull',
                'icon' => 'fa fa-plus',
            ]
        ]
    ],
];
