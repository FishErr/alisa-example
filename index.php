<?php
include_once 'xml2array.php';

$cities= [
    'Ялта' => 11470,
    'Симферополь'=> 146,
    'Севастополь' => 959,
    'Евпатория' => 11463,
    'Керчь' => 11464
];

$response = '';
$buttons = [];
foreach ($cities as $city=>$id) {
    $buttons[] = [
        'title'=>$city,
        'hide'=>true
    ];
        //    "buttons": [
        //        {
        //            "title": "Надпись на кнопке",
        //            "payload": {},
        //            "url": "https://example.com/",
        //            "hide": true
        //        }
        //    ],
}

$dataRow = file_get_contents('php://input');
header('Content-Type: application/json');

/**
 * Впишите сюда своё активационное имя
 */
$mySkillName = 'Погода в Крыму';



try{
    if (!empty($dataRow)) {
        /**
         * Простейший лог, чтобы проверять запросы. Закомментируйте эту стрчоку, если он вам не нужен
         */
//        file_put_contents('alisalog.txt', date('Y-m-d H:i:s') . PHP_EOL . $dataRow . PHP_EOL, FILE_APPEND);

        /**
         * Преобразуем запрос пользователя в массив
         */
        $data = json_decode($dataRow, true);

        /**
         * Проверяем наличие всех необходимых полей
         */
        if (!isset($data['request'], $data['request']['command'], $data['session'], $data['session']['session_id'], $data['session']['message_id'], $data['session']['user_id'])) {
            /**
             * Нет всех необходимых полей. Не понятно, что вернуть, поэтому возвращаем ничего.
             */
            $result = json_encode([]);
        } else {
            /**
             * Получаем что конкретно спросил пользователь
             */
            $text = $data['request']['command'];

            session_id($data['session']['session_id']); // В Чате спрашивали неодногравтно как использовать сессии в навыке - показываю
            session_start();

            /**
             * Приводим на всякий случай запрос пользователя к нижнему регистру
             */
            $textToCheck = strtolower($text);

//            if (strpos($text, $mySkillName) !== false) {
            if (empty($text)) {
                $response = json_encode([
                    'version' => '1.0',
                    'session' => [
                        'session_id' => $data['session']['session_id'],
                        'message_id' => $data['session']['message_id'],
                        'user_id' => $data['session']['user_id']
                    ],
                    'response' => [
                        'text' => 'Выберите или назовите город, погода в котором вас интересует',
                        /**
                         * Ставьте плюсик перед гласной, на которую делается ударение.
                         * Если вам нужна пауза, добавьте " - ", т.е. дефис с пробелом до и после него.
                         */
                        'tts' => 'Выб+ерите или назов+ите город, пог+ода в кот+ором вас интерес+ует',
                        'buttons' => $buttons
                    ]
                ]);
            } elseif($text == 'помощь') {
                $response = json_encode([
                    'version' => '1.0',
                    'session' => [
                        'session_id' => $data['session']['session_id'],
                        'message_id' => $data['session']['message_id'],
                        'user_id' => $data['session']['user_id']
                    ],
                    'response' => [
                        'text' => 'Навык позволяет получать погоду в городах Крыма,
                         для проверки погоды следует назвать город или выбрать его с помощью кнопки. Для выхода скажите "Алиса хватит"',
                        'tts' => 'Навык позволяет получать погоду в городах Крыма,
                         для проверки погоды следует назвать город или выбрать его с помощью кнопки. Для выхода скажите "Алиса хватит"',
                        'buttons' => $buttons
                    ]
                ]);
            }elseif($text == 'хватит' || $text == 'выход') { // Обязательно добавляем условия выхода
                $response = json_encode([
                    'version' => '1.0',
                    'session' => [
                        'session_id' => $data['session']['session_id'],
                        'message_id' => $data['session']['message_id'],
                        'user_id' => $data['session']['user_id']
                    ],
                    'response' => [
                        'text' => 'Приятного дня',
                        'tts' =>  'Приятного дня',
                        'buttons' => [],
                        'end_session' => true // при возврате true сессия в навыке прерывается,
                                              // но на смартфонах навык не закрывается и следующий запрос пользователя
                                              // идет опять в наш навык, не в алису.
                    ]
                ]);
            } else {
                /**
                 * Здесь опишите логику обработки запроса пользователя.
                 * Например, давайте возвращать количество символов в запросе пользователя.
                 */

                if(array_key_exists($text, $cities)) {
                    $id = $cities[$text];
                    $array = xml2array('https://export.yandex.ru/bar/reginfo.xml?region=' . $id);
                    $weather = $array['info']['weather']['day']['day_part'][0];
                    $answer_text = 'Погода в г.' . $text . ': ' .
                        $weather['temperature']
                        .  'C , Давление '
                        . $weather['pressure']
                        . 'мм.р.ст, Влажность '
                        . $weather['dampness']
                        . '\n';
                }else{
                    $answer_text = 'Такой город не найден, назовите другой или выберите из списка. для выхода скажите хватит';
                }

                // Притянутый за уши пример работы с сессией
                if(empty($_SESSION['count'])) {
                    $_SESSION['count']=1;
                }else{
                    $_SESSION['count']++;
                }
                $answer_text .= ' Вы сделали ' . $_SESSION['count'] . ' запросов';

                $response = json_encode([
                    'version' => '1.0',
                    'session' => [
                        'session_id' => $data['session']['session_id'],
                        'message_id' => $data['session']['message_id'],
                        'user_id' => $data['session']['user_id']
                    ],
                    'response' => [
                        'text' => $answer_text,
                        'tts' => $answer_text,
                        'buttons' => $buttons,
                        'end_session' => false
                    ]
                ]);

            }
        }
    } else {
        $response = json_encode([
            'version' => '1.0',
            'session' => 'Error',
            'response' => [
                'text' => 'Отсутствуют данные',
                'tts' =>  'Отсутствуют данные'
            ]
        ]);
    }

    echo $response;
} catch(\Exception $e){
    echo '["Error occured"]';
}