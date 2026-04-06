<?php
namespace Installer;

use GuzzleHttp\Client;

class InstallCommand
{
    public function handle($token, $package)
    {
        // $apiUrl='https://oms.promato.co/api/package/info';
        $apiUrl='http://192.168.1.47:8000/api/package/info';
        // ✅ Check git
        if (!shell_exec('git --version')) {
            echo "❌ Git not installed\n";
            exit(1);
        }

        // ✅ Prevent overwrite
        if (is_dir("Modules/" . $package)) {
            echo "❌ Package already installed\n";
            exit(1);
        }
        $folder = "Modules/" . $package;
        $client = new Client();

        // 🌐 Detect domain automatically
        $domain = gethostname();

        echo "🔍 Validating license...\n";
         // 🔥 Generate RAW payload (NO HASH)
        $payload = $this->generateFingerprint();

        // 🔐 Create signature (IMPORTANT)
        $signature = hash_hmac('sha256', json_encode($payload), $token);
        $clientIp = $this->getClientIp();
        try {
            $res = $client->post($apiUrl, [
                'json' => [
                    'token'     => $token,
                    'package'   => $package,
                    'domain'    => $domain,
                    'ip'        => $clientIp,
                    'payload'   => $payload,
                    'signature' => $signature,
                ],
                'stream'  => true,
                'timeout' => 300
            ]);
        } catch (\Exception $e) {
            echo "❌ Connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }

        if ($res->getStatusCode() !== 200) {
            echo "❌ Server error: " . $res->getStatusCode() . "\n";
            exit(1);
        }

        $contentType = $res->getHeaderLine('Content-Type');

        echo "📦 Installing package...\n";

        // ❌ JSON error
        if (strpos($contentType, 'json') !== false) {
            $data = json_decode($res->getBody(), true);
            echo "❌ " . ($data['message'] ?? 'Error') . "\n";
            exit(1);
        }

        // ✅ ZIP case
        $zipFile = "package_" . uniqid() . ".zip";

        try {

            echo "⬇️ Downloading package...\n";

            $body = $res->getBody();
            $fp = fopen($zipFile, 'w');

            if (!$fp) {
                throw new \Exception("Cannot write ZIP file");
            }

            while (!$body->eof()) {
                fwrite($fp, $body->read(1024 * 8));
            }

            fclose($fp);

            // Ensure Modules dir exists
            if (!is_dir("Modules")) {
                mkdir("Modules", 0755, true);
            }

            $zip = new \ZipArchive;

            if ($zip->open($zipFile) !== TRUE) {
                throw new \Exception("Invalid ZIP archive");
            }

            if ($zip->numFiles === 0) {
                throw new \Exception("Empty package");
            }

            mkdir($folder, 0755, true);

            if (!$zip->extractTo($folder)) {
                throw new \Exception("Extraction failed");
            }

            $zip->close();

            echo "✅ Package extracted\n";

        } catch (\Exception $e) {

            // 🧹 Cleanup partially created folder
            if (is_dir($folder)) {
                system("rm -rf " . escapeshellarg($folder));
            }

            echo "❌ " . $e->getMessage() . "\n";
            exit(1);

        } finally {

            // 🔥 ALWAYS delete ZIP
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
        }

        // ===============================
        // ⚙️ POST-INSTALL
        // ===============================

        $folderSafe = escapeshellarg($folder);
        $packageSafe = escapeshellarg($package);

        system("cd $folderSafe && composer install");
        echo "✅ Dependencies installed\n";

        system("php artisan module:enable $packageSafe");
        echo "✅ Module registered\n";

        system("composer dump-autoload");

        echo "⚙️ Running migrations...\n";
        system("php artisan migrate --path=$folder/Database/migrations --force");

        $seederClass = "Modules\\$package\\Database\\Seeders\\DatabaseSeeder";

        if (file_exists("$folder/database/seeders/DatabaseSeeder.php")) {
            echo "🌱 Running seeders...\n";
            system("php artisan db:seed --class=\"$seederClass\" --force");
        }

        echo "🎉 Setup completed successfully\n";

    }
    // 🔥 RAW fingerprint (NO HASH)
    private function generateFingerprint()
    {
        $isDocker = $this->isDocker();

        $data = [
            'app_key'  => $this->getEnv('APP_KEY'),
            'composer' => $this->getComposerName(),
        ];

        // Only include these if NOT docker
        if (!$isDocker) {
            $data['hostname'] = gethostname();
            $data['mac'] = $this->getMacAddress();
            $data['path'] = realpath(getcwd());
        }

        return $data;
    }
    private function getEnv($key)
    {
        $envPath = getcwd() . '/.env';

        if (!file_exists($envPath)) {
            return null;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, $key . '=') === 0) {
                return trim(explode('=', $line, 2)[1]);
            }
        }

        return null;
    }

    private function getComposerName()
    {
        $composerPath = getcwd() . '/composer.json';

        if (!file_exists($composerPath)) {
            return null;
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        return $composer['name'] ?? null;
    }

    // 🔐 Robust MAC address detection
    private function getMacAddress()
    {
        if (!function_exists('shell_exec')) {
            return 'mac_unavailable';
        }

        $mac = null;

        // Windows
        if (stripos(PHP_OS, 'WIN') === 0) {
            $output = shell_exec('getmac');
            preg_match('/([0-9A-F]{2}[-:]){5}[0-9A-F]{2}/i', $output, $matches);
            $mac = $matches[0] ?? null;
        } else {
            // Linux / Unix
            $output = shell_exec('ip link 2>/dev/null');

            if ($output) {
                preg_match_all('/link\/ether\s([0-9a-f:]{17})/i', $output, $matches);
                $mac = $matches[1][0] ?? null;
            }

            if (!$mac) {
                $mac = shell_exec('cat /sys/class/net/eth0/address 2>/dev/null');
            }
        }

        $mac = trim($mac);
        return $mac ? strtolower(str_replace('-', ':', $mac)) : '00:00:00:00:00:00';
    }
    private function isDocker()
    {
        return file_exists('/.dockerenv') ||
            strpos(file_get_contents('/proc/1/cgroup') ?? '', 'docker') !== false;
    }
    private function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
 
}