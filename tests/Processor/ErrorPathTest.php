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
 * Tests the path reported with validation errors.
 */
class ErrorPathTest extends TestCase
{
    /**
     * @param array<string, mixed> $input
     */
    private function thrown(array $input, SchemaInterface $schema): InvalidConfigException
    {
        try {
            (new Processor())->process($input, $schema);
        } catch (InvalidConfigException $e) {
            return $e;
        }

        $this->fail('Exception expected.');
    }

    /** Field error contains the full path. */
    public function testFieldErrorContainsFullPath(): void
    {
        $e = $this->thrown([
            'tabs'     => ['t' => []],
            'sections' => ['api' => ['tab' => 't', 'groups' => ['auth' => ['fields' => [
                'timeout' => ['type' => 'number', 'min' => 10, 'max' => 1],
            ]]]]],
        ], new SystemConfig());

        $this->assertSame('sections.api.groups.auth.fields.timeout', $e->getPath());
        $this->assertSame("Field 'max' cannot be less than 'min' for number field 'timeout'.", $e->getReason());
        $this->assertStringStartsWith('sections.api.groups.auth.fields.timeout: ', $e->getMessage());
    }

    /** Same field id in different groups is distinguishable. */
    public function testSameFieldIdInDifferentGroupsIsDistinguishable(): void
    {
        $e = $this->thrown(['groups' => [
            'first'  => ['fields' => ['enabled' => ['type' => 'boolean']]],
            'second' => ['fields' => ['enabled' => ['type' => 'boolean', 'default' => 'yes']]],
        ]], new GroupedConfig());

        $this->assertSame('groups.second.fields.enabled', $e->getPath());
    }

    /** Tab error contains the tab path. */
    public function testTabErrorContainsTabPath(): void
    {
        $e = $this->thrown(['tabs' => ['main' => ['label' => 404]]], new SystemConfig());

        $this->assertSame('tabs.main', $e->getPath());
    }

    /** Section error contains the section path. */
    public function testSectionErrorContainsSectionPath(): void
    {
        $e = $this->thrown(['tabs' => ['t' => []], 'sections' => ['s' => ['tab' => 'missing']]], new SystemConfig());

        $this->assertSame('sections.s', $e->getPath());
        $this->assertStringContainsString('Defined tabs: t.', $e->getReason());
    }

    /** Section without any tabs explains that no tabs are defined. */
    public function testSectionWithoutTabsExplainsMissingTabs(): void
    {
        $e = $this->thrown(['sections' => ['s' => ['tab' => 't']]], new SystemConfig());

        $this->assertStringContainsString('No tabs are defined.', $e->getReason());
    }

    /** Dependency error contains the dependent field path. */
    public function testDependencyErrorContainsFieldPath(): void
    {
        $e = $this->thrown(['groups' => ['g' => ['fields' => [
            'a' => ['type' => 'text', 'depends' => ['ghost' => 1]],
        ]]]], new GroupedConfig());

        $this->assertSame('groups.g.fields.a', $e->getPath());
        $this->assertStringContainsString("undefined parent field 'ghost'", $e->getMessage());
    }

    /** Invalid identifier is reported at the level of its container. */
    public function testInvalidIdentifierIsReportedAtContainerLevel(): void
    {
        $e = $this->thrown(['groups' => ['g' => ['fields' => ['bad id' => ['type' => 'text']]]]], new GroupedConfig());

        $this->assertSame('groups.g.fields', $e->getPath());
    }

    /** Root level errors have no path. */
    public function testRootErrorHasNoPath(): void
    {
        $e = $this->thrown([], new GroupedConfig());

        $this->assertSame('', $e->getPath());
        $this->assertSame($e->getReason(), $e->getMessage());
    }

    /** Path segments are prepended from the inside out. */
    public function testPrependPathBuildsDottedPath(): void
    {
        $e = (new InvalidConfigException('Broken.'))->prependPath('c')->prependPath('b')->prependPath('a');

        $this->assertSame('a.b.c', $e->getPath());
        $this->assertSame('a.b.c: Broken.', $e->getMessage());
        $this->assertSame('Broken.', $e->getReason());
    }
}