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
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Validator\SystemConfig\Groups;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class GroupedConfigTest
 *
 * Covers lifecycle validation and exceptional flows for the GroupedConfig schema strategy.
 */
class GroupedConfigTest extends TestCase
{
    /**
     * Verify that GroupedConfig successfully delegates data processing to the internal Groups validator.
     */
    public function testValidateProcessesValidGroupsStructureSuccessfully(): void
    {
        // 1. Prepare isolated mocked dependencies to ensure pure unit test execution
        $groupsValidatorMock = $this->createMock(Groups::class);

        $dirtyGroupsData = [
            'demo_feature_group' => [
                'label' => 'Demo Feature Settings',
                'fields' => []
            ]
        ];

        $expectedCleanedData = [
            'demo_feature_group' => [
                'label' => 'Demo Feature Settings',
                'fields' => []
            ]
        ];

        // Expect the internal validator to be triggered exactly once with our data slice
        $groupsValidatorMock->expects($this->once())
            ->method('validate')
            ->with($dirtyGroupsData)
            ->willReturn($expectedCleanedData);

        // 2. Inject mock via Constructor Dependency Injection
        $schema = new GroupedConfig($groupsValidatorMock);

        $inputPayload = ['groups' => $dirtyGroupsData];
        $result = $schema->validate($inputPayload);

        // 3. Assert root structure layout encapsulation remains intact
        $this->assertArrayHasKey('groups', $result);
        $this->assertEquals($expectedCleanedData, $result['groups']);
    }

    /**
     * Verify that an exception is immediately thrown if the mandatory 'groups' root key is missing.
     */
    public function testValidateThrowsExceptionWhenGroupsKeyIsMissing(): void
    {
        $schema = new GroupedConfig();
        $invalidInputPayload = ['tabs' => [], 'sections' => []]; // Wrong schema context entirely

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Invalid configuration root structure. The 'groups' key is required for GroupedConfig.");

        $schema->validate($invalidInputPayload);
    }

    /**
     * Verify that an exception is thrown if the 'groups' configuration node is present but isn't an array.
     */
    public function testValidateThrowsExceptionWhenGroupsKeyIsNotAnArray(): void
    {
        $schema = new GroupedConfig();
        $invalidInputPayload = ['groups' => 'string_instead_of_array_payload'];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("The 'groups' section must be an array.");

        $schema->validate($invalidInputPayload);
    }
}