<?php

namespace App\Models;

class Navigation
{

    const GROUPS = [
        'MANAGEMENT' => ['LABEL' => 'Керування', 'SORT' => 1, 'ICON' => 'heroicon-o-cog-6-tooth'],
    ];
    public static $navigation = [
        'WORKSPACE' => [
            'icon' => 'heroicon-o-rectangle-stack',
            'group' => 'Робочі простори',
            'label' => 'Робочі простори',
            'breadcrumbs' => ['Workspaces' => '/workspaces', 'All Workspaces' => '/workspaces'],
            'permission' => 'viewAny workspaces',
            'sort' => 1,
            'url' => '/workspaces',
        ],
    ];

}
