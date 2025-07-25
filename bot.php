<?php
// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Debug timezone
echo "Current timezone: " . date_default_timezone_get() . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

// Tambahkan di awal script, setelah <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug mode
define('DEBUG', true);

if (DEBUG) {
    echo "Starting bot in debug mode...\n";
    echo "PHP SAPI: " . PHP_SAPI . "\n";
    echo "OS: " . PHP_OS . "\n";
}

// Cek apakah running di CLI
if (PHP_SAPI !== 'cli') {
    die("This script should be run from command line\n");
}

function fetchWalletData($wallet_address) {
    $url = "https://zpool.ca/api/wallet?address=" . $wallet_address;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)){
        echo 'Error: ' . curl_error($ch);
        return false;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

function fetchMinerData($wallet_address) {
    $url = "https://zpool.ca/api/walletEx?address=" . $wallet_address;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)){
        echo 'Error: ' . curl_error($ch) . "\n";
        return [];
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['miners'])) {
        return [];
    }
    
    $miners = [];
    foreach ($data['miners'] as $miner) {
        $miners[] = [
            'version' => $miner['version'],
            'password' => $miner['password'],
            'algo' => $miner['algo'],
            'difficulty' => $miner['difficulty'],
            'hashrate' => $miner['spm'] * 60 // Convert shares per minute to hashrate
        ];
    }
    
    return $miners;
}

function clearScreen() {
    if (PHP_SAPI === 'cli') {  // Cek apakah running di CLI
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
}

function displayWalletInfo($data, $wallet_address, $coin) {
    clearScreen();
    
    // Header dengan warna, sesuaikan dengan coin
    echo "\033[1;36m╔════════════════════════════════════╗\033[0m\n";
    echo "\033[1;36m║      ZPOOL {$coin} WALLET MONITOR      ║\033[0m\n";
    echo "\033[1;36m╚════════════════════════════════════╝\033[0m\n";
    
    if (!$data) {
        echo "\033[1;31mGagal mengambil data wallet!\033[0m\n";
        return;
    }
    
    // Data dari API
    $unpaid = isset($data['unpaid']) ? $data['unpaid'] : 0;
    $paid24h = isset($data['paid24h']) ? $data['paid24h'] : 0;
    $total_paid = isset($data['paidtotal']) ? $data['paidtotal'] : 0;
    $hashrate = isset($data['hashrate']) ? $data['hashrate'] : 0;
    $workers = isset($data['workers']) ? $data['workers'] : 0;
    $balance = isset($data['balance']) ? $data['balance'] : 0;
    $unsold = isset($data['unsold']) ? $data['unsold'] : 0;
    
    // Tampilan informasi dengan simbol coin yang sesuai
    echo "\033[1;33m▶ Wallet: " . $wallet_address . "\033[0m\n";
    echo "──────────────────────────────────────\n";
    echo " 💰 Balance     : " . number_format((float)$balance, 8) . " {$coin}\n";
    echo " 📊 Unsold      : " . number_format((float)$unsold, 8) . " {$coin}\n";
    echo " ⏳ Pending     : " . number_format((float)$unpaid, 8) . " {$coin}\n";
    echo " ✨ 24h Paid    : " . number_format((float)$paid24h, 8) . " {$coin}\n";
    echo " 💎 Total Paid  : " . number_format((float)$total_paid, 8) . " {$coin}\n";
    
    // Tambahkan informasi next payout
    // zPool melakukan pembayaran setiap 4 jam: 00:00, 04:00, 08:00, 12:00, 16:00, 20:00
    $current_hour = (int)date('H');
    $next_payout = $current_hour + (4 - ($current_hour % 4));
    if ($next_payout == $current_hour) $next_payout += 4;
    if ($next_payout >= 24) $next_payout = 0;
    
    $next_payout_time = date('Y-m-d ') . sprintf('%02d', $next_payout) . ':00:00';
    $time_until = strtotime($next_payout_time) - time();
    $hours = floor($time_until / 3600);
    $minutes = floor(($time_until % 3600) / 60);
    
    echo " ⏰ Next Payout : " . sprintf('%02d', $next_payout) . ":00 (" . $hours . "h " . $minutes . "m)\n";
    
    // Hitung total hashrate dari data miners
    $total_hashrate = 0;
    if (isset($GLOBALS['miners']) && !empty($GLOBALS['miners'])) {
        foreach ($GLOBALS['miners'] as $miner) {
            $total_hashrate += floatval($miner['hashrate']);
        }
    }
    
    // Gunakan total hashrate yang dihitung
    echo " ⚡ Hashrate    : " . number_format($total_hashrate, 1) . " H/s\n";
    echo " 👥 Workers     : " . count($GLOBALS['miners']) . "\n";
    
    // Tampilkan waktu dalam berbagai timezone
    $local_time = new DateTime('now');
    $utc_time = new DateTime('now', new DateTimeZone('UTC'));
    $wib_time = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    
    echo "──────────────────────────────────────\n";
    echo " 🕒 Time Info:\n";
    echo "    Local : " . $local_time->format('Y-m-d H:i:s') . " " . date_default_timezone_get() . "\n";
    echo "    UTC   : " . $utc_time->format('Y-m-d H:i:s') . " UTC\n";
    echo "──────────────────────────────────────\n";
    
    // Debug mode (hapus // di bawah untuk mengaktifkan debug)
    // echo "\n\033[1;31mDebug Info:\033[0m\n";
    // echo "Raw API Response:\n";
    // print_r($data);
}

function displayMinerInfo($miners, $coin) {
    if (!$miners || empty($miners)) {
        echo "📊 \033[1;33mMiner Summary: Tidak ada miner aktif\033[0m\n";
        return;
    }
    
    echo "\033[1;36m╔════════════════════════════════════╗\033[0m\n";
    echo "\033[1;36m║          MINER SUMMARY              ║\033[0m\n";
    echo "\033[1;36m╚════════════════════════════════════╝\033[0m\n";
    
    $total_hashrate = 0;
    $miners_count = count($miners);
    
    // Tampilkan miner dalam 2 kolom
    for ($i = 0; $i < $miners_count; $i += 2) {
        // Kolom pertama
        echo "\033[1;33m ⚒ Worker #" . ($i + 1) . "\033[0m";
        // Padding antara kolom
        echo str_repeat(" ", 12);
        
        // Kolom kedua (jika ada)
        if ($i + 1 < $miners_count) {
            echo "\033[1;33m ⚒ Worker #" . ($i + 2) . "\033[0m";
        }
        echo "\n";
        
        // ID
        $id1 = str_replace("c={$coin},id=", '', $miners[$i]['password']);
        $id1_shortened = substr($id1, 0, 10); // Menampilkan hanya 5 karakter pertama
        echo " ├─ ID   : " . str_pad($id1_shortened, 13);

        if ($i + 1 < $miners_count) {
            $id2 = str_replace("c={$coin},id=", '', $miners[$i + 1]['password']);
            $id2_shortened = substr($id2, 0, 10); // Menampilkan hanya 5 karakter pertama
            echo " ├─ ID   : " . $id2_shortened;
            }
            echo "\n";
        
        // Algo
        echo " ├─ Algo : " . str_pad($miners[$i]['algo'], 13);
        if ($i + 1 < $miners_count) {
            echo " ├─ Algo : " . $miners[$i + 1]['algo'];
        }
        echo "\n";
        
        // Difficulty
        echo " ├─ Diff : " . str_pad($miners[$i]['difficulty'], 13);
        if ($i + 1 < $miners_count) {
            echo " ├─ Diff : " . $miners[$i + 1]['difficulty'];
        }
        echo "\n";
        
        // Hashrate
        echo " └─ HR   : " . str_pad(number_format($miners[$i]['hashrate'], 1) . " H/s", 13);
        if ($i + 1 < $miners_count) {
            echo " └─ HR   : " . number_format($miners[$i + 1]['hashrate'], 1) . " H/s";
        }
        echo "\n\033[1;36m ══════════════════════════════════════\033[0m\n";
                                              
        $total_hashrate += $miners[$i]['hashrate'];
        if ($i + 1 < $miners_count) {
            $total_hashrate += $miners[$i + 1]['hashrate'];
        }
    }
}

function checkPayment($old_total, $new_total) {
    if ($new_total > $old_total) {
        $payment = $new_total - $old_total;
        echo "\007"; // Beep sound
    }
}

function calculateEstimate($hashrate) {
    // Estimasi berdasarkan data aktual pool
    $pool_rate = 0.00000731; // Rate dari debug output
    return ($hashrate * $pool_rate * 24); // Estimasi per 24 jam
}

function saveLog($data) {
    $log = date('Y-m-d H:i:s') . " | Balance: " . $data['balance'] . "\n";
    file_put_contents('zpool_log.txt', $log, FILE_APPEND);
}

function checkMinerHealth($miners) {
    static $last_check = [];
    
    foreach ($miners as $miner) {
        $id = str_replace('c=DGB,id=', '', $miner['password']);
        $current_hashrate = $miner['hashrate'];
        
        if (isset($last_check[$id]) && $current_hashrate < ($last_check[$id] * 0.5)) {
            // Hashrate turun lebih dari 50%
            echo "\033[1;31m⚠ WARNING: Miner #$id hashrate dropped significantly!\033[0m\n";
            // Bisa ditambahkan notifikasi (email/telegram/discord)
        }
        
        $last_check[$id] = $current_hashrate;
    }
}

function loadConfigs() {
    $configs = [];
    foreach (glob("config_*.txt") as $file) {
        $content = json_decode(file_get_contents($file), true);
        if ($content) {
            $configs[] = $content;
        }
    }
    return $configs;
}

function saveConfig($coin, $wallet) {
    $config = [
        'coin' => $coin,
        'wallet' => $wallet,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $filename = "config_" . strtolower($coin) . ".txt";
    file_put_contents($filename, json_encode($config, JSON_PRETTY_PRINT));
}

function setupNewConfig() {
    clearScreen();
    echo "\033[1;36m╔════════════════════════════════════╗\033[0m\n";
    echo "\033[1;36m║        ZPOOL MINING SETUP          ║\033[0m\n";
    echo "\033[1;36m╚════════════════════════════════════╝\033[0m\n\n";
    
    // Daftar coin yang didukung (diurutkan)
    $supported_coins = [
        'BTC' => 'Bitcoin',
        'LTC' => 'Litecoin',
        'DOGE' => 'Dogecoin',
        'DGB' => 'DigiByte'
    ];
    
    echo "Pilih coin yang akan di-mining:\n\n";
    $index = 1;
    $coin_map = [];
    foreach ($supported_coins as $symbol => $name) {
        echo "[$index] $symbol\n";
        $coin_map[$index] = $symbol;
        $index++;
    }
    
    // Reset mode blocking untuk input
    stream_set_blocking(STDIN, 1);
    
    do {
        echo "\nTekan angka (1-" . count($supported_coins) . "): ";
        $choice = trim(fgets(STDIN));
    } while (!is_numeric($choice) || !isset($coin_map[(int)$choice]));
    
    $coin = $coin_map[(int)$choice];
    
    // Pastikan input wallet bisa diterima
    echo "\nMasukkan alamat wallet {$supported_coins[$coin]}: ";
    $wallet = trim(fgets(STDIN));
    
    // Validasi input wallet tidak kosong
    while (empty($wallet)) {
        echo "Alamat wallet tidak boleh kosong!\n";
        echo "Masukkan alamat wallet {$supported_coins[$coin]}: ";
        $wallet = trim(fgets(STDIN));
    }
    
    saveConfig($coin, $wallet);
    echo "\nKonfigurasi telah disimpan!\n";
    sleep(2);
    return ['coin' => $coin, 'wallet' => $wallet];
}

function selectConfig() {
    $configs = loadConfigs();
    
    if (empty($configs)) {
        echo "Tidak ada konfigurasi tersimpan. Membuat konfigurasi baru...\n";
        sleep(2);
        return setupNewConfig();
    }
    
    clearScreen();
    echo "\033[1;36m╔════════════════════════════════════╗\033[0m\n";
    echo "\033[1;36m║        PILIH KONFIGURASI          ║\033[0m\n";
    echo "\033[1;36m╚════════════════════════════════════╝\033[0m\n\n";
    
    echo "Konfigurasi tersimpan:\n\n";
    foreach ($configs as $i => $config) {
        echo "[" . ($i + 1) . "] {$config['coin']} - {$config['wallet']}\n";
    }
    echo "[N] Buat konfigurasi baru\n";
    
    do {
        echo "\nPilih nomor konfigurasi atau 'N' untuk baru: ";
        $choice = strtoupper(trim(fgets(STDIN)));
        
        if ($choice === 'N') {
            return setupNewConfig();
        }
        
        $index = intval($choice) - 1;
    } while ($index < 0 || $index >= count($configs));
    
    return $configs[$index];
}

// Tambahkan fungsi ini
function setUserTimezone() {
    // Dapatkan IP pengguna
    $ip = file_get_contents('https://api.ipify.org');
    
    // Dapatkan timezone dari IP menggunakan ipapi.co
    $details = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
    
    if ($details && isset($details['timezone'])) {
        date_default_timezone_set($details['timezone']);
        return true;
    }
    
    // Fallback ke timezone default jika gagal
    date_default_timezone_set('Asia/Jakarta');
    return false;
}

// Ganti bagian timezone setting di awal script dengan:
setUserTimezone();

// Debug timezone (opsional)
echo "Current timezone: " . date_default_timezone_get() . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

// Di awal script, sebelum loop monitoring
$config = selectConfig();
$wallet_address = $config['wallet'];
$coin = $config['coin'];

// Update judul window sesuai coin yang dipilih
echo "\033]0;ZPOOL {$coin} WALLET MONITOR\007";

// Inisialisasi variabel untuk tracking pembayaran
$last_total_paid = 0;

// Loop monitoring
while (true) {
    $data = fetchWalletData($wallet_address);
    $miners = fetchMinerData($wallet_address);
    
    displayWalletInfo($data, $wallet_address, $coin);
    displayMinerInfo($miners, $coin);
    checkMinerHealth($miners);
    
    if (isset($data['paidtotal'])) {
        checkPayment($last_total_paid, $data['paidtotal']);
        $last_total_paid = $data['paidtotal'];
    }
    
    saveLog($data);
    
    // Tampilkan instruksi dan timer
    echo "\nTekan 'x' lalu ENTER untuk reset halaman\n";
    
    // Set mode non-blocking untuk input
    stream_set_blocking(STDIN, 0);
    $start = time();
    $last_input = '';
    
    while (time() - $start < 20) {
        $current = time() - $start;
        $remaining = 20 - $current;
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        
        // Tampilkan ulang semua data
        clearScreen();
        displayWalletInfo($data, $wallet_address, $coin);
        displayMinerInfo($miners, $coin);
        
        // Tambahkan header untuk bagian timer
        echo "\033[1;36m╔════════════════════════════════════╗\033[0m\n";
        echo "\033[1;36m║             AUTO UPDATE             ║\033[0m\n";
        echo "\033[1;36m╚════════════════════════════════════╝\033[0m\n";
        
        // Tampilkan timer
        echo "🔄 Mereset halaman, tekan x dan enter\n";
        echo "⏱️ Update dalam: {$minutes}:" . sprintf("%02d", $seconds) . "\n";
        
        // Cek input dengan timeout
        stream_set_blocking(STDIN, 0);  // Non-blocking input
        $input = trim(fgets(STDIN));
        if ($input === 'x') {
            echo "\nMereset halaman...\n";
            sleep(1);
            stream_set_blocking(STDIN, 1);  // Reset ke blocking mode
            break;
        }
        
        sleep(1);
    }
    
    // Reset mode blocking
    stream_set_blocking(STDIN, 1);
}

?>
