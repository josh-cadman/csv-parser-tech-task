<?php

use Inertia\Testing\AssertableInertia as Assert;

it('returns the upload inertia component', function () {
    // Check that the index Inertia component is shown and no output prop is sent
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('index')
            ->missing('output')
        );
});