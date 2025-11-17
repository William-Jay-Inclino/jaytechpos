<?php

it('contains mobile icons and responsive classes in admin layout', function () {
    $filePath = base_path('resources/js/layouts/AdminLayout.vue');
    $content = file_get_contents($filePath);

    // Check that we added sm:hidden on the icon elements
    expect(str_contains($content, 'sm:hidden'))->toBeTrue();

    // Check that the username display is hidden on mobile (hidden sm:inline)
    expect(str_contains($content, 'hidden sm:inline'))->toBeTrue();

    // Check that the navigation contains icon references
    expect(str_contains($content, 'Users as UsersIcon') || str_contains($content, "icon: UsersIcon"))->toBeTrue();
    expect(str_contains($content, 'List as ListIcon') || str_contains($content, "icon: ListIcon"))->toBeTrue();
});
