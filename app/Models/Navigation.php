<?php

namespace App\Models;

class Navigation
{

    const MANAGEMENT = 'КЕРУВАННЯ';
    const GROUPS = [
        'MANAGEMENT' => ['LABEL' => 'Керування', 'SORT' => 1, 'ICON' => 'heroicon-o-cog-6-tooth'],
    ];
    const NAVIGATION = [
        'WORKSPACE' => [
            'ICON' => 'heroicon-o-cog-6-tooth',
            'GROUP' => self::MANAGEMENT,
            'LABEL' => 'Робочі простори',
            'BREADCRUMBS' => ['Workspaces' => '/workspaces', 'All Workspaces' => '/workspaces'],
            'PERMISSION' => 'viewAny workspaces',
            'SORT' => 1,
            'URL' => '/workspaces',
        ],
        'PROJECT_USER' => [
            'ICON' => 'heroicon-o-users',
            'GROUP' => self::MANAGEMENT,
            'LABEL' => 'Користувачі проєктів',
            'BREADCRUMBS' => ['Project Users' => '/project-users', 'All Project Users' => '/project-users'],
            'PERMISSION' => 'viewAny project users',
            'SORT' => 2,
            'URL' => '/project-users',
        ],
        'PROJECT' => [
            'ICON' => 'heroicon-o-briefcase',
            'GROUP' => self::MANAGEMENT,
            'LABEL' => 'Проєкти',
            'BREADCRUMBS' => ['Projects' => '/projects', 'All Projects' => '/projects'],
            'PERMISSION' => 'viewAny projects',
            'SORT' => 3,
            'URL' => '/projects',
        ],
        'TASK' => [
            'ICON' => 'heroicon-o-check-badge',
            'GROUP' => self::MANAGEMENT,
            'LABEL' => 'Задачі',
            'BREADCRUMBS' => ['Tasks' => '/tasks', 'All Tasks' => '/tasks'],
            'PERMISSION' => 'viewAny tasks',
            'SORT' => 4,
            'URL' => '/tasks',
        ],
        'TIME' => [
            'ICON' => 'heroicon-o-clock',
            'GROUP' => self::MANAGEMENT,
            'LABEL' => 'Час',
            'BREADCRUMBS' => ['Times' => '/times', 'All Times' => '/times'],
            'PERMISSION' => 'viewAny times',
            'SORT' => 5,
            'URL' => '/times',
        ],
    ];

}
