<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig\Fields;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class File
 *
 * Validates declarative file upload rules.
 */
class File extends AbstractFieldValidator
{
    /** @inheritDoc */
    protected function specificKeys(): array
    {
        return ['upload_dir', 'extensions'];
    }

    /** @inheritDoc */
    public function validate(string $fieldId, array $fieldData, array $baseMeta): array
    {
        $uploadDir = $this->validateString($fieldData, 'upload_dir', $fieldId, '');
        if ($this->isUnsafePath($uploadDir)) {
            throw new InvalidConfigException("Field 'upload_dir' for field '{$fieldId}' must not contain '..' segments or null bytes.");
        }

        $extensions = preg_replace('/\s+/', '', $this->validateString($fieldData, 'extensions', $fieldId, '')) ?? '';
        if ($extensions !== '' && !preg_match('/^[A-Za-z0-9]+(,[A-Za-z0-9]+)*$/D', $extensions)) {
            throw new InvalidConfigException("Field 'extensions' for field '{$fieldId}' must be a comma-separated list of extensions without dots (e.g. 'jpg,png').");
        }

        $specific = [
            'upload_dir' => $uploadDir,
            'extensions' => $extensions,
        ];

        return array_merge($baseMeta, $specific, $this->validateCommon($fieldId, $fieldData));
    }

    /**
     * Detects path traversal.
     */
    private function isUnsafePath(string $path): bool
    {
        if (str_contains($path, "\0")) {
            return true;
        }

        return in_array('..', preg_split('#[\\\\/]+#', $path) ?: [], true);
    }
}