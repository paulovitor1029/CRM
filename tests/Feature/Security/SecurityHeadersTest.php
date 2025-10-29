<?php

it('adds security headers (CSP, XFO, XCTO) to responses', function () {
    $resp = $this->get('/api/metrics');
    $resp->assertHeader('Content-Security-Policy');
    $resp->assertHeader('X-Frame-Options', 'DENY');
    $resp->assertHeader('X-Content-Type-Options', 'nosniff');
});

