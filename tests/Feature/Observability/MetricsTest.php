<?php

it('exposes prometheus metrics and tracks http requests', function () {
    $this->getJson('/api/ping')->assertStatus(404); // ensure some request
    $resp = $this->get('/api/metrics')->assertOk();
    expect($resp->getContent())->toContain('http_requests_total');
});

