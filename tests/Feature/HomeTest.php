<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_carrega(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Silvia Souza');
        $response->assertSee('Fotografa');
    }

    public function test_home_mostra_whatsapp(): void
    {
        $response = $this->get('/');
        $response->assertSee('wa.me');
        $response->assertSee('99950-2677');
    }

    public function test_home_mostra_instagram(): void
    {
        $response = $this->get('/');
        $response->assertSee('@silviadesouza.fotografa_');
        $response->assertSee('instagram.com');
    }

    public function test_home_tem_link_login_discreto(): void
    {
        $response = $this->get('/');
        $response->assertSee('/login');
    }

    public function test_home_nao_exige_login(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertGuest();
    }

    public function test_home_mostra_servicos(): void
    {
        $response = $this->get('/');
        $response->assertSee('Festas Infantis');
        $response->assertSee('Casamentos');
        $response->assertSee('Ensaios');
    }

    public function test_home_mostra_depoimentos(): void
    {
        $response = $this->get('/');
        $response->assertSee('Ana Silva');
        $response->assertSee('Carlos Mendes');
        $response->assertSee('Marcos Oliveira');
    }
}
