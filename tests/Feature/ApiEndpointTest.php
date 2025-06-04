<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
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
            ->assertViewIs("components.variant-tile");
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
                'variant_name',
                'image_urls',
                'thumbnail_urls',
                'tabs',
                'sizes',
                'images',
                'thumbnails',
                'color',
                'stock',
            ]);

        $res = $this->get("/api/products/for-markings?suppliers[]=Asgard")
            ->assertOk()
            ->assertJsonStructure(['results']);

        $res = $this->get("/api/products/by/Asgard")
            ->assertOk()
            ->assertJsonIsArray();
        $this->assertNotEmpty($res->json());
        $res = $this->get("/api/products/by/Asgard/---/19061")
            ->assertOk()
            ->assertJsonIsArray();
        $this->assertNotEmpty($res->json());

        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061-00", "AS19061-01"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([["product_family"]]);
        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061-00", "AS19061-01"],
            "include" => ["markings", "stock"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([["product_family", "markings", "stock"]]);
        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061"],
            "families" => true,
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([["products" => [["product_family"]]]]);
        $res = $this->post("/api/products/by/ids", [
            "ids" => ["AS19061"],
            "families" => true,
            "include" => ["markings"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([["products" => [["product_family", "markings"]]]]);

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

        $res = $this->post("/api/stock/by/id", [
            "values" => ["AS19061-00", "AS19061-01", "AS19061-XX"],
        ])
            ->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(2);
        $this->assertNotEmpty($res->json());
    }

    public function test_suppliers(): void
    {
        $res = $this->get("/api/suppliers/by-name/Ateko")
            ->assertOk()
            ->assertJsonStructure(["supplier", "categoriesSelector"]);

        $res = $this->post("/api/suppliers/prepare-categories/", [
            "categories" => [
                "raz",
                "dwa",
                "trzy",
            ],
        ])
            ->assertOk()
            ->assertViewIs("components.suppliers.categories-editor")
            ->assertViewHas("items");
    }

    public function test_synchronizations(): void
    {
        $res = $this->get("/api/synchronizations")
            ->assertOk()
            ->assertViewIs("components.synchronizations.table")
            ->assertViewHasAll(["synchronizations", "sync_statuses", "quickness_levels"]);
    }
}
