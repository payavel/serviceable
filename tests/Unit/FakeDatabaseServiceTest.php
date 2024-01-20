<?php

namespace Payavel\Orchestration\Tests\Unit;

use Payavel\Orchestration\Tests\Traits\CreatesDatabaseServiceables;
use Payavel\Orchestration\Tests\Traits\SetsDatabaseDriver;

class FakeDatabaseServiceTest extends TestService
{
    use CreatesDatabaseServiceables,
        SetsDatabaseDriver;
    
    public $fake = true;
}
