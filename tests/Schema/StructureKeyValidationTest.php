<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class StructureKeyValidationTest
 *
 * Verifies that all configuration tree identifier keys strictly allow only a-z, 0-9, and underscores.
 */
class StructureKeyValidationTest extends TestCase
{
    private Processor $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    /**
     * @covers \Modestox\ConfigProcessor\Validator\SystemConfig\Tabs::validate
     */
    public function testTabKeyWithSpacesThrowsException(): void
    {
        $invalidInput = [
            'tabs' => [
                'main tab' => [
                    'label' => 'General Settings',
                ],
            ],
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The tab identifier key 'main tab' contains invalid characters.");

        $this->processor->process($invalidInput, new SystemConfig());
    }

    /**
     * @covers \Modestox\ConfigProcessor\Validator\SystemConfig\Sections::validate
     */
    public function testSectionKeyWithUppercaseAndSpacesThrowsException(): void
    {
        $invalidInput = [
            'tabs'     => [
                'main_tab' => ['label' => 'Main'],
            ],
            'sections' => [
                'API_Section ' => [
                    'tab'   => 'main_tab',
                    'label' => 'API Core Integration',
                ],
            ],
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The section identifier key 'API_Section ' contains invalid characters.");

        $this->processor->process($invalidInput, new SystemConfig());
    }

    /**
     * @covers \Modestox\ConfigProcessor\Validator\SystemConfig\Groups::validate
     */
    public function testGroupKeyWithSpacesThrowsException(): void
    {
        $invalidInput = [
            'groups' => [
                'general 2' => [
                    'label'  => 'All Field Types Test',
                    'fields' => [],
                ],
            ],
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The group identifier key 'general 2' contains invalid characters.");

        $this->processor->process($invalidInput, new GroupedConfig());
    }

    /**
     * @covers \Modestox\ConfigProcessor\Validator\SystemConfig\Fields::validate
     */
    public function testFieldKeyWithHyphenThrowsException(): void
    {
        $invalidInput = [
            'groups' => [
                'general_group' => [
                    'label'  => 'Configuration',
                    'fields' => [
                        'site-name' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The field identifier key 'site-name' contains invalid characters.");

        $this->processor->process($invalidInput, new GroupedConfig());
    }

    /**
     * Verifies that valid identifiers (snake_case, numeric, lowercase) pass successfully.
     */
    public function testStrictIdentifiersPassValidation(): void
    {
        $validInput = [
            'tabs'     => [
                'main_tab_1' => ['label' => 'Main Tab'],
            ],
            'sections' => [
                'core_integration_2' => [
                    'tab'    => 'main_tab_1',
                    'label'  => 'API Core Integration',
                    'groups' => [
                        'auth_group_3' => [
                            'label'  => 'Authentication',
                            'fields' => [
                                'api_secret_key_4' => [
                                    'type'    => 'text',
                                    'default' => 'token',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $cleanConfig = $this->processor->process($validInput, new SystemConfig());

        $this->assertArrayHasKey('tabs', $cleanConfig);
        $this->assertArrayHasKey('main_tab_1', $cleanConfig['tabs']);
        $this->assertArrayHasKey('api_secret_key_4', $cleanConfig['sections']['core_integration_2']['groups']['auth_group_3']['fields']);
    }
}