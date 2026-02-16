<?php

namespace Tests\Unit\Services\Scorers;

use App\Services\Scorers\PrecisionScorer;
use App\Services\Scorers\CumulativeGainScorer;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Transformers\Transformers;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Scorers\Concerns\CreatesScorerTestData;

/**
 * Tests for the abstract Scorer base class, exercised through concrete implementations.
 */
class ScorerBaseTest extends TestCase
{
    use CreatesScorerTestData;

    public function test_get_scale_lazy_initialization(): void
    {
        $scorer = new PrecisionScorer();

        $scale = $scorer->getScale();
        $this->assertInstanceOf(BinaryScale::class, $scale);

        // Second call returns the same cached instance
        $this->assertSame($scale, $scorer->getScale());
    }

    public function test_get_scale_uses_scale_class_property(): void
    {
        $precision = new PrecisionScorer();
        $cg = new CumulativeGainScorer();

        $this->assertInstanceOf(BinaryScale::class, $precision->getScale());
        $this->assertInstanceOf(GradedScale::class, $cg->getScale());
    }

    public function test_get_display_name_formats_with_num_results(): void
    {
        $scorer = new PrecisionScorer();

        // PrecisionScorer returns 'P@%d' for single keyword
        $this->assertEquals('P@10', $scorer->getDisplayName(10, 1));
        $this->assertEquals('P@5', $scorer->getDisplayName(5, 1));
    }

    public function test_get_display_name_uses_keywords_count(): void
    {
        $scorer = new PrecisionScorer();

        // PrecisionScorer returns 'MP@%d' for multiple keywords
        $this->assertEquals('MP@10', $scorer->getDisplayName(10, 2));
        $this->assertEquals('MP@20', $scorer->getDisplayName(20, 5));
    }

    public function test_get_settings_returns_empty_by_default(): void
    {
        $scorer = new PrecisionScorer();

        $this->assertIsArray($scorer->getSettings());
        $this->assertEmpty($scorer->getSettings());
    }

    public function test_json_serialize_structure(): void
    {
        $scorer = new PrecisionScorer();
        $json = $scorer->jsonSerialize();

        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('brief_description', $json);
        $this->assertArrayHasKey('description', $json);
        $this->assertArrayHasKey('settings', $json);
        $this->assertArrayHasKey('scale', $json);

        $this->assertEquals('precision', $json['type']);
        $this->assertEquals('P@%d', $json['name']);
        $this->assertEquals('Precision', $json['brief_description']);
        $this->assertIsString($json['description']);
        $this->assertIsArray($json['settings']);
        $this->assertIsArray($json['scale']);
    }

    public function test_json_serialize_scale_contains_type_and_name(): void
    {
        $scorer = new PrecisionScorer();
        $json = $scorer->jsonSerialize();

        $this->assertEquals('binary', $json['scale']['type']);
        $this->assertEquals('Binary Scale', $json['scale']['name']);
    }

    public function test_set_transformers_returns_self(): void
    {
        $scorer = new PrecisionScorer();
        $transformers = new Transformers('binary', []);

        $result = $scorer->setTransformers($transformers);

        $this->assertSame($scorer, $result);
    }

    public function test_get_value_without_transformers(): void
    {
        $scorer = new PrecisionScorer();
        $scorer->setTransformers(new Transformers('binary', []));

        // All relevant
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [BinaryScale::RELEVANT],
            2 => [BinaryScale::RELEVANT],
        ]);

        $result = $scorer->calculate($keyword, 2);

        $this->assertEquals(1.0, $result);
    }

    public function test_get_value_with_transformers(): void
    {
        $scorer = new PrecisionScorer();

        // Transform graded to binary: POOR => IRRELEVANT, others => RELEVANT
        $transformers = new Transformers('graded', [
            'binary' => [
                GradedScale::POOR => BinaryScale::IRRELEVANT,
                GradedScale::FAIR => BinaryScale::RELEVANT,
                GradedScale::GOOD => BinaryScale::RELEVANT,
                GradedScale::PERFECT => BinaryScale::RELEVANT,
            ],
        ]);
        $scorer->setTransformers($transformers);

        // Feedbacks contain graded values, should be transformed to binary before calculation
        $keyword = $this->createKeywordWithFeedbacks([
            1 => [GradedScale::PERFECT],  // transforms to RELEVANT
            2 => [GradedScale::POOR],     // transforms to IRRELEVANT
        ]);

        $result = $scorer->calculate($keyword, 2);

        // 1 relevant out of 2
        $this->assertEquals(0.5, $result);
    }

    public function test_get_relevance_values_respects_limit(): void
    {
        $scorer = new PrecisionScorer();
        $scorer->setTransformers(new Transformers('binary', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [BinaryScale::RELEVANT],
            2 => [BinaryScale::RELEVANT],
            3 => [BinaryScale::IRRELEVANT],
            4 => [BinaryScale::IRRELEVANT],
            5 => [BinaryScale::IRRELEVANT],
        ]);

        // Limit to 2 — both are relevant
        $this->assertEquals(1.0, $scorer->calculate($keyword, 2));

        // All 5 — 2 relevant out of 5
        $this->assertEquals(0.4, $scorer->calculate($keyword, 5));
    }

    public function test_get_relevance_values_with_null_grades(): void
    {
        $scorer = new PrecisionScorer();
        $scorer->setTransformers(new Transformers('binary', []));

        $keyword = $this->createKeywordWithFeedbacks([
            1 => [null],
            2 => [null],
        ]);

        $this->assertNull($scorer->calculate($keyword, 2));
    }

    public function test_json_serialize_cg_scorer(): void
    {
        $scorer = new CumulativeGainScorer();
        $json = $scorer->jsonSerialize();

        $this->assertEquals('cg', $json['type']);
        $this->assertEquals('CG@%d', $json['name']);
        $this->assertEquals('graded', $json['scale']['type']);
    }
}
