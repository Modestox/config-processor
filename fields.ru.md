# Справочник по типам полей

[English](fields.md) | **Русский** | [Deutsch](fields.de.md)

Все примеры ниже можно вставлять в `fields` любой группы. Результаты показаны в сокращённом виде: без общих ключей (`label`, `sort_order`, `comment`, `class`, `validation`, `required`, `provider`, `depends`), они одинаковы у всех типов и описаны сразу ниже.

## Содержание

- [Общие свойства](#общие-свойства)
- [`text` и `textarea`](#text-и-textarea)
- [`password`](#password)
- [`boolean`](#boolean)
- [`yes_no`](#yes_no)
- [`number`](#number)
- [`datetime`](#datetime)
- [`dynamic_rows`](#dynamic_rows)
- [`file` и `image`](#file-и-image)
- [`select` и `radio`](#select-и-radio)
- [`multiselect` и `checkbox`](#multiselect-и-checkbox)
- [`infoblock`](#infoblock)
- [Памятка: какие ключи у какого типа](#памятка-какие-ключи-у-какого-типа)

## Общие свойства

Эти ключи принимает **любой** тип поля.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `type` | string | **обязателен** | Тип поля. Пробелы по краям обрезаются. |
| `label` | string | id поля | Подпись. Пустая строка или `null` заменяются на id поля. |
| `sort_order` | int | `0` | Порядок внутри группы. Сортировка стабильная. |
| `comment` | string | `''` | Подсказка под полем. Обрезается. |
| `class` | string | `''` | CSS-класс. Обрезается. |
| `validation` | array of string | `[]` | Список имён правил валидации, например `['required-entry']`. Каждое обрезается. Библиотека их не выполняет, а только передаёт дальше. |
| `required` | bool | `false` | Обязательно ли поле. Только `true` или `false`. |
| `provider` | string | `''` | Имя класса поставщика данных. Хранится как строка и не проверяется. |
| `depends` | array | `null` | Условия видимости, см. [«Зависимости полей» в README](README.ru.md#зависимости-полей-depends). |
| `x_...` | любой | | Свои параметры с префиксом `x_`. Попадают в результат без изменений. |

Любой другой ключ это ошибка.

Пример общих ключей:

```php
'site_name' => [
    'type'       => 'text',
    'label'      => '  Site name ',
    'comment'    => ' Shown in the browser tab ',
    'class'      => ' wide ',
    'validation' => [' required-entry ', 'validate-url'],
    'required'   => true,
    'provider'   => ' My\Provider ',
]
```

Результат:

```php
[
    'type'       => 'text',
    'label'      => 'Site name',
    'sort_order' => 0,
    'default'    => '',
    'placeholder' => '',
    'comment'    => 'Shown in the browser tab',
    'class'      => 'wide',
    'validation' => ['required-entry', 'validate-url'],
    'required'   => true,
    'provider'   => 'My\Provider',
    'depends'    => null,
]
```

---

## `text` и `textarea`

Однострочное и многострочное поле. Правила одинаковые.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `default` | string | `''` | Значение по умолчанию. Обрезается. |
| `placeholder` | string | `''` | Подсказка внутри поля. Обрезается. |

```php
['type' => 'text', 'default' => '  Hello  ']
// → ['type' => 'text', 'default' => 'Hello', 'placeholder' => '']
```

## `password`

Секрет (пароль, API-ключ). Библиотека ничего не шифрует и не маскирует, это задача рендерера.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `default` | string | `''` | Обрезается, чтобы случайные пробелы при копировании не ломали ключ. |

Число или другой тип в `default` это ошибка: `Field 'default' for field 'f' must be a strict string.`

## `boolean`

Переключатель «да/нет».

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `default` | bool | `false` | Только настоящие `true` или `false`. |

```php
['type' => 'boolean']                    // → default: false
['type' => 'boolean', 'default' => 1]    // ошибка: 1 не является bool
['type' => 'boolean', 'default' => 'true'] // ошибка: Field 'default' for field 'f' must be a strict boolean.
```

## `yes_no`

Выпадающий список «No / Yes» без описания вариантов.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `default` | `0` или `1` | `0` | Строки `'0'` и `'1'` тоже подходят, они приводятся к числу. |

Варианты всегда `[0 => 'No', 1 => 'Yes']`. Ключ `options` для этого типа **запрещён** (раньше он молча заменялся).

```php
['type' => 'yes_no']                   // → options [0 => 'No', 1 => 'Yes'], default 0
['type' => 'yes_no', 'default' => '1'] // → default 1
['type' => 'yes_no', 'default' => 2]   // ошибка: The default value '2' for field 'f' does not exist within the permitted options scope.
['type' => 'yes_no', 'options' => [1 => 'On']] // ошибка: Unknown key 'options' in field 'f'.
```

## `number`

Числовое поле.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `min` | int или float | нет | Нижняя граница. |
| `max` | int или float | нет | Верхняя граница. |
| `default` | int или float | `0`, см. ниже | Значение по умолчанию. |

Правила:

- Только `int` и `float`. Строка `"10"` это ошибка.
- `NAN` и `INF` запрещены.
- `max` не может быть меньше `min`.
- Если `default` указан, он должен лежать в диапазоне (границы включительно).
- Если `default` **не указан**, берётся `0`. А если `0` вне диапазона, то ближайшая граница.

```php
['type' => 'number']                                  // default 0
['type' => 'number', 'min' => 5]                      // default 5, а не 0
['type' => 'number', 'min' => -10, 'max' => -1]       // default -1
['type' => 'number', 'min' => 0.5, 'max' => 2.5, 'default' => 1] // default 1
['type' => 'number', 'min' => 100, 'max' => 10]       // ошибка: Field 'max' cannot be less than 'min'...
['type' => 'number', 'min' => '1']                    // ошибка: Field 'min' for number field 'f' must be a strict integer or float.
```

## `datetime`

Дата, время или дата с временем. Формат фиксированный, проверяется реальный календарь.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `view_mode` | string | `'datetime'` | `datetime`, `date` или `time`. |
| `default` | string | `''` | Значение в формате выбранного режима. Обрезается. Пустая строка допустима. |

| `view_mode` | Формат | Пример |
| :--- | :--- | :--- |
| `datetime` | `Y-m-d H:i:s` | `2026-11-25 18:30:00` |
| `date` | `Y-m-d` | `2026-06-01` |
| `time` | `H:i:s` | `14:30:00` |

```php
['type' => 'datetime']                                            // view_mode 'datetime', default ''
['type' => 'datetime', 'view_mode' => 'time', 'default' => ' 14:30:00 '] // default '14:30:00'
['type' => 'datetime', 'view_mode' => 'date', 'default' => '2026-02-31']  // ошибка: такой даты нет
['type' => 'datetime', 'view_mode' => 'year']                     // ошибка: Unsupported view_mode 'year'. Supported: datetime, date, time.
```

## `dynamic_rows`

Таблица, в которую администратор добавляет строки (например, тарифы доставки).

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `columns` | array | **обязателен** | `id столбца => заголовок`. Ключ и заголовок обрезаются. |
| `default` | array of array | `[]` | Строки по умолчанию. |

Правила для `columns`:

- Нужен хотя бы один столбец.
- Id столбца это строка, не пустая после обрезки. Два id, совпавшие после обрезки (`'a'` и `' a'`), это ошибка.
- Id из одних цифр не подходит (PHP превратит его в число).

Правила для строк в `default`:

- Каждая строка это массив.
- В строке нельзя использовать столбец, которого нет в `columns`.
- Значения ячеек: строка или число. Число превращается в строку. Массив или `bool` это ошибка.
- Если ячейки нет, подставляется `''`, поэтому форма всех строк одинакова.

```php
[
    'type'    => 'dynamic_rows',
    'columns' => ['weight' => ' Weight ', 'cost' => 'Cost'],
    'default' => [
        ['weight' => 5, 'cost' => ' 12.5 '],
        ['weight' => '10'],                     // cost отсутствует
    ],
]
// → columns: ['weight' => 'Weight', 'cost' => 'Cost']
//   default: [['weight' => '5', 'cost' => '12.5'], ['weight' => '10', 'cost' => '']]
```

```php
['type' => 'dynamic_rows', 'columns' => []]
// ошибка: Field 'columns' for dynamic rows field 'f' must declare at least one column.
['type' => 'dynamic_rows', 'columns' => ['w' => 'W'], 'default' => [['w' => '1', 'zzz' => '2']]]
// ошибка: Row at index '0' inside dynamic rows field 'f' contains unknown column 'zzz'.
```

## `file` и `image`

Описание правил загрузки файла или картинки. `image` ничем не отличается от `file`, это отдельное имя для рендерера.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `upload_dir` | string | `''` | Папка назначения. Обрезается. Нельзя использовать сегмент `..` и нулевые байты. |
| `extensions` | string | `''` | Список расширений через запятую, **без точек**. Все пробелы удаляются. |

```php
['type' => 'file', 'upload_dir' => ' media/docs ', 'extensions' => ' pdf, docx ']
// → upload_dir 'media/docs', extensions 'pdf,docx'

['type' => 'image', 'upload_dir' => 'media/v1..2/files']   // можно: две точки внутри имени не считаются обходом пути
['type' => 'file', 'upload_dir' => '../etc']               // ошибка: must not contain '..' segments or null bytes.
['type' => 'file', 'extensions' => '.jpg']                 // ошибка: must be a comma-separated list of extensions without dots (e.g. 'jpg,png').
['type' => 'file', 'extensions' => 'jpg,,png']             // ошибка: пустой элемент списка
```

Библиотека **не** проверяет, существует ли папка и разрешена ли она. Это решает твой код при загрузке.

## `select` и `radio`

Выбор одного варианта. Правила `radio` те же.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `options` | array | **обязателен** | `ключ => подпись`. Не пустой. Подписи обрезаются. |
| `default` | string или int | первый ключ из `options` | Один из ключей `options`. |

Правила:

- Ключи вариантов: строки или целые числа. Подписи: строки.
- `default` должен быть среди ключей `options`.
- Числовые строки приводятся к числу так же, как PHP делает с ключами массива, поэтому `'2'` станет `2`, если вариант записан как `2 => 'Two'`.

```php
['type' => 'select', 'options' => ['b' => ' B ', 'a' => 'A']]
// → options ['b' => 'B', 'a' => 'A'], default 'b' (первый)

['type' => 'select', 'options' => [1 => 'One', 2 => 'Two'], 'default' => '2']
// → default 2 (число, как у ключа)

['type' => 'select', 'options' => []]
// ошибка: Field 'f' of type 'select' must contain a non-empty array of 'options'.
['type' => 'select', 'options' => ['a' => 'A'], 'default' => 'b']
// ошибка: The default value 'b' for field 'f' does not exist within the permitted options scope.
```

В сообщениях стоит реальный тип поля (`radio` для radio).

## `multiselect` и `checkbox`

Выбор нескольких вариантов. Правила `checkbox` те же.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `options` | array | **обязателен** | Как у `select`. |
| `default` | array | `[]` | Список выбранных ключей. |

Правила для `default`:

- Это массив ключей из `options`.
- Повторы запрещены. `'1'` и `1` считаются одним и тем же вариантом.
- Результат всегда **список** (`[...]`), ключи приводятся к типу ключей `options`, порядок сохраняется.

```php
['type' => 'multiselect', 'options' => ['de' => 'DE', 'it' => 'IT', 'fr' => 'FR'], 'default' => ['it', 'de']]
// → default ['it', 'de']

['type' => 'multiselect', 'options' => [1 => 'One', 2 => 'Two'], 'default' => ['2', 1]]
// → default [2, 1]

['type' => 'multiselect', 'options' => ['DE' => 'Germany'], 'default' => ['DE', 'DE']]
// ошибка: The default value 'DE' for field 'f' is listed more than once.
['type' => 'multiselect', 'options' => ['a' => 'A'], 'default' => 'a']
// ошибка: Field 'default' for multiselect field 'f' must be a strict array of keys.
```

## `infoblock`

Информационный блок. Это не поле ввода: ему не нужно значение, он только показывает текст.

| Ключ | Тип | По умолчанию | Описание |
| :--- | :--- | :--- | :--- |
| `text` | string | **обязателен** | Текст блока. Обрезается. |
| `format` | string | `'text'` | `text` или `html`. Пробелы по краям обрезаются. |

```php
['type' => 'infoblock', 'text' => '  Careful!  ']
// → text 'Careful!', format 'text'

['type' => 'infoblock', 'text' => '<b>Careful</b>', 'format' => 'html']
```

Что это значит для рендерера:

- `text`: выводить как обычный текст, **обязательно экранировать** (`htmlspecialchars`).
- `html`: разметка допускается, безопасность содержимого на твоей стороне.

Библиотека текст не очищает и не экранирует.

Другие поля **не могут** зависеть от `infoblock`: у него нет значения.

```php
['type' => 'infoblock']                                         // ошибка: Field 'text' is mandatory for infoblock type in 'f'.
['type' => 'infoblock', 'text' => 'x', 'format' => 'markdown']  // ошибка: Unsupported format 'markdown' in infoblock 'f'. Supported: text, html.
```

---

## Памятка: какие ключи у какого типа

Кроме общих ключей (`type`, `label`, `sort_order`, `depends`, `comment`, `class`, `validation`, `required`, `provider`):

| Тип | Дополнительные ключи |
| :--- | :--- |
| `text`, `textarea` | `default`, `placeholder` |
| `password` | `default` |
| `boolean` | `default` |
| `yes_no` | `default` |
| `number` | `default`, `min`, `max` |
| `datetime` | `view_mode`, `default` |
| `dynamic_rows` | `columns`, `default` |
| `file`, `image` | `upload_dir`, `extensions` |
| `select`, `radio` | `options`, `default` |
| `multiselect`, `checkbox` | `options`, `default` |
| `infoblock` | `text`, `format` |

Ключ из другой строки таблицы это ошибка `Unknown key`. Так ловятся опечатки и случайно скопированные параметры.