<?php
// غیرفعال کردن نمایش خطاها در خروجی
ini_set('display_errors', 0);
error_reporting(0);

// شروع بافر خروجی برای جلوگیری از خروجی‌های ناخواسته
ob_start();

// تنظیم هدر برای همه درخواست‌ها
header('Content-Type: application/json; charset=utf-8');

// تابع برای ارسال پاسخ JSON
function sendJsonResponse($data, $statusCode = 200) {
    // پاک کردن بافر خروجی
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// بررسی روش درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'متد درخواست نامعتبر است'], 405);
}

// بررسی وجود داده‌های مورد نیاز
if (!isset($_POST['grid']) || empty($_POST['grid'])) {
    sendJsonResponse(['error' => 'داده‌های گرید ارسال نشده است'], 400);
}

// دریافت نوع خروجی
$outputType = isset($_POST['type']) ? $_POST['type'] : 'html';

// تلاش برای دیکود کردن JSON
$gridData = json_decode($_POST['grid'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(['error' => 'داده‌های JSON نامعتبر هستند'], 400);
}

try {
    if ($outputType === 'json') {
        // خروجی JSON با فرمت استاندارد
        sendJsonResponse($gridData);
    } else {
        // خروجی HTML
        $html = generateGridHTML($gridData);
        sendJsonResponse(['html' => $html]);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => 'خطای سرور: ' . $e->getMessage()], 500);
}

function generateGridHTML($gridData) {
    $html = '<div class="container">' . PHP_EOL;
    
    foreach ($gridData as $row) {
        $html .= generateRowHTML($row);
    }
    
    $html .= '</div>';
    return $html;
}

function generateRowHTML($rowData, $isNested = false) {
    $html = '<div class="row">' . PHP_EOL;
    
    foreach ($rowData['columns'] as $column) {
        $html .= '    <div class="' . htmlspecialchars($column['size']) . '">' . PHP_EOL;
        $html .= '        <h5>' . htmlspecialchars($column['name']) . '</h5>' . PHP_EOL;
        $html .= '        <p>' . htmlspecialchars($column['content']) . '</p>' . PHP_EOL;
        
        if (!empty($column['nestedRows'])) {
            foreach ($column['nestedRows'] as $nestedRow) {
                $html .= generateRowHTML($nestedRow, true);
            }
        }
        
        $html .= '    </div>' . PHP_EOL;
    }
    
    $html .= '</div>' . PHP_EOL;
    return $html;
}
?>