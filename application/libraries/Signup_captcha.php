<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Offline, session-bound signup challenge. No answer is sent in HTML. */
class Signup_captcha
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    public function create()
    {
        $this->ci->session->unset_userdata('signup_captcha');
        if (!function_exists('imagecreatetruecolor')) {
            log_message('error', 'Signup CAPTCHA requires the PHP GD extension.');
            return false;
        }
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $answer = '';
        for ($i = 0; $i < 6; $i++) {
            $answer .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $image = imagecreatetruecolor(260, 86);
        imagefill($image, 0, 0, imagecolorallocate($image, 248, 245, 240));
        for ($i = 0; $i < 180; $i++) {
            imagesetpixel($image, random_int(0, 259), random_int(0, 85), imagecolorallocate($image, 150, 155, 165));
        }
        for ($i = 0; $i < 7; $i++) {
            imageline($image, random_int(0, 259), random_int(0, 85), random_int(0, 259), random_int(0, 85), imagecolorallocate($image, 185, 180, 190));
        }
        $font = FCPATH . 'assets/fonts/DM_Sans/static/DMSans_18pt-Bold.ttf';
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($image, random_int(30, 85), random_int(25, 70), random_int(40, 90));
            if (function_exists('imagettftext') && is_file($font)) {
                imagettftext($image, 28, random_int(-18, 18), 15 + $i * 39, random_int(51, 65), $color, $font, $answer[$i]);
            } else {
                imagestring($image, 5, 25 + $i * 39, random_int(28, 48), $answer[$i], $color);
            }
        }
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        $this->ci->session->set_userdata('signup_captcha', array(
            'hash' => hash('sha256', $answer),
            'expires' => time() + 300,
        ));
        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function verify($answer)
    {
        $challenge = $this->ci->session->userdata('signup_captcha');
        // Consume even incorrect or rate-limited submissions to prevent replay.
        $this->ci->session->unset_userdata('signup_captcha');
        $allowed = $this->allow_attempt();
        if ($allowed === null) {
            return 'Signup verification is temporarily unavailable. Please contact the system administrator.';
        }
        if (!$allowed) {
            return 'Too many signup attempts. Please wait 15 minutes before trying again.';
        }
        $answer = is_string($answer) ? strtoupper(trim($answer)) : '';
        if (!is_array($challenge) || empty($challenge['expires']) || $challenge['expires'] <= time()
            || !preg_match('/^[A-Z2-9]{6}$/', $answer)
            || !hash_equals($challenge['hash'], hash('sha256', $answer))) {
            return 'The security code is incorrect or expired. Enter the code from the new image below.';
        }
        return true;
    }

    private function allow_attempt()
    {
        // IP-based limits survive session resets; lock the read/write together.
        $temporary_root = sys_get_temp_dir();
        // macOS XAMPP can inherit the desktop user's TMPDIR while running as daemon.
        if (!is_writable($temporary_root) && DIRECTORY_SEPARATOR === '/' && is_writable('/tmp')) {
            $temporary_root = '/tmp';
        }
        // Keep CLI and web-server processes from creating directories for each other.
        $owner = function_exists('posix_geteuid') ? '-' . posix_geteuid() : '';
        $directory = rtrim($temporary_root, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'aplead-signup-' . substr(hash('sha256', APPPATH), 0, 16) . $owner;
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            log_message('error', 'Unable to create signup rate limit storage.');
            return null;
        }
        $file = $directory . '/signup-rate-' . hash('sha256', $this->ci->input->ip_address()) . '.json';
        $handle = @fopen($file, 'c+');
        if (!$handle) {
            log_message('error', 'Unable to open signup rate limit storage.');
            return null;
        }
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return null;
        }
        $state = json_decode(stream_get_contents($handle), true);
        if (!is_array($state) || !isset($state['expires'], $state['attempts']) || $state['expires'] <= time()) {
            $state = array('expires' => time() + 900, 'attempts' => 0);
        }
        // Require both development mode and a direct loopback connection.
        $local_development = defined('ENVIRONMENT') && ENVIRONMENT === 'development'
            && in_array(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '', array('127.0.0.1', '::1'), true);
        $limit = $local_development ? 100 : 10;
        $allowed = $state['attempts'] < $limit;
        if ($allowed) {
            $state['attempts']++;
            rewind($handle);
            $saved = ftruncate($handle, 0) && fwrite($handle, json_encode($state)) !== false && fflush($handle);
            $allowed = $saved ? true : null;
            if (!$saved) {
                log_message('error', 'Unable to save signup rate limit counter.');
            }
        }
        flock($handle, LOCK_UN);
        fclose($handle);
        return $allowed;
    }
}
