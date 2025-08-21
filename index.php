<?php
// Функция для получения реального IP пользователя
function getUserIP() {
    // Проверяем различные заголовки для получения реального IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Получаем первый IP из списка (в случае прокси)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Функция для получения домена
function getCurrentDomain() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'];
}

// Функция для отправки данных в API
function sendLeadData($data) {
    $url = 'https://crm.belmar.pro/api/v1/addlead';
    $token = 'ba67df6a-a17c-476f-8e95-bcdb75ed3958';
    
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
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

$message = '';
$messageType = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация данных
    $errors = [];
    
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($firstName)) {
        $errors[] = 'Имя обязательно для заполнения';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Фамилия обязательна для заполнения';
    }
    
    if (empty($phone)) {
        $errors[] = 'Телефон обязателен для заполнения';
    }
    
    if (empty($email)) {
        $errors[] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email';
    }
    
    if (empty($errors)) {
        // Подготавливаем данные для API
        $apiData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'countryCode' => 'GB',
            'box_id' => 28,
            'offer_id' => 5,
            'landingUrl' => getCurrentDomain(),
            'ip' => getUserIP(),
            'password' => 'qwerty12',
            'language' => 'en'
        ];
        
        // Отправляем данные в API
        $result = sendLeadData($apiData);
        
        if ($result['httpCode'] === 200) {
            $message = 'Данные успешно отправлены!';
            $messageType = 'success';
            // Очищаем поля формы после успешной отправки
            $firstName = $lastName = $phone = $email = '';
        } else {
            $message = 'Ошибка при отправке данных. HTTP код: ' . $result['httpCode'];
            if ($result['error']) {
                $message .= ' Ошибка: ' . $result['error'];
            }
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <style>
        h1 { color: #444; }
         * {
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: #3498db;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        button:active {
            transform: translateY(1px);
        }
        
        .message {
            padding: 15px;
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
        
        .info-panel {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }
        
        .info-panel h3 {
            margin-top: 0;
            color: #1976d2;
        }
        
        .info-item {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
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
    </style>
</head>
<body>

    
    <h1>Добро пожаловать на главную страницу!</h1>
    <div class="container">
        <?php
        // Подключаем наш файл с навигацией
        include 'nav.php';
        ?>
        <h1>Форма отправки лида</h1>
        
        <div class="info-panel">
            <h3>Информация о запросе:</h3>
            <div class="info-item"><strong>Ваш IP:</strong> <?php echo getUserIP(); ?></div>
            <div class="info-item"><strong>Домен:</strong> <?php echo getCurrentDomain(); ?></div>
            <div class="info-item"><strong>Статичные параметры:</strong> box_id=28, offer_id=5, countryCode=GB, language=en</div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="firstName">
                    Имя <span class="required">*</span>
                </label>
                <input type="text" 
                       id="firstName" 
                       name="firstName" 
                       value="<?php echo htmlspecialchars($firstName ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="lastName">
                    Фамилия <span class="required">*</span>
                </label>
                <input type="text" 
                       id="lastName" 
                       name="lastName" 
                       value="<?php echo htmlspecialchars($lastName ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="phone">
                    Телефон <span class="required">*</span>
                </label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="email">
                    Email <span class="required">*</span>
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                       required>
            </div>
            
            <button type="submit">Отправить заявку</button>
        </form>
    </div>
</body>
</html>
