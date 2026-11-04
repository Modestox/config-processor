<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Exception;

use Throwable;

/**
 * Class InvalidConfigException
 *
 * Thrown when a configuration is invalid.
 */
class InvalidConfigException extends \Exception
{
    /** @var list<string> */
    private array $path = [];

    private string $reason;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->reason = $message;
    }

    /**
     * Adds a parent node to the beginning of the error path.
     */
    public function prependPath(string $segment): static
    {
        array_unshift($this->path, $segment);
        $this->message = $this->getPath() . ': ' . $this->reason;

        return $this;
    }

    /**
     * Returns the dotted path to the invalid node, e.g. "sections.api.groups.auth".
     */
    public function getPath(): string
    {
        return implode('.', $this->path);
    }

    /**
     * Returns the message without the path.
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}