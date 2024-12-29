<?php

namespace App\Services\Evaluations;

use App\Services\Scorers\Scales\ScaleFactory;
use Illuminate\Support\Facades\Blade;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Stevebauman\Purify\Facades\Purify;

readonly class ScoringGuidelinesService
{
    private const array ALLOWED_TAGS = [
        'sup',
        'sub',
        'dl',
        'dt',
        'dd',
    ];

    public function __construct(private CommonMarkConverter $commonMark)
    {
    }

    public function getDefaultScoringGuidelines(): array
    {
        $scoringGuidelines = [];
        $scales = ScaleFactory::getScales();

        foreach ($scales as $scale) {
            $key = $scale->getType();
            $view = $scale->getScoringGuidelinesTemplate();

            if ($view && view()->exists($view)) {
                $scoringGuidelines[$key] = Blade::render($view);
            } else {
                $scoringGuidelines[$key] = '';
            }
        }

        return $scoringGuidelines;
    }

    public function prepareScoringGuidelinesForSave(string $guidelines): string
    {
        return strip_tags(trim($guidelines), self::ALLOWED_TAGS);
    }

    public function getScoringGuidelinesHTML(string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        try {
            $unsafeHtml = $this->commonMark->convert($markdown)->getContent();
        } catch (CommonMarkException) {
            $unsafeHtml = $markdown;
        }

        return Purify::clean($unsafeHtml);
    }
}
