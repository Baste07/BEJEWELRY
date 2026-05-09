<?php
require_once __DIR__ . '/inc.php';

// Generate a CAPTCHA image based on session text.
// If missing, create a new challenge.
if (!isset($_SESSION['captcha_text']) || !is_string($_SESSION['captcha_text']) || strlen($_SESSION['captcha_text']) < 4) {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $txt = '';
    for ($i = 0; $i < 5; $i++) {
        $txt .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $_SESSION['captcha_text'] = $txt;
    $_SESSION['captcha_ts'] = time();
}

$text = (string)$_SESSION['captcha_text'];

$w = 140; $h = 44;
// If GD is unavailable, fall back to SVG (works everywhere).
if (!function_exists('imagecreatetruecolor')) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $t1 = substr($safe, 0, 1);
    $t2 = substr($safe, 1, 1);
    $t3 = substr($safe, 2, 1);
    $t4 = substr($safe, 3, 1);
    $t5 = substr($safe, 4, 1);
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'">';
    echo '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#FEF1F3"/><stop offset="1" stop-color="#FAE3E8"/></linearGradient></defs>';
    echo '<rect x="0" y="0" width="'.$w.'" height="'.$h.'" rx="10" fill="url(#g)" stroke="#ECDCE0"/>';
    // noise
    for ($i = 0; $i < 6; $i++) {
        $x1 = random_int(0, $w); $y1 = random_int(0, $h);
        $x2 = random_int(0, $w); $y2 = random_int(0, $h);
        echo '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="#ECDCE0" stroke-width="1" opacity=".9"/>';
    }
    // text (no external font dependency)
    $chars = [$t1,$t2,$t3,$t4,$t5];
    $x = 16;
    foreach ($chars as $idx => $ch) {
        if ($ch === '') continue;
        $rot = random_int(-12, 12);
        $y = 30 + random_int(-2, 2);
        $fill = ($idx % 2 === 0) ? '#241418' : '#D96070';
        echo '<text x="'.$x.'" y="'.$y.'" font-family="Georgia, serif" font-size="22" font-weight="700" fill="'.$fill.'" transform="rotate('.$rot.' '.$x.' '.$y.')">'.$ch.'</text>';
        $x += 22;
    }
    echo '</svg>';
    exit;
}

$im = imagecreatetruecolor($w, $h);

// Colors
$bg1 = imagecolorallocate($im, 254, 241, 243); // blush
$bg2 = imagecolorallocate($im, 250, 227, 232);
$ink = imagecolorallocate($im, 36, 20, 24);   // dark
$rose = imagecolorallocate($im, 217, 96, 112);
$noise = imagecolorallocate($im, 236, 220, 224);

// Background gradient-ish
imagefilledrectangle($im, 0, 0, $w, $h, $bg1);
for ($x = 0; $x < $w; $x += 2) {
    $c = ($x % 8 === 0) ? $bg2 : $bg1;
    imageline($im, $x, 0, $x, $h, $c);
}

// Noise lines
for ($i = 0; $i < 7; $i++) {
    imageline($im, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $noise);
}
for ($i = 0; $i < 80; $i++) {
    imagesetpixel($im, random_int(0, $w - 1), random_int(0, $h - 1), $noise);
}

// Draw characters (built-in font)
$spacing = (int)floor(($w - 20) / max(1, strlen($text)));
$x = 10;
for ($i = 0; $i < strlen($text); $i++) {
    $y = random_int(10, 18);
    $col = ($i % 2 === 0) ? $ink : $rose;
    imagestring($im, 5, $x, $y, $text[$i], $col);
    $x += $spacing;
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
imagepng($im);
imagedestroy($im);
exit;

