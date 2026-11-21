<?php

test('the application renders the landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
