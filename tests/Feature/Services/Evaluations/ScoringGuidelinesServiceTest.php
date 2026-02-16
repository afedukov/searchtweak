<?php

namespace Tests\Feature\Services\Evaluations;

use App\Services\Evaluations\ScoringGuidelinesService;
use Tests\TestCase;

class ScoringGuidelinesServiceTest extends TestCase
{
    private ScoringGuidelinesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ScoringGuidelinesService::class);
    }

    public function test_markdown_to_html(): void
    {
        $html = $this->service->getScoringGuidelinesHTML('**bold** text');

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('text', $html);
    }

    public function test_empty_markdown_returns_empty(): void
    {
        $html = $this->service->getScoringGuidelinesHTML('');

        $this->assertEquals('', $html);
    }

    public function test_sanitizes_script_tags(): void
    {
        $html = $this->service->getScoringGuidelinesHTML('<script>alert("xss")</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert', $html);
    }

    public function test_prepare_scoring_guidelines_for_save_strips_tags(): void
    {
        $input = '<p>Hello</p><script>bad</script><sup>1</sup>';

        $result = $this->service->prepareScoringGuidelinesForSave($input);

        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('<sup>1</sup>', $result);
    }

    public function test_prepare_scoring_guidelines_allows_sup_sub_dl_dt_dd(): void
    {
        $input = '<sup>1</sup><sub>2</sub><dl><dt>term</dt><dd>def</dd></dl>';

        $result = $this->service->prepareScoringGuidelinesForSave($input);

        $this->assertStringContainsString('<sup>', $result);
        $this->assertStringContainsString('<sub>', $result);
        $this->assertStringContainsString('<dl>', $result);
        $this->assertStringContainsString('<dt>', $result);
        $this->assertStringContainsString('<dd>', $result);
    }

    public function test_prepare_scoring_guidelines_trims_whitespace(): void
    {
        $result = $this->service->prepareScoringGuidelinesForSave('  hello  ');

        $this->assertEquals('hello', $result);
    }

    public function test_get_default_scoring_guidelines(): void
    {
        $defaults = $this->service->getDefaultScoringGuidelines();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('binary', $defaults);
        $this->assertArrayHasKey('graded', $defaults);
        $this->assertArrayHasKey('detail', $defaults);
    }

    public function test_markdown_with_lists(): void
    {
        $markdown = "- Item 1\n- Item 2\n- Item 3";

        $html = $this->service->getScoringGuidelinesHTML($markdown);

        $this->assertStringContainsString('<li>', $html);
    }

    public function test_markdown_with_headings(): void
    {
        $html = $this->service->getScoringGuidelinesHTML('# Heading');

        $this->assertStringContainsString('Heading', $html);
    }
}
