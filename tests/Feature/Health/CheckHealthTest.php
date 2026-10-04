<?php

it('reports database and cache as healthy', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'checks' => ['database' => true, 'cache' => true],
        ]);
});
