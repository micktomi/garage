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

    public function test_mobile_hides_the_sidebar_and_shows_the_menu_trigger(): void
    {
        $css = $this->stylesheet();

        $sidebar = $this->styles($css, '.ws-sidebar', self::MOBILE);
        $this->assertSame('hidden', $sidebar['visibility'] ?? null);
        $this->assertSame('translateX(-100%)', $sidebar['transform'] ?? null);
        $this->assertSame('fixed', $sidebar['position'] ?? null);

        $this->assertSame('flex', $this->styles($css, '.ws-mobile-header', self::MOBILE)['display'] ?? null);
        $this->assertSame('inline-flex', $this->styles($css, '.ws-mobile-menu-trigger', self::MOBILE)['display'] ?? null);
        $this->assertSame('inline-flex', $this->styles($css, '.ws-drawer-close', self::MOBILE)['display'] ?? null);
    }

    public function test_desktop_keeps_the_sidebar_and_hides_the_mobile_menu(): void
    {
        $css = $this->stylesheet();

        foreach ([self::TABLET, self::DESKTOP, self::WIDE] as $viewport) {
            $sidebar = $this->styles($css, '.ws-sidebar', $viewport);
            $this->assertSame('sticky', $sidebar['position'] ?? null, "sidebar @ {$viewport}px");
            $this->assertArrayNotHasKey('visibility', $sidebar, "sidebar @ {$viewport}px");
            $this->assertArrayNotHasKey('transform', $sidebar, "sidebar @ {$viewport}px");

            foreach (['.ws-mobile-header', '.ws-mobile-menu-trigger', '.ws-drawer-close', '.ws-drawer-backdrop'] as $selector) {
                $this->assertSame(
                    'none',
                    $this->styles($css, $selector, $viewport)['display'] ?? null,
                    "{$selector} @ {$viewport}px",
                );
            }
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

    public function test_menu_trigger_toggles_the_sidebar_and_every_dismissal_path_is_wired(): void
    {
        $xpath = $this->xpath($this->page());

        $sidebar = $this->element($xpath, '//aside[@id="ws-sidebar"]');
        $this->assertSame("{ 'ws-sidebar--open': open }", $sidebar->getAttribute('x-bind:class'));

        $trigger = $this->element($xpath, '//button[contains(@class, "ws-mobile-menu-trigger")]');
        $this->assertSame('Μενού', trim($trigger->textContent));
        $this->assertSame('ws-sidebar', $trigger->getAttribute('aria-controls'));
        $this->assertSame('false', $trigger->getAttribute('aria-expanded'));
        $this->assertSame("open ? 'true' : 'false'", $trigger->getAttribute('x-bind:aria-expanded'));
        $this->assertSame('openDrawer()', $trigger->getAttribute('x-on:click'));

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

        $nav = $this->element($xpath, '//aside[@id="ws-sidebar"]//nav[contains(@class, "ws-sidebar-nav")]');
        $this->assertSame('open = false', $nav->getAttribute('x-on:click'));
        $this->assertGreaterThan(0, $xpath->query('.//a[contains(@class, "ws-sidebar-item")]', $nav)->length);
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
