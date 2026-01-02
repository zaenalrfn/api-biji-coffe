<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class SchemaTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_dump_columns()
    {
        dump(Schema::getColumnListing('products'));
        dump(Schema::getColumnListing('orders'));
        $this->assertTrue(true);
    }
}
