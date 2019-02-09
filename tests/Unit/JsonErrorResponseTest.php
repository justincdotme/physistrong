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
}