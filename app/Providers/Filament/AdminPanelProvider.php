<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActOfWorks\ActOfWorkResource;
use App\Filament\Resources\Sections\SectionResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\Times\TimeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Project;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->authPasswordBroker('users')
            ->emailVerification()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\QuickTimerWidget::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $currentUserId = auth()->id();

                $projectItems = Project::where('is_active', true)
                    ->get()
                    ->map(fn (Project $project) => NavigationItem::make($project->name)
                        ->url(TaskResource::getUrl('index', [
                            'filters' => [
                                'project_id' => [
                                    'values' => [$project->id],
                                ],
                                'user_id' => [
                                    'values' => [$currentUserId],
                                ],
                                'status' => [
                                    'values' => ['in_progress', 'planned', 'new', 'needs_clarification'],
                                ],
                            ],
                        ]))
                        ->badge(fn () => \App\Models\Task::where('project_id', $project->id)
                            ->where('user_id', $currentUserId)
                            ->whereIn('status', ['in_progress', 'planned', 'new', 'needs_clarification'])
                            ->count())
                    )
                    ->all();

                return $builder
                    ->groups([
                        NavigationGroup::make('Проекти')
                            ->icon('heroicon-o-folder-open')
                            ->items($projectItems),
                    ])
                    ->items([
                        ...Dashboard::getNavigationItems(),
                        ...\App\Filament\Resources\Projects\ProjectResource::getNavigationItems(),
                        ...TaskResource::getNavigationItems(),
                        ...SectionResource::getNavigationItems(),
                        ...TimeResource::getNavigationItems(),
                        ...RoleResource::getNavigationItems(),
                        ...UserResource::getNavigationItems(),
                        ...\App\Filament\Pages\ManageCustomFields::getNavigationItems(),
                        ...ActOfWorkResource::getNavigationItems(),
                    ]);
            })
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
