<?php

namespace _namespace_\Models;

use Bayfront\BonesService\Orm\Models\OrmModel;
use Bayfront\BonesService\Orm\OrmService;

/**
 * _model_name_ model.
 *
 * Created with Bones v_bones_version_ using the Bones ORM service.
 */
class _model_name_ extends OrmModel
{

    /**
     * The container will resolve any dependencies.
     * OrmService and Db are required by the abstract model.
     *
     * @param OrmService $ormService
     */

    public function __construct(OrmService $ormService)
    {
        parent::__construct($ormService);
    }

    /**
     * Table name.
     *
     * @var string
     */
    protected string $table_name = '';

    /*
     * |--------------------------------------------------------------------------
     * | Model-specific
     * |--------------------------------------------------------------------------
     */

}