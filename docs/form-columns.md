# Работа с колонками в Filament формах

## Содержание
1. [Основные концепции](#основные-концепции)
2. [Grid компонент](#grid-компонент)
3. [Размеры колонок](#размеры-колонок)
4. [Примеры использования](#примеры-использования)

## Основные концепции

В Filament для создания колоночной структуры используются:
- `Grid` компонент для создания сетки
- `columnSpan` метод для указания ширины поля
- `columns` метод для указания количества колонок

## Grid компонент

### Базовое использование

```php
Grid::make()
    ->schema([
        // поля формы
    ])
    ->columns(2)
```

### Вложенные сетки

```php
Grid::make()
    ->schema([
        // Первая сетка
        Grid::make()
            ->schema([
                TextInput::make('field1')->columnSpan(1),
                TextInput::make('field2')->columnSpan(1),
            ])
            ->columns(2),
            
        // Вторая сетка
        Grid::make()
            ->schema([
                TextInput::make('field3')->columnSpan(1),
                TextInput::make('field4')->columnSpan(1),
            ])
            ->columns(2),
    ])
    ->columns(1)
```

## Размеры колонок

### Фиксированные размеры

```php
// Две равные колонки
->columns(2)

// Три равные колонки
->columns(3)

// Поле занимает одну колонку
->columnSpan(1)

// Поле занимает всю ширину
->columnSpanFull()
```

### Адаптивные размеры

```php
Grid::make()
    ->schema([...])
    ->columns([
        'default' => 1,    // Мобильные устройства
        'sm' => 2,         // Маленькие экраны
        'md' => 3,         // Средние экраны
        'lg' => 4,         // Большие экраны
        'xl' => 6          // Очень большие экраны
    ])
```

## Примеры использования

### 1. Простая форма в две колонки

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Grid::make()
                ->schema([
                    TextInput::make('title')
                        ->columnSpan(1),
                    TextInput::make('slug')
                        ->columnSpan(1),
                ])
                ->columns(2)
        ]);
}
```

### 2. Форма с вкладками и колонками

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Основные поля в две колонки
            Grid::make()
                ->schema([
                    TextInput::make('slug')
                        ->columnSpan(1),
                    Select::make('parent_id')
                        ->columnSpan(1),
                ])
                ->columns(2),

            // Вторая группа полей
            Grid::make()
                ->schema([
                    TextInput::make('sort_order')
                        ->columnSpan(1),
                    Toggle::make('is_active')
                        ->columnSpan(1),
                ])
                ->columns(2),

            // Вкладки с переводами
            Tabs::make('Translations')
                ->tabs([
                    Tab::make('UK')
                        ->schema([
                            Grid::make()
                                ->schema([
                                    TextInput::make('translations.uk.title')
                                        ->columnSpan(1),
                                    TextInput::make('translations.uk.meta_title')
                                        ->columnSpan(1),
                                ])
                                ->columns(2),
                            
                            // Поля на всю ширину
                            MarkdownEditor::make('translations.uk.description')
                                ->columnSpanFull(),
                            Textarea::make('translations.uk.meta_description')
                                ->columnSpanFull(),
                        ])
                ])
                ->columnSpanFull()
        ])
        ->columns(2);
}
```

### 3. Сложная структура с разными размерами

```php
Grid::make()
    ->schema([
        // Секция на всю ширину
        Section::make('Основное')
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('title')->columnSpan(2),
                        TextInput::make('slug')->columnSpan(1),
                        Select::make('status')->columnSpan(1),
                    ])
                    ->columns(4)
            ])
            ->columnSpanFull(),

        // Секция в две колонки
        Section::make('Мета-данные')
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('meta_title')->columnSpan(1),
                        TextInput::make('meta_description')->columnSpan(1),
                    ])
                    ->columns(2)
            ])
            ->columnSpan(1),

        // Секция в одну колонку
        Section::make('Настройки')
            ->schema([
                Toggle::make('is_active'),
                DateTimePicker::make('published_at'),
            ])
            ->columnSpan(1),
    ])
    ->columns(2)
```

### 4. Советы и рекомендации

1. **Правильная вложенность:**
```php
// Сначала разделите на основные секции
Grid::make()
    ->schema([
        // Затем на подсекции
        Grid::make()->schema([...])->columns(2),
        // И так далее
    ])
    ->columns(1)
```

2. **Адаптивный дизайн:**
```php
Grid::make()
    ->schema([...])
    ->columns([
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ])
```

3. **Группировка связанных полей:**
```php
Grid::make()
    ->schema([
        Group::make([
            TextInput::make('street'),
            TextInput::make('city'),
        ])
        ->columnSpan(1),
        
        Group::make([
            TextInput::make('state'),
            TextInput::make('zip'),
        ])
        ->columnSpan(1),
    ])
    ->columns(2)
```

4. **Условное отображение:**
```php
Grid::make()
    ->schema([
        TextInput::make('type')
            ->columnSpan(1)
            ->reactive(),
            
        TextInput::make('extra_field')
            ->columnSpan(1)
            ->hidden(fn ($get) => $get('type') !== 'special'),
    ])
    ->columns(2)
```

### 5. Особенности и тонкости

1. **Комбинирование с Section:**
```php
Section::make('Основная информация')
    ->schema([
        Grid::make()
            ->schema([
                TextInput::make('name')->columnSpan(1),
                TextInput::make('email')->columnSpan(1),
            ])
            ->columns(2)
    ])
    ->collapsible()
```

2. **Работа с карточками:**
```php
Card::make()
    ->schema([
        Grid::make()
            ->schema([
                TextInput::make('price')
                    ->numeric()
                    ->columnSpan(1),
                Select::make('currency')
                    ->options([
                        'USD' => 'US Dollar',
                        'EUR' => 'Euro'
                    ])
                    ->columnSpan(1),
            ])
            ->columns(2)
    ])
```

3. **Вложенные формы:**
```php
Grid::make()
    ->schema([
        Repeater::make('items')
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('name')->columnSpan(1),
                        TextInput::make('quantity')->columnSpan(1),
                    ])
                    ->columns(2)
            ])
            ->columnSpanFull(),
    ])
    ->columns(2)
```

### 6. Продвинутые техники

1. **Динамическое количество колонок:**
```php
Grid::make()
    ->schema([...])
    ->columns(function ($context) {
        return $context === 'create' ? 1 : 2;
    })
```

2. **Условное отображение секций:**
```php
Grid::make()
    ->schema([
        Section::make('Базовые настройки')
            ->schema([...])
            ->columnSpan(1)
            ->visible(fn ($get) => $get('type') === 'advanced'),

        Section::make('Расширенные настройки')
            ->schema([...])
            ->columnSpan(1)
            ->visible(fn ($get) => $get('type') === 'advanced'),
    ])
    ->columns(2)
```

3. **Responsive настройки для разных размеров экрана:**
```php
Grid::make()
    ->schema([...])
    ->columns([
        'default' => 1,      // < 640px
        'sm' => 2,           // ≥ 640px
        'md' => 3,           // ≥ 768px
        'lg' => 4,           // ≥ 1024px
        'xl' => 5,           // ≥ 1280px
        '2xl' => 6,          // ≥ 1536px
    ])
```

### 7. Лучшие практики

1. **Организация формы:**
   - Группируйте связанные поля в секции
   - Используйте вкладки для разделения большой формы
   - Придерживайтесь единого стиля расположения полей

2. **Производительность:**
   - Избегайте слишком глубокой вложенности Grid компонентов
   - Используйте условное отображение для сложных форм
   - Оптимизируйте количество колонок для разных размеров экрана

3. **UX рекомендации:**
   - Сохраняйте консистентность в размерах колонок
   - Группируйте логически связанные поля
   - Используйте понятные заголовки для секций
   - Добавляйте подсказки для сложных полей

4. **Responsive д��зайн:**
   - Всегда начинайте с мобиль��ой версии (mobile-first)
   - Тестируйте форму на разных размерах экрана
   - Используйте адаптивные колонки где это необходимо

### 8. Решение проблем

1. **Неправильное выравнивание:**
```php
Grid::make()
    ->schema([...])
    ->columns(2)
    ->columnSpan('full')  // Исправление выравнивания
```

2. **Перекрывающиеся поля:**
```php
// Исправление с помощью явного указания размеров
Grid::make()
    ->schema([
        TextInput::make('field1')
            ->columnSpan([
                'default' => 'full',
                'sm' => 1
            ]),
        TextInput::make('field2')
            ->columnSpan([
                'default' => 'full',
                'sm' => 1
            ]),
    ])
    ->columns([
        'default' => 1,
        'sm' => 2
    ])
```

3. **Проблемы с вложенностью:**
```php
// Правильная структура вложенности
Grid::make()
    ->schema([
        Section::make('Секция 1')
            ->schema([
                Grid::make()
                    ->schema([...])
                    ->columns(2)
            ])
            ->columnSpan(1),
    ])
    ->columns(2)
```

### 9. Примеры готовых шаблонов

1. **Форма с адресом:**
```php
Grid::make()
    ->schema([
        Section::make('Адрес')
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('street')
                            ->label('Улица')
                            ->columnSpan(2),
                        TextInput::make('house')
                            ->label('Дом')
                            ->columnSpan(1),
                        TextInput::make('apartment')
                            ->label('Квартира')
                            ->columnSpan(1),
                    ])
                    ->columns(4),
                Grid::make()
                    ->schema([
                        TextInput::make('city')
                            ->label('Город')
                            ->columnSpan(1),
                        TextInput::make('state')
                            ->label('Область')
                            ->columnSpan(1),
                        TextInput::make('zip')
                            ->label('Индекс')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
            ])
            ->columnSpanFull()
    ])
```

2. **Форма контактов:**
```php
Grid::make()
    ->schema([
        Card::make()
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('last_name')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                Grid::make()
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('phone')
                            ->tel()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ])
            ->columnSpanFull()
    ])
```
