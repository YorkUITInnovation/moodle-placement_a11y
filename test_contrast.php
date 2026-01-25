<?php
// Test color contrast calculation
function color_to_rgb($color) {
    $color = trim($color);
    if (preg_match("/^#([0-9a-f]{3}|[0-9a-f]{6})$/i", $color, $matches)) {
        $hex = $matches[1];
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            "r" => hexdec(substr($hex, 0, 2)),
            "g" => hexdec(substr($hex, 2, 2)),
            "b" => hexdec(substr($hex, 4, 2)),
        ];
    }
    return null;
}

function get_relative_luminance($rgb) {
    $r = $rgb["r"] / 255;
    $g = $rgb["g"] / 255;
    $b = $rgb["b"] / 255;

    $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function calculate_contrast_ratio($color1, $color2) {
    $rgb1 = color_to_rgb($color1);
    $rgb2 = color_to_rgb($color2);

    if ($rgb1 === null || $rgb2 === null) {
        return 21;
    }

    $l1 = get_relative_luminance($rgb1);
    $l2 = get_relative_luminance($rgb2);

    if ($l2 > $l1) {
        list($l1, $l2) = [$l2, $l1];
    }

    return ($l1 + 0.05) / ($l2 + 0.05);
}

$color = "#FFB6C1";
$bg = "#FFFFFF";
$ratio = calculate_contrast_ratio($color, $bg);

echo "Testing: $color on $bg\n";
echo "Contrast ratio: " . round($ratio, 2) . ":1\n";
echo "WCAG AA requirement: 4.5:1\n";
echo "Result: " . ($ratio < 4.5 ? "FAIL ❌" : "PASS ✓") . "\n";
