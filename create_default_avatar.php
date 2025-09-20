<?php
// Create a simple default avatar image
$width = 150;
$height = 150;

// Create image
$image = imagecreatetruecolor($width, $height);

// Define colors
$bg_color = imagecolorallocate($image, 108, 117, 125); // Bootstrap secondary color
$text_color = imagecolorallocate($image, 255, 255, 255); // White text

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add text
$text = "USER";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Create directory if it doesn't exist
if (!is_dir('assets/images')) {
    mkdir('assets/images', 0755, true);
}

// Save image
imagepng($image, 'assets/images/default-avatar.png');

// Clean up
imagedestroy($image);

echo "Default avatar created successfully!\n";
?>
