# ☕ Biji Coffee - Backend API (Laravel)

Dokumentasi lengkap pengembangan backend untuk aplikasi mobile Biji Coffee Shop. Backend ini dibangun menggunakan **Laravel 10/11** dan berfungsi sebagai RESTful API yang melayani aplikasi Flutter.

---

## 📋 Daftar Isi
1. [Pendahuluan](#1-pendahuluan)
2. [Persiapan & Setup](#2-persiapan--setup)
3. [Implementasi Autentikasi](#3-implementasi-autentikasi)
4. [Implementasi Data (Database & Model)](#4-implementasi-data)
5. [Implementasi CRUD & Bisnis Logic](#5-implementasi-crud--bisnis-logic)
6. [Struktur Response UI (JSON)](#6-struktur-response-ui)
7. [Penggunaan AI](#7-penggunaan-ai)
8. [Konfigurasi Environment (.env)](#8-konfigurasi-environment)

---

## 1. Pendahuluan
**Studi Kasus:**
Membangun sistem server-side untuk "Biji Coffee", sebuah aplikasi pemesanan kopi. Sistem ini mengelola katalog produk, kategori, keranjang belanja user, dan memproses transaksi pemesanan.

**Tujuan:**
- Menyediakan **API** yang cepat dan aman untuk aplikasi Flutter.
- Mengelola data user, produk, dan transaksi secara terpusat.
- Menyimpan asset gambar produk yang dapat diakses publik.

---

## 2. Persiapan & Setup
Langkah-langkah teknis untuk menjalankan proyek ini dari nol.

### A. Instalasi Framework
```bash
# 1. Buat project baru
composer create-project laravel/laravel api-biji-coffe

# 2. Masuk ke direktori
cd api-biji-coffe

# 3. Install API & Sanctum (Laravel 11+)
php artisan install:api
```

### B. Konfigurasi Database
Pastikan layanan MySQL berjalan (via Laragon/XAMPP).
1. Buat database baru bernama `api_biji_coffe`.
2. Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_biji_coffe
DB_USERNAME=root
DB_PASSWORD=
```

### C. Setup Storage (Penting untuk Gambar)
Agar gambar produk bisa diakses oleh aplikasi Flutter, kita harus membuat symlink folder storage ke public.
```bash
php artisan storage:link
```
*Hasil: Folder `storage/app/public` sekarang bisa diakses via `http://localhost:8000/storage/`.*

### D. Menjalankan Server
```bash
# Jalankan server lokal
php artisan serve

# Atau tentukan host agar bisa diakses HP/Emulator
php artisan serve --host=0.0.0.0
```

---

## 3. Implementasi Autentikasi
Menggunakan **Laravel Sanctum** untuk keamanan token.

**Alur Kerja:**
1. **Register**: User input data -> Server return Token.
2. **Login**: User input credentials -> Server return Token.
3. **Request API**: Flutter menyisipkan token di Header: `Authorization: Bearer <token>`.

**Kode Kunci (`AuthController.php`):**
```php
public function login(Request $request) {
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    $user = User::where('email', $request->email)->first();
    $token = $user->createToken('auth_token')->plainTextToken;
    
    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user
    ]);
}
```

---

## 4. Implementasi Data
Desain relasi database untuk mendukung fitur E-Commerce.

### Struktur Tabel Utama
- **users**: Data pengguna.
- **categories**: Kategori menu (Coffee, Non-Coffee, dll).
- **products**: Katalog menu.
  - `category_id` (Relasi ke Category)
  - `price`, `image`, `description`
- **orders**: Head transaksi.
  - `shipping_address` (Disimpan sebagai JSON Text).
  - `status` (pending, paid, completed).
- **order_items**: Detail barang yang dibeli per transaksi.

### Model & Relasi
**Product.php:**
```php
// Mengambil URL lengkap gambar otomatis
protected $appends = ['image_url'];
public function getImageUrlAttribute() {
    return url('storage/' . $this->image);
}

// Relasi ke kategori
public function category() {
    return $this->belongsTo(Category::class);
}
```

---

## 5. Implementasi CRUD & Bisnis Logic

### A. Produk (ProductController)
Menangani upload gambar & manajemen data.
- **Create**: Validasi input -> Upload Gambar ke `storage/public/products` -> Simpan path ke DB.
- **Update**: Cek gambar baru -> Hapus gambar lama (jika ada) -> Upload gambar baru -> Update DB.

### B. Order & Transaksi (OrderController)
Menggunakan **Database Transaction** agar data aman (All or Nothing).

```php
DB::beginTransaction();
try {
    // 1. Buat Order
    $order = Order::create([...]);
    
    // 2. Pindahkan item dari Cart ke OrderItem
    foreach ($cartItems as $item) {
        OrderItem::create([...]);
    }
    
    // 3. Kosongkan Keranjang
    CartItem::where('user_id', $user->id)->delete();
    
    DB::commit(); // Simpan permanen
} catch (\Exception $e) {
    DB::rollBack(); // Batalkan jika ada error
}
```

---

## 6. Struktur Response UI
Data JSON yang dikirim ke Flutter dirancang agar mudah diparsing.

**Contoh Response Produk:**
```json
{
    "id": 1,
    "title": "Cappucino",
    "price": 25000,
    "image_url": "http://192.168.1.5:8000/storage/products/cappucino.jpg",
    "category": {
        "id": 1,
        "name": "Coffee"
    }
}
```

---

## 7. Penggunaan AI
AI digunakan sebagai asisten virtual dalam pengembangan:
1. **Boilerplate Code**: Membuat kerangka Controller API Resource dengan cepat.
2. **Logic Refactoring**: Mengoptimalkan logika `CartController` menggunakan method `updateOrCreate`.
3. **Debugging**: Menemukan solusi error migrasi database dan konfigurasi storage link.

---

## 8. Konfigurasi Environment
File `.env` menyimpan rahasia aplikasi.

**Variabel Penting:**
```env
APP_NAME="Biji Coffee API"
APP_URL=http://192.168.1.X:8000  <-- PENTING: Ganti IP sesuai IP Laptop Anda agar bisa diakses HP
DB_DATABASE=api_biji_coffe
FILESYSTEM_DISK=public           <-- PENTING: Agar default upload ke folder public
```
