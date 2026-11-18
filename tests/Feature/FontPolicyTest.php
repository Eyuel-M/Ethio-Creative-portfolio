<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;

/**
 * Typography rule: the whole site uses exactly two font families —
 * 'Inter Variable' (body/UI) and 'Inter Tight Variable' (headings).
 * See the TYPOGRAPHY RULE block at the top of resources/css/app.css.
 */
class FontPolicyTest extends MarketplaceTestCase
{
    private const ALLOWED = ['Inter Variable', 'Inter Tight Variable'];

    public function test_stylesheet_declares_only_the_two_allowed_families(): void
    {
        $css = File::get(resource_path('css/app.css'));

        preg_match_all("/@font-face\s*\{[^}]*font-family:\s*'([^']+)'/", $css, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertEqualsCanonicalizing(self::ALLOWED, array_values(array_unique($m[1])), 'Only the two allowed families may have @font-face rules.');

        // Latin subsets are embedded so text never renders in a fallback face.
        $this->assertSame(2, substr_count($css, "-latin-wght-normal.woff2?inline'"));
        $this->assertStringContainsString('font-display: block', $css);
        $this->assertStringNotContainsString('font-display: swap', $css);

        // Theme tokens: mono and serif deliberately alias to Inter.
        $this->assertMatchesRegularExpression("/--font-sans:\s*'Inter Variable'/", $css);
        $this->assertMatchesRegularExpression("/--font-display:\s*'Inter Tight Variable'/", $css);
        $this->assertStringContainsString('--font-mono: var(--font-sans)', $css);
        $this->assertStringContainsString('--font-serif: var(--font-sans)', $css);

        // No third-party font imports.
        $this->assertDoesNotMatchRegularExpression('/@import\s+[\'"](?!tailwindcss)/', $css);
    }

    public function test_templates_and_code_do_not_introduce_other_fonts(): void
    {
        $violations = [];
        $files = array_merge(File::allFiles(resource_path('views')), File::allFiles(resource_path('js')), File::allFiles(app_path()));
        foreach ($files as $file) {
            $src = $file->getContents();
            foreach ([
                '/\bfont-(mono|serif)\b/' => 'font-mono/font-serif utility',
                '/font-family\s*:/i' => 'inline font-family',
                '/fonts\.(googleapis|gstatic|bunny)\.|use\.typekit|@fontsource(?!-variable\/inter)/i' => 'external/other font source',
                '/<font[\s>]/i' => '<font> tag',
                '/font-\[[^\]]+\]/' => 'arbitrary font-[…] utility',
            ] as $pattern => $why) {
                if (preg_match($pattern, $src)) {
                    $violations[] = $file->getRelativePathname().": $why";
                }
            }
        }
        $this->assertSame([], $violations, "Typography rule violations:\n".implode("\n", $violations));
    }

    public function test_rendered_pages_load_no_extra_fonts(): void
    {
        $pages = ['/', '/pricing', '/jobs', '/freelancers', '/this-page-does-not-exist'];
        foreach ($pages as $url) {
            $html = $this->get($url)->getContent();
            $this->assertDoesNotMatchRegularExpression('/font-family\s*:|fonts\.(googleapis|bunny)|Figtree|Instrument Sans/i', $html, "Unexpected font on $url");
        }

        $client = $this->client();
        foreach (['/', '/dashboard', '/settings', '/billing'] as $url) {
            $html = $this->actingAs($client)->get($url)->getContent();
            $this->assertDoesNotMatchRegularExpression('/font-family\s*:|fonts\.(googleapis|bunny)/i', $html, "Unexpected font on $url");
        }
    }

    public function test_error_pages_use_the_site_design(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found')->assertSee('Go to home');
    }
}
