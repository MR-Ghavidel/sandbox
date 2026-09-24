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
            'label' => 'کارهای من',
            'route' => 'tasks.index',
            'active' => 'tasks.*',
            'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
    ],

];
