<?php

namespace App\Livewire\Traits;

use App\Models\UserWidget;
use Illuminate\Support\Facades\Auth;
use Toaster;

trait ControlWidgetTrait
{
    protected function getWidgetClass(): string
    {
        return '';
    }

    protected function getWidgetEntityId(): int
    {
        return 0;
    }

    public function attach(): void
    {
        $widget = Auth::user()->widgets()
            ->where(UserWidget::FIELD_WIDGET_CLASS, $this->getWidgetClass())
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->getWidgetEntityId())
            ->first();

        if ($widget instanceof UserWidget) {
            $this->detachWidget($widget);
        } else {
            $this->attachWidget();
        }
    }

    private function attachWidget(): void
    {
        Auth::user()->attachWidget($this->getWidgetClass(), ['id' => $this->getWidgetEntityId()]);

        Toaster::success('Widget added to dashboard.');
    }

    private function detachWidget(UserWidget $widget): void
    {
        $widget->delete();

        Toaster::success('Widget removed from dashboard.');
    }
}
