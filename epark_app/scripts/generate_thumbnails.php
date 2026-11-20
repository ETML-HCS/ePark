<?php

// Génère des miniatures et WebP pour les images dans docs/screenshots
$srcDir = __DIR__.'/../docs/screenshots';
$dstDir = $srcDir.'/thumbs';
if (! is_dir($dstDir)) {
    mkdir($dstDir, 0755, true);
}
$files = glob($srcDir.'/*.{png,jpg,jpeg,svg}', GLOB_BRACE);
foreach ($files as $file) {
    $info = pathinfo($file);
    $ext = strtolower($info['extension']);
    $base = $info['filename'];
    $dstPng = "$dstDir/{$base}-thumb.png";
    $dstWebp = "$dstDir/{$base}-thumb.webp";
    if ($ext === 'svg') {
        // rasterize SVG using `rsvg-convert` if available, fallback copy
        $tmp = sys_get_temp_dir().'/'.$base.'.png';
        $cmd = 'rsvg-convert -w 800 -h 600 -o '.escapeshellarg($tmp).' '.escapeshellarg($file);
        exec($cmd, $o, $r);
        if ($r === 0 && file_exists($tmp)) {
            $src = $tmp;
        } else {
            // fallback: create a blank PNG
            $im = imagecreatetruecolor(800, 600);
            $bg = imagecolorallocate($im, 255, 255, 255);
            imagefilledrectangle($im, 0, 0, 800, 600, $bg);
            imagepng($im, $dstPng);
            imagedestroy($im);

            continue;
        }
    } else {
        $src = $file;
    }

    // create thumbnail width 800 keeping aspect
    [$w, $h, $type] = getimagesize($src);
    $newW = 800;
    $newH = intval($h * ($newW / $w));
    $dstIm = imagecreatetruecolor($newW, $newH);
    // preserve transparency
    imagealphablending($dstIm, false);
    imagesavealpha($dstIm, true);
    $srcIm = null;
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
        $srcIm = imagecreatefrompng($src);
    } elseif ($type === IMAGETYPE_JPEG) {
        $srcIm = imagecreatefromjpeg($src);
    } else {
        // unsupported, skip
        continue;
    }
    imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagepng($dstIm, $dstPng, 6);
    if (function_exists('imagewebp')) {
        imagewebp($dstIm, $dstWebp, 80);
    }
    imagedestroy($dstIm);
    imagedestroy($srcIm);
    if (isset($tmp) && file_exists($tmp)) {
        unlink($tmp);
    }
}
echo "Thumbnails generated in $dstDir\n";
