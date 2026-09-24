<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Drawer Menu Items
    |--------------------------------------------------------------------------
    |
    | Items shown in the right-hand drawer menu of the main layout. To add a new
    | section to the app, create its route and add an item here. "active" is a
    | route name pattern used to highlight the item, and "icon" is the "d"
    | attribute of a 24x24 outline SVG path (e.g. from heroicons.com).
    |
    */

    'items' => [
        [
            'label' => 'خانه',
            'route' => 'home',
            'active' => 'home',
            'icon' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        ],
        [
            'label' => 'کارهای من',
            'route' => 'tasks.index',
            'active' => 'tasks.*',
            'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
    ],

];
