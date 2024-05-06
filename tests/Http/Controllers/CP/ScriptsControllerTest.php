<?php

use DuncanMcClean\CookieNotice\Scripts\Scripts;
use Statamic\Facades\Role;
use Statamic\Facades\User;

use function Pest\Laravel\actingAs;

it('cant render manage scripts page without permission', function () {
    $role = Role::make('author')->addPermission('access control panel')->save();

    actingAs(User::make()->assignRole($role)->save())
        ->get('/cp/cookie-notice/scripts')
        ->assertForbidden();
});

it('renders the manage scripts page', function () {
    actingAs(User::make()->makeSuper()->save())
        ->get('/cp/cookie-notice/scripts')
        ->assertOk()
        ->assertSee('Manage Scripts');
});

it('saves the scripts', function () {
    expect(Scripts::get())->toBe([]);

    $this->withoutExceptionHandling();

    actingAs(User::make()->makeSuper()->save())
        ->post('/cp/cookie-notice/scripts', [
            'necessary' => [
                [
                    'script_type' => 'other',
                    'gtm_container_id' => null,
                    'meta_pixel_id' => null,
                    'inline_javascript' => ['code' => 'alert("Hello, world!")', 'mode' => 'javascript'],
                    'spacer' => null,
                ],
            ],
            'analytics' => [
                [
                    'script_type' => 'google-tag-manager',
                    'gtm_container_id' => 'GTM-123456CN',
                    'meta_pixel_id' => null,
                    'inline_javascript' => ['code' => null, 'mode' => 'javascript'],
                    'spacer' => null,
                ],
                [
                    'script_type' => 'meta-pixel',
                    'gtm_container_id' => null,
                    'meta_pixel_id' => '123456789123456',
                    'inline_javascript' => ['code' => null, 'mode' => 'javascript'],
                    'spacer' => null,
                ],
            ],
        ])
        ->assertOk()
        ->assertJson(['message' => 'Scripts saved']);

    expect(Scripts::get())->toBe([
        'necessary' => [
            [
                'script_type' => 'other',
                'inline_javascript' => 'alert("Hello, world!")',
            ],
        ],
        'analytics' => [
            [
                'script_type' => 'google-tag-manager',
                'gtm_container_id' => 'GTM-123456CN',
            ],
            [
                'script_type' => 'meta-pixel',
                'meta_pixel_id' => '123456789123456',
            ],
        ],
    ]);
});

it('does not save the scripts when script has invalid GTM format', function () {
    expect(Scripts::get())->toBe([]);

    actingAs(User::make()->makeSuper()->save())
        ->post('/cp/cookie-notice/scripts', [
            'analytics' => [
                [
                    'script_type' => 'google-tag-manager',
                    'gtm_container_id' => 'GMT-12345',
                    'meta_pixel_id' => null,
                    'inline_javascript' => ['code' => null, 'mode' => 'javascript'],
                    'spacer' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'analytics.0.gtm_container_id' => 'This must be a valid Google Tag Manager Container ID.',
        ]);

    expect(Scripts::get())->toBe([]);
});

it('does not save the scripts when script has invalid Meta Pixel ID format', function () {
    expect(Scripts::get())->toBe([]);

    actingAs(User::make()->makeSuper()->save())
        ->post('/cp/cookie-notice/scripts', [
            'analytics' => [
                [
                    'script_type' => 'meta-pixel',
                    'gtm_container_id' => null,
                    'meta_pixel_id' => '12345678912FB',
                    'inline_javascript' => ['code' => null, 'mode' => 'javascript'],
                    'spacer' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'analytics.0.meta_pixel_id' => 'This must be a valid Meta Pixel ID.',
        ]);

    expect(Scripts::get())->toBe([]);
});

it('does not save the scripts when script contains script tag', function () {
    expect(Scripts::get())->toBe([]);

    actingAs(User::make()->makeSuper()->save())
        ->post('/cp/cookie-notice/scripts', [
            'necessary' => [
                [
                    'script_type' => 'other',
                    'gtm_container_id' => null,
                    'meta_pixel_id' => null,
                    'inline_javascript' => ['code' => '<script>alert("Hello, world!")</script>', 'mode' => 'javascript'],
                    'spacer' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'necessary.0.inline_javascript' => 'This field must not contain `<script>` tags.',
        ]);

    expect(Scripts::get())->toBe([]);
});
