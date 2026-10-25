<?php

it('el login del técnico declara el manifest y el service worker existe', function () {
    $this->get('/tecnico/login')->assertOk()->assertSee('manifest-tecnico.webmanifest', false);

    foreach (['manifest-tecnico.webmanifest', 'sw-tecnico.js', 'offline.html'] as $f) {
        expect(file_exists(public_path($f)))->toBeTrue($f);
    }

    expect(json_decode(file_get_contents(public_path('manifest-tecnico.webmanifest')), true)['start_url'])->toBe('/tecnico');
});
