<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class NavigationService
{
    protected $menuItems;
    protected $menuSections;

    public function __construct()
    {
        $this->loadMenu();
    }

    /**
     * Load menu items from menu.json file
     */
    protected function loadMenu()
    {
        $menuPath = resource_path('menu/menu.json');

        if (File::exists($menuPath)) {
            $menuContent = File::get($menuPath);
            $menuData = json_decode($menuContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse menu.json: ' . json_last_error_msg());
                $this->menuItems = [];
                $this->menuSections = [];
                return;
            }

            // Support both old format (menu) and new format (sections)
            if (isset($menuData['sections'])) {
                $this->menuSections = $menuData['sections'] ?? [];
                // Flatten sections for backward compatibility
                $this->menuItems = $this->flattenSections($this->menuSections);
            } else {
                // Old format - flat menu array
                $this->menuItems = $menuData['menu'] ?? [];
                $this->menuSections = [];
            }
        } else {
            Log::warning('menu.json file not found at: ' . $menuPath);
            $this->menuItems = [];
            $this->menuSections = [];
        }
    }

    /**
     * Flatten sections into a single array of items
     *
     * @param array $sections
     * @return array
     */
    protected function flattenSections($sections)
    {
        $items = [];
        foreach ($sections as $section) {
            if (isset($section['items']) && is_array($section['items'])) {
                $items = array_merge($items, $section['items']);
            }
        }
        return $items;
    }

    /**
     * Whether the current user can see this menu item (permission + requires_admin + requires_field_agent).
     */
    protected function userCanSeeItem(array $item, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (isset($item['requires_admin']) && $item['requires_admin'] && !$user->isAdmin()) {
            return false;
        }

        if (isset($item['requires_field_agent']) && $item['requires_field_agent']) {
            if (!$user->relationLoaded('fieldAgentProfile')) {
                $user->load('fieldAgentProfile');
            }
            if (!$user->fieldAgentProfile) {
                return false;
            }
        }

        // Field agents see "Agent Stock Requests" only; hide branch-to-branch "Stock Requests"
        if (!empty($item['permission']) && $item['permission'] === 'stock-requests.view') {
            if (!$user->relationLoaded('fieldAgentProfile')) {
                $user->load('fieldAgentProfile');
            }
            if ($user->fieldAgentProfile && $user->branch_id) {
                return false;
            }
        }

        if (!empty($item['permission'])) {
            $permissions = array_map('trim', explode('|', $item['permission']));
            $hasAny = false;
            foreach ($permissions as $slug) {
                if ($slug === 'agent-stock-requests.view') {
                    if (!$user->relationLoaded('fieldAgentProfile')) {
                        $user->load('fieldAgentProfile');
                    }
                    if ($user->fieldAgentProfile && $user->branch_id) {
                        $hasAny = true;
                        break;
                    }
                }
                if (!$user->relationLoaded('roleModel')) {
                    $user->load('roleModel');
                }
                if ($user->hasPermission($slug)) {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get filtered menu sections (only sections/items the current user can see).
     *
     * @return array
     */
    protected function getFilteredSections(): array
    {
        $user = Auth::user();
        $out = [];

        foreach ($this->menuSections as $section) {
            $items = $section['items'] ?? [];
            $visible = [];
            foreach ($items as $item) {
                if ($this->userCanSeeItem($item, $user)) {
                    $visible[] = $item;
                }
            }
            if (!empty($visible)) {
                $out[] = array_merge($section, ['items' => $visible]);
            }
        }

        return $out;
    }

    /**
     * Get all menu items (flattened, for backward compatibility). Only items the current user can see.
     *
     * @return array
     */
    public function getMenuItems()
    {
        $sections = $this->getFilteredSections();
        return $this->flattenSections($sections);
    }

    /**
     * Get menu sections with titles. Only sections/items the current user can see.
     *
     * @return array
     */
    public function getMenuSections()
    {
        return $this->getFilteredSections();
    }

    /**
     * Check if a menu item is active based on the current route
     *
     * @param string $activePattern
     * @return bool
     */
    public function isActive($activePattern)
    {
        $currentRoute = Route::currentRouteName();

        if (!$currentRoute) {
            return false;
        }

        // Support pipe-separated patterns (e.g. "stock-operations.*|stock-takes.*")
        if (str_contains($activePattern, '|')) {
            foreach (explode('|', $activePattern) as $pattern) {
                if ($this->matchActivePattern(trim($pattern), $currentRoute)) {
                    return true;
                }
            }
            return false;
        }

        return $this->matchActivePattern($activePattern, $currentRoute);
    }

    /**
     * Check if current route matches a single active pattern
     *
     * @param string $activePattern
     * @param string $currentRoute
     * @return bool
     */
    protected function matchActivePattern(string $activePattern, string $currentRoute): bool
    {
        if ($currentRoute === $activePattern) {
            return true;
        }

        if (str_contains($activePattern, '*')) {
            $pattern = str_replace('.', '\.', $activePattern);
            $pattern = str_replace('*', '.*', $pattern);
            $pattern = '/^' . $pattern . '$/';
            return preg_match($pattern, $currentRoute) === 1;
        }

        $patternPrefix = rtrim($activePattern, '.*');
        if ($patternPrefix && str_starts_with($currentRoute, $patternPrefix . '.')) {
            return true;
        }

        return false;
    }

    /**
     * Get active class for a menu item
     *
     * @param string $activePattern
     * @return string
     */
    public function getActiveClass($activePattern)
    {
        return $this->isActive($activePattern)
            ? 'bg-gray-100 text-[#006F78]'
            : '';
    }

    /**
     * Render navigation items as HTML
     *
     * @return string
     */
    public function render()
    {
        $html = '';

        foreach ($this->menuItems as $item) {
            $activeClass = $this->getActiveClass($item['active_pattern']);
            $route = route($item['route']);

            $html .= sprintf(
                '<a href="%s" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 hover:text-[#006F78] rounded transition font-light %s">%s%s</a>',
                $route,
                $activeClass,
                $this->renderIcon($item['icon']),
                e($item['title'])
            );
        }

        return $html;
    }

    /**
     * Render SVG icon for menu item
     *
     * @param string $iconPath
     * @return string
     */
    protected function renderIcon($iconPath)
    {
        return sprintf(
            '<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="%s"></path></svg>',
            $iconPath
        );
    }

    /**
     * Get menu item by route name
     *
     * @param string $routeName
     * @return array|null
     */
    public function getMenuItemByRoute($routeName)
    {
        foreach ($this->menuItems as $item) {
            if (isset($item['route']) && $item['route'] === $routeName) {
                return $item;
            }
        }

        return null;
    }
}
