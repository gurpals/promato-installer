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
        if (is_dir("packages/" . basename($package))) {
            echo "❌ Package already installed\n";
            exit(1);
        }

        $client = new Client();

        // 🌐 Detect domain automatically
        $domain = gethostname();

        echo "🔍 Validating license...\n";

        $res = $client->post($apiUrl, [
            'json' => [
                'token' => $token,
                'package' => $package,
                'domain' => $domain
            ]
        ]);

        $data = json_decode($res->getBody(), true);

        if (!$data['success']) {
            echo "❌ " . ($data['message'] ?? 'Installation failed') . "\n";
            exit(1);
        }

        $repo = $data['repo'];
        // $version = $data['version'];
        $folder = "Modules/" . basename($package);

        
        echo "📦 Installing package...\n";
        system("git clone --branch $package --single-branch $repo $folder");
        // // 🚀 Clone
        // system("git clone --depth=1 $repo $folder");

        // // 🔄 Fetch tags
        // system("cd $folder && git fetch --tags");

        // // 🎯 Checkout version
        // system("cd $folder && git checkout $version");

        // 📦 Install dependencies
        system("cd $folder && composer install");

        echo "✅ Package installed successfully\n";
    }
}