<?php

namespace App\Livewire;

use App\Livewire\Widgets\BaseWidget;
use App\Livewire\Widgets\EvaluationWidget;
use App\Models\UserWidget;
use App\Services\WidgetsService;
use Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Dashboard extends Component
{
    public array $widgets = [];

    public array $attachedWidgets = [];

    public bool $addWidgetModal = false;

    public string $add = '';

    public function mount(): void
    {
        $widgets = app(WidgetsService::class)->getUserWidgets(Auth::user());

        $this->attachedWidgets = $widgets
            ->where(UserWidget::FIELD_VISIBLE, true)
            ->pluck(UserWidget::FIELD_ID)
            ->all();

        $this->widgets = $widgets
            ->sortBy(UserWidget::FIELD_POSITION)
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.dashboard')
            ->title(sprintf('Dashboard: %s', Auth::user()->currentTeam->name));
    }

    protected function sync(): void
    {
        foreach (array_values($this->widgets) as $key => $widget) {
            $this->widgets[$key][UserWidget::FIELD_POSITION] = $key;
        }

        $this->attachedWidgets = array_column(
            array_filter($this->widgets, fn (array $widget) => $widget[UserWidget::FIELD_VISIBLE]),
            UserWidget::FIELD_ID
        );

        Auth::user()->syncWidgets($this->widgets);
    }

    #[On('detach-widget')]
    public function detachWidget(string $id): void
    {
        $index = array_search($id, array_column($this->widgets, UserWidget::FIELD_ID));
        if ($index === false) {
            return;
        }

        $this->widgets[$index][UserWidget::FIELD_VISIBLE] = false;

        $this->sync();
    }

    #[On('remove-widget')]
    public function removeWidget(string $id): void
    {
        $index = array_search($id, array_column($this->widgets, UserWidget::FIELD_ID));
        if ($index === false) {
            return;
        }

        /** @var BaseWidget|class-string $widgetClass */
        $widgetClass = $this->widgets[$index][UserWidget::FIELD_WIDGET_CLASS];

        if (!$widgetClass::isRemovable()) {
            Toaster::error('This widget cannot be removed.');

            return;
        }

        unset($this->widgets[$index]);

        $this->widgets = array_values($this->widgets);

        $this->sync();
    }

    #[On('up-widget')]
    public function upWidget(string $id): void
    {
        $index = array_search($id, array_column($this->widgets, UserWidget::FIELD_ID));
        if ($index === 0 || $index === false) {
            return;
        }

        for ($i = $index - 1; $i >= 0; $i--) {
            if ($this->widgets[$i][UserWidget::FIELD_VISIBLE]) {
                $this->swapWidgets($index, $i);
                break;
            }
        }
    }

    #[On('down-widget')]
    public function downWidget(string $id): void
    {
        $index = array_search($id, array_column($this->widgets, UserWidget::FIELD_ID));
        if ($index === count($this->widgets) - 1 || $index === false) {
            return;
        }

        for ($i = $index + 1; $i < count($this->widgets); $i++) {
            if ($this->widgets[$i][UserWidget::FIELD_VISIBLE]) {
                $this->swapWidgets($index, $i);
                break;
            }
        }
    }

    private function swapWidgets(int $index, int $param): void
    {
        $temp = $this->widgets[$index];

        $this->widgets[$index] = $this->widgets[$param];
        $this->widgets[$param] = $temp;

        $this->widgets[$index][UserWidget::FIELD_POSITION] = $index;
        $this->widgets[$param][UserWidget::FIELD_POSITION] = $param;

        $this->sync();
    }

    public function apply(): void
    {
        foreach ($this->widgets as $key => $widget) {
            $this->widgets[$key][UserWidget::FIELD_VISIBLE] = in_array($widget[UserWidget::FIELD_ID], $this->attachedWidgets);
        }

        $this->sync();
    }

    public function resetWidgets(): void
    {
        Auth::user()->widgets()->delete();

        $this->mount();
    }

    public function addWidget(): void
    {
        $this->addWidgetModal = false;

        [$type, $id] = array_filter(explode(':', $this->add)) ?: [null, null];

        // todo: create factory

        $widgetClass = match ($type) {
            'evaluation' => EvaluationWidget::class,
            default => null,
        };

        if ($widgetClass === null) {
            Toaster::error('Invalid widget type.');
            return;
        }

        $widget = new UserWidget([
            UserWidget::FIELD_ID => Str::uuid()->toString(),
            UserWidget::FIELD_USER_ID => Auth::id(),
            UserWidget::FIELD_TEAM_ID => Auth::user()->current_team_id,
            UserWidget::FIELD_WIDGET_CLASS => $widgetClass,
            UserWidget::FIELD_VISIBLE => true,
            UserWidget::FIELD_POSITION => count($this->widgets),
            UserWidget::FIELD_SETTINGS => [
                'id' => $id,
                'name' => 'Evaluation',
            ],
        ]);

        $this->widgets[] = $widget->toArray();

        $this->sync();
    }
}
