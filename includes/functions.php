<?php
function e(?string $value): string {
	return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void {
	header('Location: ' . $path);
	exit;
}

// ---- Global Branding Helpers ----

/**
 * Get the application name from system settings with caching
 */
function getAppName(): string {
    static $appName = null;
    
    if ($appName === null) {
        try {
            // Check if database config exists
            if (!file_exists(__DIR__ . '/../config/database.php')) {
                $appName = 'The Determiners Susu System';
                return $appName;
            }
            
            require_once __DIR__ . '/../config/database.php';
            
            // Check if Database class exists
            if (!class_exists('Database')) {
                $appName = 'The Determiners Susu System';
                return $appName;
            }
            
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute(['app_name']);
            $result = $stmt->fetch();
            
            $appName = $result ? $result['setting_value'] : 'The Determiners Susu System';
        } catch (Exception $e) {
            $appName = 'The Determiners Susu System';
        } catch (Error $e) {
            $appName = 'The Determiners Susu System';
        }
    }
    
    return $appName;
}

/**
 * Get the application logo URL from system settings with caching
 */
function getAppLogo(): string {
    static $appLogo = null;
    
    if ($appLogo === null) {
        try {
            // Check if database config exists
            if (!file_exists(__DIR__ . '/../config/database.php')) {
                $appLogo = '/assets/images/logo.png';
                return $appLogo;
            }
            
            require_once __DIR__ . '/../config/database.php';
            
            // Check if Database class exists
            if (!class_exists('Database')) {
                $appLogo = '/assets/images/logo.png';
                return $appLogo;
            }
            
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute(['app_logo']);
            $result = $stmt->fetch();
            
            $appLogo = $result ? $result['setting_value'] : '/assets/images/logo.png';
        } catch (Exception $e) {
            $appLogo = '/assets/images/logo.png';
        } catch (Error $e) {
            $appLogo = '/assets/images/logo.png';
        }
    }
    
    return $appLogo;
}

/**
 * Get a system setting value with fallback
 */
function getSystemSetting(string $key, string $default = ''): string {
    static $settings = [];
    
    if (!isset($settings[$key])) {
        try {
            // Check if database config exists
            if (!file_exists(__DIR__ . '/../config/database.php')) {
                $settings[$key] = $default;
                return $settings[$key];
            }
            
            require_once __DIR__ . '/../config/database.php';
            
            // Check if Database class exists
            if (!class_exists('Database')) {
                $settings[$key] = $default;
                return $settings[$key];
            }
            
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            $settings[$key] = $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            $settings[$key] = $default;
        } catch (Error $e) {
            $settings[$key] = $default;
        }
    }
    
    return $settings[$key];
}

/**
 * Display application logo HTML with proper attributes
 */
function displayAppLogo(string $class = 'app-logo', int $width = 150, int $height = 50): string {
    $logoUrl = getAppLogo();
    $appName = getAppName();
    
    // Check if logo exists and is not the default placeholder
    if ($logoUrl === '/assets/images/logo.png' && !file_exists(__DIR__ . '/..' . $logoUrl)) {
        // No logo exists, return the beautiful default coin icon
        return '<i class="fas fa-coins" style="color: #667eea; font-size: 2rem; margin-right: 0.5rem;"></i>';
    }
    
    return sprintf(
        '<img src="%s" alt="%s" class="%s" width="%d" height="%d" style="max-width: %dpx; height: auto;">',
        htmlspecialchars($logoUrl),
        htmlspecialchars($appName),
        htmlspecialchars($class),
        $width,
        $height,
        $width
    );
}

// ---- Shared metrics helpers (used across dashboard, transactions, reports) ----
// All helpers accept a PDO connection to avoid creating globals and to keep usage consistent.

/**
 * Get client's savings balance using the canonical SavingsAccount service if present,
 * otherwise fall back to summing savings transactions.
 */
function getSavingsBalance(PDO $pdo, int $clientId): float {
    // Prefer SavingsAccount domain class when available
    $savingsAccountPath = __DIR__ . '/SavingsAccount.php';
    if (is_file($savingsAccountPath)) {
        require_once $savingsAccountPath;
        if (class_exists('SavingsAccount')) {
            $svc = new SavingsAccount($pdo);
            return (float)$svc->getBalance($clientId);
        }
    }

    // Fallback: sum savings_deposit minus savings_withdrawal in manual_transactions
    $stmt = $pdo->prepare('
        SELECT COALESCE(SUM(CASE WHEN transaction_type = "savings_deposit" THEN amount ELSE 0 END)
               - COALESCE(SUM(CASE WHEN transaction_type = "savings_withdrawal" THEN amount ELSE 0 END), 0), 0) AS balance
        FROM manual_transactions
        WHERE client_id = ?
    ');
    $stmt->execute([$clientId]);
    $row = $stmt->fetch();
    return $row ? (float)$row['balance'] : 0.0;
}

/**
 * Sum all-time collections for a client, minus agent commission for completed cycles.
 * For flexible cycles, totals are already net of commission rules handled elsewhere; we just sum dc.collected_amount.
 */
function getAllTimeCollectionsNet(PDO $pdo, int $clientId): float {
    // Sum all collected amounts
    $colStmt = $pdo->prepare('
        SELECT COALESCE(SUM(dc.collected_amount), 0) AS total
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        WHERE sc.client_id = ? AND dc.collection_status = "collected"
    ');
    $colStmt->execute([$clientId]);
    $totalCollected = (float)($colStmt->fetchColumn() ?: 0);

    // Subtract agent fees (commission) for completed cycles to show client-facing net
    $feeStmt = $pdo->prepare('
        SELECT COALESCE(SUM(agent_fee), 0) AS fee
        FROM susu_cycles
        WHERE client_id = ? AND status = "completed"
    ');
    $feeStmt->execute([$clientId]);
    $totalFees = (float)($feeStmt->fetchColumn() ?: 0);

    return max(0.0, $totalCollected - $totalFees);
}

/**
 * Get withdrawals total (manual withdrawals + emergency withdrawals).
 */
function getTotalWithdrawals(PDO $pdo, int $clientId): float {
    $stmt = $pdo->prepare('
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM manual_transactions
        WHERE client_id = ? AND transaction_type IN ("withdrawal", "emergency_withdrawal")
    ');
    $stmt->execute([$clientId]);
    return (float)($stmt->fetchColumn() ?: 0);
}

/**
 * Get the active/current cycle collections total for the client.
 * - For fixed cycles: client-portion = (days_collected - 1) * daily_amount
 * - For flexible cycles: use sc.total_amount
 */
function getCurrentCycleCollections(PDO $pdo, int $clientId): float {
    $stmt = $pdo->prepare('SELECT id, is_flexible, daily_amount, total_amount FROM susu_cycles WHERE client_id = ? AND status = "active" ORDER BY id DESC LIMIT 1');
    $stmt->execute([$clientId]);
    $cycle = $stmt->fetch();
    if (!$cycle) {
        return 0.0;
    }

    if (!empty($cycle['is_flexible'])) {
        return (float)($cycle['total_amount'] ?? 0);
    }

    // Fixed: count days collected
    $daysStmt = $pdo->prepare('SELECT COUNT(*) FROM daily_collections WHERE susu_cycle_id = ? AND collection_status = "collected"');
    $daysStmt->execute([(int)$cycle['id']]);
    $daysCollected = (int)($daysStmt->fetchColumn() ?: 0);
    $clientDays = max(0, $daysCollected - 1); // minus agent day
    $daily = (float)($cycle['daily_amount'] ?? 0);
    return (float)($clientDays * $daily);
}

// ---- Business Information Helpers ----

/**
 * Get business information from system settings
 */
function getBusinessInfo(): array {
    static $businessInfo = null;
    
    if ($businessInfo === null) {
        try {
            // Check if database config exists
            if (!file_exists(__DIR__ . '/../config/database.php')) {
                $businessInfo = [
                    'name' => 'The Determiners',
                    'phone' => '',
                    'email' => '',
                    'address' => ''
                ];
                return $businessInfo;
            }
            
            require_once __DIR__ . '/../config/database.php';
            
            // Check if Database class exists
            if (!class_exists('Database')) {
                $businessInfo = [
                    'name' => 'The Determiners',
                    'phone' => '',
                    'email' => '',
                    'address' => ''
                ];
                return $businessInfo;
            }
            
            $pdo = Database::getConnection();
            
            $businessSettings = [
                'business_name', 'business_phone', 'business_email', 'business_address', 
                'business_weekdays_hours', 'business_saturday_hours', 'business_sunday_hours',
                'business_support_email', 'business_loans_email', 'business_info_email',
                'business_support_phone', 'business_emergency_phone'
            ];
            $businessInfo = [];
            
            foreach ($businessSettings as $key) {
                $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
                $stmt->execute([$key]);
                $result = $stmt->fetch();
                
                // Map database keys to function keys
                $settingName = match($key) {
                    'business_name' => 'name',
                    'business_phone' => 'phone',
                    'business_email' => 'email',
                    'business_address' => 'address',
                    'business_weekdays_hours' => 'weekdays_hours',
                    'business_saturday_hours' => 'saturday_hours',
                    'business_sunday_hours' => 'sunday_hours',
                    'business_support_email' => 'support_email',
                    'business_loans_email' => 'loans_email',
                    'business_info_email' => 'info_email',
                    'business_support_phone' => 'support_phone',
                    'business_emergency_phone' => 'emergency_phone',
                    default => str_replace('business_', '', $key)
                };
                $businessInfo[$settingName] = $result ? $result['setting_value'] : '';
            }
            
        } catch (Exception $e) {
            $businessInfo = [
                'name' => 'The Determiners',
                'phone' => '',
                'email' => '',
                'address' => ''
            ];
        } catch (Error $e) {
            $businessInfo = [
                'name' => 'The Determiners',
                'phone' => '',
                'email' => '',
                'address' => ''
            ];
        }
    }
    
    return $businessInfo;
}

/**
 * Get specific business setting
 */
function getBusinessSetting(string $key): string {
    $businessInfo = getBusinessInfo();
    return $businessInfo[$key] ?? '';
}

/**
 * Display business name
 */
function getBusinessName(): string {
    return getBusinessSetting('name');
}

/**
 * Display business phone
 */
function getBusinessPhone(): string {
    return getBusinessSetting('phone');
}

/**
 * Display business email
 */
function getBusinessEmail(): string {
    return getBusinessSetting('email');
}

/**
 * Display business address
 */
function getBusinessAddress(): string {
    return getBusinessSetting('address');
}

/**
 * Display business hours
 */
function getBusinessHours(): array {
    $businessInfo = getBusinessInfo();
    return [
        'weekdays' => $businessInfo['weekdays_hours'] ?? 'Mon-Fri: 8:00 AM - 6:00 PM',
        'saturday' => $businessInfo['saturday_hours'] ?? 'Sat: 9:00 AM - 2:00 PM',
        'sunday' => $businessInfo['sunday_hours'] ?? 'Sun: Closed'
    ];
}

/**
 * Get all business contact information
 */
function getBusinessContacts(): array {
    $businessInfo = getBusinessInfo();
    return [
        'primary' => [
            'phone' => $businessInfo['phone'] ?? '+233 123 456 789',
            'email' => $businessInfo['email'] ?? 'thedeterminers@site.com'
        ],
        'support' => [
            'phone' => $businessInfo['support_phone'] ?? '+233 302 123 457',
            'email' => $businessInfo['support_email'] ?? 'support@thedeterminers.com'
        ],
        'loans' => [
            'email' => $businessInfo['loans_email'] ?? 'loans@thedeterminers.com'
        ],
        'info' => [
            'email' => $businessInfo['info_email'] ?? 'info@thedeterminers.com'
        ],
        'emergency' => [
            'phone' => $businessInfo['emergency_phone'] ?? '+233 302 123 458'
        ]
    ];
}

/**
 * Get support email
 */
function getBusinessSupportEmail(): string {
    return getBusinessSetting('support_email');
}

/**
 * Get loans email
 */
function getBusinessLoansEmail(): string {
    return getBusinessSetting('loans_email');
}

/**
 * Get info email
 */
function getBusinessInfoEmail(): string {
    return getBusinessSetting('info_email');
}

/**
 * Get support phone
 */
function getBusinessSupportPhone(): string {
    return getBusinessSetting('support_phone');
}

/**
 * Get emergency phone
 */
function getBusinessEmergencyPhone(): string {
    return getBusinessSetting('emergency_phone');
}
?>

