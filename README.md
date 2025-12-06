
# 🎯 **Hackathon Kolosal – PHP Native + AI Kolosal Integration**

Project ini merupakan aplikasi **Full PHP Native** yang memanfaatkan **AI Kolosal API** untuk menghasilkan konten otomatis seperti caption, strategy, story, dan berbagai fitur AI lainnya. Aplikasi ini dirancang untuk kebutuhan hackathon, eksperimen, dan implementasi AI pada sistem berbasis PHP.

---

## 🚀 **Features**

* ✅ Full PHP Native (tanpa framework)
* ✅ Terintegrasi dengan **AI Kolosal API**
* ✅ Generate Caption / Story / Strategy dengan AI
* ✅ Form input sederhana & responsif
* ✅ Struktur folder rapi untuk maintainability
* ✅ Compatible untuk hosting shared (InfinityFree, cPanel, Laragon, XAMPP)

---

## 📁 **Project Structure (Contoh)**

```
/hackathon-kolosal
│── index.php
│── ai-caption.php
│── ai-story.php
│── ai-strategy.php
│── ai-collab.php
│── /assets
│     ├── css
│     ├── js
│── /includes
│     ├── header.php
│     ├── footer.php
│     ├── navbar.php
│     └── config.php   ← API KEY & konfigurasi
```

---

# 🔧 **Installation Guide**

## **1. Clone repository**

```bash
git clone https://github.com/Yowhathvb/hackathon-kolosal.git
cd hackathon-kolosal
```

## **2. Pastikan PHP sudah terinstall**

Untuk XAMPP/Laragon → sudah otomatis.

Cek:

```bash
php -v
```

## **3. Pindahkan folder ke web root**

* XAMPP → `htdocs/`
* Laragon → `www/`
* Linux → `/var/www/html/`

Contoh:

```
C:\xampp\htdocs\hackathon-kolosal
```

## **4. Tambahkan API Key Kolosal**

Edit file:

```
/includes/config.php
```

Masukkan:

```php
<?php

$KolosalAPI = "MASUKKAN_API_KEY_DI_SINI";  

function kolosalGenerate($prompt) {
    global $KolosalAPI;

    $url = "https://api.kolosal.ai/v1/chat/completions";

    $payload = json_encode([
        "model" => "kolossal-ai",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $KolosalAPI"
        ],
        CURLOPT_POSTFIELDS => $payload
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
```

---

# 📌 **How to Use (Cara Penerapan)**

## **1. Generate AI Caption**

File: `ai-caption.php`

Contoh penggunaan:

```php
require 'includes/config.php';

if (isset($_POST['generate'])) {

    $topic = $_POST['topic'];

    $result = kolosalGenerate("Create social media caption about: $topic");

    $caption = $result['choices'][0]['message']['content'] ?? "Error generating caption.";
}
```

User memasukkan topik → AI menghasilkan caption otomatis.

---

## **2. Generate AI Story**

File: `ai-story.php`

```php
$result = kolosalGenerate("Write a short story about: $topic");
```

---

## **3. Generate Strategy / Marketing Plan**

File: `ai-strategy.php`

```php
$result = kolosalGenerate("Create a marketing strategy for: $business");
```

---

## **4. Kolaborasi AI (AI Collaboration Feature)**

File: `ai-collab.php`

```php
$result = kolosalGenerate("Suggest collaboration ideas for: $brand");
```

---

# 🛠 **Deploy ke Hosting**

**Jika pakai InfinityFree / cPanel:**

1. Upload semua file ke `/htdocs` atau `/public_html`
2. Pastikan API Key tidak disimpan di folder publik (gunakan `includes/config.php`)
3. Jika cURL error → aktifkan:

   * cPanel → *Select PHP Version* → enable **curl**
4. Tes fitur AI

---

# 🔐 **Environment Variables (Optional – lebih aman)**

Jika ingin format `.env` agar API Key tidak terlihat:

```
KOL_KEY=xxxxxx
```

Lalu ambil:

```php
$KolosalAPI = getenv("KOL_KEY");
```

---

# 🧪 **Testing API**

Tes langsung via terminal:

```bash
curl -X POST "https://api.kolosal.ai/v1/chat/completions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer API_KEY" \
  -d '{
    "model":"kolossal-ai",
    "messages":[{"role":"user","content":"Hello"}]
  }'
```

Jika berhasil → project siap berjalan.

---

# 🤝 **Contributing**

1. Fork repo
2. Buat branch baru:

   ```bash
   git checkout -b feature-new-update
   ```
3. Commit perubahan:

   ```bash
   git commit -m "Add new AI feature"
   ```
4. Push:

   ```bash
   git push origin feature-new-update
   ```
5. Buat Pull Request

---

# ⭐ **License**

This project is open-source under MIT License.

---

# 🚀 **Need More?**

Saya bisa buatkan:
✔ UI baru
✔ Sistem auth login
✔ Dashboard admin
✔ Dokumentasi API Kolosal versi lengkap
✔ Struktur backend lebih rapi


