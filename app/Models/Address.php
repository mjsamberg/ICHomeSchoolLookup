<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected  $primaryKey = 'id';

    
    // Override the default Eloquent methods to make them read-only
    public function save(array $options = [])
    {
        throw new \Exception('Cannot create or update read-only model');
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new \Exception('Cannot update read-only model');
    }

    public function delete()
    {
        throw new \Exception('Cannot delete read-only model');
    }

}
