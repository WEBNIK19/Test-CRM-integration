<?php
// Функция для получения статусов лидов
function getLeadsStatuses($dateFrom = null, $dateTo = null, $page = 0, $limit = 100) {
    $url = 'https://crm.belmar.pro/api/v1/getstatuses';
    $token = 'ba67df6a-a17c-476f-8e95-bcdb75ed3958';
    
    // Устанавливаем дефолтные даты, если не переданы
    if (!$dateFrom) {
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
    }
    if (!$dateTo) {
        $dateTo = date('Y-m-d 23:59:59');
    }
    
    $data = [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'page' => $page,
        'limit' => $limit
    ];
    
    $headers = [
        'token: ' . $token,
        'Content-Type: application/json'
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
    ]);
    
    $response = curl_exec($curl);
    print_r($response);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error,
        'data' => $httpCode === 200 ? json_decode($response, true) : null
    ];
}

// Обработка фильтра
$dateFrom = '';
$dateTo = '';
$leads = [];
$message = '';
$messageType = '';
$totalCount = 0;

// Устанавливаем дефолтные даты для фильтра
$defaultDateFrom = date('Y-m-d', strtotime('-30 days'));
$defaultDateTo = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    
    // Валидация дат
    $errors = [];
    
    if (empty($dateFrom)) {
        $errors[] = 'Дата "с" обязательна для заполнения';
    }
    
    if (empty($dateTo)) {
        $errors[] = 'Дата "по" обязательна для заполнения';
    }
    
    if (!empty($dateFrom) && !empty($dateTo)) {
        $dateFromTs = strtotime($dateFrom);
        $dateToTs = strtotime($dateTo);
        
        if ($dateFromTs > $dateToTs) {
            $errors[] = 'Дата "с" не может быть больше даты "по"';
        }
        
        // Проверяем ограничение в 60 дней назад
        $maxDateFrom = strtotime('-60 days');
        if ($dateFromTs < $maxDateFrom) {
            $errors[] = 'Максимальный период выборки - 60 дней назад';
        }
    }
    
    if (empty($errors)) {
        $apiDateFrom = $dateFrom . ' 00:00:00';
        $apiDateTo = $dateTo . ' 23:59:59';
        
        $result = getLeadsStatuses($apiDateFrom, $apiDateTo);
        
        if ($result['httpCode'] === 200 && $result['data']) {
            $responseData = $result['data'];
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $leads = $responseData['data'];
                $totalCount = $responseData['total'] ?? count($leads);
                $message = 'Найдено лидов: ' . count($leads);
                $messageType = 'success';
            } else {
                $message = 'Данные не найдены';
                $messageType = 'info';
            }
        } else {
            $message = 'Ошибка при получении данных. HTTP код: ' . $result['httpCode'];
            if ($result['error']) {
                $message .= ' Ошибка: ' . $result['error'];
            }
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
} else {
    // При первом открытии страницы загружаем данные за последние 30 дней
    $dateFrom = $defaultDateFrom;
    $dateTo = $defaultDateTo;
    
    $result = getLeadsStatuses();
    
    if ($result['httpCode'] === 200 && $result['data']) {
        $responseData = $result['data'];
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            $leads = $responseData['data'];
            $totalCount = $responseData['total'] ?? count($leads);
        }
    }
}

// Функция для форматирования статуса
function formatStatus($status) {
    $statuses = [
        'new' => 'Новый',
        'contacted' => 'Связались',
        'qualified' => 'Квалифицирован',
        'converted' => 'Конверсия',
        'ftd' => 'FTD',
        'rejected' => 'Отклонен'
    ];
    
    return $statuses[$status] ?? $status;
}

// Функция для получения CSS класса статуса
function getStatusClass($status) {
    $classes = [
        'new' => 'status-new',
        'contacted' => 'status-contacted', 
        'qualified' => 'status-qualified',
        'converted' => 'status-converted',
        'ftd' => 'status-ftd',
        'rejected' => 'status-rejected'
    ];
    
    return $classes[$status] ?? 'status-default';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статусы лидов</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            height: fit-content;
        }
        
        button:hover {
            background-color: #0056b3;
        }
        
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-new {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-contacted {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .status-qualified {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .status-converted {
            background-color: #e8f5e8;
            color: #388e3c;
        }
        
        .status-ftd {
            background-color: #e1f5fe;
            color: #0277bd;
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .status-default {
            background-color: #f5f5f5;
            color: #666;
        }
        
        .ftd-yes {
            color: #28a745;
            font-weight: bold;
        }
        
        .ftd-no {
            color: #6c757d;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        
        .stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .nav-links {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .nav-links a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #5a6268;
        }
        
        .nav-links a.active {
            background-color: #007bff;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .stats {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // Подключаем наш файл с навигацией
        include 'nav.php';
        ?>
        <h1>Статусы лидов</h1>
        
        <div class="filter-section">
            <form method="POST" class="filter-form">
                <div class="form-group">
                    <label for="date_from">Дата с:</label>
                    <input type="date" 
                           id="date_from" 
                           name="date_from" 
                           value="<?php echo htmlspecialchars($dateFrom); ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="date_to">Дата по:</label>
                    <input type="date" 
                           id="date_to" 
                           name="date_to" 
                           value="<?php echo htmlspecialchars($dateTo); ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           required>
                </div>
                
                <button type="submit">Применить фильтр</button>
            </form>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($leads)): ?>
            <div class="stats">
                <div>
                    <strong>Период:</strong> <?php echo $dateFrom; ?> - <?php echo $dateTo; ?>
                </div>
                <div>
                    <strong>Всего лидов:</strong> <?php echo count($leads); ?>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Статус</th>
                            <th>FTD</th>
                            <th>Дата создания</th>
                            <th>Имя</th>
                            <th>Телефон</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lead['id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($lead['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($lead['status'] ?? ''); ?>">
                                        <?php echo formatStatus($lead['status'] ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo ($lead['ftd'] ?? false) ? 'ftd-yes' : 'ftd-no'; ?>">
                                        <?php echo ($lead['ftd'] ?? false) ? 'Да' : 'Нет'; ?>
                                    </span>
                                </td>
                                <td><?php echo isset($lead['created_at']) ? date('d.m.Y H:i', strtotime($lead['created_at'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars(($lead['firstName'] ?? '') . ' ' . ($lead['lastName'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($lead['phone'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    Нет данных за выбранный период
                <?php else: ?>
                    Загрузка данных...
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Автоматическая валидация дат на клиенте
        document.addEventListener('DOMContentLoaded', function() {
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            
            dateFrom.addEventListener('change', function() {
                if (this.value && dateTo.value && this.value > dateTo.value) {
                    alert('Дата "с" не может быть больше даты "по"');
                    this.focus();
                }
            });
            
            dateTo.addEventListener('change', function() {
                if (this.value && dateFrom.value && dateFrom.value > this.value) {
                    alert('Дата "по" не может быть меньше даты "с"');
                    this.focus();
                }
            });
        });
    </script>
</body>
</html>