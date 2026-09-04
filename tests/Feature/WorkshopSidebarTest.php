<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_preserves_real_navigation_without_actions_or_dashboard_content(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $sidebars = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-sidebar ")]');
        $this->assertCount(1, $sidebars);
        $sidebar = $sidebars->item(0);

        $expectedLinks = [
            'Πίνακας Ελέγχου' => route('workshop.dashboard'),
            'Πελάτες' => route('workshop.customers.index'),
            'Οχήματα' => route('workshop.search'),
            'Εντολές Εργασίας' => route('workshop.work-orders.index'),
            'Ραντεβού' => route('workshop.appointments.index'),
            'Admin' => url('/admin'),
        ];

        foreach ($expectedLinks as $label => $href) {
            $links = $xpath->query('.//a[@href="'.$href.'" and .//*[normalize-space(text())="'.$label.'"]]', $sidebar);
            $this->assertCount(1, $links, $label);
        }

        $this->assertCount(0, $xpath->query('.//form | .//input', $sidebar));

        // The mobile drawer dismiss control is the only button the sidebar may hold.
        $buttons = $xpath->query('.//button', $sidebar);
        $this->assertCount(1, $buttons);
        $this->assertSame('ws-drawer-close', $buttons->item(0)->getAttribute('class'));

        $this->assertCount(0, $xpath->query('.//*[contains(@class, "ws-search") or contains(@class, "ws-stat") or contains(@class, "ws-primary-action")]', $sidebar));
    }

    public function test_sidebar_marks_exactly_one_current_workshop_destination(): void
    {
        $user = User::factory()->create();

        $this->assertCurrentItem(
            $this->actingAs($user)->get(route('workshop.dashboard'))->getContent(),
            'Πίνακας Ελέγχου',
        );
        $this->assertCurrentItem(
            $this->actingAs($user)->get(route('workshop.work-orders.index'))->getContent(),
            'Εντολές Εργασίας',
        );
        $this->assertCurrentItem(
            $this->actingAs($user)->get(route('workshop.search'))->getContent(),
            'Οχήματα',
        );
        $this->assertCurrentItem(
            $this->actingAs($user)->get(route('workshop.appointments.index'))->getContent(),
            'Ραντεβού',
        );
    }

    public function test_sidebar_css_uses_the_approved_dark_slate_224px_shell(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/workshop.blade.php'));
        // /workshop CSS was extracted out of the layout's inline <style>
        // block into its own stylesheet — the layout keeps the markup, the
        // rules themselves now live here.
        $css = file_get_contents(resource_path('css/workshop.css'));

        // The sidebar shell (not the old top-nav .ws-header layout) is the
        // actual page structure: a flex .ws-shell root with the sidebar as
        // a direct child, asserted positively rather than by checking the
        // old class is absent (which would false-positive on any future,
        // unrelated use of "header").
        $this->assertMatchesRegularExpression(
            '/\.ws-shell\s*\{[^}]*display:\s*flex;[^}]*\}/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/<div class="ws-shell">.*?<x-workshop\.shell\.sidebar\s*\/>/s',
            $layout,
        );
        $this->assertStringContainsString('--ws-nav: #1B1F27;', $css);
        $this->assertStringContainsString('--ws-nav-active: #232935;', $css);

        $this->assertMatchesRegularExpression(
            '/\.ws-sidebar\s*\{[^}]*position:\s*sticky;[^}]*width:\s*224px;[^}]*flex:\s*0 0 224px;[^}]*background:\s*var\(--ws-nav\);[^}]*border-right:\s*1px solid var\(--ws-border\);[^}]*\}/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-sidebar-item\s*\{[^}]*min-height:\s*44px;[^}]*flex-direction:\s*row;[^}]*font-size:\s*14px;[^}]*\}/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-sidebar-item\[aria-current="page"\]\s*\{[^}]*color:\s*var\(--ws-primary-fg\);[^}]*background:\s*var\(--ws-nav-active\);[^}]*\}/s',
            $css,
        );
        // The sidebar has exactly two legitimate widths — 224px on desktop,
        // 272px in the mobile slide-out drawer — asserted as an allow-list
        // scoped to .ws-sidebar blocks, rather than a whole-file ban on the
        // old removed 80px/240px breakpoint values (which could false-fail
        // on any unrelated element that legitimately needs one of those
        // widths for its own reasons).
        preg_match_all('/\.ws-sidebar\s*\{([^}]*)\}/s', $css, $sidebarBlocks);
        $sidebarWidths = [];
        foreach ($sidebarBlocks[1] as $block) {
            if (preg_match('/width:\s*([\d.]+px)/', $block, $match)) {
                $sidebarWidths[] = $match[1];
            }
        }
        $this->assertNotEmpty($sidebarWidths);
        foreach ($sidebarWidths as $width) {
            $this->assertContains($width, ['224px', '272px'], "Unexpected .ws-sidebar width: {$width}");
        }
        $this->assertDoesNotMatchRegularExpression(
            '/\.ws-sidebar(?:-item)?[^{]*\{[^}]*var\(--ws-primary\)/s',
            $css,
        );
    }

    private function assertCurrentItem(string $html, string $label): void
    {
        $xpath = $this->xpath($html);
        $current = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " ws-sidebar ")]//a[@aria-current="page"]',
        );

        $this->assertCount(1, $current);
        $this->assertSame($label, trim($current->item(0)->textContent));
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
