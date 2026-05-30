<?php

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Renderer;

use Throwable;

/**
 * Class HtmlRenderer
 *
 * Dedicated strictly to reading source file contexts and binding variables to the phtml template.
 */
class HtmlRenderer
{

    /**
     * HtmlRenderer constructor.
     * Uses PHP 8.0 Constructor Property Promotion.
     *
     * @param string|null $templatePath Optional custom path to the template file.
     */
    public function __construct(
        private ?string $templatePath = null,
    ) {
        // Fallback to default path if not provided by DI container or manual instantiation
        $this->templatePath = $this->templatePath ?? (dirname(__DIR__) . '/view/error.phtml');
    }

    /**
     * Renders the premium error screen.
     *
     * @param int $code
     * @param Throwable $exception
     * @return void
     */
    public function render(int $code, Throwable $exception): void
    {
        [$realFile, $realLine] = $this->getRealCulprit($exception);
        $message = htmlspecialchars($exception->getMessage());

        $color = match (true) {
            $code >= 500 && $code != 503 => '#e74c3c',
            $code === 503                => '#f39c12',
            $code === 404                => '#3498db',
            default                      => '#2c3e50'
        };

        $codeSnippet = $this->renderCodeSnippet($realFile, $realLine);

        if (file_exists($this->templatePath)) {
            include $this->templatePath;
        } else {
            echo "<h1>Error {$code}</h1><p>{$message}</p>";
        }
    }

    private function getRealCulprit(Throwable $e): array
    {
        $trace = $e->getTrace();
        if (isset($trace[0]['file']) && isset($trace[0]['line'])) {
            return [$trace[0]['file'], (int)$trace[0]['line']];
        }
        return [$e->getFile(), $e->getLine()];
    }

    private function renderCodeSnippet(string $file, int $targetLine): string
    {
        if (!is_file($file) || !is_readable($file)) {
            return '<div class="code-line"><div class="line-content">Source file unavailable.</div></div>';
        }

        $lines = file($file);
        $output = '';
        $start = max(0, $targetLine - 6);
        $end = min(count($lines), $targetLine + 5);

        for ($i = $start; $i < $end; $i++) {
            $currentLineNum = $i + 1;
            $class = ($currentLineNum === $targetLine) ? 'code-line line-error' : 'code-line';

            $output .= '<div class="' . $class . '">';
            $output .= '<div class="line-num">' . $currentLineNum . '</div>';
            $output .= '<div class="line-content">' . htmlspecialchars($lines[$i]) . '</div>';
            $output .= '</div>';
        }

        return $output;
    }
}