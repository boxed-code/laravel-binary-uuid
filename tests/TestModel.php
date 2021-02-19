<?php

namespace BoxedCode\BinaryUuid\Test;

use BoxedCode\BinaryUuid\HasBinaryUuid;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasBinaryUuid;

    protected $uuids = [
        'relation_uuid',
    ];

    protected $uuidSuffix = '_text';

    protected $table = 'test';

    public function setUuidSuffix($suffix = '_text')
    {
        $this->uuidSuffix = $suffix;
    }
}
