<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\ProductType;
use App\Models\DiscipleshipProgram;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Sample store catalogue (books and merchandise). Runs once: skipped when products exist.
 * Prices and stock are examples — edit them in Store → Products.
 */
class StoreDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::withTrashed()->exists()) {
            $this->command?->info('Store products already present — skipped.');

            return;
        }

        $category = fn (string $slug) => ProductCategory::where('slug', $slug)->value('id');
        $program = fn (string $name) => DiscipleshipProgram::where('name', $name)->value('id');

        $books = [
            ['One 2 One', 'Every Nation', 'Buku panduan One 2 One — tujuh pelajaran dasar untuk memulai perjalanan mengikut Yesus bersama seorang discipler.', 35000, null, 120, 'One 2 One', true],
            ['Purple Book', 'Every Nation', 'Purple Book: dua belas pelajaran fondasi Alkitab untuk membangun iman yang kokoh.', 65000, 75000, 80, 'Purple Book', true],
            ['Making Disciples Handbook', 'Every Nation', 'Buku pegangan kelas Making Disciples 1 & 2 — belajar memuridkan orang yang memuridkan orang lain.', 85000, null, 40, 'Making Disciples 1', false],
            ['Victory Weekend Journal', 'Every Nation Bekasi', 'Jurnal pendamping untuk Preparing for Victory dan Victory Weekend.', 45000, null, 60, 'Victory Weekend', false],
            ['Daily Devotional Journal', 'Every Nation Bekasi', 'Jurnal saat teduh 90 hari: baca Firman, tulis refleksi, dan catat doa setiap hari.', 75000, null, 25, null, false],
        ];
        foreach ($books as $i => [$name, $author, $excerpt, $price, $compare, $stock, $programName, $featured]) {
            Product::create([
                'product_category_id' => $programName ? $category('discipleship-books') : $category('christian-living'),
                'discipleship_program_id' => $programName ? $program($programName) : null,
                'type' => ProductType::Book,
                'name' => $name,
                'author' => $author,
                'excerpt' => $excerpt,
                'description' => $excerpt."\n\nTersedia di meja Store setiap hari Minggu, atau pesan online dan ambil setelah ibadah.",
                'price' => $price,
                'compare_at_price' => $compare,
                'stock' => $stock,
                'is_featured' => $featured,
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
            ]);
        }

        $merch = [
            ['Honor God Make Disciples T-Shirt', 'apparel', 'Kaos katun combed 30s warna navy dengan tulisan HONOR GOD. MAKE DISCIPLES.', 120000, ['S' => 10, 'M' => 15, 'L' => 15, 'XL' => 10, 'XXL' => [5, 130000]], true],
            ['Every Nation Bekasi Hoodie', 'apparel', 'Hoodie fleece tebal dengan logo Every Nation Bekasi — nyaman untuk Campus Night.', 250000, ['M' => 6, 'L' => 6, 'XL' => 4], false],
            ['Tote Bag', 'accessories', 'Tote bag kanvas untuk Alkitab, jurnal, dan perlengkapan LifeGroup.', 60000, [], false],
            ['Tumbler 500ml', 'accessories', 'Tumbler stainless 500ml, menjaga minuman tetap hangat/dingin.', 95000, [], false],
            ['Lanyard & ID Holder', 'accessories', 'Lanyard Every Nation Bekasi untuk tim pelayanan.', 25000, [], false],
        ];
        foreach ($merch as $i => [$name, $categorySlug, $excerpt, $price, $sizes, $featured]) {
            $product = Product::create([
                'product_category_id' => $category($categorySlug),
                'type' => ProductType::Merchandise,
                'name' => $name,
                'excerpt' => $excerpt,
                'description' => $excerpt,
                'price' => $price,
                'stock' => $sizes === [] ? 30 : null,
                'max_per_order' => 10,
                'is_featured' => $featured,
                'status' => ContentStatus::Published,
                'sort_order' => 10 + $i,
            ]);

            $sequence = 0;
            foreach ($sizes as $size => $stock) {
                [$stock, $variantPrice] = is_array($stock) ? $stock : [$stock, null];
                $product->variants()->create(['name' => $size, 'stock' => $stock, 'price' => $variantPrice, 'sort_order' => $sequence++, 'is_active' => true]);
            }
        }
    }
}
