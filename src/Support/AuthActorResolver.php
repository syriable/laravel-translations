<?php

namespace Syriable\Translations\Support;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Syriable\Translations\Contracts\ResolvesActor;

class AuthActorResolver implements ResolvesActor
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {}

    public function resolve(): ?string
    {
        $id = $this->auth->guard(config('translations.auth_guard'))->id();

        if ($id !== null) {
            return (string) $id;
        }

        return config('translations.system_actor');
    }
}
