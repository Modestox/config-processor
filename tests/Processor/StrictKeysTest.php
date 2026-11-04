<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Tests\Processor;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Schema\SchemaInterface;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests rejection of unknown keys and custom x_ keys.
 */
class StrictKeysTest extends TestCase
{
    /**
     * @param array<string, mixed> $input
     */
    private function assertRejected(array $input, SchemaInterface $schema, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new Processor())->process($input, $schema);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private static function withField(array $field): array
    {
        return ['groups' => ['g' => ['fields' => ['f' => $field]]]];
    }

    /** Typo in a field key suggests the right key. */
    public function testFieldKeyTypoSuggestsClosestKey(): void
    {
        $this->assertRejected(
            self::withField(['type' => 'text', 'defualt' => 'x']),
            new GroupedConfig(),
            "Unknown key 'defualt' in field 'f'. Did you mean 'default'?",
        );
    }

    /** Typo in required is not silently ignored. */
    public function testMisspelledRequiredIsRejected(): void
    {
        $this->assertRejected(
            self::withField(['type' => 'text', 'requried' => true]),
            new GroupedConfig(),
            "Did you mean 'required'?",
        );
    }

    /** Key without a close match gets no suggestion. */
    public function testUnrelatedKeyGetsNoSuggestion(): void
    {
        try {
            (new Processor())->process(self::withField(['type' => 'text', 'banana_split' => 1]), new GroupedConfig());
            $this->fail('Exception expected.');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString("Unknown key 'banana_split'", $e->getMessage());
            $this->assertStringNotContainsString('Did you mean', $e->getMessage());
            $this->assertStringContainsString('Allowed keys:', $e->getMessage());
        }
    }

    /** Typo at the root of the system schema is reported. */
    public function testRootKeyTypoIsReported(): void
    {
        $this->assertRejected(
            ['tab' => [], 'sections' => []],
            new SystemConfig(),
            "Unknown key 'tab' in the configuration root. Did you mean 'tabs'?",
        );
    }

    /** Foreign root keys are rejected by the grouped schema. */
    public function testGroupedSchemaRejectsSystemRootKeys(): void
    {
        $this->assertRejected(['groups' => [], 'tabs' => []], new GroupedConfig(), "Unknown key 'tabs' in the configuration root.");
    }

    /**
     * Unknown keys are rejected on every level.
     *
     * @dataProvider levelProvider
     * @param array<string, mixed> $input
     */
    public function testUnknownKeyIsRejectedOnEveryLevel(array $input, string $expectedMessage): void
    {
        $this->assertRejected($input, new SystemConfig(), $expectedMessage);
    }

    /**
     * @return array<string, array{input: array<string, mixed>, expectedMessage: string}>
     */
    public static function levelProvider(): array
    {
        return [
            'tab'     => [
                'input'           => ['tabs' => ['t' => ['colour' => 'red']]],
                'expectedMessage' => "Unknown key 'colour' in tab 't'.",
            ],
            'section' => [
                'input'           => ['tabs' => ['t' => []], 'sections' => ['s' => ['tab' => 't', 'group' => []]]],
                'expectedMessage' => "Unknown key 'group' in section 's'. Did you mean 'groups'?",
            ],
            'group'   => [
                'input'           => ['tabs' => ['t' => []], 'sections' => ['s' => ['tab' => 't', 'groups' => ['g' => ['field' => []]]]]],
                'expectedMessage' => "Unknown key 'field' in group 'g'. Did you mean 'fields'?",
            ],
        ];
    }

    /**
     * Keys of another field type are rejected.
     *
     * @dataProvider foreignTypeKeyProvider
     * @param array<string, mixed> $field
     */
    public function testKeysOfAnotherFieldTypeAreRejected(array $field, string $unknownKey): void
    {
        $this->assertRejected(self::withField($field), new GroupedConfig(), "Unknown key '{$unknownKey}' in field 'f'.");
    }

    /**
     * @return array<string, array{field: array<string, mixed>, unknownKey: string}>
     */
    public static function foreignTypeKeyProvider(): array
    {
        return [
            'placeholder_on_select'   => ['field' => ['type' => 'select', 'options' => ['a' => 'A'], 'placeholder' => 'x'], 'unknownKey' => 'placeholder'],
            'options_on_yes_no'       => ['field' => ['type' => 'yes_no', 'options' => [1 => 'On']], 'unknownKey' => 'options'],
            'min_on_text'             => ['field' => ['type' => 'text', 'min' => 1], 'unknownKey' => 'min'],
            'upload_dir_on_text'      => ['field' => ['type' => 'text', 'upload_dir' => 'media'], 'unknownKey' => 'upload_dir'],
            'text_on_boolean'         => ['field' => ['type' => 'boolean', 'text' => 'x'], 'unknownKey' => 'text'],
        ];
    }

    /** A numeric key inside a field is reported as unknown. */
    public function testNumericKeyInsideFieldIsRejected(): void
    {
        $this->assertRejected(self::withField(['type' => 'text', 0 => 'stray']), new GroupedConfig(), "Unknown key '0' in field 'f'.");
    }

    /** Custom x_ keys are allowed and passed through on every level. */
    public function testCustomExtensionKeysArePassedThrough(): void
    {
        $clean = (new Processor())->process([
            'x_root'   => ['any' => 'thing'],
            'tabs'     => ['t' => ['x_tab' => 1]],
            'sections' => ['s' => [
                'tab'       => 't',
                'x_section' => true,
                'groups'    => ['g' => [
                    'x_group' => 'value',
                    'fields'  => ['f' => ['type' => 'text', 'x_frontend_model' => 'My\\Model']],
                ]],
            ]],
        ], new SystemConfig());

        $this->assertSame(['any' => 'thing'], $clean['x_root']);
        $this->assertSame(1, $clean['tabs']['t']['x_tab']);
        $this->assertTrue($clean['sections']['s']['x_section']);
        $this->assertSame('value', $clean['sections']['s']['groups']['g']['x_group']);
        $this->assertSame('My\\Model', $clean['sections']['s']['groups']['g']['fields']['f']['x_frontend_model']);
    }

    /**
     * Keys that only look like extensions are rejected.
     *
     * @dataProvider fakeExtensionProvider
     */
    public function testMalformedExtensionKeysAreRejected(string $key): void
    {
        $this->assertRejected(self::withField(['type' => 'text', $key => 1]), new GroupedConfig(), "Unknown key '{$key}'");
    }

    /**
     * @return array<string, array{key: string}>
     */
    public static function fakeExtensionProvider(): array
    {
        return [
            'prefix_only'   => ['key' => 'x_'],
            'upper_case'    => ['key' => 'X_custom'],
            'no_underscore' => ['key' => 'xcustom'],
            'dash'          => ['key' => 'x_custom-key'],
        ];
    }
}