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
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\AbstractFieldValidator;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests registration and strictness of custom field types.
 */
class CustomTypeTest extends TestCase
{
    private function colorPicker(): AbstractFieldValidator
    {
        return new class extends AbstractFieldValidator {
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

                return array_merge($baseMeta, ['default' => $default, 'palette' => $palette], $this->validateCommon($fieldId, $fieldData));
            }
        };
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function process(array $field): array
    {
        $schema = GroupedConfig::withFieldTypes(['colorpicker' => $this->colorPicker()]);
        $clean = (new Processor())->process(['groups' => ['g' => ['fields' => ['accent' => $field]]]], $schema);

        return $clean['groups']['g']['fields']['accent'];
    }

    /** Custom type is validated and normalized. */
    public function testCustomTypeIsValidated(): void
    {
        $field = $this->process(['type' => 'colorpicker', 'default' => ' #FFAA00 ', 'comment' => ' Brand color ']);

        $this->assertSame('#FFAA00', $field['default']);
        $this->assertSame('basic', $field['palette']);
        $this->assertSame('Brand color', $field['comment']);
        $this->assertNull($field['depends']);
    }

    /** Custom type rejects its own invalid values. */
    public function testCustomTypeRejectsInvalidValue(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Field 'accent' must have a hex color default.");

        $this->process(['type' => 'colorpicker', 'default' => 'red']);
    }

    /** Custom type keys are strict as well. */
    public function testCustomTypeRejectsUnknownKeys(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Unknown key 'pallete' in field 'accent'. Did you mean 'palette'?");

        $this->process(['type' => 'colorpicker', 'pallete' => 'x']);
    }

    /** System schema accepts custom types through its factory. */
    public function testSystemSchemaFactoryRegistersCustomTypes(): void
    {
        $schema = SystemConfig::withFieldTypes(['colorpicker' => $this->colorPicker()]);

        $clean = (new Processor())->process([
            'tabs'     => ['t' => []],
            'sections' => ['s' => ['tab' => 't', 'groups' => ['g' => ['fields' => ['accent' => ['type' => 'colorpicker']]]]]],
        ], $schema);

        $this->assertSame('#000000', $clean['sections']['s']['groups']['g']['fields']['accent']['default']);
    }

    /** Validator implementing only the interface declares its own keys. */
    public function testInterfaceOnlyValidatorDeclaresItsKeys(): void
    {
        $validator = new class implements FieldValidatorInterface {
            public function allowedKeys(): array
            {
                return ['type', 'label', 'sort_order', 'depends', 'token'];
            }

            public function validate(string $fieldId, array $fieldData, array $baseMeta): array
            {
                return array_merge($baseMeta, ['token' => (string)($fieldData['token'] ?? '')]);
            }
        };

        $schema = GroupedConfig::withFieldTypes(['raw' => $validator]);
        $clean = (new Processor())->process(['groups' => ['g' => ['fields' => ['f' => ['type' => 'raw', 'token' => 'abc']]]]], $schema);

        $this->assertSame('abc', $clean['groups']['g']['fields']['f']['token']);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Unknown key 'comment' in field 'f'.");

        (new Processor())->process(['groups' => ['g' => ['fields' => ['f' => ['type' => 'raw', 'comment' => 'x']]]]], $schema);
    }
}