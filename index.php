<?php

$token = getenv('BOT_TOKEN');
$main_chat_id = getenv('MAIN_CHAT_ID');
$blocked_users = getenv('BLOCKED_USERS');

define('BASIC_API_URL', 'https://api.telegram.org/bot' . $token . '/');
define('MAIN_CHAT_ID', $main_chat_id);

$update = json_decode(file_get_contents("php://input"), TRUE);

if ($update != null) {
    $chat_id = $update["message"]["chat"]["id"];
    $user_name = $update["message"]["chat"]["first_name"];
    $message = $update["message"]["text"];
    $message_id = $update["message"]["message_id"];
    $is_reply = $update["message"]["reply_to_message"] != null;
    
    $blocked_arr = explode(',', $blocked_users);
    $is_blocked = in_array($chat_id, $blocked_arr);
    
    if ($is_blocked) return;

    if ($chat_id == MAIN_CHAT_ID && $is_reply) {
        if (array_key_exists('forward_from', $update["message"])) {
            copy_message($update["message"]["forward_from"]['id'], MAIN_CHAT_ID, $message_id);
        } else {
            try {
                $user_id = explode("\n", $update["message"]["reply_to_message"]["text"])[0];
            } catch (Exception) {
                $user_id = null;
            }
            if ($user_id != null) {
                copy_message($user_id, MAIN_CHAT_ID, $message_id);
            } else {
                sendMessage(MAIN_CHAT_ID, 'Помилка відповіді.');
            }
        }
    } else {
        $message = forward_message(MAIN_CHAT_ID, $chat_id, $message_id);
        $text = $chat_id . "\n" . 'Відповісти на повідомлення';
        sendMessage(MAIN_CHAT_ID, $text);
    }
}
function sendMessage(string $chat_id, string $text)
{
    $type = 'sendMessage';
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
    ];

    return send_request($params, $type);
}

function send_request(array $params, string $type)
{
    $ch = curl_init(BASIC_API_URL . $type);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function forward_message(string $chat_id, string $from_chat_id, int $message_id)
{
    $type = 'forwardMessage';
    $params = [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id,
    ];

    return send_request($params, $type);
}

function copy_message(string $chat_id, string $from_chat_id, int $message_id)
{
    $type = 'copyMessage';
    $params = [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id,
    ];

    send_request($params, $type);
}
