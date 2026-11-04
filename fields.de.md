# Referenz der Feldtypen

[English](fields.md) | [Русский](fields.ru.md) | **Deutsch**

Alle Beispiele unten lassen sich in `fields` einer beliebigen Gruppe einfügen. Die Ergebnisse sind verkürzt dargestellt: ohne die gemeinsamen Schlüssel (`label`, `sort_order`, `comment`, `class`, `validation`, `required`, `provider`, `depends`), die bei allen Typen gleich sind und gleich unten beschrieben werden.

## Inhalt

- [Gemeinsame Eigenschaften](#gemeinsame-eigenschaften)
- [`text` und `textarea`](#text-und-textarea)
- [`password`](#password)
- [`boolean`](#boolean)
- [`yes_no`](#yes_no)
- [`number`](#number)
- [`datetime`](#datetime)
- [`dynamic_rows`](#dynamic_rows)
- [`file` und `image`](#file-und-image)
- [`select` und `radio`](#select-und-radio)
- [`multiselect` und `checkbox`](#multiselect-und-checkbox)
- [`infoblock`](#infoblock)
- [Übersicht: welche Schlüssel zu welchem Typ gehören](#übersicht-welche-schlüssel-zu-welchem-typ-gehören)

## Gemeinsame Eigenschaften

Diese Schlüssel akzeptiert **jeder** Feldtyp.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `type` | string | **Pflicht** | Feldtyp. Leerzeichen an den Rändern werden entfernt. |
| `label` | string | Feld-ID | Beschriftung. Ein leerer String oder `null` wird durch die Feld-ID ersetzt. |
| `sort_order` | int | `0` | Reihenfolge innerhalb der Gruppe. Die Sortierung ist stabil. |
| `comment` | string | `''` | Hinweis unter dem Feld. Wird getrimmt. |
| `class` | string | `''` | CSS-Klasse. Wird getrimmt. |
| `validation` | array of string | `[]` | Liste der Namen von Validierungsregeln, z. B. `['required-entry']`. Jeder Eintrag wird getrimmt. Die Bibliothek führt sie nicht aus, sondern reicht sie nur weiter. |
| `required` | bool | `false` | Ob das Feld Pflicht ist. Nur `true` oder `false`. |
| `provider` | string | `''` | Name einer Datenanbieter-Klasse. Wird als String gespeichert und nicht geprüft. |
| `depends` | array | `null` | Sichtbarkeitsbedingungen, siehe [„Feldabhängigkeiten“ in der README](README.de.md#feldabhängigkeiten-depends). |
| `x_...` | beliebig | | Eigene Parameter mit dem Präfix `x_`. Gelangen unverändert ins Ergebnis. |

Jeder andere Schlüssel ist ein Fehler.

Beispiel für die gemeinsamen Schlüssel:

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

Ergebnis:

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

## `text` und `textarea`

Ein einzeiliges und ein mehrzeiliges Feld. Die Regeln sind gleich.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `default` | string | `''` | Standardwert. Wird getrimmt. |
| `placeholder` | string | `''` | Hinweis im Feld. Wird getrimmt. |

```php
['type' => 'text', 'default' => '  Hello  ']
// → ['type' => 'text', 'default' => 'Hello', 'placeholder' => '']
```

## `password`

Ein Geheimnis (Passwort, API-Schlüssel). Die Bibliothek verschlüsselt und maskiert nichts, das ist Aufgabe des Renderers.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `default` | string | `''` | Wird getrimmt, damit versehentliche Leerzeichen beim Kopieren den Schlüssel nicht kaputt machen. |

Eine Zahl oder ein anderer Typ in `default` ist ein Fehler: `Field 'default' for field 'f' must be a strict string.`

## `boolean`

Ein Ja/Nein-Schalter.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `default` | bool | `false` | Nur ein echtes `true` oder `false`. |

```php
['type' => 'boolean']                      // → default: false
['type' => 'boolean', 'default' => 1]      // Fehler: 1 ist kein bool
['type' => 'boolean', 'default' => 'true'] // Fehler: Field 'default' for field 'f' must be a strict boolean.
```

## `yes_no`

Ein „No / Yes“-Dropdown ohne Beschreibung der Optionen.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `default` | `0` oder `1` | `0` | Die Strings `'0'` und `'1'` passen ebenfalls, sie werden in eine Zahl umgewandelt. |

Die Optionen sind immer `[0 => 'No', 1 => 'Yes']`. Der Schlüssel `options` ist für diesen Typ **verboten** (früher wurde er stillschweigend ersetzt).

```php
['type' => 'yes_no']                   // → options [0 => 'No', 1 => 'Yes'], default 0
['type' => 'yes_no', 'default' => '1'] // → default 1
['type' => 'yes_no', 'default' => 2]   // Fehler: The default value '2' for field 'f' does not exist within the permitted options scope.
['type' => 'yes_no', 'options' => [1 => 'On']] // Fehler: Unknown key 'options' in field 'f'.
```

## `number`

Ein Zahlenfeld.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `min` | int oder float | keiner | Untere Grenze. |
| `max` | int oder float | keiner | Obere Grenze. |
| `default` | int oder float | `0`, siehe unten | Standardwert. |

Regeln:

- Nur `int` und `float`. Der String `"10"` ist ein Fehler.
- `NAN` und `INF` sind verboten.
- `max` darf nicht kleiner als `min` sein.
- Ist `default` angegeben, muss er im Bereich liegen (Grenzen eingeschlossen).
- Ist `default` **nicht angegeben**, wird `0` genommen. Liegt `0` außerhalb des Bereichs, die nächstgelegene Grenze.

```php
['type' => 'number']                                  // default 0
['type' => 'number', 'min' => 5]                      // default 5, nicht 0
['type' => 'number', 'min' => -10, 'max' => -1]       // default -1
['type' => 'number', 'min' => 0.5, 'max' => 2.5, 'default' => 1] // default 1
['type' => 'number', 'min' => 100, 'max' => 10]       // Fehler: Field 'max' cannot be less than 'min'...
['type' => 'number', 'min' => '1']                    // Fehler: Field 'min' for number field 'f' must be a strict integer or float.
```

## `datetime`

Ein Datum, eine Uhrzeit oder Datum mit Uhrzeit. Das Format ist fest, und der echte Kalender wird geprüft.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `view_mode` | string | `'datetime'` | `datetime`, `date` oder `time`. |
| `default` | string | `''` | Wert im Format des gewählten Modus. Wird getrimmt. Ein leerer String ist erlaubt. |

| `view_mode` | Format | Beispiel |
| :--- | :--- | :--- |
| `datetime` | `Y-m-d H:i:s` | `2026-11-25 18:30:00` |
| `date` | `Y-m-d` | `2026-06-01` |
| `time` | `H:i:s` | `14:30:00` |

```php
['type' => 'datetime']                                                    // view_mode 'datetime', default ''
['type' => 'datetime', 'view_mode' => 'time', 'default' => ' 14:30:00 ']  // default '14:30:00'
['type' => 'datetime', 'view_mode' => 'date', 'default' => '2026-02-31']  // Fehler: dieses Datum gibt es nicht
['type' => 'datetime', 'view_mode' => 'year']                             // Fehler: Unsupported view_mode 'year'. Supported: datetime, date, time.
```

## `dynamic_rows`

Eine Tabelle, zu der der Administrator Zeilen hinzufügt (zum Beispiel Versandtarife).

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `columns` | array | **Pflicht** | `Spalten-ID => Titel`. Schlüssel und Titel werden getrimmt. |
| `default` | array of array | `[]` | Standardzeilen. |

Regeln für `columns`:

- Mindestens eine Spalte ist nötig.
- Eine Spalten-ID ist ein String, der nach dem Trimmen nicht leer ist. Zwei IDs, die nach dem Trimmen zusammenfallen (`'a'` und `' a'`), sind ein Fehler.
- Eine ID nur aus Ziffern ist nicht zulässig (PHP würde sie in eine Zahl umwandeln).

Regeln für Zeilen in `default`:

- Jede Zeile ist ein Array.
- Eine Zeile darf keine Spalte verwenden, die nicht in `columns` steht.
- Zellwerte: String oder Zahl. Eine Zahl wird zum String. Ein Array oder `bool` ist ein Fehler.
- Fehlt eine Zelle, wird `''` eingesetzt, damit alle Zeilen dieselbe Form haben.

```php
[
    'type'    => 'dynamic_rows',
    'columns' => ['weight' => ' Weight ', 'cost' => 'Cost'],
    'default' => [
        ['weight' => 5, 'cost' => ' 12.5 '],
        ['weight' => '10'],                     // cost fehlt
    ],
]
// → columns: ['weight' => 'Weight', 'cost' => 'Cost']
//   default: [['weight' => '5', 'cost' => '12.5'], ['weight' => '10', 'cost' => '']]
```

```php
['type' => 'dynamic_rows', 'columns' => []]
// Fehler: Field 'columns' for dynamic rows field 'f' must declare at least one column.
['type' => 'dynamic_rows', 'columns' => ['w' => 'W'], 'default' => [['w' => '1', 'zzz' => '2']]]
// Fehler: Row at index '0' inside dynamic rows field 'f' contains unknown column 'zzz'.
```

## `file` und `image`

Eine Beschreibung der Regeln für den Upload einer Datei oder eines Bildes. `image` unterscheidet sich nicht von `file`, es ist nur ein eigener Name für den Renderer.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `upload_dir` | string | `''` | Zielordner. Wird getrimmt. Das Segment `..` und Nullbytes sind nicht erlaubt. |
| `extensions` | string | `''` | Kommagetrennte Liste der Endungen, **ohne Punkte**. Alle Leerzeichen werden entfernt. |

```php
['type' => 'file', 'upload_dir' => ' media/docs ', 'extensions' => ' pdf, docx ']
// → upload_dir 'media/docs', extensions 'pdf,docx'

['type' => 'image', 'upload_dir' => 'media/v1..2/files']   // erlaubt: zwei Punkte innerhalb eines Namens sind keine Pfadumgehung
['type' => 'file', 'upload_dir' => '../etc']               // Fehler: must not contain '..' segments or null bytes.
['type' => 'file', 'extensions' => '.jpg']                 // Fehler: must be a comma-separated list of extensions without dots (e.g. 'jpg,png').
['type' => 'file', 'extensions' => 'jpg,,png']             // Fehler: leeres Listenelement
```

Die Bibliothek prüft **nicht**, ob der Ordner existiert oder erlaubt ist. Das entscheidet dein Code beim Upload.

## `select` und `radio`

Auswahl einer Option. `radio` folgt denselben Regeln.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `options` | array | **Pflicht** | `Schlüssel => Beschriftung`. Nicht leer. Beschriftungen werden getrimmt. |
| `default` | string oder int | erster Schlüssel aus `options` | Einer der Schlüssel aus `options`. |

Regeln:

- Schlüssel der Optionen: Strings oder Ganzzahlen. Beschriftungen: Strings.
- `default` muss unter den Schlüsseln von `options` sein.
- Numerische Strings werden so in Zahlen umgewandelt, wie PHP es bei Array-Schlüsseln tut; `'2'` wird also zu `2`, wenn die Option als `2 => 'Two'` geschrieben ist.

```php
['type' => 'select', 'options' => ['b' => ' B ', 'a' => 'A']]
// → options ['b' => 'B', 'a' => 'A'], default 'b' (der erste)

['type' => 'select', 'options' => [1 => 'One', 2 => 'Two'], 'default' => '2']
// → default 2 (eine Zahl, wie der Schlüssel)

['type' => 'select', 'options' => []]
// Fehler: Field 'f' of type 'select' must contain a non-empty array of 'options'.
['type' => 'select', 'options' => ['a' => 'A'], 'default' => 'b']
// Fehler: The default value 'b' for field 'f' does not exist within the permitted options scope.
```

In den Meldungen steht der echte Feldtyp (`radio` bei radio).

## `multiselect` und `checkbox`

Auswahl mehrerer Optionen. `checkbox` folgt denselben Regeln.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `options` | array | **Pflicht** | Wie bei `select`. |
| `default` | array | `[]` | Liste der ausgewählten Schlüssel. |

Regeln für `default`:

- Es ist ein Array von Schlüsseln aus `options`.
- Wiederholungen sind verboten. `'1'` und `1` zählen als dieselbe Option.
- Das Ergebnis ist immer eine **Liste** (`[...]`), die Schlüssel werden auf den Typ der `options`-Schlüssel gebracht, die Reihenfolge bleibt erhalten.

```php
['type' => 'multiselect', 'options' => ['de' => 'DE', 'it' => 'IT', 'fr' => 'FR'], 'default' => ['it', 'de']]
// → default ['it', 'de']

['type' => 'multiselect', 'options' => [1 => 'One', 2 => 'Two'], 'default' => ['2', 1]]
// → default [2, 1]

['type' => 'multiselect', 'options' => ['DE' => 'Germany'], 'default' => ['DE', 'DE']]
// Fehler: The default value 'DE' for field 'f' is listed more than once.
['type' => 'multiselect', 'options' => ['a' => 'A'], 'default' => 'a']
// Fehler: Field 'default' for multiselect field 'f' must be a strict array of keys.
```

## `infoblock`

Ein Informationsblock. Er ist kein Eingabefeld: Er braucht keinen Wert und zeigt nur Text.

| Schlüssel | Typ | Standard | Beschreibung |
| :--- | :--- | :--- | :--- |
| `text` | string | **Pflicht** | Text des Blocks. Wird getrimmt. |
| `format` | string | `'text'` | `text` oder `html`. Leerzeichen an den Rändern werden entfernt. |

```php
['type' => 'infoblock', 'text' => '  Careful!  ']
// → text 'Careful!', format 'text'

['type' => 'infoblock', 'text' => '<b>Careful</b>', 'format' => 'html']
```

Was das für den Renderer bedeutet:

- `text`: als einfacher Text ausgeben, **unbedingt maskieren** (`htmlspecialchars`).
- `html`: Markup ist erlaubt, für die Sicherheit des Inhalts bist du zuständig.

Die Bibliothek bereinigt und maskiert den Text nicht.

Andere Felder **können nicht** von einem `infoblock` abhängen: Er hat keinen Wert.

```php
['type' => 'infoblock']                                        // Fehler: Field 'text' is mandatory for infoblock type in 'f'.
['type' => 'infoblock', 'text' => 'x', 'format' => 'markdown'] // Fehler: Unsupported format 'markdown' in infoblock 'f'. Supported: text, html.
```

---

## Übersicht: welche Schlüssel zu welchem Typ gehören

Zusätzlich zu den gemeinsamen Schlüsseln (`type`, `label`, `sort_order`, `depends`, `comment`, `class`, `validation`, `required`, `provider`):

| Typ | Zusätzliche Schlüssel |
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

Ein Schlüssel aus einer anderen Tabellenzeile ist ein `Unknown key`-Fehler. So werden Tippfehler und versehentlich kopierte Parameter erkannt.