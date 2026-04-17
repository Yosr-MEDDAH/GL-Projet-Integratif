<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;


class Administrateur extends User
{
    use HasFactory;

    protected $table = 'users';

    public $fillable = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->fillable = array_merge(parent::getFillable(), []);
    }
    public function getFillable()
    {
        return array_merge(parent::getFillable(), []);
    }
}
