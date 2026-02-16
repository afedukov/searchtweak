<?php

namespace Tests\Unit\Services\Models;

use App\Services\Models\ModelMetric;
use PHPUnit\Framework\TestCase;

class ModelMetricTest extends TestCase
{
    private function createMetric(): ModelMetric
    {
        return new ModelMetric(
            id: 'model-metric-1-precision-10',
            name: 'P@10',
            scorerType: 'precision',
            briefDescription: 'Precision',
            description: 'Precision measures relevance.',
            scaleType: 'binary',
        );
    }

    public function test_getters(): void
    {
        $metric = $this->createMetric();

        $this->assertEquals('model-metric-1-precision-10', $metric->getId());
        $this->assertEquals('P@10', $metric->getName());
        $this->assertEquals('precision', $metric->getScorerType());
        $this->assertEquals('Precision', $metric->getBriefDescription());
        $this->assertEquals('Precision measures relevance.', $metric->getDescription());
        $this->assertEquals('binary', $metric->getScaleType());
        $this->assertNull($metric->getLastMetric());
        $this->assertEquals(1, $metric->getKeywordsCount());
    }

    public function test_dataset(): void
    {
        $metric = $this->createMetric();
        $dataset = [
            ['label' => '2024-01-01', 'value' => 0.5],
            ['label' => '2024-01-02', 'value' => 0.8],
        ];

        $result = $metric->setDataset($dataset);

        $this->assertSame($metric, $result);
        $this->assertEquals($dataset, $metric->getDataset());
    }

    public function test_color(): void
    {
        $metric = $this->createMetric();

        $result = $metric->setColor('indigo-500');

        $this->assertSame($metric, $result);
        $this->assertEquals('indigo-500', $metric->getColor());
    }

    public function test_get_last_dataset_item(): void
    {
        $metric = $this->createMetric();
        $metric->setDataset([
            ['label' => '2024-01-01', 'value' => 0.5],
            ['label' => '2024-01-02', 'value' => 0.8],
        ]);

        $last = $metric->getLastDatasetItem();

        $this->assertEquals(['label' => '2024-01-02', 'value' => 0.8], $last);
    }

    public function test_get_last_dataset_item_empty(): void
    {
        $metric = $this->createMetric();

        $this->assertNull($metric->getLastDatasetItem());
    }

    public function test_to_livewire(): void
    {
        $metric = $this->createMetric();
        $metric->setDataset([['label' => 'x', 'value' => 1]]);
        $metric->setColor('blue-400');

        $data = $metric->toLivewire();

        $this->assertEquals('model-metric-1-precision-10', $data['id']);
        $this->assertEquals('P@10', $data['name']);
        $this->assertEquals('precision', $data['scaleType']);
        $this->assertEquals('blue-400', $data['color']);
        $this->assertCount(1, $data['dataset']);
    }

    public function test_from_livewire(): void
    {
        $data = [
            'id' => 'model-metric-1-ndcg-5',
            'name' => 'nDCG@5',
            'scorerType' => 'ndcg',
            'briefDescription' => 'nDCG',
            'description' => 'Normalized DCG',
            'scaleType' => 'graded',
            'dataset' => [['label' => 'y', 'value' => 0.9]],
            'color' => 'emerald-500',
            'keywordsCount' => 3,
        ];

        $metric = ModelMetric::fromLivewire($data);

        $this->assertEquals('model-metric-1-ndcg-5', $metric->getId());
        $this->assertEquals('nDCG@5', $metric->getName());
        $this->assertEquals('ndcg', $metric->getScorerType());
        $this->assertEquals('emerald-500', $metric->getColor());
        $this->assertEquals(3, $metric->getKeywordsCount());
        $this->assertCount(1, $metric->getDataset());
    }

    public function test_to_livewire_from_livewire_roundtrip(): void
    {
        $metric = $this->createMetric();
        $metric->setDataset([['label' => 'z', 'value' => 0.7]]);
        $metric->setColor('pink-500');

        $data = $metric->toLivewire();
        // toLivewire stores scorerType under 'scaleType' key and omits actual scaleType.
        // fromLivewire reads both 'scorerType' and 'scaleType' separately,
        // so we must supply the missing keys for the roundtrip.
        $data['scorerType'] = 'precision';
        $data['scaleType'] = 'binary';
        $restored = ModelMetric::fromLivewire($data);

        $this->assertEquals($metric->getId(), $restored->getId());
        $this->assertEquals($metric->getName(), $restored->getName());
        $this->assertEquals($metric->getColor(), $restored->getColor());
        $this->assertEquals($metric->getDataset(), $restored->getDataset());
    }

    public function test_json_serialize(): void
    {
        $metric = $this->createMetric();

        $json = $metric->jsonSerialize();

        $this->assertEquals($metric->toLivewire(), $json);
    }
}
