<?php

namespace Tests\Unit;

use App\Exceptions\Errors\JsonApi;
use Tests\TestCase;

class JsonErrorResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_ensures_pointer_is_prefixed_with_forward_slash()
    {
        $pointer = 'test/path/foo';

        $formattedError = JsonApi::formatError(000, $pointer, '');

        $this->assertEquals("/{$pointer}", $formattedError['errors']['source']['pointer']);
    }

    /**
     * @test
     */
    public function it_properly_formats_a_single_error()
    {
        $status = 401;
        $pointer = '/data/attributes/password';
        $message = 'You shall not pass';

        $formattedError = JsonApi::formatError($status, $pointer, $message);

        $this->assertArrayHasKey('errors', $formattedError);
        $this->assertEquals($status, $formattedError['errors']['status']);
        $this->assertEquals($pointer, $formattedError['errors']['source']['pointer']);
        $this->assertEquals($message, $formattedError['errors']['detail']);
    }

    /**
     * @test
     */
    public function it_properly_formats_validation_errors()
    {
        $status = 422;
        $errors = [
            'name' => [
                'name error one',
                'name error two'
            ],
            'email' => [
                'email error one'
            ]
        ];


        $formattedErrors = JsonApi::formatValidationErrors($status, $errors);

        $this->assertArrayHasKey('errors', $formattedErrors);
        $this->assertCount(3, $formattedErrors['errors']);
        foreach ($formattedErrors['errors'] as $error) {
            $this->assertArrayHasKey('status', $error);
            $this->assertEquals('422', $error['status']);
            $this->assertArrayHasKey('source', $error);
            $this->assertIsArray($error['source']);
            $this->assertArrayHasKey('pointer', $error['source']);
            $pointerParts = explode('/', $error['source']['pointer']);
            $field = end($pointerParts);
            $this->assertTrue(in_array($field, array_keys($errors)));
            $this->assertArrayHasKey('detail', $error);
        }
    }
}
