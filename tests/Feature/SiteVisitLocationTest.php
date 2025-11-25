<?php

describe('SiteVisit location detection', function () {
    it('saves country, region, and city from IP address', function () {
        $requestData = [
            'session_id' => 'test-session',
            'ip_address' => '8.8.8.8', // Google Public DNS, should resolve to US
            'user_agent' => 'Mozilla/5.0',
            'referer' => 'https://example.com',
            'page_url' => 'https://example.com/page',
        ];

        $response = $this->postJson(route('site-visits.store'), $requestData);
        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'country',
                'region',
                'city',
            ],
        ]);
        $data = $response->json('data');
        expect($data['country'])->not->toBeEmpty();
        expect($data['region'])->not->toBeEmpty();
        expect($data['city'])->not->toBeEmpty();
    });
});
