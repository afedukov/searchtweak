<?php

namespace Tests\Unit\Rules;

use App\Models\SearchEvaluation;
use App\Rules\AutoRestartRule;
use PHPUnit\Framework\TestCase;

class AutoRestartRuleTest extends TestCase
{
    private AutoRestartRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new AutoRestartRule();
    }

    private function validate(array $data, mixed $value): ?string
    {
        $this->rule->setData($data);

        $error = null;
        $this->rule->validate('setting_auto_restart', $value, function ($message) use (&$error) {
            $error = $message;
        });

        return $error;
    }

    public function test_auto_restart_allowed_with_no_reuse_strategy(): void
    {
        $error = $this->validate(
            ['setting_reuse_strategy' => SearchEvaluation::REUSE_STRATEGY_NONE],
            true
        );

        $this->assertNull($error);
    }

    public function test_auto_restart_fails_with_reuse_query_doc(): void
    {
        $error = $this->validate(
            ['setting_reuse_strategy' => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC],
            true
        );

        $this->assertEquals('Auto-restart cannot be enabled when re-use strategy is set.', $error);
    }

    public function test_auto_restart_fails_with_reuse_query_doc_position(): void
    {
        $error = $this->validate(
            ['setting_reuse_strategy' => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION],
            true
        );

        $this->assertEquals('Auto-restart cannot be enabled when re-use strategy is set.', $error);
    }

    public function test_auto_restart_false_allowed_with_any_reuse_strategy(): void
    {
        $error = $this->validate(
            ['setting_reuse_strategy' => SearchEvaluation::REUSE_STRATEGY_QUERY_DOC],
            false
        );

        $this->assertNull($error);
    }

    public function test_auto_restart_fails_when_no_strategy_key_present(): void
    {
        // When no setting_reuse_strategy key exists, the null value !== REUSE_STRATEGY_NONE (0),
        // so auto-restart is blocked.
        $error = $this->validate(
            [],
            true
        );

        $this->assertEquals('Auto-restart cannot be enabled when re-use strategy is set.', $error);
    }
}
