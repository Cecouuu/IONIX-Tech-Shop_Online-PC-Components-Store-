<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    static $schemaEnsured = false;

    if ($pdo instanceof PDO) {
        if (!$schemaEnsured) {
            ensure_schema($pdo);
            $schemaEnsured = true;
        }
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'project_app';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    try {
        $pdo = connect_with_fallback($host, $port, $name, $user, $pass);
    } catch (PDOException $e) {
        render_db_unavailable();
        exit;
    }

    if (!$schemaEnsured) {
        ensure_schema($pdo);
        $schemaEnsured = true;
    }

    return $pdo;
}

function connect_with_fallback(string $host, string $port, string $name, string $user, string $pass): PDO
{
    try {
        return connect_pdo($host, $port, $name, $user, $pass);
    } catch (PDOException $e) {
        $errorCode = (int)($e->errorInfo[1] ?? 0);
        if ($errorCode === 2002 && $host === '127.0.0.1') {
            try {
                return connect_pdo('localhost', $port, $name, $user, $pass);
            } catch (PDOException $fallbackError) {
                throw $fallbackError;
            }
        }
        if ($errorCode !== 1049) {
            throw $e;
        }
        $serverPdo = connect_pdo($host, $port, null, $user, $pass);
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return connect_pdo($host, $port, $name, $user, $pass);
    }
}

function connect_pdo(string $host, string $port, ?string $dbName, string $user, string $pass): PDO
{
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    if ($dbName) {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    }

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function ensure_schema(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = (bool)$stmt->fetchColumn();

    if (!$usersTableExists) {
        run_sql_file($pdo, __DIR__ . '/../database/schema.sql');
    }

    $columnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $hasAdminColumn = (bool)$columnStmt->fetchColumn();
    if (!$hasAdminColumn) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash');
    }
    if (!column_exists($pdo, 'users', 'is_owner')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_owner TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin');
    }

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS user_logins (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id INT UNSIGNED NOT NULL,
          logged_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          ip_address VARCHAR(45) NULL,
          user_agent VARCHAR(255) NULL,
          CONSTRAINT fk_user_logins_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS products (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(150) NOT NULL,
          category VARCHAR(80) NOT NULL DEFAULT \'Components\',
          description TEXT NOT NULL,
          image_url VARCHAR(600) NOT NULL DEFAULT \'\',
          price DECIMAL(10,2) NOT NULL,
          stock INT UNSIGNED NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS orders (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id INT UNSIGNED NULL,
          guest_name VARCHAR(120) NOT NULL,
          guest_email VARCHAR(190) NOT NULL,
          guest_phone VARCHAR(40) NOT NULL,
          guest_address VARCHAR(255) NOT NULL,
          total_amount DECIMAL(10,2) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          CONSTRAINT fk_orders_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE SET NULL
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS order_items (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          order_id INT UNSIGNED NOT NULL,
          product_id INT UNSIGNED NOT NULL,
          quantity INT UNSIGNED NOT NULL,
          unit_price DECIMAL(10,2) NOT NULL,
          CONSTRAINT fk_order_items_order
            FOREIGN KEY (order_id) REFERENCES orders(id)
            ON DELETE CASCADE,
          CONSTRAINT fk_order_items_product
            FOREIGN KEY (product_id) REFERENCES products(id)
            ON DELETE RESTRICT
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS product_highlights (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          product_id INT UNSIGNED NOT NULL,
          highlight_type ENUM(\'weekly\', \'weekend\') NOT NULL,
          discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          label VARCHAR(120) NOT NULL DEFAULT \'\',
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          CONSTRAINT fk_highlights_product
            FOREIGN KEY (product_id) REFERENCES products(id)
            ON DELETE CASCADE,
          UNIQUE KEY uniq_highlight_product_type (product_id, highlight_type)
        )
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS app_settings (
          setting_key VARCHAR(120) PRIMARY KEY,
          setting_value TEXT NOT NULL
        )
    ');

    ensure_orders_upgrade($pdo);
    ensure_products_upgrade($pdo);
    seed_defaults($pdo);
}

function ensure_orders_upgrade(PDO $pdo): void
{
    if (!column_exists($pdo, 'orders', 'guest_name')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN guest_name VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id");
    }
    if (!column_exists($pdo, 'orders', 'guest_email')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN guest_email VARCHAR(190) NOT NULL DEFAULT '' AFTER guest_name");
    }
    if (!column_exists($pdo, 'orders', 'guest_phone')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN guest_phone VARCHAR(40) NOT NULL DEFAULT '' AFTER guest_email");
    }
    if (!column_exists($pdo, 'orders', 'guest_address')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN guest_address VARCHAR(255) NOT NULL DEFAULT '' AFTER guest_phone");
    }

    $pdo->exec('ALTER TABLE orders MODIFY COLUMN user_id INT UNSIGNED NULL');

    try {
        $pdo->exec('ALTER TABLE orders DROP FOREIGN KEY fk_orders_user');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
    } catch (PDOException $e) {
    }
}

function ensure_products_upgrade(PDO $pdo): void
{
    if (!column_exists($pdo, 'products', 'category')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN category VARCHAR(80) NOT NULL DEFAULT 'Components' AFTER name");
    }
    if (!column_exists($pdo, 'products', 'image_url')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(600) NOT NULL DEFAULT '' AFTER description");
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name
    ');
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    return (int)$stmt->fetchColumn() > 0;
}

function run_sql_file(PDO $pdo, string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException("SQL file not found: {$path}");
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Unable to read SQL file: {$path}");
    }

    $statement = '';
    foreach (preg_split('/\R/', $sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }

        $statement .= $line . "\n";
        if (str_ends_with(rtrim($line), ';')) {
            $pdo->exec($statement);
            $statement = '';
        }
    }

    if (trim($statement) !== '') {
        $pdo->exec($statement);
    }
}

function seed_defaults(PDO $pdo): void
{
    $pdo->exec("
        INSERT INTO users (full_name, email, password_hash, is_admin, is_owner)
        SELECT 'Admin', 'admin@techstore.local', '\$2y\$10\$aKHKePxcxF39lgG9VBIFY.oUkEuTyq.x9EJWPKAi9huGzcEfOE97.', 1, 1
        WHERE NOT EXISTS (
          SELECT 1 FROM users WHERE email = 'admin@techstore.local'
        )
    ");
    $pdo->exec("UPDATE users SET is_owner = 1, is_admin = 1 WHERE email = 'admin@techstore.local'");

    $catalogVersion = '2026-04-18-storefront-v4';
    if (app_setting($pdo, 'catalog_version') !== $catalogVersion) {
        sync_catalog_products($pdo, default_catalog_products());
        sync_catalog_highlights($pdo, default_catalog_highlights());
        set_app_setting($pdo, 'catalog_version', $catalogVersion);
    }
}

function app_setting(PDO $pdo, string $key): ?string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
    $stmt->execute(['setting_key' => $key]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : (string)$value;
}

function set_app_setting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('
        INSERT INTO app_settings (setting_key, setting_value)
        VALUES (:setting_key, :setting_value)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ');
    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
    ]);
}

function sync_catalog_products(PDO $pdo, array $products): void
{
    $findStmt = $pdo->prepare('SELECT id FROM products WHERE name = :name LIMIT 1');
    $insertStmt = $pdo->prepare('
        INSERT INTO products (name, category, description, image_url, price, stock)
        VALUES (:name, :category, :description, :image_url, :price, :stock)
    ');
    $updateStmt = $pdo->prepare('
        UPDATE products
        SET category = :category,
            description = :description,
            image_url = :image_url,
            price = :price,
            stock = :stock
        WHERE id = :id
    ');

    foreach ($products as $product) {
        $findStmt->execute(['name' => $product['name']]);
        $existingId = (int)$findStmt->fetchColumn();
        if ($existingId > 0) {
            $updateStmt->execute([
                'id' => $existingId,
                'category' => $product['category'],
                'description' => $product['description'],
                'image_url' => $product['image_url'],
                'price' => $product['price'],
                'stock' => $product['stock'],
            ]);
            continue;
        }

        $insertStmt->execute([
            'name' => $product['name'],
            'category' => $product['category'],
            'description' => $product['description'],
            'image_url' => $product['image_url'],
            'price' => $product['price'],
            'stock' => $product['stock'],
        ]);
    }
}

function sync_catalog_highlights(PDO $pdo, array $highlights): void
{
    $pdo->exec('DELETE FROM product_highlights');

    $stmt = $pdo->prepare('
        INSERT INTO product_highlights (product_id, highlight_type, discount_percent, label, is_active)
        SELECT id, :highlight_type, :discount_percent, :label, 1
        FROM products
        WHERE name = :name
        LIMIT 1
        ON DUPLICATE KEY UPDATE
          discount_percent = VALUES(discount_percent),
          label = VALUES(label),
          is_active = VALUES(is_active)
    ');

    foreach ($highlights as $highlight) {
        $stmt->execute([
            'name' => $highlight['name'],
            'highlight_type' => $highlight['highlight_type'],
            'discount_percent' => $highlight['discount_percent'],
            'label' => $highlight['label'],
        ]);
    }
}

function default_catalog_products(): array
{
    $nvidia4070Image = 'https://www.nvidia.com/content/dam/en-zz/Solutions/geforce/news/geforce-rtx-4070/geforce-rtx-4070-newsfeed.png';
    $nvidia4060Image = 'https://www.nvidia.com/content/dam/en-zz/Solutions/geforce/ada/rtx-4060-4060ti/geforce-rtx-4060-ti-og-1200x630.jpg';
    $amdGpuImage = 'https://www.amd.com/content/dam/amd/en/images/products/graphics/2648997-amd-radeon-7800xt.jpg';
    $amdCpuImage = 'https://www.amd.com/content/dam/amd/en/images/products/processors/ryzen/2505503-ryzen-7-7800x3d-og.jpg';
    $intelCpuImage = 'https://c1.neweggimages.com/ProductImage/19-118-466-04.jpg';
    $asusBoardImage = 'https://c1.neweggimages.com/ProductImage/13-119-672-04.png';
    $msiBoardImage = 'https://c1.neweggimages.com/ProductImage/13-144-567-17.png';
    $corsairMemoryImage = 'https://c1.neweggimages.com/ProductImage/20-236-941-08.jpg';
    $gskillMemoryImage = 'https://c1.neweggimages.com/ProductImage/20-374-445-11.png';
    $samsungSsdImage = 'https://images.samsung.com/is/image/samsung/p6pim/us/mz-v9p1t0b-am/gallery/us-nvme-ssd-mz-v9p1t0b-am-----pro---tb-ssd-nvme--m---black-551141121?$PD_GALLERY_PNG$';
    $wdSsdImage = 'https://www.sandisk.com/content/dam/sandisk/en-us/assets/products/internal-storage/wd-black-sn850x-nvme-ssd/gallery/wd-black-sn850x-nvme-ssd-front.png';
    $corsairPsuImage = 'https://c1.neweggimages.com/ProductImage/17-139-336-06.png';
    $nzxtCaseImage = 'https://nzxt.com/cdn/shop/files/h7-flow-hero-white.png?v=1762528078&width=2048';

    return [
        ['name' => 'NVIDIA GeForce RTX 4070', 'category' => 'Graphics Cards', 'description' => 'Видео карта за плавен 1440p гейминг, стрийминг и работа с тежки графични проекти.', 'image_url' => $nvidia4070Image, 'price' => 1229.00, 'stock' => 14],
        ['name' => 'NVIDIA GeForce RTX 4060 Ti', 'category' => 'Graphics Cards', 'description' => 'Енергийно ефективен модел за 1080p и 1440p конфигурации с DLSS и ray tracing.', 'image_url' => $nvidia4060Image, 'price' => 899.00, 'stock' => 19],
        ['name' => 'AMD Radeon RX 7800 XT', 'category' => 'Graphics Cards', 'description' => 'Мощна AMD видео карта за високи настройки при 1440p гейминг и модерни API технологии.', 'image_url' => $amdGpuImage, 'price' => 1049.00, 'stock' => 11],
        ['name' => 'AMD Ryzen 7 7800X3D', 'category' => 'Processors', 'description' => '8-ядрен процесор с 3D V-Cache, ориентиран към максимална производителност в игрите.', 'image_url' => $amdCpuImage, 'price' => 769.00, 'stock' => 22],
        ['name' => 'Intel Core i7-14700K', 'category' => 'Processors', 'description' => 'Процесор от 14-то поколение с отличен баланс между гейминг, многозадачност и рендер.', 'image_url' => $intelCpuImage, 'price' => 809.00, 'stock' => 16],
        ['name' => 'ASUS TUF B650-PLUS WIFI', 'category' => 'Motherboards', 'description' => 'AM5 дънна платка с Wi-Fi, стабилно захранване и отлична база за Ryzen конфигурация.', 'image_url' => $asusBoardImage, 'price' => 449.00, 'stock' => 18],
        ['name' => 'MSI MAG Z790 Tomahawk WiFi', 'category' => 'Motherboards', 'description' => 'Intel Z790 дънна платка с добра секция на захранване и богати възможности за разширение.', 'image_url' => $msiBoardImage, 'price' => 629.00, 'stock' => 9],
        ['name' => 'Corsair Vengeance 32GB DDR5', 'category' => 'Memory', 'description' => '32GB DDR5 комплект за съвременни конфигурации с бърз отклик и стабилна работа.', 'image_url' => $corsairMemoryImage, 'price' => 239.00, 'stock' => 34],
        ['name' => 'G.Skill Trident Z5 64GB DDR5', 'category' => 'Memory', 'description' => '64GB висок клас DDR5 памет за тежки приложения, виртуализация и многозадачна работа.', 'image_url' => $gskillMemoryImage, 'price' => 479.00, 'stock' => 12],
        ['name' => 'Samsung 990 PRO 1TB NVMe SSD', 'category' => 'Storage', 'description' => 'Бърз PCIe 4.0 SSD за операционна система, игри и професионални файлове.', 'image_url' => $samsungSsdImage, 'price' => 219.00, 'stock' => 40],
        ['name' => 'WD Black SN850X 2TB', 'category' => 'Storage', 'description' => 'Висок клас NVMe SSD с отлични скорости за игри, проекти и големи библиотеки.', 'image_url' => $wdSsdImage, 'price' => 359.00, 'stock' => 27],
        ['name' => 'Corsair RM850e PSU', 'category' => 'Power Supplies', 'description' => '850W модулно захранване с 80 Plus Gold сертификат за стабилна и тиха работа.', 'image_url' => $corsairPsuImage, 'price' => 259.00, 'stock' => 21],
        ['name' => 'NZXT H7 Flow Case', 'category' => 'Cases', 'description' => 'ATX кутия с акцент върху въздушния поток и подредения cable management.', 'image_url' => $nzxtCaseImage, 'price' => 269.00, 'stock' => 13],

        ['name' => 'NVIDIA GeForce RTX 4070 SUPER', 'category' => 'Graphics Cards', 'description' => 'По-силен вариант за 1440p гейминг с комфортен резерв за нови AAA заглавия.', 'image_url' => $nvidia4070Image, 'price' => 1369.00, 'stock' => 10],
        ['name' => 'NVIDIA GeForce RTX 4060 8GB', 'category' => 'Graphics Cards', 'description' => 'Компактна и икономична видео карта за балансирани 1080p конфигурации.', 'image_url' => $nvidia4060Image, 'price' => 699.00, 'stock' => 17],
        ['name' => 'AMD Radeon RX 7700 XT', 'category' => 'Graphics Cards', 'description' => 'AMD модел за силен 1440p гейминг с добър ценови баланс в средния клас.', 'image_url' => $amdGpuImage, 'price' => 939.00, 'stock' => 8],
        ['name' => 'AMD Radeon RX 7900 GRE', 'category' => 'Graphics Cards', 'description' => 'Видео карта за по-висок клас конфигурации и високи кадри при 1440p резолюция.', 'image_url' => $amdGpuImage, 'price' => 1199.00, 'stock' => 7],

        ['name' => 'AMD Ryzen 5 7600X', 'category' => 'Processors', 'description' => '6-ядрен Ryzen процесор за бързи геймърски и работни системи в средния клас.', 'image_url' => $amdCpuImage, 'price' => 489.00, 'stock' => 24],
        ['name' => 'AMD Ryzen 9 7900X3D', 'category' => 'Processors', 'description' => 'Процесор с 12 ядра и 3D V-Cache за комбиниран гейминг и професионална работа.', 'image_url' => $amdCpuImage, 'price' => 1129.00, 'stock' => 6],
        ['name' => 'Intel Core i5-14600K', 'category' => 'Processors', 'description' => 'Силен избор за гейминг конфигурация с добър баланс между цена и производителност.', 'image_url' => $intelCpuImage, 'price' => 649.00, 'stock' => 18],
        ['name' => 'Intel Core i9-14900K', 'category' => 'Processors', 'description' => 'Флагмански Intel процесор за тежки натоварвания, стрийминг и създаване на съдържание.', 'image_url' => $intelCpuImage, 'price' => 1299.00, 'stock' => 5],

        ['name' => 'ASUS ROG Strix B650E-F Gaming WiFi', 'category' => 'Motherboards', 'description' => 'AM5 дънна платка от серията ROG Strix с богат набор от портове и gaming ориентация.', 'image_url' => $asusBoardImage, 'price' => 569.00, 'stock' => 9],
        ['name' => 'ASUS TUF Gaming Z790-PLUS WiFi', 'category' => 'Motherboards', 'description' => 'Надеждна Intel платка с Wi-Fi и здрава основа за Core процесори от висок клас.', 'image_url' => $asusBoardImage, 'price' => 589.00, 'stock' => 11],
        ['name' => 'MSI B650 Gaming Plus WiFi', 'category' => 'Motherboards', 'description' => 'Практична AM5 дънна платка за геймърски компютър с добра свързаност и охлаждане.', 'image_url' => $msiBoardImage, 'price' => 399.00, 'stock' => 15],
        ['name' => 'MSI PRO Z790-A MAX WiFi', 'category' => 'Motherboards', 'description' => 'Z790 модел за Intel конфигурации с удобен BIOS, Wi-Fi и стабилно захранване.', 'image_url' => $msiBoardImage, 'price' => 549.00, 'stock' => 7],

        ['name' => 'Corsair Vengeance RGB 32GB DDR5', 'category' => 'Memory', 'description' => '32GB RGB памет за геймърски системи с модерен външен вид и висока честота.', 'image_url' => $corsairMemoryImage, 'price' => 279.00, 'stock' => 20],
        ['name' => 'Corsair Vengeance 64GB DDR5', 'category' => 'Memory', 'description' => '64GB комплект за работа с видео, дизайн, виртуални машини и тежки приложения.', 'image_url' => $corsairMemoryImage, 'price' => 469.00, 'stock' => 10],
        ['name' => 'G.Skill Trident Z5 RGB 32GB DDR5', 'category' => 'Memory', 'description' => '32GB премиум DDR5 памет с RGB осветление за high-end конфигурации.', 'image_url' => $gskillMemoryImage, 'price' => 299.00, 'stock' => 13],
        ['name' => 'G.Skill Ripjaws S5 32GB DDR5', 'category' => 'Memory', 'description' => 'Лек и бърз DDR5 комплект за чисти и балансирани системи без излишни елементи.', 'image_url' => $gskillMemoryImage, 'price' => 249.00, 'stock' => 16],

        ['name' => 'Samsung 990 PRO 2TB', 'category' => 'Storage', 'description' => 'NVMe SSD с по-голям капацитет за игри, работни проекти и бърз ежедневен достъп.', 'image_url' => $samsungSsdImage, 'price' => 389.00, 'stock' => 18],
        ['name' => 'Samsung 990 EVO 2TB', 'category' => 'Storage', 'description' => 'Бърз SSD диск за модерни конфигурации с добро съотношение между цена и капацитет.', 'image_url' => $samsungSsdImage, 'price' => 299.00, 'stock' => 22],
        ['name' => 'WD Black SN850X 1TB', 'category' => 'Storage', 'description' => 'Компактен високоскоростен SSD за игри, система и често използвани приложения.', 'image_url' => $wdSsdImage, 'price' => 229.00, 'stock' => 19],

        ['name' => 'NZXT H6 Flow RGB Case', 'category' => 'Cases', 'description' => 'Кутия с панорамен дизайн, добър airflow и място за модерни high-end компоненти.', 'image_url' => $nzxtCaseImage, 'price' => 319.00, 'stock' => 8],

        ['name' => 'NVIDIA GeForce RTX 4080 SUPER', 'category' => 'Graphics Cards', 'description' => 'По-висок клас NVIDIA карта за максимални детайли при 1440p и много силно 4K представяне.', 'image_url' => $nvidia4070Image, 'price' => 1899.00, 'stock' => 5],
        ['name' => 'NVIDIA GeForce RTX 4060 Ti 16GB', 'category' => 'Graphics Cards', 'description' => 'Вариант с повече видео памет за модерни игри и по-тежки графични сцени.', 'image_url' => $nvidia4060Image, 'price' => 999.00, 'stock' => 9],
        ['name' => 'AMD Radeon RX 7600 XT', 'category' => 'Graphics Cards', 'description' => 'AMD предложение за стабилен 1080p и комфортен 1440p гейминг в средния клас.', 'image_url' => $amdGpuImage, 'price' => 749.00, 'stock' => 12],
        ['name' => 'AMD Radeon RX 7900 XT', 'category' => 'Graphics Cards', 'description' => 'Висок клас видео карта за ентусиасти, стрийминг и взискателни AAA заглавия.', 'image_url' => $amdGpuImage, 'price' => 1499.00, 'stock' => 4],

        ['name' => 'AMD Ryzen 7 7700X', 'category' => 'Processors', 'description' => '8-ядрен Ryzen процесор за гейминг конфигурации с добра ефективност и високи честоти.', 'image_url' => $amdCpuImage, 'price' => 629.00, 'stock' => 14],
        ['name' => 'AMD Ryzen 9 7950X', 'category' => 'Processors', 'description' => '16-ядрен процесор за тежък рендер, монтаж и професионални натоварвания.', 'image_url' => $amdCpuImage, 'price' => 1199.00, 'stock' => 5],
        ['name' => 'Intel Core i7-13700K', 'category' => 'Processors', 'description' => 'Процесор от висок клас с много силна многозадачна производителност и добър гейминг резултат.', 'image_url' => $intelCpuImage, 'price' => 719.00, 'stock' => 11],
        ['name' => 'Intel Core i5-13600K', 'category' => 'Processors', 'description' => 'Популярен избор за gaming PC с отличен баланс между цена, ядра и високи честоти.', 'image_url' => $intelCpuImage, 'price' => 559.00, 'stock' => 17],

        ['name' => 'ASUS TUF Gaming Z790-PLUS WiFi', 'category' => 'Motherboards', 'description' => 'Intel дънна платка с TUF серия качество, добра стабилност и богата свързаност.', 'image_url' => $asusBoardImage, 'price' => 599.00, 'stock' => 8],
        ['name' => 'MSI B650 Tomahawk WiFi', 'category' => 'Motherboards', 'description' => 'AM5 дънна платка с добра VRM секция и надеждна основа за Ryzen 7000 конфигурация.', 'image_url' => $msiBoardImage, 'price' => 469.00, 'stock' => 10],

        ['name' => 'Corsair Vengeance RGB 64GB DDR5', 'category' => 'Memory', 'description' => '64GB RGB DDR5 комплект за high-end конфигурации и работа с големи проекти.', 'image_url' => $corsairMemoryImage, 'price' => 529.00, 'stock' => 7],
        ['name' => 'G.Skill Trident Z5 Neo 32GB DDR5', 'category' => 'Memory', 'description' => '32GB DDR5 памет, оптимизирана за AM5 платформи и високоскоростни gaming системи.', 'image_url' => $gskillMemoryImage, 'price' => 289.00, 'stock' => 15],

        ['name' => 'Samsung 990 PRO 4TB', 'category' => 'Storage', 'description' => 'Голям NVMe SSD капацитет за игри, архиви и професионални библиотеки с много данни.', 'image_url' => $samsungSsdImage, 'price' => 699.00, 'stock' => 6],
        ['name' => 'WD Black SN850X 4TB', 'category' => 'Storage', 'description' => 'Премиум SSD с голям капацитет и много висока скорост за модерни workstation системи.', 'image_url' => $wdSsdImage, 'price' => 649.00, 'stock' => 8],

        ['name' => 'Corsair RM1000e PSU', 'category' => 'Power Supplies', 'description' => '1000W модулно захранване за мощни конфигурации с high-end видеокарта и резерв за ъпгрейд.', 'image_url' => $corsairPsuImage, 'price' => 319.00, 'stock' => 9],
        ['name' => 'NZXT H9 Flow Case', 'category' => 'Cases', 'description' => 'Просторна dual-chamber кутия за high-end конфигурации с добро охлаждане и чист вътрешен вид.', 'image_url' => $nzxtCaseImage, 'price' => 389.00, 'stock' => 6],
    ];
}

function default_catalog_highlights(): array
{
    return [
        ['name' => 'NVIDIA GeForce RTX 4070 SUPER', 'highlight_type' => 'weekly', 'discount_percent' => 0.0, 'label' => 'Избор на седмицата'],
        ['name' => 'AMD Ryzen 7 7800X3D', 'highlight_type' => 'weekly', 'discount_percent' => 0.0, 'label' => 'Топ процесор'],
        ['name' => 'ASUS TUF B650-PLUS WIFI', 'highlight_type' => 'weekly', 'discount_percent' => 0.0, 'label' => 'Най-търсен'],
        ['name' => 'WD Black SN850X 2TB', 'highlight_type' => 'weekly', 'discount_percent' => 0.0, 'label' => 'Бърз избор'],
        ['name' => 'Samsung 990 PRO 2TB', 'highlight_type' => 'weekend', 'discount_percent' => 15.0, 'label' => 'Уикенд оферта'],
        ['name' => 'Corsair Vengeance 64GB DDR5', 'highlight_type' => 'weekend', 'discount_percent' => 12.0, 'label' => 'Уикенд оферта'],
        ['name' => 'NVIDIA GeForce RTX 4060 Ti', 'highlight_type' => 'weekend', 'discount_percent' => 10.0, 'label' => 'Уикенд оферта'],
        ['name' => 'NZXT H6 Flow RGB Case', 'highlight_type' => 'weekend', 'discount_percent' => 8.0, 'label' => 'Уикенд оферта'],
    ];
}

function render_db_unavailable(): void
{
    http_response_code(503);
    echo '<!doctype html><html lang="bg"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Базата данни не е достъпна</title>';
    echo '<style>body{font-family:Segoe UI,Arial,sans-serif;background:#f2f5fa;margin:0;padding:32px;color:#1f3553}.box{max-width:760px;margin:40px auto;background:#fff;border:1px solid #dce4ef;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(30,50,80,.08)}h1{margin:0 0 12px;font-size:24px}p{margin:0 0 10px;line-height:1.5}code{background:#eef3fb;padding:2px 6px;border-radius:5px}</style></head><body>';
    echo '<div class="box"><h1>Връзката с базата данни не е налична</h1>';
    echo '<p>Стартирайте <strong>MySQL</strong> в XAMPP и презаредете страницата.</p>';
    echo '<p>Ако MySQL работи на друг порт или хост, задайте променливите на средата: <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>.</p>';
    echo '</div></body></html>';
}

