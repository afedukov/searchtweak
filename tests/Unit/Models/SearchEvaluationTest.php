<?php

namespace Tests\Unit\Models;

use App\Models\SearchEvaluation;
use PHPUnit\Framework\TestCase;

class SearchEvaluationTest extends TestCase
{
    public function test_get_status_by_label_pending(): void
    {
        $this->assertEquals(SearchEvaluation::STATUS_PENDING, SearchEvaluation::getStatusByLabel('Pending'));
    }

    public function test_get_status_by_label_active(): void
    {
        $this->assertEquals(SearchEvaluation::STATUS_ACTIVE, SearchEvaluation::getStatusByLabel('Active'));
    }

    public function test_get_status_by_label_finished(): void
    {
        $this->assertEquals(SearchEvaluation::STATUS_FINISHED, SearchEvaluation::getStatusByLabel('Finished'));
    }

    public function test_get_status_by_label_case_insensitive(): void
    {
        $this->assertEquals(SearchEvaluation::STATUS_ACTIVE, SearchEvaluation::getStatusByLabel('active'));
        $this->assertEquals(SearchEvaluation::STATUS_PENDING, SearchEvaluation::getStatusByLabel('PENDING'));
        $this->assertEquals(SearchEvaluation::STATUS_FINISHED, SearchEvaluation::getStatusByLabel('fInIsHeD'));
    }

    public function test_get_status_by_label_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status label');

        SearchEvaluation::getStatusByLabel('invalid');
    }

    public function test_is_pending(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = SearchEvaluation::STATUS_PENDING;

        $this->assertTrue($evaluation->isPending());
        $this->assertFalse($evaluation->isActive());
        $this->assertFalse($evaluation->isFinished());
    }

    public function test_is_active(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = SearchEvaluation::STATUS_ACTIVE;

        $this->assertFalse($evaluation->isPending());
        $this->assertTrue($evaluation->isActive());
        $this->assertFalse($evaluation->isFinished());
    }

    public function test_is_finished(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = SearchEvaluation::STATUS_FINISHED;

        $this->assertFalse($evaluation->isPending());
        $this->assertFalse($evaluation->isActive());
        $this->assertTrue($evaluation->isFinished());
    }

    public function test_is_deletable_when_pending(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = SearchEvaluation::STATUS_PENDING;

        $this->assertTrue($evaluation->isDeletable());
    }

    public function test_is_deletable_when_finished(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = SearchEvaluation::STATUS_FINISHED;

        $this->assertTrue($evaluation->isDeletable());
    }

    public function test_is_not_deletable_when_active(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = SearchEvaluation::STATUS_ACTIVE;

        $this->assertFalse($evaluation->isDeletable());
    }

    public function test_is_archivable(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->archived = false;

        $this->assertTrue($evaluation->isArchivable());
        $this->assertFalse($evaluation->isUnarchivable());
    }

    public function test_is_unarchivable(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->archived = true;

        $this->assertFalse($evaluation->isArchivable());
        $this->assertTrue($evaluation->isUnarchivable());
    }

    public function test_is_pinnable(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->pinned = false;

        $this->assertTrue($evaluation->isPinnable());
        $this->assertFalse($evaluation->isUnpinnable());
    }

    public function test_is_unpinnable(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->pinned = true;

        $this->assertFalse($evaluation->isPinnable());
        $this->assertTrue($evaluation->isUnpinnable());
    }

    public function test_has_started_true(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->max_num_results = 10;

        $this->assertTrue($evaluation->hasStarted());
    }

    public function test_has_started_false(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->max_num_results = null;

        $this->assertFalse($evaluation->hasStarted());
    }

    public function test_can_give_feedback_only_when_active(): void
    {
        $evaluation = new SearchEvaluation();

        $evaluation->status = SearchEvaluation::STATUS_ACTIVE;
        $this->assertTrue($evaluation->canGiveFeedback());

        $evaluation->status = SearchEvaluation::STATUS_PENDING;
        $this->assertFalse($evaluation->canGiveFeedback());

        $evaluation->status = SearchEvaluation::STATUS_FINISHED;
        $this->assertFalse($evaluation->canGiveFeedback());
    }

    public function test_status_label_attribute(): void
    {
        $evaluation = new SearchEvaluation();

        $evaluation->status = SearchEvaluation::STATUS_PENDING;
        $this->assertEquals('Pending', $evaluation->status_label);

        $evaluation->status = SearchEvaluation::STATUS_ACTIVE;
        $this->assertEquals('Active', $evaluation->status_label);

        $evaluation->status = SearchEvaluation::STATUS_FINISHED;
        $this->assertEquals('Finished', $evaluation->status_label);
    }

    public function test_status_label_attribute_unknown(): void
    {
        $evaluation = new SearchEvaluation();
        $evaluation->status = 999;

        $this->assertEquals('Unknown', $evaluation->status_label);
    }

    public function test_show_position_defaults_to_false(): void
    {
        $evaluation = new SearchEvaluation([SearchEvaluation::FIELD_SETTINGS => []]);

        $this->assertFalse($evaluation->showPosition());
    }

    public function test_show_position_true(): void
    {
        $evaluation = new SearchEvaluation([
            SearchEvaluation::FIELD_SETTINGS => [SearchEvaluation::SETTING_SHOW_POSITION => true],
        ]);

        $this->assertTrue($evaluation->showPosition());
    }

    public function test_get_feedback_strategy_default(): void
    {
        $evaluation = new SearchEvaluation([SearchEvaluation::FIELD_SETTINGS => []]);

        $this->assertEquals(1, $evaluation->getFeedbackStrategy());
    }

    public function test_get_reuse_strategy_default(): void
    {
        $evaluation = new SearchEvaluation([SearchEvaluation::FIELD_SETTINGS => []]);

        $this->assertEquals(SearchEvaluation::REUSE_STRATEGY_NONE, $evaluation->getReuseStrategy());
    }

    public function test_auto_restart_defaults_to_false(): void
    {
        $evaluation = new SearchEvaluation([SearchEvaluation::FIELD_SETTINGS => []]);

        $this->assertFalse($evaluation->autoRestart());
    }

    public function test_get_scoring_guidelines_default_empty(): void
    {
        $evaluation = new SearchEvaluation([SearchEvaluation::FIELD_SETTINGS => []]);

        $this->assertEquals('', $evaluation->getScoringGuidelines());
    }
}
