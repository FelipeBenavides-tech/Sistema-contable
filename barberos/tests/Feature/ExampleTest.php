<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_principal_envia_al_login_si_no_hay_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/pos')->assertRedirect(route('login'));
    }
}
