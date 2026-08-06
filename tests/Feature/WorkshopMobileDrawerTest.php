<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Responsive behaviour of the workshop shell.
 *
 * The layout ships a single inline stylesheet, so these tests resolve the
 * declarations that actually apply to a selector at a given viewport width
 * (base rules plus every matching @media block, in source order) instead of
 * pattern-matching raw CSS.
 */
class WorkshopMobileDrawerTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE = 375;

    private const TABLET = 768;

    private const DESKTOP = 1024;

    private const WIDE = 1440;

    public function test_mobile_hides_the_sidebar_and_uses_the_bottom_navigation(): void
    {
        $css = $this->stylesheet();

        $sidebar = $this->styles($css, '.ws-sidebar', self::MOBILE);
        $this->assertSame('hidden', $sidebar['visibility'] ?? null);
        $this->assertSame('translateX(-100%)', $sidebar['transform'] ?? null);
        $this->assertSame('fixed', $sidebar['position'] ?? null);

        $this->assertSame('flex', $this->styles($css, '.ws-mobile-header', self::MOBILE)['display'] ?? null);
        $this->assertSame('inline-flex', $this->styles($css, '.ws-drawer-close', self::MOBILE)['display'] ?? null);
        $mobileHeader = $this->styles($css, '.ws-mobile-header', self::MOBILE);
        $this->assertSame('var(--ws-nav)', $mobileHeader['background'] ?? null);
        $this->assertSame('var(--ws-nav-text-hi)', $mobileHeader['color'] ?? null);
        $this->assertSame('1px solid var(--ws-nav-divider)', $mobileHeader['border-bottom'] ?? null);
        $bottomNav = $this->styles($css, '.ws-mobile-bottom-nav', self::MOBILE);
        $this->assertSame('grid', $bottomNav['display'] ?? null);
        $this->assertSame('fixed', $bottomNav['position'] ?? null);
        $this->assertSame('0', $bottomNav['bottom'] ?? null);
        $this->assertSame('repeat(5, minmax(0, 1fr))', $bottomNav['grid-template-columns'] ?? null);
        $bottomItem = $this->styles($css, '.ws-mobile-bottom-item', self::MOBILE);
        $this->assertSame('#52647A', $bottomItem['color'] ?? null);
        $activeBottomItem = $this->styles($css, '.ws-mobile-bottom-item[aria-current="page"]', self::MOBILE);
        $this->assertSame('#E8F1FF', $activeBottomItem['background'] ?? null);
        $this->assertSame('var(--ws-primary)', $activeBottomItem['color'] ?? null);
        $tokens = $this->styles($css, ':root', self::MOBILE);
        $this->assertSame('#F1F5F9', $tokens['--ws-page'] ?? null);
        $this->assertSame('#FFFFFF', $tokens['--ws-card'] ?? null);
        $this->assertSame('var(--ws-card)', $this->styles($css, '.ws-dashboard .ws-stat-card', self::MOBILE)['background'] ?? null);
        $this->assertSame('none', $this->styles($css, '.ws-system-status', self::MOBILE)['display'] ?? null);
        $this->assertSame('none', $this->styles($css, '.ws-dashboard-subtitle-prefix', self::MOBILE)['display'] ?? null);

        $metricLabel = $this->styles($css, '.ws-dashboard .ws-stat-label', self::MOBILE);
        $this->assertSame('none', $metricLabel['text-transform'] ?? null);
        $this->assertSame('0', $metricLabel['letter-spacing'] ?? null);

        $statusBadge = $this->styles($css, '.ws-dashboard .ws-status-badge', self::MOBILE);
        $this->assertSame('none', $statusBadge['text-transform'] ?? null);
        $this->assertSame('0', $statusBadge['letter-spacing'] ?? null);
        $this->assertSame('104px', $this->styles($css, '.ws-recent-order-status', self::MOBILE)['max-width'] ?? null);
    }

    public function test_desktop_keeps_the_sidebar_and_hides_the_mobile_menu(): void
    {
        $css = $this->stylesheet();

        foreach ([self::TABLET, self::DESKTOP, self::WIDE] as $viewport) {
            $sidebar = $this->styles($css, '.ws-sidebar', $viewport);
            $this->assertSame('sticky', $sidebar['position'] ?? null, "sidebar @ {$viewport}px");
            $this->assertArrayNotHasKey('visibility', $sidebar, "sidebar @ {$viewport}px");
            $this->assertArrayNotHasKey('transform', $sidebar, "sidebar @ {$viewport}px");

            foreach (['.ws-mobile-header', '.ws-mobile-bottom-nav', '.ws-drawer-close', '.ws-drawer-backdrop'] as $selector) {
                $this->assertSame(
                    'none',
                    $this->styles($css, $selector, $viewport)['display'] ?? null,
                    "{$selector} @ {$viewport}px",
                );
            }

            $this->assertSame('inline-flex', $this->styles($css, '.ws-system-status', $viewport)['display'] ?? null);
            $this->assertArrayNotHasKey('display', $this->styles($css, '.ws-dashboard-subtitle-prefix', $viewport));
            $this->assertSame('#F1F5F9', $this->styles($css, ':root', $viewport)['--ws-page'] ?? null);
            $this->assertSame('#FFFFFF', $this->styles($css, ':root', $viewport)['--ws-card'] ?? null);
            $this->assertSame('uppercase', $this->styles($css, '.ws-dashboard .ws-stat-label', $viewport)['text-transform'] ?? null);
            $this->assertSame('uppercase', $this->styles($css, '.ws-dashboard .ws-status-badge', $viewport)['text-transform'] ?? null);
            $this->assertSame('none', $this->styles($css, '.ws-section-label-mobile', $viewport)['display'] ?? null);
        }

        // The approved Next-inspired desktop sidebar is stable at every
        // desktop/tablet breakpoint.
        foreach ([self::TABLET, self::DESKTOP, self::WIDE] as $viewport) {
            $this->assertSame('224px', $this->styles($css, '.ws-sidebar', $viewport)['width'] ?? null);
            $this->assertSame('0 0 224px', $this->styles($css, '.ws-sidebar', $viewport)['flex'] ?? null);
        }
    }

    public function test_mobile_content_uses_the_full_width_with_no_space_reserved_for_the_sidebar(): void
    {
        $css = $this->stylesheet();

        // position: fixed takes the sidebar out of the flex flow, so it reserves
        // no track; nothing may re-introduce width or margin for it either.
        $sidebar = $this->styles($css, '.ws-sidebar', self::MOBILE);
        $this->assertSame('fixed', $sidebar['position'] ?? null);
        $this->assertSame('0', $sidebar['left'] ?? null);

        $content = $this->styles($css, '.ws-content', self::MOBILE);
        $this->assertSame('100%', $content['width'] ?? null);
        $this->assertArrayNotHasKey('margin-left', $content);
        $this->assertArrayNotHasKey('padding-left', $content);

        $main = $this->styles($css, '.ws-main', self::MOBILE);
        $this->assertSame('none', $main['max-width'] ?? null);
        $this->assertSame('0', $main['margin'] ?? null);
        $this->assertStringContainsString('env(safe-area-inset-bottom, 0px)', $main['padding-bottom'] ?? '');

        // The drawer sits above the backdrop, which covers the whole viewport.
        $backdrop = $this->styles($css, '.ws-drawer-backdrop', self::MOBILE);
        $this->assertSame('fixed', $backdrop['position'] ?? null);
        $this->assertSame('0', $backdrop['inset'] ?? null);
        $this->assertGreaterThan((int) $backdrop['z-index'], (int) $sidebar['z-index']);
    }

    public function test_open_state_reveals_the_drawer_on_mobile_only(): void
    {
        $css = $this->stylesheet();

        $open = $this->styles($css, '.ws-sidebar--open', self::MOBILE);
        $this->assertSame('visible', $open['visibility'] ?? null);
        $this->assertSame('translateX(0)', $open['transform'] ?? null);

        $this->assertSame([], $this->styles($css, '.ws-sidebar--open', self::DESKTOP));
    }

    public function test_more_tab_is_the_only_drawer_trigger_and_every_dismissal_path_is_wired(): void
    {
        $xpath = $this->xpath($this->page());

        $sidebar = $this->element($xpath, '//aside[@id="ws-sidebar"]');
        $this->assertSame("{ 'ws-sidebar--open': open }", $sidebar->getAttribute('x-bind:class'));

        $this->assertCount(0, $xpath->query('//header[contains(@class, "ws-mobile-header")]//*[contains(@class, "ws-mobile-menu-trigger")]'));
        $trigger = $this->element($xpath, '//nav[contains(@class, "ws-mobile-bottom-nav")]/button[@aria-label="Περισσότερα"]');
        $this->assertSame('Περισσότερα', trim($trigger->textContent));
        $this->assertSame('ws-sidebar', $trigger->getAttribute('aria-controls'));
        $this->assertSame('false', $trigger->getAttribute('aria-expanded'));
        $this->assertSame("open ? 'true' : 'false'", $trigger->getAttribute('x-bind:aria-expanded'));
        $this->assertSame('openDrawer($el)', $trigger->getAttribute('x-on:click'));

        // Close: the X, the backdrop, Escape, and picking a navigation link.
        $close = $this->element($xpath, '//aside[@id="ws-sidebar"]//button[contains(@class, "ws-drawer-close")]');
        $this->assertSame('closeDrawer()', $close->getAttribute('x-on:click'));
        $this->assertNotSame('', $close->getAttribute('aria-label'));

        $backdrop = $this->element($xpath, '//div[contains(@class, "ws-drawer-backdrop")]');
        $this->assertSame('closeDrawer()', $backdrop->getAttribute('x-on:click'));
        $this->assertSame('open', $backdrop->getAttribute('x-show'));

        $body = $this->element($xpath, '//body');
        $this->assertSame('closeDrawer()', $body->getAttribute('x-on:keydown.escape.window'));
        $this->assertStringContainsString('open: false', $body->getAttribute('x-data'));

        $this->assertStringContainsString('drawerTrigger: null', $body->getAttribute('x-data'));
        $this->assertStringContainsString('$refs.mobileBottomNav.inert = open', $body->getAttribute('x-effect'));
        $nav = $this->element($xpath, '//aside[@id="ws-sidebar"]//nav[contains(@class, "ws-sidebar-nav")]');
        $this->assertSame('open = false', $nav->getAttribute('x-on:click'));
        $this->assertGreaterThan(0, $xpath->query('.//a[contains(@class, "ws-sidebar-item")]', $nav)->length);
    }

    public function test_mobile_navigation_uses_only_existing_routes_and_actions(): void
    {
        $xpath = $this->xpath($this->page());
        $nav = $this->element($xpath, '//nav[contains(@class, "ws-mobile-bottom-nav")]');

        $this->assertSame('mobileBottomNav', $nav->getAttribute('x-ref'));
        $this->assertCount(4, $xpath->query('./a', $nav));
        $this->assertCount(1, $xpath->query('./button', $nav));
        $this->assertCount(0, $xpath->query('.//form | .//input', $nav));

        foreach ([
            route('workshop.dashboard'),
            route('workshop.work-orders.index'),
            route('workshop.work-orders.create'),
            route('workshop.appointments.index'),
        ] as $href) {
            $this->assertCount(1, $xpath->query('./a[@href="'.$href.'"]', $nav));
        }

        $more = $this->element($xpath, '//nav[contains(@class, "ws-mobile-bottom-nav")]/button[@aria-label="Περισσότερα"]');
        $this->assertSame('ws-sidebar', $more->getAttribute('aria-controls'));
        $this->assertSame('openDrawer($el)', $more->getAttribute('x-on:click'));

        $create = $this->element($xpath, '//nav[contains(@class, "ws-mobile-bottom-nav")]/a[@href="'.route('workshop.work-orders.create').'"]');
        $this->assertSame('Νέα εντολή', $create->getAttribute('aria-label'));
        $this->assertSame('Νέα εντολή', $create->getAttribute('title'));
        $this->assertCount(0, $xpath->query('.//span[contains(@class, "ws-mobile-bottom-label")]', $create));

        $this->assertCount(1, $xpath->query('./a[@aria-current="page" and @href="'.route('workshop.dashboard').'"]', $nav));
        $this->assertSame(
            'Πίνακας Ελέγχου',
            trim($this->element($xpath, '//span[contains(@class, "ws-mobile-context-title")]')->textContent),
        );
        $this->assertSame('Πρόσφατες εντολές', trim($this->element($xpath, '//section[contains(@class, "ws-dashboard-primary")]//h2/span[contains(@class, "ws-section-label-mobile")]')->textContent));
        $this->assertSame('Όλες', trim($this->element($xpath, '//section[contains(@class, "ws-dashboard-primary")]//a/span[contains(@class, "ws-section-label-mobile")]')->textContent));
        $search = $this->element($xpath, '//a[contains(@class, "ws-mobile-header-action") and @href="'.route('workshop.search').'"]');
        $this->assertCount(1, $xpath->query('.//circle[@cx="10.75" and @cy="10.75" and @r="6.75"]', $search));
        $this->assertCount(1, $xpath->query('.//path[@d="m15.75 15.75 4.5 4.5"]', $search));
        $this->assertCount(0, $xpath->query('.//path[contains(@d, "m21 21-5.197-5.197")]', $search));
    }

    public function test_nested_mobile_page_uses_its_existing_parent_route_as_the_top_bar_back_action(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('workshop.work-orders.create'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $header = $this->element($xpath, '//header[contains(@class, "ws-mobile-header")]');
        $back = $this->element($xpath, './/a[contains(@class, "ws-mobile-back-action")]', $header);
        $this->assertSame(route('workshop.work-orders.index'), $back->getAttribute('href'));
        $this->assertSame('Πίσω', $back->getAttribute('aria-label'));
        $this->assertSame('Πίσω', $back->getAttribute('title'));
    }

    private function page(): string
    {
        $user = User::factory()->create();

        return $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk()->getContent();
    }

    /** The inline stylesheet as the browser receives it. */
    private function stylesheet(): string
    {
        preg_match('#<style>(.*?)</style>#s', $this->page(), $matches);
        $this->assertNotEmpty($matches, 'The workshop layout must ship an inline stylesheet.');

        return preg_replace('#/\*.*?\*/#s', '', $matches[1]);
    }

    /** Declarations applying to $selector at $viewport, in cascade order. */
    private function styles(string $css, string $selector, int $viewport): array
    {
        $declarations = [];

        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $this->flatten($css, $viewport), $rules, PREG_SET_ORDER);

        foreach ($rules as [, $selectors, $body]) {
            if (! in_array($selector, array_map('trim', explode(',', $selectors)), true)) {
                continue;
            }

            foreach (explode(';', $body) as $declaration) {
                if (! str_contains($declaration, ':')) {
                    continue;
                }

                [$property, $value] = explode(':', $declaration, 2);
                $declarations[strtolower(trim($property))] = trim($value);
            }
        }

        return $declarations;
    }

    /** Inline every @media block that matches $viewport; drop the rest. */
    private function flatten(string $css, int $viewport): string
    {
        $flat = '';
        $offset = 0;

        while (($start = strpos($css, '@media', $offset)) !== false) {
            $flat .= substr($css, $offset, $start - $offset);

            $braceAt = strpos($css, '{', $start);
            $condition = substr($css, $start + 6, $braceAt - $start - 6);

            $depth = 0;
            for ($end = $braceAt; $end < strlen($css); $end++) {
                $depth += ($css[$end] === '{' ? 1 : ($css[$end] === '}' ? -1 : 0));

                if ($depth === 0) {
                    break;
                }
            }

            if ($this->mediaApplies($condition, $viewport)) {
                $flat .= substr($css, $braceAt + 1, $end - $braceAt - 1);
            }

            $offset = $end + 1;
        }

        return $flat.substr($css, $offset);
    }

    private function mediaApplies(string $condition, int $viewport): bool
    {
        // Non-width queries (prefers-reduced-motion, …) never apply here.
        if (! preg_match_all('/\((min|max)-width:\s*(\d+)px\)/', $condition, $bounds, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($bounds as [, $bound, $pixels]) {
            if ($bound === 'min' && $viewport < (int) $pixels) {
                return false;
            }

            if ($bound === 'max' && $viewport > (int) $pixels) {
                return false;
            }
        }

        return true;
    }

    private function element(DOMXPath $xpath, string $query): DOMElement
    {
        $nodes = $xpath->query($query);
        $this->assertCount(1, $nodes, $query);

        return $nodes->item(0);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }
}
