<?php

namespace Bayfront\BonesService\Orm;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\Service;
use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\BonesService\Orm\Events\OrmServiceEvents;
use Bayfront\BonesService\Orm\Exceptions\OrmServiceException;
use Bayfront\BonesService\Orm\Filters\OrmServiceFilters;
use Bayfront\SimplePdo\Db;

class OrmService extends Service
{

    public EventService $events;
    public FilterService $filters;
    public Db $db;
    private array $config;

    /**
     * The container will resolve any dependencies.
     * EventService is required by the abstract service.
     *
     * @param EventService $events
     * @param FilterService $filters
     * @param Db $db
     * @param array $config
     * @throws OrmServiceException
     */

    public function __construct(EventService $events, FilterService $filters, Db $db, array $config = [])
    {
        $this->events = $events;
        $this->filters = $filters;
        $this->db = $db;
        $this->config = $config;

        parent::__construct($events);

        // Enqueue events

        try {
            $this->events->addSubscriptions(new OrmServiceEvents());
        } catch (ServiceException $e) {
            throw new OrmServiceException('Unable to start OrmService: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        // Enqueue filters

        try {
            $this->filters->addSubscriptions(new OrmServiceFilters());
        } catch (ServiceException $e) {
            throw new OrmServiceException('Unable to start RbacService: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $this->events->doEvent('orm.start', $this);

    }

    /**
     * Get ORM service configuration value in dot notation.
     *
     * @param string $key (Key to return in dot notation)
     * @param mixed|null $default (Default value to return if not existing)
     * @return mixed
     */
    public function getConfig(string $key = '', mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

}