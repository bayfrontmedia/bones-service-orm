<?php

namespace Bayfront\BonesService\Orm\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Application\Services\Events\EventSubscription;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\BonesService\Orm\Commands\MakeOrmModel;
use Symfony\Component\Console\Application;

class OrmServiceEvents extends EventSubscriber implements EventSubscriberInterface
{

    /**
     * The container will resolve any dependencies.
     */

    public function __construct()
    {

    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {
        return [
            new EventSubscription('app.cli', [$this, 'addConsoleCommands'], 10)
        ];
    }

    public function addConsoleCommands(Application $application): void
    {
        $application->add(new MakeOrmModel());
    }

}