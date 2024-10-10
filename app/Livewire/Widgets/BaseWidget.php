<?php

namespace App\Livewire\Widgets;

use App\Models\UserWidget;
use Livewire\Component;

abstract class BaseWidget extends Component
{
    public array $widget;

    abstract public static function getWidgetName(array $data = null): string;

    abstract public static function isRemovable(): bool;

    public function getRemovableProperty(): bool
    {
        return static::isRemovable();
    }

    public function up(): void
    {
        $this->dispatch('up-widget', $this->widget[UserWidget::FIELD_ID]);
    }

    public function down(): void
    {
        $this->dispatch('down-widget', $this->widget[UserWidget::FIELD_ID]);
    }

    public function detach(): void
    {
        $this->dispatch('detach-widget', $this->widget[UserWidget::FIELD_ID]);
    }

    public function remove(): void
    {
        $this->dispatch('remove-widget', $this->widget[UserWidget::FIELD_ID]);
    }
}
