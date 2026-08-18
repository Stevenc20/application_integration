<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $connection = 'mysql_integration';
    protected $table = 'production_plans';
}

