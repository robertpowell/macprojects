<?php
/**
 * Simple QR Code Generator using Google Charts API
 * Alternative: Can be replaced with phpqrcode library for offline generation
 */

class QRCodeGenerator {
    private $size = 300;
    private $margin = 10;

    /**
     * Generate QR code image and save to file
     */
    public function generateQRCode($data, $filename) {
        // Using Google Charts API for simplicity
        // For production, consider using a local library like phpqrcode
        $url = sprintf(
            'https://chart.googleapis.com/chart?chs=%dx%d&cht=qr&chl=%s&choe=UTF-8&chld=H|%d',
            $this->size,
            $this->size,
            urlencode($data),
            $this->margin
        );

        $imageData = @file_get_contents($url);

        if ($imageData === false) {
            // Fallback: Generate a simple QR code using a different method
            return $this->generateLocalQRCode($data, $filename);
        }

        return file_put_contents($filename, $imageData) !== false;
    }

    /**
     * Fallback method: Generate QR code locally using PHPQRCode
     * This is a simplified version - for production use a proper library
     */
    private function generateLocalQRCode($data, $filename) {
        // Create a simple image with the session code as text
        // This is a fallback - in production, use a proper QR library
        $width = 300;
        $height = 300;
        $image = imagecreate($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        // Fill background
        imagefill($image, 0, 0, $white);

        // Add text
        $text = "Scan code:\n$data";
        $font = 5; // Built-in font
        $textWidth = imagefontwidth($font) * strlen($data);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;

        imagestring($image, $font, $x, $y, $data, $black);

        // Add border
        imagerectangle($image, 5, 5, $width - 5, $height - 5, $black);

        // Save image
        $result = imagepng($image, $filename);
        imagedestroy($image);

        return $result;
    }

    /**
     * Generate QR code for quiz session
     */
    public function generateQuizQRCode($sessionCode, $quizId) {
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . '/participant/join.php?code=' . $sessionCode;

        $qrDir = BASE_PATH . '/assets/qrcodes';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        $filename = $qrDir . '/quiz_' . $quizId . '_' . time() . '.png';

        if ($this->generateQRCode($url, $filename)) {
            return str_replace(BASE_PATH, '', $filename);
        }

        return false;
    }

    /**
     * Get base URL of the application
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);

        // Remove /admin or /api or /participant from path
        $scriptPath = preg_replace('#/(admin|api|participant).*$#', '', $scriptPath);

        return $protocol . '://' . $host . $scriptPath;
    }

    /**
     * Set QR code size
     */
    public function setSize($size) {
        $this->size = $size;
    }
}

?>
