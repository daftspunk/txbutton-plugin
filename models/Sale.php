<?php namespace Txbutton\App\Models;

use Model;

/**
 * Sale Model
 */
class Sale extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'txbutton_app_sales';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
}
