<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

namespace Modestox\ConfigProcessor\Exception;

// CRITICAL FIX: Explicitly importing the renderer from its new dedicated namespace
use Modestox\ConfigProcessor\Renderer\HtmlRenderer;
use Throwable;

class InvalidConfigException extends \Exception
{
    /**
     * Registers this exception handler globally.
     *
     * @return void
     */
    public static function register(): void
    {
        set_exception_handler(function (Throwable $e) {
            if ($e instanceof self) {
                $e->renderAndDie();
            }
            return false;
        });
    }

    /**
     * Terminate and display error via the correct context mechanism.
     *
     * @return void
     */
    public function renderAndDie(): void
    {

        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "\033[31m[Configuration Error]: " . $this->getMessage() . "\033[0m\n");
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code(400);
        }

        // Instantiating the decoupled renderer service (SOLID implementation)
        $renderer = new HtmlRenderer();
        $renderer->render(400, $this);

        exit(1);
    }
}