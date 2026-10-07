<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value site settings (CMS texts, WhatsApp templates, contact info) with sensible defaults.
 */
class Settings
{
    private const CACHE_KEY = 'site_settings.all';

    /** @var array<string, string|null>|null */
    private ?array $loaded = null;

    /**
     * Defaults grouped for the settings screen.
     *
     * @return array<string, array<string, array{label: string, default: string, type?: string}>>
     */
    public static function definitions(): array
    {
        return [
            'general' => [
                'site_name' => ['label' => 'Site name', 'default' => 'Every Nation Bekasi'],
                'tagline' => ['label' => 'Tagline', 'default' => 'HONOR GOD. MAKE DISCIPLES.'],
                'service_times' => ['label' => 'Sunday Service times', 'default' => 'Minggu, 10.00 WIB'],
                'address' => ['label' => 'Address', 'default' => 'Bekasi, Jawa Barat', 'type' => 'textarea'],
                'maps_url' => ['label' => 'Google Maps URL', 'default' => ''],
                'contact_whatsapp' => ['label' => 'Church WhatsApp', 'default' => ''],
                'contact_email' => ['label' => 'Church email', 'default' => 'hello@everynationbekasi.org'],
                'instagram_url' => ['label' => 'Instagram URL', 'default' => 'https://instagram.com/everynationbekasi'],
                'youtube_url' => ['label' => 'YouTube URL', 'default' => ''],
                'spotify_url' => ['label' => 'Spotify URL', 'default' => ''],
            ],
            'homepage' => [
                'hero_headline' => ['label' => 'Hero headline', 'default' => "HONOR GOD.\nMAKE DISCIPLES.", 'type' => 'textarea'],
                'hero_subheadline' => ['label' => 'Hero subheadline', 'default' => 'Together, we follow Jesus and help others follow Him.'],
                'hero_body' => ['label' => 'Hero body (Bahasa Indonesia)', 'default' => 'Menjadi komunitas yang mengasihi Tuhan, bertumbuh bersama dalam pemuridan, dan membawa dampak bagi generasi dan kota.', 'type' => 'textarea'],
                'hero_image' => ['label' => 'Hero background image', 'default' => '', 'type' => 'image'],
                'about_heading' => ['label' => 'About heading', 'default' => 'A church for every generation in Bekasi.'],
                'about_body' => ['label' => 'About text', 'default' => 'Every Nation Bekasi adalah bagian dari Every Nation, gerakan gereja dan pelayanan kampus yang hadir di lebih dari 80 negara. Kami rindu menghormati Tuhan dengan membangun gereja yang berpusat pada Kristus, dipenuhi Roh Kudus, dan fokus pada pemuridan — di kota Bekasi, di kampus, dan sampai ke segala bangsa.', 'type' => 'textarea'],
                'about_image' => ['label' => 'About image', 'default' => '', 'type' => 'image'],
                'campus_headline' => ['label' => 'Campus headline', 'default' => "CHANGE THE CAMPUS.\nCHANGE THE WORLD.", 'type' => 'textarea'],
                'campus_body' => ['label' => 'Campus text', 'default' => 'Kami percaya mahasiswa hari ini adalah pemimpin masa depan. Melalui Campus Ministry, kami menjangkau, memuridkan, dan memperlengkapi mahasiswa untuk mengubah dunia.', 'type' => 'textarea'],
                'campus_image' => ['label' => 'Campus image', 'default' => '', 'type' => 'image'],
                'testimonies' => ['label' => 'Testimonies (one per line: Name | Quote)', 'default' => "Sarah | LifeGroup membuat saya benar-benar merasa punya keluarga rohani di Bekasi.\nDaniel | Lewat One 2 One saya belajar dasar iman yang membuat hidup saya berubah.", 'type' => 'textarea'],
            ],
            'get_involved' => [
                'get_involved_heading' => ['label' => 'Heading', 'default' => "LET'S GET CONNECTED."],
                'get_involved_subheading' => ['label' => 'Subheading', 'default' => 'Kami ingin mengenalmu dan membantu kamu menemukan langkah berikutnya dalam perjalanan iman dan komunitas.', 'type' => 'textarea'],
                'get_involved_thanks' => ['label' => 'Thank-you message', 'default' => 'Terima kasih sudah connect! Tim kami akan menghubungi kamu melalui WhatsApp dalam beberapa hari ke depan.', 'type' => 'textarea'],
            ],
            'whatsapp' => [
                'wa_template_followup' => ['label' => 'Follow-up template', 'type' => 'textarea', 'default' => "Hi {nickname}! 👋\n\nTerima kasih sudah connect dengan Every Nation Bekasi.\n\nKami senang bisa mengenal kamu.\n\nSaya {followup_person} dari Every Nation Bekasi.\n\nKami melihat kamu tertarik dengan {interest}.\n\nBoleh kami membantu kamu mengambil next step?\n\nGod bless!"],
                'wa_template_birthday' => ['label' => 'Birthday greeting template', 'type' => 'textarea', 'default' => "Happy birthday, {nickname}! 🎉\n\nKami bersyukur untuk hidupmu. Kiranya tahun ini kamu semakin mengenal Tuhan dan mengalami kasih-Nya setiap hari.\n\nGod bless you!\n— {sender}, Every Nation Bekasi"],
                'wa_template_lifegroup' => ['label' => 'LifeGroup join request template', 'type' => 'textarea', 'default' => "Hi {nickname}! 👋\n\nSaya {sender}, leader LifeGroup {lifegroup}. Terima kasih sudah tertarik bergabung!\n\nKami bertemu setiap {schedule}. Boleh kita ngobrol sebentar?"],
                'wa_template_lifegroup_invite' => ['label' => 'LifeGroup invitation template', 'type' => 'textarea', 'default' => "Hi {nickname}! Selamat bergabung di LifeGroup {lifegroup} 🙌\n\nIni link grup WhatsApp kita: {invite_url}\n\nSampai jumpa di pertemuan berikutnya!"],
                'wa_template_volunteer' => ['label' => 'Volunteer application template', 'type' => 'textarea', 'default' => "Hi {nickname}! 👋\n\nTerima kasih sudah mendaftar untuk melayani di {ministry}. Saya {sender}, boleh kita atur waktu untuk ngobrol?"],
            ],
            'store' => [
                'store_enabled' => ['label' => 'Store open for orders', 'type' => 'toggle', 'default' => '1'],
                'store_intro' => ['label' => 'Store intro text', 'type' => 'textarea', 'default' => 'Buku pemuridan dan merchandise Every Nation Bekasi. Pesan online, bayar via transfer, lalu ambil di gereja atau kami kirim ke alamatmu.'],
                'store_bank_accounts' => ['label' => 'Bank accounts / QRIS info (one per line)', 'type' => 'textarea', 'default' => ''],
                'store_payment_instructions' => ['label' => 'Payment instructions', 'type' => 'textarea', 'default' => 'Transfer sesuai total pesanan, lalu upload bukti transfer di halaman pesanan. Pesanan diproses setelah pembayaran dikonfirmasi oleh tim kami.'],
                'store_pay_on_pickup' => ['label' => 'Allow pay on pickup', 'type' => 'toggle', 'default' => '1'],
                'store_pickup_location' => ['label' => 'Pickup location & times', 'type' => 'textarea', 'default' => 'Ambil di meja Store Every Nation Bekasi setiap hari Minggu setelah ibadah.'],
                'store_delivery_enabled' => ['label' => 'Offer delivery', 'type' => 'toggle', 'default' => '1'],
                'store_shipping_fee' => ['label' => 'Flat delivery fee (Rp)', 'type' => 'number', 'default' => '20000'],
                'store_free_shipping_min' => ['label' => 'Free delivery from subtotal (Rp, empty = never)', 'type' => 'number', 'default' => ''],
                'store_delivery_note' => ['label' => 'Delivery note', 'type' => 'textarea', 'default' => 'Pengiriman ke area Bekasi & sekitarnya. Untuk luar kota, ongkir disesuaikan dan akan dikonfirmasi lewat WhatsApp.'],
                'store_unpaid_cancel_hours' => ['label' => 'Auto-cancel unpaid transfer orders after (hours, 0 = never)', 'type' => 'number', 'default' => '72'],
                'store_whatsapp' => ['label' => 'Store WhatsApp (empty = church WhatsApp)', 'default' => ''],
                'wa_template_order' => ['label' => 'WhatsApp message to customer', 'type' => 'textarea', 'default' => "Halo {name}! 👋\n\nTerima kasih sudah memesan di Every Nation Bekasi Store.\n\nNo. pesanan: {order_number}\nTotal: {total}\nStatus: {status}\n\nDetail pesanan: {order_url}\n\nGod bless!"],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function defaults(): array
    {
        return collect(self::definitions())->flatMap(fn ($group) => collect($group)->map(fn ($def) => $def['default']))->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return self::defaults()[$key] ?? $default;
    }

    /** On/off settings are stored as "1" / "0". */
    public function enabled(string $key): bool
    {
        return (string) $this->get($key) === '1';
    }

    public function integer(string $key): ?int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : null;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('site_settings')) {
                return [];
            }

            return SiteSetting::pluck('value', 'key')->all();
        });
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function save(array $values): void
    {
        $groups = collect(self::definitions())->flatMap(fn ($defs, $group) => collect($defs)->map(fn () => $group));

        foreach ($values as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $groups[$key] ?? 'general']);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /** @return list<array{name: string, quote: string}> */
    public function testimonies(): array
    {
        return collect(preg_split('/\r?\n/', (string) $this->get('testimonies')))
            ->filter(fn ($line) => str_contains($line, '|'))
            ->map(function ($line) {
                [$name, $quote] = array_map('trim', explode('|', $line, 2));

                return ['name' => $name, 'quote' => $quote];
            })
            ->values()
            ->all();
    }
}
