<?php

namespace Tests\Feature\frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FrontendContainerTest extends TestCase
{
    /**
     * @test
     */
    public function app_container_page_has_correct_data()
    {
        $this->response = $this->get(route('app.home'));

        $this->response->assertViewHas('baseUrl', config('app.api_url'));
        $this->response->assertViewHas('baseDomain', config('app.api_base_url'));
        $this->response->assertViewHas('baseProtocol', config('app.protocol'));
        $this->response->assertViewHas('basePort', 443);
    }
}
