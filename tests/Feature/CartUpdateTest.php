<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_adds_a_product_to_cart()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $payload = [
          'product_id' => $product->id,
          'quantity' => 2,
        ];

        $response = $this->actingAs($user)->putJson('api/cart/update', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Card updated successfully',
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_adding_more_than_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $payload = [
            'product_id' => $product->id,
            'quantity' => 10,
        ];

        $response = $this->actingAs($user)->putJson('/api/cart/update', $payload);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Stock limit exceeded',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_increases_quantity_if_product_already_in_cart()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $payload = [
            'product_id' => $product->id,
            'quantity' => 3,
        ];

        $response = $this->actingAs($user)->putJson('/api/cart/update', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }
}
