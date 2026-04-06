# promato-installer 🚀  
### Laravel Package Installer with Authentication & Modular Service Support

`promato-installer` is a powerful Laravel-based installer package that enables secure, token-based installation of modular packages such as messaging systems, communication services, utilities, and custom enterprise modules.

Designed for scalability and automation, this package simplifies dependency installation, authentication, and module configuration in one command.

---

## 🔑 Keywords (SEO)
Laravel installer package, composer installer, modular Laravel packages, WhatsApp integration Laravel, package installer with authentication, Laravel module manager, automated package installer

---

## ✨ Features

- 🔐 Token-based secure authentication  
- 📦 Install multiple types of packages (Messaging, Utilities, Integrations)  
- ⚙️ One-command automated installer  
- 🧩 Modular architecture using `nwidart/laravel-modules`  
- 🔄 Version-controlled installation & updates  
- 🚀 Production-ready and scalable  

---

## 📦 Supported Package Types

- Messaging services (WhatsApp, SMS, Email, etc.)  
- Communication pipelines  
- Third-party integrations  
- Internal utility modules  
- Custom enterprise packages  

---

## 🛠️ Installation Guide

### Step 1: Install via Composer

```bash
composer require gurpals/promato-installer
```

### Install Specific Version

```bash
composer require gurpals/promato-installer:^version
```

---

## 🔄 Update Package

```bash
composer update gurpals/promato-installer
```

Or specific version:

```bash
composer update gurpals/promato-installer:^1.0.7
```

---

## ⚙️ First-Time Setup

### Prerequisite: Install Laravel Modules

```bash
composer require nwidart/laravel-modules
```

---

## ▶️ Usage

Run the installer with authentication:

```bash
composer exec promato <TOKEN> <PACKAGE_NAME>
```

### Parameters

- `<TOKEN>` → Secure authentication token  
- `<PACKAGE_NAME>` → Target package/module name  

---

## ⚡ How It Works

1. Validates authentication token  
2. Fetches requested package  
3. Installs and registers module  
4. Performs auto configuration  
5. Makes module production-ready  

---

## 📁 Project Structure

```
promato-installer/
├── bin/
│   └── promato
├── src/
│   └── InstallCommand.php
├── composer.json
└── README.md
```

---

## 📌 Example

```bash
composer exec promato your_token whatsapp-module
```

---

## 🔐 Authentication Flow

- Token-based validation  
- Secure access to installer services  
- Safe package deployment  

---

## 🤝 Contributing

1. Fork repository  
2. Create feature branch  
3. Commit changes  
4. Submit pull request  

---

## 📄 License

MIT License

---

## ⭐ Why Use promato-installer?

- Reduces manual setup effort  
- Ensures secure package installation  
- Works seamlessly with Laravel ecosystem  
- Ideal for SaaS, enterprise, and scalable apps  

---

## 📬 Support

For issues, bugs, or feature requests, open an issue on GitHub.