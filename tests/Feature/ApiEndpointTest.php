<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    public function test_attributes(): void
    {
        $res = $this->get("/api/attributes")
            ->assertOk()
            ->assertJsonIsArray();

        $res = $this->get("/api/attributes/1")
            ->assertOk()
            ->assertJsonIsObject()
            ->assertJsonStructure(['id', 'name', 'type', 'variants']);
    }

    public function test_main_attributes(): void
    {
        $res = $this->get("/api/main-attributes")
            ->assertOk()
            ->assertJsonIsArray();

        $res = $this->get("/api/main-attributes/1")
            ->assertOk()
            ->assertJsonIsObject()
            ->assertJsonStructure(['id', 'name', 'color', 'description']);

        $res = $this->get("/api/main-attributes/tile/czerwony")
            ->assertOk()
            ->assertViewIs("components.color-tag");
    }

    public function test_products(): void
    {
        $res = $this->get("/api/products/AS19061-00")
            ->assertOk()
            ->assertJsonIsObject()
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'price',
                'product_family_id',
                'original_color_name',
                'image_urls',
                'thumbnail_urls',
                'tabs',
                'size_name',
                'images',
                'thumbnails',
                'color',
                'attributes',
                'stock',
            ]);

        $res = $this->get("/api/products/for-markings?suppliers[]=Asgard")
            ->assertOk()
            ->assertJsonStructure(['results']);
    }
}
