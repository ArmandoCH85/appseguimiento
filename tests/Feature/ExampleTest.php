<?php

it('redirects root', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
});

