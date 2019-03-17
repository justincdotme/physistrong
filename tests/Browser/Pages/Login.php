<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class Login extends Page
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/login';
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array
     */
    public function elements()
    {
        return [
            '@email' => '#email',
            '@password' => '#password',
            '@submit' => '#submit',
        ];
    }

    /**
     * Submit the login form.
     *
     * @param Browser $browser
     * @param $username
     * @param $password
     */
    public function attemptLogin(Browser $browser, $username, $password)
    {
        $browser->type('@email', $username)
            ->type('@password', $password)
            ->click('@submit');
    }
}
