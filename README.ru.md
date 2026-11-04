# Config Processor

[English](README.md) | **Русский** | [Deutsch](README.de.md)

![Tests](https://github.com/Modestox/config-processor/actions/workflows/phpunit.yml/badge.svg)

PHP-библиотека, которая проверяет и приводит в порядок массив с описанием настроек. Формат вдохновлён `system.xml` из Magento: `Tabs -> Sections -> Groups -> Fields`.

На вход идёт «сырой» массив и схема. На выходе либо чистый массив с одинаковой, предсказуемой формой, либо `InvalidConfigException` с точным путём к ошибке.

```text
сырой массив  ──►  Processor + Schema  ──►  чистый массив
                         │
                         └──►  InvalidConfigException (путь + причина)
```

## Содержание

- [Зачем это нужно](#зачем-это-нужно)
- [Требования и установка](#требования-и-установка)
- [Быстрый старт](#быстрый-старт)
- [Как это устроено](#как-это-устроено)
- [Справочник по уровням](#справочник-по-уровням)
- [Основные правила](#основные-правила)
- [Ошибки](#ошибки)
- [Зависимости полей (`depends`)](#зависимости-полей-depends)
- [Типы полей](#типы-полей)
- [Свои типы полей](#свои-типы-полей)
- [Чего библиотека не делает](#чего-библиотека-не-делает)
- [Структура репозитория](#структура-репозитория)
- [Разработка и тесты](#разработка-и-тесты)
- [Переход с 1.x на 2.0](#переход-с-1x-на-20)

---

## Зачем это нужно

Админские настройки модуля (как `system.xml` в Magento) удобно описывать декларативно: вкладки, разделы, группы, поля. Но когда такое описание пишет человек, в нём бывают опечатки, лишние пробелы, пропущенные ключи и поля, которые ссылаются на несуществующие поля.

Библиотека решает одну задачу: **проверить описание и вернуть его в нормализованном виде**.

Главная идея, ради которой так сделано: на выходе **все ключи есть всегда**. Если ты не указал `sort_order`, придёт `0`. Если не указал `class`, придёт `''`. Поэтому код, который потом рисует форму или сохраняет настройки, не делает проверок `isset` и не ломается из-за отсутствующего ключа.

Что библиотека **не** делает: не рисует форму, не хранит значения, не читает `$_POST`. Она проверяет только *описание* настроек. Это важно, см. [Чего библиотека не делает](#чего-библиотека-не-делает).

## Требования и установка

- PHP 8.3 или новее
- Зависимостей нет

```bash
composer require modestox/config-processor
```

## Быстрый старт

### 1. Сырое описание

Обрати внимание на лишние пробелы и неупорядоченные `sort_order`:

```php
$rawInput = [
    'tabs' => [
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
        ],
        'main_tab' => [
            'label'      => '  General Settings  ', // пробелы по краям будут убраны
            'sort_order' => 10,                      // эта вкладка окажется первой
        ],
    ],
    'sections' => [
        'api_integration' => [
            'tab'        => 'main_tab',
            'label'      => 'API Core Integration',
            'sort_order' => 5,
            'groups'     => [
                'auth_group' => [
                    'label'  => 'Authentication Keys',
                    'fields' => [
                        'enable_api' => [
                            'type' => 'yes_no', // варианты Yes/No создаются автоматически
                        ],
                        'api_secret' => [
                            'type'    => 'password',
                            'default' => '   secret-token-key   ',
                            'depends' => [
                                'enable_api' => 1, // показывать, только если API включён
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

### 2. Обработка

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$processor = new Processor();

// Для маленьких плагинов без вкладок и разделов:
// $schema = new \Modestox\ConfigProcessor\Schema\GroupedConfig();
$schema = new SystemConfig();

$cleanConfig = $processor->process($rawInput, $schema);
```

### 3. Результат

Все значения нормализованы, все ключи на месте, всё отсортировано по `sort_order`:

```php
[
    'tabs' => [
        'main_tab' => [
            'label'      => 'General Settings',
            'sort_order' => 10,
            'class'      => '',
            'icon'       => '',
        ],
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
            'class'      => '',
            'icon'       => '',
        ],
    ],
    'sections' => [
        'api_integration' => [
            'tab'        => 'main_tab',
            'label'      => 'API Core Integration',
            'sort_order' => 5,
            'class'      => '',
            'icon'       => '',
            'groups'     => [
                'auth_group' => [
                    'label'      => 'Authentication Keys',
                    'sort_order' => 0,
                    'fields'     => [
                        'enable_api' => [
                            'type'       => 'yes_no',
                            'label'      => 'enable_api',
                            'sort_order' => 0,
                            'options'    => [0 => 'No', 1 => 'Yes'],
                            'default'    => 0,
                            'comment'    => '',
                            'class'      => '',
                            'validation' => [],
                            'required'   => false,
                            'provider'   => '',
                            'depends'    => null,
                        ],
                        'api_secret' => [
                            'type'       => 'password',
                            'label'      => 'api_secret',
                            'sort_order' => 0,
                            'default'    => 'secret-token-key',
                            'comment'    => '',
                            'class'      => '',
                            'validation' => [],
                            'required'   => false,
                            'provider'   => '',
                            'depends'    => ['enable_api' => 1],
                        ],
                    ],
                ],
            ],
        ],
    ],
]
```

### Типичный сценарий

Модуль хранит описание в своём файле и обрабатывает его один раз:

```php
// etc/config.php модуля
return [
    'tabs'     => [ /* ... */ ],
    'sections' => [ /* ... */ ],
];
```

```php
$raw   = require __DIR__ . '/etc/config.php';
$clean = (new Processor())->process($raw, new SystemConfig());

// $clean дальше можно кэшировать и отдавать рендереру без повторных проверок.
```

## Как это устроено

`Processor` ничего не проверяет сам, он передаёт данные схеме. Схема раздаёт работу валидаторам уровней, а валидатор поля выбирает нужный тип:

```text
Processor::process($raw, $schema)
└── SystemConfig::validate()          проверяет корневые ключи
    ├── Tabs::validate()              вкладки
    └── Sections::validate()          разделы (знают список вкладок)
        └── Groups::validate()        группы
            └── Fields::validate()    поля: тип, зависимости
                └── Text / Select / Number / ...   правила конкретного типа
```

Tabs, Sections, Groups и Fields наследуют общий класс `AbstractNodeValidator`. Он делает одинаковую для всех работу: проверяет идентификатор, проверяет неизвестные ключи, обрабатывает `label` и `sort_order`, сортирует результат и добавляет путь к ошибке. Конкретный валидатор описывает только то, что отличает его уровень.

Две готовые схемы:

| Схема | Структура | Когда использовать |
| :--- | :--- | :--- |
| `SystemConfig` | Tabs → Sections → Groups → Fields | Большие модули и админ-панели |
| `GroupedConfig` | Groups → Fields (ключ `groups` обязателен) | Маленькие плагины и виджеты |

## Справочник по уровням

| Уровень | Ключ | Тип | Что с ним происходит |
| :--- | :--- | :--- | :--- |
| Корень `SystemConfig` | `tabs`, `sections` | array | Оба необязательны. |
| Корень `GroupedConfig` | `groups` | array | Обязателен. |
| Вкладка (tab) | `label` | string | По умолчанию равен id вкладки. |
| | `sort_order` | int | По умолчанию `0`. |
| | `class`, `icon` | string | По умолчанию `''`. |
| Раздел (section) | `tab` | string | **Обязателен.** Должен ссылаться на описанную вкладку. |
| | `label`, `sort_order`, `class`, `icon` | | Как у вкладки. |
| | `groups` | array | По умолчанию `[]`. |
| Группа (group) | `label`, `sort_order` | | Как у вкладки. |
| | `fields` | array | По умолчанию `[]`. |
| Поле (field) | `type` | string | **Обязателен.** См. [типы полей](#типы-полей). |
| | остальное | | Общие ключи и ключи типа, см. [fields.ru.md](fields.ru.md). |

## Основные правила

### 1. Идентификаторы

Ключи вкладок, разделов, групп и полей должны подходить под `^[a-z0-9_]+$`: строчные латинские буквы, цифры и подчёркивание.

```php
'site_name'  // хорошо
'Site Name'  // ошибка: заглавные и пробел
'site-name'  // ошибка: дефис
"site_name\n" // ошибка: перевод строки в конце
'123'        // ошибка: PHP превращает такой ключ в число
```

С ключами, состоящими только из цифр, есть тонкость: PHP автоматически делает `'123'` целым числом. Библиотека не может отличить `'123'` от `123`, поэтому такие id не поддерживаются. `'item_123'` работает.

### 2. Строгие типы, без приведения

Библиотека ничего не преобразует «по-хорошему». Строка `"10"` не подходит там, где нужно число, `1` не подходит там, где нужен `true`:

```php
'cache_ttl' => ['type' => 'number', 'default' => '10']  // ошибка: нужен int или float
'maintenance' => ['type' => 'boolean', 'default' => 1]  // ошибка: нужен true или false
```

Единственное осознанное преобразование: числа в ячейках `dynamic_rows` превращаются в строки (`5` → `'5'`), чтобы все ячейки были одного типа.

### 3. Всё, что не указано, заполняется

```php
'site_name' => ['type' => 'text']
```

превращается в

```php
'site_name' => [
    'type' => 'text', 'label' => 'site_name', 'sort_order' => 0,
    'default' => '', 'placeholder' => '', 'comment' => '', 'class' => '',
    'validation' => [], 'required' => false, 'provider' => '', 'depends' => null,
]
```

Если у `number` есть `min`, а `default` не указан, значением по умолчанию станет не `0`, а ближайшая граница диапазона:

```php
['type' => 'number', 'min' => 5]  // default станет 5, а не 0
```

### 4. Неизвестные ключи это ошибка

Опечатка в ключе не должна пропадать молча. Если написать `defualt` вместо `default`, значение бы потерялось, и ты узнал бы об этом только на странице настроек. Теперь библиотека останавливается сразу и подсказывает:

```text
sections.api.groups.auth.fields.site_name: Unknown key 'defualt' in field 'site_name'.
Did you mean 'default'? Allowed keys: type, label, sort_order, depends, comment, class,
validation, required, provider, default, placeholder. Custom keys must start with 'x_'.
```

То же на корневом уровне:

```text
Unknown key 'tab' in the configuration root. Did you mean 'tabs'? Allowed keys: tabs, sections.
Custom keys must start with 'x_'.
```

Допустимые ключи зависят от типа поля. `placeholder` у `select` или `options` у `yes_no` тоже считаются неизвестными ключами.

#### Свои параметры: префикс `x_`

Если модулю нужен собственный параметр (например, класс модели для источника данных), назови его с префиксом `x_`. Такие ключи разрешены на любом уровне и попадают в результат **как есть, без проверки**:

```php
'country' => [
    'type'             => 'select',
    'options'          => ['de' => 'Germany'],
    'x_source_model'   => 'My\\Module\\Model\\Countries', // попадёт в результат без изменений
],
```

Формат: `x_`, затем строчные буквы, цифры и подчёркивания. `x_`, `X_custom`, `x_my-key` не подходят.

### 5. `null` значит «не задано»

```php
'label' => null  // то же самое, что убрать ключ: label станет равен id
```

Это удобно для массивов, собранных программно.

### 6. Сортировка

Узлы каждого уровня упорядочиваются по `sort_order` по возрастанию. Сортировка **стабильная**: узлы с одинаковым `sort_order` остаются в том порядке, в каком были описаны.

## Ошибки

Первая найденная ошибка останавливает обработку и выбрасывает `Modestox\ConfigProcessor\Exception\InvalidConfigException`. Сообщение начинается с пути к проблемному месту:

```text
sections.api.groups.auth.fields.cache_timeout: Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
```

Путь нужен, потому что одинаковые id полей могут быть в разных группах.

```php
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

try {
    $clean = $processor->process($raw, new SystemConfig());
} catch (InvalidConfigException $e) {
    echo $e->getPath();    // sections.api.groups.auth.fields.cache_timeout
    echo $e->getReason();  // Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
    echo $e->getMessage(); // путь + двоеточие + причина
}
```

Если ошибка в самом корне, путь пустой, а `getMessage()` равно `getReason()`.

Ошибки программиста (например, регистрация неверного валидатора типа) выбрасывают обычный `\InvalidArgumentException`.

### Примеры: что написал и что получил

| Что в описании | Сообщение (после пути) |
| :--- | :--- |
| `'Site Name' => [...]` | `The field identifier key 'Site Name' contains invalid characters. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).` |
| `'123' => [...]` | `The field identifier key must be a valid string. Numeric keys are cast to integers by PHP and are not supported.` |
| `'type' => 'colour'` | `Unsupported type 'colour' in field 'f'. Supported: text, boolean, select, ...` |
| раздел с `'tab' => 'other'` | `Section 'api' references an undefined parent tab 'other'. Defined tabs: main_tab.` |
| раздел при отсутствии `tabs` | `Section 'api' references an undefined parent tab 'other'. No tabs are defined.` |
| `number`: `min` 100, `max` 10 | `Field 'max' cannot be less than 'min' for number field 'cache_timeout'.` |
| `number`: `'default' => '10'` | `Field 'default' for number field 'n' must be a strict integer or float.` |
| `datetime` `date`: `'2026-02-31'` | `Default value '2026-02-31' for field 'd' does not match mandatory 'date' format standard ('Y-m-d').` |
| `file`: `'upload_dir' => '../etc'` | `Field 'upload_dir' for field 'f' must not contain '..' segments or null bytes.` |
| `dynamic_rows`: лишний столбец в строке | `Row at index '0' inside dynamic rows field 'r' contains unknown column 'zzz'.` |
| `multiselect`: `['DE', 'DE']` | `The default value 'DE' for field 'c' is listed more than once.` |

## Зависимости полей (`depends`)

Поле может зависеть от других полей **той же группы**. В `depends` указывается id родителя и значение, при котором поле должно быть видно:

```php
'fields' => [
    'mode' => [
        'type'    => 'select',
        'options' => ['live' => 'Live', 'test' => 'Test'],
    ],
    'live_key' => [
        'type'    => 'password',
        'depends' => ['mode' => 'live'],   // видно, только если mode = live
    ],
    'debug' => [
        'type'    => 'boolean',
        'depends' => ['mode' => 'test', 'live_key' => 'x'], // можно несколько условий
    ],
],
```

Что проверяется:

| Ситуация | Результат |
| :--- | :--- |
| Родителя нет в группе | Ошибка: `depends on an undefined parent field 'ghost'` |
| Поле зависит от самого себя | Ошибка цикла: `a -> a` |
| Цепочка по кругу | Ошибка цикла: `a -> b -> a` |
| Родитель `infoblock` | Ошибка: у него нет значения |
| Родитель `select`, `radio`, `yes_no` | Ожидаемое значение должно быть одним из ключей `options` |
| Родитель `boolean` | Ожидаемое значение должно быть `true` или `false` |
| Родитель `number` | Ожидаемое значение должно быть `int` или `float` |
| Родитель `text`, `multiselect` и другие | Значение не проверяется |

Примеры сообщений:

```text
Field 'key' expects the value "prod" from 'mode', which is not one of its options (live, test).
Field 'key' cannot depend on infoblock 'note': it holds no value.
Field 'key' expects the value 1 from boolean field 'on'; use true or false.
Circular dependency detected between fields: a -> b -> a.
```

Порядок описания полей не важен: `live_key` может стоять в массиве раньше `mode`.

Ожидаемое значение может быть только скаляром (строка, число, bool). Несколько допустимых значений сейчас указать нельзя.

## Типы полей

| Тип (`type`) | Назначение |
| :--- | :--- |
| `text`, `textarea` | Текст; `default` и `placeholder` обрезаются по краям. |
| `password` | Секрет; `default` обрезается. |
| `boolean` | `default` строго `true` или `false`. |
| `yes_no` | Выпадающий список с фиксированными `[0 => 'No', 1 => 'Yes']`. |
| `number` | Только `int` и `float`, конечные; `min <= default <= max`. |
| `datetime` | `datetime`, `date` или `time` с проверкой реального календаря. |
| `dynamic_rows` | Таблица с заданными столбцами. |
| `file`, `image` | Описание правил загрузки: `upload_dir`, `extensions`. |
| `select`, `radio` | Один вариант из списка; без `default` берётся первый. |
| `multiselect`, `checkbox` | Несколько вариантов из списка. |
| `infoblock` | Информационный блок (не поле ввода). |

Подробно про каждый тип, его ключи, правила и примеры: [fields.ru.md](fields.ru.md).

## Свои типы полей

Свой тип поля это класс, который наследует `AbstractFieldValidator`. Он делает две вещи: перечисляет **свои** ключи в `specificKeys()` и проверяет значения в `validate()`.

```php
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\AbstractFieldValidator;

final class ColorPicker extends AbstractFieldValidator
{
    /** Ключи, которые разрешены только этому типу (общие ключи добавятся сами). */
    protected function specificKeys(): array
    {
        return ['default', 'palette'];
    }

    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $default = $this->validateString($fieldData, 'default', $fieldId, '#000000');
        if (!preg_match('/^#[0-9a-f]{6}$/iD', $default)) {
            throw new InvalidConfigException("Field '{$fieldId}' must have a hex color default.");
        }

        $palette = $this->validateString($fieldData, 'palette', $fieldId, 'basic');

        return array_merge(
            $baseMeta,                                  // type, label, sort_order
            ['default' => $default, 'palette' => $palette],
            $this->validateCommon($fieldId, $fieldData) // comment, class, validation, required, provider
        );
    }
}
```

Подключение к схеме:

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$schema = SystemConfig::withFieldTypes(['colorpicker' => new ColorPicker()]);
// то же есть у GroupedConfig::withFieldTypes([...])

$clean = (new Processor())->process($raw, $schema);
```

Теперь в описании можно писать `'type' => 'colorpicker'`. Опечатка в ключе вроде `pallete` даст ту же подсказку `Did you mean 'palette'?`, что и у встроенных типов.

Правила:

- Имя типа подходит под `^[a-z0-9_]+$`.
- Можно переопределить встроенный тип, зарегистрировав своё имя `text`, `select` и т. д.
- Если не хочешь наследоваться от `AbstractFieldValidator`, реализуй `FieldValidatorInterface` целиком. Тогда `allowedKeys()` должен вернуть **все** допустимые ключи, включая `type`, `label`, `sort_order`, `depends`.
- Хелперы базового класса: `validateString`, `validateBool`, `validateArray`, `validateOptions`, `validateCommon`.

## Чего библиотека не делает

- **Не проверяет значения, введённые пользователем.** Она проверяет *описание* настроек. Данные из формы (`$_POST`) приходят строками, и строгая проверка типов их отклонит. Для значений нужен отдельный слой.
- **Не экранирует текст.** `label`, `comment` и текст `infoblock` возвращаются как есть. Экранируй при выводе (`htmlspecialchars`), особенно для `format => 'html'`.
- **Не проверяет, что папки и файлы существуют.** `upload_dir` лишь проверяется на `..` и нулевые байты. Применение решает твой код.
- **Не загружает `provider`.** Это просто строка с именем класса, она не проверяется и не вызывается.
- **Не собирает все ошибки сразу.** Останавливается на первой. Путь в сообщении помогает быстро её найти.

## Структура репозитория

```text
src/
├── Processor.php                      точка входа
├── Exception/InvalidConfigException   ошибка с путём
├── Schema/
│   ├── SchemaInterface                контракт схемы
│   ├── SystemConfig                   tabs → sections → groups → fields
│   └── GroupedConfig                  groups → fields
└── Validator/
    ├── ValidatorInterface             контракт валидатора уровня
    ├── Identifier                     проверка идентификаторов
    ├── Keys                           неизвестные ключи, x_ и подсказки
    └── SystemConfig/
        ├── AbstractNodeValidator      общая логика уровней
        ├── Tabs, Sections, Groups, Fields
        └── Fields/                    валидаторы типов полей
tests/                                 PHPUnit
fields.ru.md                           справочник по типам полей (+ fields.md, fields.de.md)
```

## Разработка и тесты

```bash
composer install
composer test
```

В DDEV: `ddev composer install` и `ddev exec vendor/bin/phpunit`.

## Переход с 1.x на 2.0

Версия 2.0 строже. Конфиг, который проходил в 1.x, может перестать проходить. Что изменилось:

| Что | Было в 1.x | В 2.0 |
| :--- | :--- | :--- |
| Неизвестные ключи | Молча отбрасывались | Ошибка с подсказкой; свои ключи только с префиксом `x_` |
| `options` у `yes_no` | Молча заменялись | Ошибка |
| Сообщения об ошибках | Без пути | Путь в начале, есть `getPath()` и `getReason()` |
| `depends` | Проверялось только существование родителя | Ещё: циклы, значения, `infoblock` как родитель |
| Дубли в `default` у `multiselect` | Принимались | Ошибка |
| `default` у `select`/`multiselect` | Оставался как написан (`'1'`) | Приводится к типу ключа опции (`1`) |
| Идентификатор с `\n` в конце | Принимался (баг) | Ошибка |
| `number` без `default` при `min > 0` | `default = 0` (вне диапазона) | `default = min` |
| `dynamic_rows` | Лишние столбцы молча терялись | Ошибка; нужен хотя бы один столбец |
| `datetime` с неверным `view_mode` | Молча заменялся на `datetime` | Ошибка |
| `file`: `extensions`, `upload_dir` | Без проверки | Формат `jpg,png`; `..` запрещены |
| Tabs и Sections | Без `class` и `icon`, если не заданы | Всегда есть, по умолчанию `''` |
| `FieldValidatorInterface` | Только `validate()` | Ещё `allowedKeys()` |

Подробности: [CHANGELOG.ru.md](CHANGELOG.ru.md).

## Лицензия

MIT. См. [LICENSE](LICENSE).