# ZPOOL-WALLET-MONITOR
Monitor mining wallet dari ZPOOL secara realtime via terminal (PHP)



Script PHP ringan untuk memonitor wallet mining di ZPOOL secara realtime dari terminal.  
Menampilkan informasi lengkap seperti saldo wallet, pembayaran terbaru, dan statistik worker.

A lightweight PHP script to monitor ZPOOL mining wallet in real-time via terminal.  
It displays full stats including wallet balance, recent payouts, and worker status.

---

## 🖼️ Contoh Tampilan / Example Output

╔════════════════════════════════════╗ ║      ZPOOL DGB WALLET MONITOR     ║ ╚════════════════════════════════════╝ ▶ Wallet: D8FBfUPiT5Q4vcDuthrFFQkmj6r6HMDnbk 💰 Balance     : 0.06083547 DGB 📊 Unsold      : 0.38428498 DGB ⏳ Pending     : 0.44512045 DGB ✨ 24h Paid    : 0.72866509 DGB 💎 Total Paid  : 0.75518761 DGB ⏰ Next Payout : 04:00 (3h 47m) ⚡ Hashrate    : 540.0 H/s 👥 Workers     : 3

---

## 🌐 Fitur / Features

🇮🇩 **Bahasa Indonesia**
- Mendukung banyak wallet (multi-wallet)
- Ambil data dari ZPOOL API (otomatis)
- Menampilkan hashrate, saldo, dan status worker
- Format CLI yang rapi
- Auto-refresh setiap beberapa detik

🇬🇧 **English**
- Supports multiple wallets (multi-wallet)
- Fetches data from ZPOOL API
- Displays hashrate, balance, and worker status
- Neat CLI-style output
- Auto-refresh every few seconds

---

## 🚀 Cara Menjalankan / How to Run

1. Clone repo atau upload file `bot.php` ke server Anda  
   Clone the repo or upload `bot.php` to your server

2. Tambahkan alamat wallet di bagian ini / Add your wallet address:
   ```php
   $wallets[] = 'D8FBfUPiT5Q4vcDuthrFFQkmj6r6HMDnbk';

3. Jalankan lewat terminal / Run via terminal:

php bot.php




---

📦 Persyaratan / Requirements

PHP 7.0 atau lebih tinggi / PHP 7.0 or higher

Koneksi internet aktif / Active internet connection



---

📄 Lisensi / License

Lisensi MIT — bebas digunakan, dimodifikasi, dan dibagikan
MIT License — free to use, modify, and distribute


---

🤝 Kontribusi / Contributions

Sangat terbuka untuk pull request, saran, atau penambahan fitur baru.
Pull requests, suggestions, and feature ideas are very welcome!


---

👤 Pembuat / Author

PlayZonz (https://playzonz.my.id)

Terima kasih telah menggunakan proyek ini!
Thanks for using this project!

---
