<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class NavigationService
{
    /**
     * Build the filtered navigation structure for the given user.
     */
    public function forUser(User $user): array
    {
        $config = config('navigation');
        $roleSlug = $user->role?->slug;
        $colors = $config['colors'];
        $sections = [];

        foreach ($config['sections'] as $sectionKey => $section) {
            $filteredItems = [];

            foreach ($section['items'] as $item) {
                if (! $this->userCanSeeItem($user, $roleSlug, $item)) {
                    continue;
                }

                $filteredItems[] = $item;
            }

            $sections[$sectionKey] = [
                'label' => $section['label'],
                'items' => $filteredItems,
            ];
        }

        return [
            'colors' => $colors,
            'quick_action_colors' => $config['quick_action_colors'],
            'sections' => $sections,
            'has_quick_actions' => ! empty($sections['quick_actions']['items']),
            'has_main_items' => ! empty($sections['main']['items']),
        ];
    }

    /**
     * Determine if the user can see a specific navigation item.
     */
    private function userCanSeeItem(User $user, ?string $roleSlug, array $item): bool
    {
        // system_admin sees everything (except gate-restricted items)
        if ($roleSlug === 'system_admin') {
            if ($this->isWildcardOnly($item)) {
                return $this->checkOptionalGate($user, $item);
            }

            return $this->checkOptionalGate($user, $item);
        }

        // Check role access
        $roles = $item['roles'] ?? [];

        if ($roles === ['*']) {
            return $this->checkOptionalGate($user, $item);
        }

        if ($roleSlug === null || ! in_array($roleSlug, $roles, true)) {
            return false;
        }

        return $this->checkOptionalGate($user, $item);
    }

    /**
     * Check if item uses only wildcard role access.
     */
    private function isWildcardOnly(array $item): bool
    {
        return ($item['roles'] ?? []) === ['*'];
    }

    /**
     * Check optional gate ability if defined on the item.
     */
    private function checkOptionalGate(User $user, array $item): bool
    {
        if (isset($item['gate'])) {
            $modelClass = $item['gate_model'] ?? null;

            if ($modelClass && class_exists($modelClass)) {
                return Gate::allows($item['gate'], [$modelClass]);
            }

            return Gate::allows($item['gate']);
        }

        return true;
    }
}
