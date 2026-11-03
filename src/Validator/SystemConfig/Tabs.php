<?php
/**
 * Modestox Config Processor
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Validator\Identifier;
use Modestox\ConfigProcessor\Validator\ValidatorInterface;

/**
 * Class Tabs
 *
 * Validates top-level tabs.
 */
class Tabs implements ValidatorInterface
{
    /** @inheritDoc */
    public function validate(array $tabs, array $context = []): array
    {
        $cleanTabs = [];

        foreach ($tabs as $tabId => $tabData) {
            $tabId = Identifier::assert($tabId, 'tab');

            if (!is_array($tabData)) {
                throw new InvalidConfigException("Configuration for tab '{$tabId}' must be an array.");
            }

            $label = $tabId;
            if (isset($tabData['label'])) {
                if (!is_string($tabData['label'])) {
                    throw new InvalidConfigException("Field 'label' for tab '{$tabId}' must be a strict string.");
                }

                $trimmedLabel = trim($tabData['label']);
                if ($trimmedLabel !== '') {
                    $label = $trimmedLabel;
                }
            }

            $sortOrder = 0;
            if (isset($tabData['sort_order'])) {
                if (!is_int($tabData['sort_order'])) {
                    throw new InvalidConfigException("Field 'sort_order' for tab '{$tabId}' must be a strict integer.");
                }
                $sortOrder = $tabData['sort_order'];
            }

            $class = '';
            if (isset($tabData['class'])) {
                if (!is_string($tabData['class'])) {
                    throw new InvalidConfigException("Field 'class' for tab '{$tabId}' must be a valid string.");
                }
                $class = trim($tabData['class']);
            }

            $icon = '';
            if (isset($tabData['icon'])) {
                if (!is_string($tabData['icon'])) {
                    throw new InvalidConfigException("Field 'icon' for tab '{$tabId}' must be a valid string layout modifier.");
                }
                $icon = trim($tabData['icon']);
            }

            $cleanTabs[$tabId] = [
                'label'      => $label,
                'sort_order' => $sortOrder,
                'class'      => $class,
                'icon'       => $icon,
            ];
        }

        uasort($cleanTabs, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanTabs;
    }
}