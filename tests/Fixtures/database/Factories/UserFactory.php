<?php

namespace Luttje\UserCustomId\Tests\Fixtures\Database\Factories;

use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Orchestra\Testbench\Factories\UserFactory as OrchestraUserFactory;

class UserFactory extends OrchestraUserFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;
}
