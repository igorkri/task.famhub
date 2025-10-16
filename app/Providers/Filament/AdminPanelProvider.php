<?php

namespace App\Providers\Filament;

use Filament\Panel;
use App\Models\Project;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\Times\TimeResource;
use App\Filament\Resources\Users\UserResource;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Resources\Sections\SectionResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;

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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
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
                    ]);
            })
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
