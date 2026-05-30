<?php

declare(strict_types=1);

namespace Modestox\ConfigProcessor\Validator\SystemConfig;

use Modestox\ConfigProcessor\Validator\ValidatorInterface;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;

class Tabs implements ValidatorInterface
{
    /**
     * Validates and sanitizes the tabs list with fallback rules.
     *
     * @param array<string, mixed> $tabs
     * @param array $context
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public function validate(array $tabs, array $context = []): array
    {
        $cleanTabs = [];

        foreach ($tabs as $tabId => $tabData) {
            // 1. Validate that the configuration array key is strictly a string identifier
            if (!is_string($tabId)) {
                throw new InvalidConfigException("The tab identifier key must be a valid string.");
            }

            if (!is_array($tabData)) {
                throw new InvalidConfigException("Configuration for tab '{$tabId}' must be an array.");
            }

            // 2. Validate and sanitize 'label' (Strictly string)
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

            // 3. Validate 'sort_order' (Strictly integer)
            $sortOrder = 0;
            if (isset($tabData['sort_order'])) {
                if (!is_int($tabData['sort_order'])) {
                    throw new InvalidConfigException("Field 'sort_order' for tab '{$tabId}' must be a strict integer.");
                }
                $sortOrder = $tabData['sort_order'];
            }

            $cleanTabs[$tabId] = [
                'label'      => $label,
                'sort_order' => $sortOrder,
            ];

            // 4. Validate 'class' modifier (Strictly string)
            if (isset($tabData['class'])) {
                if (!is_string($tabData['class'])) {
                    throw new InvalidConfigException("Field 'class' for tab '{$tabId}' must be a valid string.");
                }
                $cleanTabs[$tabId]['class'] = trim($tabData['class']);
            }

            // 5. Validate 'icon' modifier (Strictly string)
            if (isset($tabData['icon'])) {
                if (!is_string($tabData['icon'])) {
                    throw new InvalidConfigException("Field 'icon' for tab '{$tabId}' must be a valid string layout modifier.");
                }
                $cleanTabs[$tabId]['icon'] = trim($tabData['icon']);
            }
        }

        uasort($cleanTabs, fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $cleanTabs;
    }
}