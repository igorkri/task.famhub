# Редактирование форм и таблиц в Laravel Filament

## Содержание
1. [Формы](#формы)
2. [Таблицы](#таблицы)
3. [Примеры кастомизации](#примеры-кастомизации)

## Формы

### Базовая структура формы

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // поля формы
        ])
        ->columns(1)
        ->statePath('data');
}
```

### Типы полей

```php
TextInput::make('title')
    ->label('Название')
    ->required()
    ->maxLength(255)
    ->placeholder('Введите название')
    ->helperText('Максимум 255 символов')
    ->hint('Это будет отображаться в заголовке')
    ->disabled(fn () => auth()->user()->cannot('edit_titles'));

Select::make('status')
    ->options([
        'draft' => 'Черновик',
        'published' => 'Опубликовано',
        'archived' => 'В архиве'
    ])
    ->default('draft')
    ->searchable();

Toggle::make('is_active')
    ->label('Активно')
    ->default(true)
    ->inline(false);

DateTimePicker::make('published_at')
    ->label('Дата публикации')
    ->format('Y-m-d H:i')
    ->nullable();

FileUpload::make('image')
    ->label('Изображение')
    ->image()
    ->directory('posts')
    ->maxSize(1024)
    ->acceptedFileTypes(['image/png', 'image/jpeg']);
```

### Группировка полей

```php
// Вкладки
Tabs::make('Настройки')
    ->tabs([
        Tab::make('Основное')
            ->schema([
                // поля
            ]),
        Tab::make('Мета')
            ->schema([
                // поля
            ]),
    ]);

// Секции
Section::make('Информация')
    ->description('Основная информация о записи')
    ->schema([
        // поля
    ])
    ->columns(2);

// Сетка
Grid::make()
    ->schema([
        // поля
    ])
    ->columns([
        'default' => 1,
        'md' => 2,
        'lg' => 3
    ]);
```

### Валидация

```php
TextInput::make('email')
    ->required()
    ->email()
    ->unique(ignoreRecord: true)
    ->rules(['required', 'email'])
    ->validationAttribute('email address')
    ->validateFor(['create', 'update']);
```

### Зависимые поля

```php
Select::make('country_id')
    ->relationship('country', 'name')
    ->reactive()
    ->afterStateUpdated(fn ($state, callable $set) => 
        $set('city_id', null)
    );

Select::make('city_id')
    ->relationship('city', 'name')
    ->options(function (callable $get) {
        $country = Country::find($get('country_id'));
        if (!$country) return [];
        return $country->cities->pluck('name', 'id');
    });
```

## Таблицы

### Базовая структура таблицы

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // колонки
        ])
        ->filters([
            // фильтры
        ])
        ->actions([
            // действия
        ])
        ->bulkActions([
            // массовые действия
        ]);
}
```

### Типы колонок

```php
// Текстовая колонка
TextColumn::make('title')
    ->label('Название')
    ->searchable()
    ->sortable()
    ->toggleable()
    ->wrap();

// Иконка/Булево значение
IconColumn::make('is_active')
    ->label('Статус')
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle');

// Изображение
ImageColumn::make('image')
    ->label('Изображение')
    ->circular()
    ->size(40);

// Форматированный текст
TextColumn::make('created_at')
    ->label('Создано')
    ->dateTime('d.m.Y H:i')
    ->sortable();
```

### Фильтры

```php
// Селект фильтр
SelectFilter::make('status')
    ->options([
        'active' => 'Активные',
        'inactive' => 'Неактивные'
    ])
    ->attribute('is_active')
    ->default(true);

// Фильтр по дате
DateRangeFilter::make('created_at')
    ->label('Дата создания');

// Поиск по связанной модели
SelectFilter::make('category')
    ->relationship('category', 'name')
    ->searchable()
    ->preload()
    ->multiple();
```

### Действия

```php
// Действия для записи
ActionGroup::make([
    ViewAction::make()
        ->label('Просмотр')
        ->icon('heroicon-o-eye'),
    EditAction::make()
        ->label('Редактировать')
        ->icon('heroicon-o-pencil'),
    DeleteAction::make()
        ->label('Удалить')
        ->icon('heroicon-o-trash')
        ->requiresConfirmation(),
]);

// Массовые действия
BulkActionGroup::make([
    DeleteBulkAction::make()
        ->label('Удалить выбранные')
        ->requiresConfirmation(),
    ExportBulkAction::make()
        ->label('Экспортировать'),
]);
```

## Примеры кастомизации

### 1. Кастомная форма с зависимыми полями

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Grid::make()->schema([
            Select::make('type')
                ->options([
                    'simple' => 'Простой',
                    'complex' => 'Сложный'
                ])
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) => 
                    $set('has_additional_fields', $state === 'complex')
                ),

            Toggle::make('has_additional_fields')
                ->label('Дополнительные поля')
                ->hidden(fn (callable $get) => 
                    $get('type') !== 'complex'
                ),

            Fieldset::make('Дополнительно')
                ->schema([
                    TextInput::make('extra_field_1'),
                    TextInput::make('extra_field_2'),
                ])
                ->hidden(fn (callable $get) => 
                    !$get('has_additional_fields')
                ),
        ])->columns(1)
    ]);
}
```

### 2. Кастомная таблица с форматированием

```php
public static function table(Table $table): Table
{
    return $table
        ->poll('30s') // Автообновление каждые 30 секунд
        ->defaultSort('created_at', 'desc')
        ->columns([
            TextColumn::make('title')
                ->formatStateUsing(fn ($state) => Str::limit($state, 50))
                ->description(fn ($record) => $record->subtitle)
                ->searchable()
                ->sortable(),

            TextColumn::make('status')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'active' => 'success',
                    'inactive' => 'danger',
                    default => 'warning',
                }),

            TextColumn::make('price')
                ->money('uah')
                ->sortable()
                ->alignment(Alignment::Right),
        ])
        ->defaultPaginationPageOption(25)
        ->persistFiltersInSession()
        ->persistSortInSession()
        ->persistSearchInSession();
}
```

### 3. Кастомные действия

```php
public static function getActions(): array
{
    return [
        Action::make('approve')
            ->label('Одобрить')
            ->icon('heroicon-o-check')
            ->requiresConfirmation()
            ->action(function (Model $record) {
                $record->update(['status' => 'approved']);
                Notification::make()
                    ->success()
                    ->title('Запись одобрена')
                    ->send();
            })
            ->visible(fn ($record) => $record->status === 'pending')
            ->color('success'),

        Action::make('export')
            ->label('Экспорт')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function (array $data) {
                return Excel::download(
                    new YourExport($data),
                    'export.xlsx'
                );
            }),
    ];
}
```

### 4. Кастомные фильтры

```php
class StatusFilter extends SelectFilter
{
    protected function setUp(): void
    {
        $this->options([
            'active' => 'Активные',
            'inactive' => 'Неактивные',
            'pending' => 'Ожидают',
        ])
        ->query(function (Builder $query, array $data): Builder {
            return $query->when(
                $data['value'],
                fn (Builder $query, $value): Builder => $query->where('status', $value)
            );
        })
        ->indicateUsing(function (array $data): ?string {
            if (!$data['value']) {
                return null;
            }
 
            return 'Статус: ' . $this->options[$data['value']];
        });
    }
}
```
