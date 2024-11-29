<?php

namespace Bayfront\BonesService\Orm\Filters;

use Bayfront\Bones\Abstracts\FilterSubscriber;
use Bayfront\Bones\Application\Services\Filters\FilterSubscription;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;

class OrmServiceFilters extends FilterSubscriber implements FilterSubscriberInterface
{

    public function __construct()
    {

    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {
        return [
            new FilterSubscription('orm.query.filter', [$this, 'dynamicVariables'], 10)
        ];
    }

    /**
     * Process dynamic variables.
     *
     * @param mixed $filter
     * @return mixed
     */
    public function dynamicVariables(mixed $filter): mixed
    {
        if (!is_string($filter)) {
            return $filter;
        }

        $time = time();

        // $TIME()

        preg_match_all('/\$TIME\((.*?)\)/', $filter, $now_fxs);

        if (isset($now_fxs[1]) && is_array($now_fxs[1])) {

            foreach ($now_fxs[1] as $now_fx) {
                $filter = str_replace('$TIME(' . $now_fx . ')', strtotime($now_fx, $time), $filter);
            }

        }

        // $DATETIME()

        preg_match_all('/\$DATETIME\((.*?)\)/', $filter, $now_fxs);

        if (isset($now_fxs[1]) && is_array($now_fxs[1])) {

            foreach ($now_fxs[1] as $now_fx) {
                $filter = str_replace('$DATETIME(' . $now_fx . ')', date('Y-m-d H:i:s', strtotime($now_fx, $time)), $filter);
            }

        }

        // $DATE()

        preg_match_all('/\$DATE\((.*?)\)/', $filter, $now_fxs);

        if (isset($now_fxs[1]) && is_array($now_fxs[1])) {

            foreach ($now_fxs[1] as $now_fx) {
                $filter = str_replace('$DATE(' . $now_fx . ')', date('Y-m-d', strtotime($now_fx, $time)), $filter);
            }

        }

        // $TIME, $DATETIME, $DATE

        return str_replace([
            '$TIME',
            '$DATETIME',
            '$DATE'
        ], [
            $time,
            date('Y-m-d H:i:s', $time),
            date('Y-m-d', $time)
        ], $filter);

    }

}