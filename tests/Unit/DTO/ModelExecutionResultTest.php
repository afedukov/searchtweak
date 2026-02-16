<?php

namespace Tests\Unit\DTO;

use App\DTO\ModelExecutionResult;
use App\Services\Mapper\Document;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ModelExecutionResultTest extends TestCase
{
    public function test_successful_result(): void
    {
        $result = new ModelExecutionResult(200, 'OK', collect());

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(200, $result->getCode());
        $this->assertEquals('OK', $result->getMessage());
    }

    public function test_successful_result_201(): void
    {
        $result = new ModelExecutionResult(201, 'Created', collect());

        $this->assertTrue($result->isSuccessful());
    }

    public function test_unsuccessful_result_400(): void
    {
        $result = new ModelExecutionResult(400, 'Bad Request', collect());

        $this->assertFalse($result->isSuccessful());
    }

    public function test_unsuccessful_result_500(): void
    {
        $result = new ModelExecutionResult(500, 'Server Error', collect());

        $this->assertFalse($result->isSuccessful());
    }

    public function test_unsuccessful_result_199(): void
    {
        $result = new ModelExecutionResult(199, 'Weird', collect());

        $this->assertFalse($result->isSuccessful());
    }

    public function test_boundary_200_is_successful(): void
    {
        $this->assertTrue((new ModelExecutionResult(200, '', collect()))->isSuccessful());
    }

    public function test_boundary_299_is_successful(): void
    {
        $this->assertTrue((new ModelExecutionResult(299, '', collect()))->isSuccessful());
    }

    public function test_boundary_300_is_not_successful(): void
    {
        $this->assertFalse((new ModelExecutionResult(300, '', collect()))->isSuccessful());
    }

    public function test_documents_collection(): void
    {
        $docs = collect([
            (new Document())->setId('1')->setName('Doc 1'),
            (new Document())->setId('2')->setName('Doc 2'),
        ]);

        $result = new ModelExecutionResult(200, 'OK', $docs);

        $this->assertCount(2, $result->getDocuments());
    }

    public function test_total_count_defaults_to_unknown(): void
    {
        $result = new ModelExecutionResult(200, 'OK', collect());

        $this->assertEquals(-1, $result->getTotalCount());
    }

    public function test_total_count_custom_value(): void
    {
        $result = new ModelExecutionResult(200, 'OK', collect(), 42);

        $this->assertEquals(42, $result->getTotalCount());
    }

    public function test_response_defaults_to_empty(): void
    {
        $result = new ModelExecutionResult(200, 'OK', collect());

        $this->assertEquals('', $result->getResponse());
    }

    public function test_response_custom_value(): void
    {
        $result = new ModelExecutionResult(200, 'OK', collect(), 0, '{"data":"test"}');

        $this->assertEquals('{"data":"test"}', $result->getResponse());
    }

    public function test_to_array_structure(): void
    {
        $docs = collect([
            (new Document())->setId('1')->setName('Doc 1'),
        ]);

        $result = new ModelExecutionResult(200, 'OK', $docs, 10, 'raw response');

        $array = $result->toArray();

        $this->assertEquals(200, $array['code']);
        $this->assertEquals('OK', $array['message']);
        $this->assertTrue($array['successful']);
        $this->assertEquals(1, $array['count']);
        $this->assertEquals(10, $array['total_count']);
        $this->assertEquals('raw response', $array['response']);
        $this->assertEquals('document', $array['documents_plural']);
    }

    public function test_to_array_documents_plural(): void
    {
        $docs = collect([
            (new Document())->setId('1')->setName('Doc 1'),
            (new Document())->setId('2')->setName('Doc 2'),
        ]);

        $result = new ModelExecutionResult(200, 'OK', $docs);
        $array = $result->toArray();

        $this->assertEquals('documents', $array['documents_plural']);
    }

    public function test_to_array_empty_documents(): void
    {
        $result = new ModelExecutionResult(200, 'OK', collect());
        $array = $result->toArray();

        $this->assertEquals(0, $array['count']);
        $this->assertEmpty($array['documents']);
    }
}
