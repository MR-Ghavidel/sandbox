<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Drawer Menu Items
    |--------------------------------------------------------------------------
    |
    | Items shown in the right-hand drawer menu of the main layout. To add a new
    | section to the app, create its route and add an item here. "active" is a
    | route name pattern (or a list of them) used to highlight the item, and "icon" is the "d"
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
        [
            'label' => 'کارکرد و حقوق',
            'route' => 'payroll.index',
            'active' => ['payroll.*', 'attendance-imports.*'],
            'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        [
            'label' => 'ابزارها',
            'route' => 'tools.index',
            'active' => 'tools.*',
            'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z',
        ],
    ],

];
