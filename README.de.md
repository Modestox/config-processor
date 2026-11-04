# Config Processor

[English](README.md) | [Русский](README.ru.md) | **Deutsch**

![Tests](https://github.com/Modestox/config-processor/actions/workflows/phpunit.yml/badge.svg)

Eine PHP-Bibliothek, die ein Array mit der Beschreibung von Einstellungen prüft und normalisiert. Das Format ist von Magentos `system.xml` inspiriert: `Tabs -> Sections -> Groups -> Fields`.

Eingabe sind ein „rohes“ Array und ein Schema. Ausgabe ist entweder ein sauberes Array mit einheitlicher, vorhersehbarer Form oder eine `InvalidConfigException` mit dem genauen Pfad zum Fehler.

```text
rohes Array  ──►  Processor + Schema  ──►  sauberes Array
                        │
                        └──►  InvalidConfigException (Pfad + Grund)
```

## Inhalt

- [Wozu das gut ist](#wozu-das-gut-ist)
- [Anforderungen und Installation](#anforderungen-und-installation)
- [Schnellstart](#schnellstart)
- [Wie es funktioniert](#wie-es-funktioniert)
- [Referenz der Ebenen](#referenz-der-ebenen)
- [Grundregeln](#grundregeln)
- [Fehler](#fehler)
- [Feldabhängigkeiten (`depends`)](#feldabhängigkeiten-depends)
- [Feldtypen](#feldtypen)
- [Eigene Feldtypen](#eigene-feldtypen)
- [Was die Bibliothek nicht tut](#was-die-bibliothek-nicht-tut)
- [Aufbau des Repositorys](#aufbau-des-repositorys)
- [Entwicklung und Tests](#entwicklung-und-tests)
- [Umstieg von 1.x auf 2.0](#umstieg-von-1x-auf-20)

---

## Wozu das gut ist

Die Admin-Einstellungen eines Moduls (wie `system.xml` in Magento) lassen sich bequem deklarativ beschreiben: Tabs, Sections, Groups, Fields. Wenn aber ein Mensch eine solche Beschreibung schreibt, schleichen sich Tippfehler, überflüssige Leerzeichen, fehlende Schlüssel und Felder ein, die auf nicht vorhandene Felder verweisen.

Die Bibliothek löst genau eine Aufgabe: **die Beschreibung prüfen und in normalisierter Form zurückgeben**.

Die Kernidee: **In der Ausgabe sind immer alle Schlüssel vorhanden.** Wurde `sort_order` nicht gesetzt, kommt `0`. Wurde `class` nicht gesetzt, kommt `''`. Der Code, der später ein Formular zeichnet oder Einstellungen speichert, braucht deshalb keine `isset`-Prüfungen und bricht nicht an einem fehlenden Schlüssel.

Was die Bibliothek **nicht** tut: Sie zeichnet kein Formular, speichert keine Werte und liest nicht `$_POST`. Sie prüft nur die *Beschreibung* der Einstellungen. Das ist wichtig, siehe [Was die Bibliothek nicht tut](#was-die-bibliothek-nicht-tut).

## Anforderungen und Installation

- PHP 8.3 oder neuer
- Keine Abhängigkeiten

```bash
composer require modestox/config-processor
```

## Schnellstart

### 1. Rohe Beschreibung

Beachte die überflüssigen Leerzeichen und die ungeordneten `sort_order`-Werte:

```php
$rawInput = [
    'tabs' => [
        'payment_tab' => [
            'label'      => 'Sales & Payments',
            'sort_order' => 20,
        ],
        'main_tab' => [
            'label'      => '  General Settings  ', // Leerzeichen an den Rändern werden entfernt
            'sort_order' => 10,                      // dieser Tab steht danach an erster Stelle
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
                            'type' => 'yes_no', // die Optionen Yes/No werden automatisch angelegt
                        ],
                        'api_secret' => [
                            'type'    => 'password',
                            'default' => '   secret-token-key   ',
                            'depends' => [
                                'enable_api' => 1, // nur sichtbar, wenn die API aktiviert ist
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

### 2. Verarbeitung

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$processor = new Processor();

// Für kleine Plugins ohne Tabs und Sections:
// $schema = new \Modestox\ConfigProcessor\Schema\GroupedConfig();
$schema = new SystemConfig();

$cleanConfig = $processor->process($rawInput, $schema);
```

### 3. Ergebnis

Alle Werte sind normalisiert, alle Schlüssel vorhanden, alles ist nach `sort_order` sortiert:

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

### Typisches Szenario

Ein Modul hält seine Beschreibung in einer eigenen Datei und verarbeitet sie einmal:

```php
// etc/config.php des Moduls
return [
    'tabs'     => [ /* ... */ ],
    'sections' => [ /* ... */ ],
];
```

```php
$raw   = require __DIR__ . '/etc/config.php';
$clean = (new Processor())->process($raw, new SystemConfig());

// $clean kann danach gecacht und ohne erneute Prüfung an den Renderer übergeben werden.
```

## Wie es funktioniert

`Processor` prüft selbst nichts, er reicht die Daten an ein Schema weiter. Das Schema verteilt die Arbeit an die Validatoren der Ebenen, und der Feld-Validator wählt den passenden Typ:

```text
Processor::process($raw, $schema)
└── SystemConfig::validate()          prüft die Wurzelschlüssel
    ├── Tabs::validate()              Tabs
    └── Sections::validate()          Sections (kennen die Liste der Tabs)
        └── Groups::validate()        Groups
            └── Fields::validate()    Fields: Typ, Abhängigkeiten
                └── Text / Select / Number / ...   Regeln des konkreten Typs
```

Tabs, Sections, Groups und Fields erben von der gemeinsamen Klasse `AbstractNodeValidator`. Sie erledigt, was für alle gleich ist: den Bezeichner prüfen, unbekannte Schlüssel prüfen, `label` und `sort_order` behandeln, das Ergebnis sortieren und den Pfad zum Fehler ergänzen. Ein konkreter Validator beschreibt nur das, was seine Ebene unterscheidet.

Zwei fertige Schemas:

| Schema | Struktur | Wann verwenden |
| :--- | :--- | :--- |
| `SystemConfig` | Tabs → Sections → Groups → Fields | Große Module und Admin-Oberflächen |
| `GroupedConfig` | Groups → Fields (der Schlüssel `groups` ist Pflicht) | Kleine Plugins und Widgets |

## Referenz der Ebenen

| Ebene | Schlüssel | Typ | Was damit passiert |
| :--- | :--- | :--- | :--- |
| Wurzel `SystemConfig` | `tabs`, `sections` | array | Beide optional. |
| Wurzel `GroupedConfig` | `groups` | array | Pflicht. |
| Tab | `label` | string | Standardmäßig die Tab-ID. |
| | `sort_order` | int | Standard `0`. |
| | `class`, `icon` | string | Standard `''`. |
| Section | `tab` | string | **Pflicht.** Muss auf einen definierten Tab verweisen. |
| | `label`, `sort_order`, `class`, `icon` | | Wie beim Tab. |
| | `groups` | array | Standard `[]`. |
| Group | `label`, `sort_order` | | Wie beim Tab. |
| | `fields` | array | Standard `[]`. |
| Field | `type` | string | **Pflicht.** Siehe [Feldtypen](#feldtypen). |
| | übrige | | Gemeinsame und typspezifische Schlüssel, siehe [fields.de.md](fields.de.md). |

## Grundregeln

### 1. Bezeichner

Die Schlüssel von Tabs, Sections, Groups und Fields müssen auf `^[a-z0-9_]+$` passen: lateinische Kleinbuchstaben, Ziffern und Unterstrich.

```php
'site_name'   // gut
'Site Name'   // Fehler: Großbuchstaben und Leerzeichen
'site-name'   // Fehler: Bindestrich
"site_name\n" // Fehler: Zeilenumbruch am Ende
'123'         // Fehler: PHP macht aus so einem Schlüssel eine Zahl
```

Bei Schlüsseln, die nur aus Ziffern bestehen, gibt es eine Besonderheit: PHP macht `'123'` automatisch zu einer Ganzzahl. Die Bibliothek kann `'123'` nicht von `123` unterscheiden, deshalb werden solche IDs nicht unterstützt. `'item_123'` funktioniert.

### 2. Strikte Typen, keine Umwandlung

Die Bibliothek wandelt nichts „freundlich“ um. Der String `"10"` passt nicht, wo eine Zahl verlangt wird, `1` passt nicht, wo `true` verlangt wird:

```php
'cache_ttl'   => ['type' => 'number', 'default' => '10']  // Fehler: int oder float nötig
'maintenance' => ['type' => 'boolean', 'default' => 1]    // Fehler: true oder false nötig
```

Die einzige bewusste Umwandlung: Zahlen in `dynamic_rows`-Zellen werden zu Strings (`5` → `'5'`), damit alle Zellen denselben Typ haben.

### 3. Alles Nicht-Angegebene wird aufgefüllt

```php
'site_name' => ['type' => 'text']
```

wird zu

```php
'site_name' => [
    'type' => 'text', 'label' => 'site_name', 'sort_order' => 0,
    'default' => '', 'placeholder' => '', 'comment' => '', 'class' => '',
    'validation' => [], 'required' => false, 'provider' => '', 'depends' => null,
]
```

Hat ein `number` ein `min`, aber kein `default`, ist der Standardwert nicht `0`, sondern die nächstgelegene Grenze des Bereichs:

```php
['type' => 'number', 'min' => 5]  // default wird 5, nicht 0
```

### 4. Unbekannte Schlüssel sind ein Fehler

Ein Tippfehler in einem Schlüssel darf nicht stillschweigend verschwinden. Wer `defualt` statt `default` schreibt, würde den Wert verlieren und es erst auf der Einstellungsseite merken. Jetzt hält die Bibliothek sofort an und macht einen Vorschlag:

```text
sections.api.groups.auth.fields.site_name: Unknown key 'defualt' in field 'site_name'.
Did you mean 'default'? Allowed keys: type, label, sort_order, depends, comment, class,
validation, required, provider, default, placeholder. Custom keys must start with 'x_'.
```

Dasselbe auf Wurzelebene:

```text
Unknown key 'tab' in the configuration root. Did you mean 'tabs'? Allowed keys: tabs, sections.
Custom keys must start with 'x_'.
```

Die erlaubten Schlüssel hängen vom Feldtyp ab. `placeholder` bei einem `select` oder `options` bei einem `yes_no` sind ebenfalls unbekannte Schlüssel.

#### Eigene Parameter: das Präfix `x_`

Braucht ein Modul einen eigenen Parameter (zum Beispiel eine Modellklasse für eine Datenquelle), benenne ihn mit dem Präfix `x_`. Solche Schlüssel sind auf jeder Ebene erlaubt und gelangen **unverändert und ohne Prüfung** ins Ergebnis:

```php
'country' => [
    'type'           => 'select',
    'options'        => ['de' => 'Germany'],
    'x_source_model' => 'My\\Module\\Model\\Countries', // landet unverändert im Ergebnis
],
```

Format: `x_`, danach Kleinbuchstaben, Ziffern und Unterstriche. `x_`, `X_custom`, `x_my-key` sind nicht gültig.

### 5. `null` bedeutet „nicht gesetzt“

```php
'label' => null  // dasselbe wie den Schlüssel wegzulassen: label wird zur ID
```

Das ist praktisch für Arrays, die per Code aufgebaut werden.

### 6. Sortierung

Die Knoten jeder Ebene werden aufsteigend nach `sort_order` geordnet. Die Sortierung ist **stabil**: Knoten mit gleichem `sort_order` behalten die Reihenfolge, in der sie beschrieben wurden.

## Fehler

Der erste gefundene Fehler stoppt die Verarbeitung und löst `Modestox\ConfigProcessor\Exception\InvalidConfigException` aus. Die Meldung beginnt mit dem Pfad zur Problemstelle:

```text
sections.api.groups.auth.fields.cache_timeout: Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
```

Der Pfad ist nötig, weil gleiche Feld-IDs in verschiedenen Gruppen vorkommen können.

```php
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

try {
    $clean = $processor->process($raw, new SystemConfig());
} catch (InvalidConfigException $e) {
    echo $e->getPath();    // sections.api.groups.auth.fields.cache_timeout
    echo $e->getReason();  // Field 'max' cannot be less than 'min' for number field 'cache_timeout'.
    echo $e->getMessage(); // Pfad + Doppelpunkt + Grund
}
```

Liegt der Fehler direkt an der Wurzel, ist der Pfad leer und `getMessage()` entspricht `getReason()`.

Programmierfehler (zum Beispiel das Registrieren eines falschen Typ-Validators) lösen eine gewöhnliche `\InvalidArgumentException` aus.

### Beispiele: was geschrieben wurde und was herauskam

Die Meldungen bleiben auf Englisch, so wie sie die Bibliothek ausgibt.

| Was in der Beschreibung steht | Meldung (nach dem Pfad) |
| :--- | :--- |
| `'Site Name' => [...]` | `The field identifier key 'Site Name' contains invalid characters. Only lower-case alphanumeric characters and underscores are allowed (a-z, 0-9, _).` |
| `'123' => [...]` | `The field identifier key must be a valid string. Numeric keys are cast to integers by PHP and are not supported.` |
| `'type' => 'colour'` | `Unsupported type 'colour' in field 'f'. Supported: text, boolean, select, ...` |
| Section mit `'tab' => 'other'` | `Section 'api' references an undefined parent tab 'other'. Defined tabs: main_tab.` |
| Section, wenn `tabs` fehlt | `Section 'api' references an undefined parent tab 'other'. No tabs are defined.` |
| `number`: `min` 100, `max` 10 | `Field 'max' cannot be less than 'min' for number field 'cache_timeout'.` |
| `number`: `'default' => '10'` | `Field 'default' for number field 'n' must be a strict integer or float.` |
| `datetime` `date`: `'2026-02-31'` | `Default value '2026-02-31' for field 'd' does not match mandatory 'date' format standard ('Y-m-d').` |
| `file`: `'upload_dir' => '../etc'` | `Field 'upload_dir' for field 'f' must not contain '..' segments or null bytes.` |
| `dynamic_rows`: überzählige Spalte in einer Zeile | `Row at index '0' inside dynamic rows field 'r' contains unknown column 'zzz'.` |
| `multiselect`: `['DE', 'DE']` | `The default value 'DE' for field 'c' is listed more than once.` |

## Feldabhängigkeiten (`depends`)

Ein Feld kann von anderen Feldern **derselben Gruppe** abhängen. In `depends` stehen die ID des Eltern-Felds und der Wert, bei dem das Feld sichtbar sein soll:

```php
'fields' => [
    'mode' => [
        'type'    => 'select',
        'options' => ['live' => 'Live', 'test' => 'Test'],
    ],
    'live_key' => [
        'type'    => 'password',
        'depends' => ['mode' => 'live'],   // nur sichtbar, wenn mode = live
    ],
    'debug' => [
        'type'    => 'boolean',
        'depends' => ['mode' => 'test', 'live_key' => 'x'], // mehrere Bedingungen sind möglich
    ],
],
```

Was geprüft wird:

| Situation | Ergebnis |
| :--- | :--- |
| Das Eltern-Feld ist nicht in der Gruppe | Fehler: `depends on an undefined parent field 'ghost'` |
| Ein Feld hängt von sich selbst ab | Zyklusfehler: `a -> a` |
| Eine Kette läuft im Kreis | Zyklusfehler: `a -> b -> a` |
| Das Eltern-Feld ist ein `infoblock` | Fehler: Es hat keinen Wert |
| Eltern-Feld `select`, `radio`, `yes_no` | Der erwartete Wert muss einer der `options`-Schlüssel sein |
| Eltern-Feld `boolean` | Der erwartete Wert muss `true` oder `false` sein |
| Eltern-Feld `number` | Der erwartete Wert muss `int` oder `float` sein |
| Eltern-Feld `text`, `multiselect` und andere | Der Wert wird nicht geprüft |

Beispielmeldungen:

```text
Field 'key' expects the value "prod" from 'mode', which is not one of its options (live, test).
Field 'key' cannot depend on infoblock 'note': it holds no value.
Field 'key' expects the value 1 from boolean field 'on'; use true or false.
Circular dependency detected between fields: a -> b -> a.
```

Die Reihenfolge der Felder spielt keine Rolle: `live_key` darf im Array vor `mode` stehen.

Der erwartete Wert darf nur ein Skalar sein (String, Zahl, bool). Mehrere zulässige Werte anzugeben ist derzeit nicht möglich.

## Feldtypen

| Typ (`type`) | Zweck |
| :--- | :--- |
| `text`, `textarea` | Text; `default` und `placeholder` werden an den Rändern getrimmt. |
| `password` | Ein Geheimnis; `default` wird getrimmt. |
| `boolean` | `default` ist strikt `true` oder `false`. |
| `yes_no` | Dropdown mit festem `[0 => 'No', 1 => 'Yes']`. |
| `number` | Nur endliche `int` und `float`; `min <= default <= max`. |
| `datetime` | `datetime`, `date` oder `time`, geprüft gegen den echten Kalender. |
| `dynamic_rows` | Tabelle mit festgelegten Spalten. |
| `file`, `image` | Beschreibt Upload-Regeln: `upload_dir`, `extensions`. |
| `select`, `radio` | Eine Option aus einer Liste; ohne `default` wird die erste genommen. |
| `multiselect`, `checkbox` | Mehrere Optionen aus einer Liste. |
| `infoblock` | Informationsblock (kein Eingabefeld). |

Details zu jedem Typ, seinen Schlüsseln, Regeln und Beispielen: [fields.de.md](fields.de.md).

## Eigene Feldtypen

Ein eigener Feldtyp ist eine Klasse, die von `AbstractFieldValidator` erbt. Sie tut zwei Dinge: Sie listet **ihre eigenen** Schlüssel in `specificKeys()` auf und prüft die Werte in `validate()`.

```php
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\AbstractFieldValidator;

final class ColorPicker extends AbstractFieldValidator
{
    /** Schlüssel, die nur für diesen Typ erlaubt sind (die gemeinsamen kommen automatisch dazu). */
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

Einbinden in ein Schema:

```php
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;

$schema = SystemConfig::withFieldTypes(['colorpicker' => new ColorPicker()]);
// bei GroupedConfig::withFieldTypes([...]) funktioniert es genauso

$clean = (new Processor())->process($raw, $schema);
```

Jetzt kann die Beschreibung `'type' => 'colorpicker'` verwenden. Ein Tippfehler im Schlüssel wie `pallete` liefert denselben Hinweis `Did you mean 'palette'?` wie bei den eingebauten Typen.

Regeln:

- Der Typname passt auf `^[a-z0-9_]+$`.
- Ein eingebauter Typ lässt sich überschreiben, indem man einen eigenen unter `text`, `select` usw. registriert.
- Wer nicht von `AbstractFieldValidator` erben will, implementiert `FieldValidatorInterface` vollständig. Dann muss `allowedKeys()` **alle** erlaubten Schlüssel liefern, einschließlich `type`, `label`, `sort_order`, `depends`.
- Hilfsmethoden der Basisklasse: `validateString`, `validateBool`, `validateArray`, `validateOptions`, `validateCommon`.

## Was die Bibliothek nicht tut

- **Sie prüft keine Werte, die Benutzer eingeben.** Sie prüft die *Beschreibung* der Einstellungen. Formulardaten (`$_POST`) kommen als Strings an, und die strikte Typprüfung würde sie ablehnen. Für Werte braucht es eine eigene Schicht.
- **Sie maskiert keinen Text.** `label`, `comment` und der Text von `infoblock` werden unverändert zurückgegeben. Maskiere bei der Ausgabe (`htmlspecialchars`), besonders bei `format => 'html'`.
- **Sie prüft nicht, ob Ordner und Dateien existieren.** `upload_dir` wird nur auf `..` und Nullbytes geprüft. Die Verwendung liegt bei deinem Code.
- **Sie lädt `provider` nicht.** Es ist nur ein String mit einem Klassennamen, er wird weder geprüft noch aufgerufen.
- **Sie sammelt nicht alle Fehler auf einmal.** Sie hält beim ersten an. Der Pfad in der Meldung hilft, ihn schnell zu finden.

## Aufbau des Repositorys

```text
src/
├── Processor.php                      Einstiegspunkt
├── Exception/InvalidConfigException   Fehler mit Pfad
├── Schema/
│   ├── SchemaInterface                Vertrag eines Schemas
│   ├── SystemConfig                   tabs → sections → groups → fields
│   └── GroupedConfig                  groups → fields
└── Validator/
    ├── ValidatorInterface             Vertrag eines Ebenen-Validators
    ├── Identifier                     Prüfung der Bezeichner
    ├── Keys                           unbekannte Schlüssel, x_ und Hinweise
    └── SystemConfig/
        ├── AbstractNodeValidator      gemeinsame Logik der Ebenen
        ├── Tabs, Sections, Groups, Fields
        └── Fields/                    Validatoren der Feldtypen
tests/                                 PHPUnit
fields.de.md                           Referenz der Feldtypen
```

## Entwicklung und Tests

```bash
composer install
composer test
```

In DDEV: `ddev composer install` und `ddev exec vendor/bin/phpunit`.

## Umstieg von 1.x auf 2.0

Version 2.0 ist strenger. Eine Konfiguration, die in 1.x durchging, kann jetzt abgelehnt werden. Was sich geändert hat:

| Was | In 1.x | In 2.0 |
| :--- | :--- | :--- |
| Unbekannte Schlüssel | Stillschweigend verworfen | Fehler mit Hinweis; eigene Schlüssel nur mit Präfix `x_` |
| `options` bei `yes_no` | Stillschweigend ersetzt | Fehler |
| Fehlermeldungen | Ohne Pfad | Pfad am Anfang, `getPath()` und `getReason()` vorhanden |
| `depends` | Nur die Existenz des Eltern-Felds wurde geprüft | Zusätzlich: Zyklen, Werte, `infoblock` als Eltern-Feld |
| Duplikate in `default` bei `multiselect` | Akzeptiert | Fehler |
| `default` bei `select`/`multiselect` | Blieb wie geschrieben (`'1'`) | Auf den Typ des Options-Schlüssels gebracht (`1`) |
| Bezeichner mit `\n` am Ende | Akzeptiert (Fehler) | Fehler |
| `number` ohne `default` bei `min > 0` | `default = 0` (außerhalb des Bereichs) | `default = min` |
| `dynamic_rows` | Überzählige Spalten gingen still verloren | Fehler; mindestens eine Spalte nötig |
| `datetime` mit falschem `view_mode` | Stillschweigend durch `datetime` ersetzt | Fehler |
| `file`: `extensions`, `upload_dir` | Ungeprüft | Format `jpg,png`; `..` verboten |
| Tabs und Sections | Ohne `class` und `icon`, wenn nicht gesetzt | Immer vorhanden, Standard `''` |
| `FieldValidatorInterface` | Nur `validate()` | Zusätzlich `allowedKeys()` |

Details: [CHANGELOG.md](CHANGELOG.md) (auf Englisch).

## Lizenz

MIT. Siehe [LICENSE](LICENSE).