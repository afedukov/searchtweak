<?php

namespace Tests\Feature\Services;

use App\Livewire\Widgets\GiveFeedbackWidget;
use App\Livewire\Widgets\LeaderboardWidget;
use App\Livewire\Widgets\TeamsWidget;
use App\Models\User;
use App\Services\WidgetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetsServiceTest extends TestCase
{
    use RefreshDatabase;

    private WidgetsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WidgetsService();
    }

    public function test_get_user_widgets_returns_defaults_when_none_exist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);

        $widgets = $this->service->getUserWidgets($user);

        $this->assertCount(3, $widgets);

        $classes = $widgets->pluck('widget_class')->all();
        $this->assertContains(GiveFeedbackWidget::class, $classes);
        $this->assertContains(LeaderboardWidget::class, $classes);
        $this->assertContains(TeamsWidget::class, $classes);
    }

    public function test_attach_widget(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);

        $widget = $this->service->attachWidget($user, GiveFeedbackWidget::class, ['key' => 'value']);

        $this->assertNotNull($widget->id);
        $this->assertEquals(GiveFeedbackWidget::class, $widget->widget_class);
        $this->assertEquals(['key' => 'value'], $widget->settings);
        $this->assertEquals(0, $widget->position);
    }

    public function test_attach_widget_creates_defaults_first_if_none(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);

        $this->assertEquals(0, $user->widgets()->count());

        $this->service->attachWidget($user, GiveFeedbackWidget::class);

        // 3 default + 1 new = 4
        $this->assertEquals(4, $user->widgets()->count());
    }

    public function test_attach_widget_shifts_positions(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->switchTeam($user->currentTeam);

        $this->service->attachWidget($user, GiveFeedbackWidget::class);

        // The newly attached widget should be at position 0
        $newWidget = $user->widgets()->orderBy('position')->first();
        $this->assertEquals(0, $newWidget->position);
    }
}
