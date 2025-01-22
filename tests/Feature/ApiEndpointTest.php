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
        $this->assertNotEmpty($res->json());

        $res = $this->get("/api/attributes/1")
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'type', 'variants']);
    }

    public function test_main_attributes(): void
    {
        $res = $this->get("/api/main-attributes")
            ->assertOk()
            ->assertJsonIsArray();
        $this->assertNotEmpty($res->json());

        $res = $this->get("/api/main-attributes/1")
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'color', 'description']);

        $res = $this->get("/api/main-attributes/tile/czerwony")
            ->assertOk()
            ->assertViewIs("components.color-tag");
    }

    public function test_products(): void
    {
        $res = $this->get("/api/products/AS19061-00")
            ->assertOk()
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
                'sizes',
                'images',
                'thumbnails',
                'color',
                'attributes',
                'stock',
            ]);

        $res = $this->get("/api/products/for-markings?suppliers[]=Asgard")
            ->assertOk()
            ->assertJsonStructure(['results']);

        $res = $this->get("/api/products/by/AS")
            ->assertOk()
            ->assertJsonIsArray();
        $this->assertNotEmpty($res->json());
        $res = $this->get("/api/products/by/AS/---/19061")
            ->assertOk()
            ->assertJsonIsArray();
        $this->assertNotEmpty($res->json());

        $res = $this->get("/api/products/get-original-categories/original_category/Do pisania")
            ->assertOk()
            ->assertViewIs("components.product.original-categories-hints")
            ->assertViewHas("hints");

        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061-00", "AS19061-01"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([["attributes", "product_family"]]);
        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061-00", "AS19061-01"],
            "include" => ["markings"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([["attributes", "product_family", "markings"]]);
        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061"],
            "families" => true,
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([["products" => [["attributes", "product_family"]]]]);
        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061"],
            "families" => true,
            "include" => ["markings"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([["products" => [["attributes", "product_family", "markings"]]]]);

        $res = $this->post("/api/products/colors", [
            "families" => ["AS19061"],
        ])
            ->assertOk()
            ->assertJsonStructure(["colors"]);
        $this->assertNotEmpty($res->json("colors"));
        $res = $this->post("/api/products/colors", [
            "products" => ["AS19061-00", "AS19061-01"],
        ])
            ->assertOk()
            ->assertJsonStructure(["colors"]);
        $this->assertNotEmpty($res->json("colors"));

        $res = $this->post("/api/products/for-refresh", [
            "ids" => ["AS19061-00", "AS19061-01", "AS19061-XX"],
        ])
            ->assertOk()
            ->assertJsonStructure(["products", "missing"])
            ->assertJsonCount(2, "products")
            ->assertJsonCount(1, "missing");
        $res = $this->post("/api/products/for-refresh", [
            "ids" => ["AS19061", "AS1906X"],
            "families" => true,
        ])
            ->assertOk()
            ->assertJsonStructure(["products", "missing"])
            ->assertJsonCount(1, "products")
            ->assertJsonCount(1, "missing");

        $res = $this->post("/api/products/prepare-tabs", [
            "tabs" => "",
            "source" => null,
        ])
            ->assertOk()
            ->assertViewIs("components.product.tabs-editor")
            ->assertViewHasAll(["tabs", "editable"]);
    }

    public function test_stock(): void
    {
        $res = $this->get("/api/stock/AS19061")
            ->assertOk()
            ->assertJsonIsArray();
        $this->assertNotEmpty($res->json());
    }

    public function test_suppliers(): void
    {
        $res = $this->get("/api/suppliers")
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonStructure([["name", "prefix"]]);
        $this->assertNotEmpty($res->json());
    }

    public function test_synchronizations(): void
    {
        $res = $this->get("/api/synchronizations")
            ->assertOk()
            ->assertViewIs("components.synchronizations.table")
            ->assertViewHasAll(["synchronizations", "sync_statuses", "quickness_levels"]);
    }
}
