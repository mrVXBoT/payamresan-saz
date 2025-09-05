<?php
error_reporting(0);
set_time_limit(5);
date_default_timezone_set('Asia/Tehran');
##----------------------
require 'config.php';
##----------------------
if (!is_dir('Data')) {
	mkdir('Data');
}
if (!is_file('Data/ads.json')) {
	file_put_contents('Data/ads.json', json_encode([]));
}
if (!is_dir('Bots')) {
	mkdir('Bots');
}
##----------------------
function CreateZip($files = array(), $destination, $password = null, $overwrite = false)
{
	if (file_exists($destination)) {
		return false;
	}
	$valid_files = array();
	if (is_array($files)) {
		foreach($files as $file) {
			if (file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	if (count($valid_files)) {
		$zip = new ZipArchive();
		if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		if (!is_null($password)) {
			$zip->setPassword($password);
		}
		foreach($valid_files as $file) {
			$zip->addFile($file, basename($file));
			if (!is_null($password)) {
				$zip->setEncryptionName(basename($file), ZipArchive::EM_AES_256);
			}
		}
		$zip->close();
		return file_exists($destination);
	} else {
		return false;
	}
}
##----------------------
function makeInlineKeyboard($text)
{
	$keyboard = [];
	$lines = explode("\n", trim($text));
	
	foreach ($lines as $line_index => $line) {
		if (empty(trim($line))) continue;
		
		$buttons = explode(',', $line);
		$row = [];
		
		foreach ($buttons as $button) {
			$button = trim($button);
			if (empty($button)) continue;
			
			if (preg_match('#^(.+?)\|(.+)$#', $button, $matches)) {
				$button_text = trim($matches[1]);
				$button_url = trim($matches[2]);
				
				if (!empty($button_text) && filter_var($button_url, FILTER_VALIDATE_URL)) {
					$row[] = [
						'text' => $button_text,
						'url' => $button_url
					];
				}
			}
		}
		
		if (!empty($row)) {
			$keyboard[] = $row;
		}
	}
	
	if (!empty($keyboard)) {
		return ['inline_keyboard' => $keyboard];
	}
	return null;
}
##----------------------
function convert_size($size)
{
    $unit = [
	'بایت',
	'کیلوبایت',
	'مگابایت',
	'گیگابایت',
	'ترابایت',
	'پنتابایت'
    ];
    $i = 0;
    return @round($size/pow(1024, ($i=(int)floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}
##----------------------
function convert($string)
{
	$persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	$arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠'];
	$num = range(0, 9);
	$string = str_replace($persian, $num, $string);
	return str_replace($arabic, $num, $string);
}
##----------------------
function bot($method, $data = [], $bot_token = API_KEY_CR)
{
	$ch = curl_init('https://api.telegram.org/bot' . $bot_token . '/' . $method);
	curl_setopt_array($ch,
	[
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_CONNECTTIMEOUT => 5
	]);
	$result = curl_exec($ch);
	curl_close($ch);
	return (!empty($result) ? json_decode($result) : false);
}
##----------------------
// function sendAction($chat_id, $action = 'typing')
// {
// 	return bot('sendChatAction', [
// 		'chat_id' => $chat_id,
// 		'action' => $action
// 	]);
// }
##----------------------
function sendMessage($chat_id, $text, $mode = null, $reply = null, $keyboard = null)
{
	return bot('sendMessage', [
		'chat_id' => $chat_id,
		'text' => $text,
		'parse_mode' => $mode,
		'reply_to_message_id' => $reply,
		'reply_markup' => $keyboard,
		'disable_web_page_preview' => true
	]);
}
##----------------------
function respondMessage($text, $keyboard = null, $mode = null)
{
	global $update, $chat_id, $messageid;
	if (isset($update->callback_query)) {
		return bot('editMessageText', [
			'chat_id' => $chat_id,
			'message_id' => $messageid,
			'text' => $text,
			'parse_mode' => $mode,
			'disable_web_page_preview' => true,
			'reply_markup' => $keyboard
		]);
	}
	return sendMessage($chat_id, $text, $mode, null, $keyboard);
}
##----------------------
function sendDocument($chatid, $document, $caption = null)
{
	return bot('sendDocument', [
		'chat_id' => $chatid,
		'document' => $document,
		'caption' => $caption,
		'parse_mode' => 'html'
	]);
}
##----------------------
function forwardMessage($chatid, $from_id, $massege_id)
{
	return bot('forwardMessage', [
		'chat_id' => $chatid,
		'from_chat_id' => $from_id,
		'message_id' => $massege_id
	]);
}
##----------------------
function getChat($chatid)
{
	return bot('getChat', [
		'chat_id' => $chatid
	]);
}
##----------------------
function myFloor($num) {
	if ($num == floor($num)) {
		return floor($num)-1;
	}
	else {
		return floor($num);
	}
}
##----------------------
$update = json_decode(file_get_contents('php://input'));
if (isset($update->message)) {
	$message = $update->message; 
	$chat_id = $message->chat->id;
	$text = $message->text;
	$message_id = $message->message_id;
	$from_id = $message->from->id;
	$user_id = $from_id;
	$tc = $message->chat->type;
	$first_name = $message->from->first_name;
	$last_name = $message->from->last_name;
	$username = $message->from->username;
	$caption = $message->caption;
	$reply = $message->reply_to_message->forward_from->id;
	$reply_id = $message->reply_to_message->from->id;
}
elseif (isset($update->callback_query)) {
	$Data = $update->callback_query->data;
	$data_id = $update->callback_query->id;
	$chatid = $update->callback_query->message->chat->id;
	$chat_id = $update->callback_query->message->chat->id;
	$fromid = $update->callback_query->from->id;
	$from_id = $fromid;
	$first_name = $update->callback_query->from->first_name;
	$user_id = $fromid;
	$tccall = $update->callback_query->chat->type;
	$messageid = $update->callback_query->message->message_id;
	$message_id = $update->callback_query->message->message_id;
	// Inline keyboard shim: map callback data into $text and acknowledge
	$text = $Data;
	bot('answerCallbackQuery', [
		'callback_query_id' => $data_id
	]);
}
else {
	exit();
}
##----------------------
$pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USERNAME, $DB_PASSWORD);
$pdo->exec('SET NAMES utf8mb4');


$pdo->exec("CREATE TABLE IF NOT EXISTS `members` (
        `id` INT(255) NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(255) NOT NULL,
        `time` INT(255) NOT NULL,
        PRIMARY KEY (`id`)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS `bots` (
        `id` int(255) NOT NULL AUTO_INCREMENT,
        `admin` BIGINT(255) NOT NULL,
        `username` varchar(1024),
	`token` varchar(1024),
	`time` int(255) NOT NULL,
        PRIMARY KEY (`id`)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS `sendlist` (
        `id` int(255) NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(255) NOT NULL,
        `message_id` int(255) DEFAULT NULL,
        `offset` int(255) NOT NULL,
        `time` int(255) NOT NULL,
        `type` varchar(255) NOT NULL,
        `data` json NOT NULL,
        `caption` varchar(1024) DEFAULT NULL,
        PRIMARY KEY (`id`)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS `vip_bots` (
        `id` int(255) NOT NULL AUTO_INCREMENT,
        `admin` BIGINT(255) NOT NULL,
        `bot` varchar(1024),
	`start` int(255) NOT NULL,
	`end` int(255) NOT NULL,
        `alert` int(1) NOT NULL,
        PRIMARY KEY (`id`)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS `bots_sendlist` (
        `id` int(255) NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(255) NOT NULL,
	`token` varchar(255) NOT NULL,
	`bot_username` varchar(255) NOT NULL,
        `message_id` int(255) DEFAULT NULL,
        `offset` int(255) NOT NULL,
        `time` int(255) NOT NULL,
        `type` varchar(255) NOT NULL,
        `data` json NOT NULL,
        `caption` varchar(1024) DEFAULT NULL,
        PRIMARY KEY (`id`)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS `xo_games` (
        `id` int(255) NOT NULL AUTO_INCREMENT,
        `message_id` varchar(255) NOT NULL,
        `start` int(255) NOT NULL,
	`time` int(255) NOT NULL,
	`bot` varchar(1024) NOT NULL,
        PRIMARY KEY (`id`)
);");

$db = $pdo->prepare("SELECT * FROM `members` WHERE `user_id`={$user_id}");
$db->execute();
if (!$db->fetch()) {
        $pdo->exec("INSERT INTO `members` (`user_id`, `time`) VALUES ({$user_id}, UNIX_TIMESTAMP());");
}
##----------------------JSON
@$list = json_decode(file_get_contents("Data/list.json"), true);
@$data = json_decode(file_get_contents("Data/$from_id/data.json"), true);
@$step = $data['step'];
##----------------------
if (!is_null($from_id) and !is_dir("Data/$from_id/")) {
	mkdir("Data/$from_id");
	$data = [
		'step' => 'none'
	];
	file_put_contents("Data/$from_id/data.json", json_encode($data));
	if ($list['user'] == null) {
		$list['user'] = [];
	}
	$list['user'][] = $from_id;
	file_put_contents('Data/list.json', json_encode($list));
}
##----------------------
if (!isset($list['bot_count'])) {
	$list['bot_count'] = 5;
	file_put_contents('Data/list.json', json_encode($list));
}
##----------------------
// Fast check for channel membership with timeout - only for non-admin users
if ($from_id != $admin) {
	$get_in_channel_1 = bot('getChatMember', ['chat_id' => $lock_channel_1, 'user_id' => $user_id], API_KEY_CR);
	$in_channel_1 = isset($get_in_channel_1->result->status) ? in_array($get_in_channel_1->result->status, ['creator', 'administrator', 'member']) : true;
	$get_in_channel_2 = bot('getChatMember', ['chat_id' => $lock_channel_2, 'user_id' => $user_id], API_KEY_LOCK_BOT);
	$in_channel_2 = isset($get_in_channel_2->result->status) ? in_array($get_in_channel_2->result->status, ['creator', 'administrator', 'member']) : true;
} else {
	// Admin always has access
	$in_channel_1 = true;
	$in_channel_2 = true;
}
##----------------------Buttons
if ($from_id != $admin) {
	$menu = json_encode(
		[
			'inline_keyboard' => [
				[
					['text'=>'🤖 ربات های من','callback_data'=>'🤖 ربات های من'],['text'=>'🔰 ساخت ربات','callback_data'=>'🔰 ساخت ربات']
				],
				[
					['text'=>'🌈 ثبت تبلیغ','callback_data'=>'🌈 ثبت تبلیغ']
				],
				[
					['text'=>'📕 قوانین','callback_data'=>'📕 قوانین'],['text'=>'📖 راهنما','callback_data'=>'📖 راهنما']
				]
			]
		]);
}
else {
	$menu = json_encode(
		[
			'inline_keyboard' => [
				[
					['text'=>'🤖 ربات های من','callback_data'=>'🤖 ربات های من'],['text'=>'🔰 ساخت ربات','callback_data'=>'🔰 ساخت ربات']
				],
				[
					['text'=>'🌈 ثبت تبلیغ','callback_data'=>'🌈 ثبت تبلیغ']
				],
				[
					['text'=>'📕 قوانین','callback_data'=>'📕 قوانین'],['text'=>'📖 راهنما','callback_data'=>'📖 راهنما']
				],
				[
					['text'=>'🔑 مدیریت','callback_data'=>'🔑 مدیریت']
				]
			]
		]
	);
}

	$ads_menu = json_encode(
		[
			'inline_keyboard' => [
				[
					['text'=>'✏️ ثبت تبلیغ','callback_data'=>'ads_create']
				],
				[
					['text'=>'🗒 لیست تبلیغات','callback_data'=>'ads_list']
				],
				[
					['text'=>'🔙 بازگشت به مدیریت','callback_data'=>'🔙 بازگشت به مدیریت']
				]
			]
		]
	);
##----------------------Dev
$panel = json_encode(
	[
		'inline_keyboard' => [
			[
				['text'=>'🔖 پیام همگانی','callback_data'=>'🔖 پیام همگانی'],['text'=>'🚀 هدایت همگانی','callback_data'=>'🚀 هدایت همگانی']
			],
			[
				['text'=>'🤖 آمار ربات ها','callback_data'=>'🤖 آمار ربات ها'],['text'=>'📊 آمار کاربران','callback_data'=>'📊 آمار کاربران']
			],
			[
				['text'=>'⛔️ لیست کاربران مسدود','callback_data'=>'⛔️ لیست کاربران مسدود']
			],
			[
				['text'=>'🔓 آزاد کردن','callback_data'=>'🔓 آزاد کردن'],['text'=>'🔒 مسدود کردن','callback_data'=>'🔒 مسدود کردن']
			],
			[
				['text'=>'🎖 لیست رباتهای ویژه','callback_data'=>'🎖 لیست رباتهای ویژه']
			],
			[
				['text'=>'➖ اشتراک ویژه','callback_data'=>'➖ اشتراک ویژه'],['text'=>'➕ اشتراک ویژه','callback_data'=>'➕ اشتراک ویژه']
			],
			[
				['text'=>'✖️ حذف ربات','callback_data'=>'✖️ حذف ربات'],['text'=>'🤖 تعداد مجاز','callback_data'=>'🤖 تعداد مجاز']
			],
							[
					['text'=>'💠 تبلیغات','callback_data'=>'ads_main']
				],
			[
				['text'=>'🔙 بازگشت','callback_data'=>'🔙 بازگشت']
			]
		]
	]
);
##----------------------Other
$back = json_encode(
	[
		'inline_keyboard' => [
			[
				['text'=>'🔙 بازگشت','callback_data'=>'🔙 بازگشت']
			]
		]
	]
);
$backpanel = json_encode(
	[
		'inline_keyboard' => [
			[
				['text'=>'🔙 بازگشت به مدیریت','callback_data'=>'🔙 بازگشت به مدیریت']
			]
		]
	]
);
	$backpanelads = json_encode(
		[
			'inline_keyboard' => [
				[
					['text'=>'🔙 بازگشت به تبلیغات','callback_data'=>'ads_main']
				]
			]
		]
	);
$remove = json_encode(
	[
		'KeyboardRemove' => [],
		'remove_keyboard' => true
	]
);
##----------------------
if (in_array($user_id, $list['ban'])) {
	exit();
}
##----------------------
if ($from_id != $admin) {
	if (time()-filectime('Data/flood.json') >= 50*60) {
		unlink('Data/flood.json');
	}

	@$flood = json_decode(file_get_contents('Data/flood.json'), true);
	$now = date('Y-m-d-h-i-a', $update->message->date);
	$flood['flood']["$now-$from_id"] += 1;
	file_put_contents('Data/flood.json', json_encode($flood));

	if ($flood['flood']["$now-$from_id"] >= 25 && $tc == 'private') {
		if ($list['ban'] == null) {
			$list['ban'] = [];
		}
		unlink('Data/flood.json');
		array_push($list['ban'], $from_id);
		file_put_contents("Data/list.json", json_encode($list));
		sendMessage($from_id, "⛔️ شما به دلیل ارسال پیام های مکرر و بیهوده مسدود گردیدید.\n\n🔰 برای آزاد شدن به $support پیام دهید.", 'markdown', null, $remove);
		sendMessage($admin, "👤 کاربر [$from_id](tg://user?id=$from_id) به دلیل ارسال پیام های مکرر و بیهوده از ربات مسدود گردید.\n/unban\_{$from_id}", 'markdown');
		exit();
	}
}
##----------------------
if (strtolower($text) == '/start') {
	sendMessage($chat_id, "😁✋🏻 سلام\n\n👇🏻 یکی از گزینه های زیر را انتخاب کنید.", null, $message_id, $menu);
	$data['step'] = "none";
	file_put_contents("Data/$from_id/data.json",json_encode($data));
}
elseif ($from_id != $admin && (!$in_channel_1 || !$in_channel_2)) {
        $lock_channel_1_emoji = $in_channel_1 ? '✅' : '❌';
        $lock_channel_2_emoji = $in_channel_2 ? '✅' : '❌';
	bot('sendMessage', [
		'chat_id'=>$chat_id,
		'reply_to_message_id'=>$message_id,
		'text'=>"🔰 لطفا برای حمایت از ما و گرفتن اجازه استفاده از ربات در کانال های زیر عضو شوید.

📣{$lock_channel_1_emoji} {$lock_channel_1}
📣{$lock_channel_2_emoji} {$lock_channel_2}",
		'reply_markup'=>json_encode([
			'inline_keyboard'=>[
			[
					['text'=>'/start','callback_data'=>'/start']
			]
			]
		])
	]);
        exit();
}
elseif ($text == "🔙 بازگشت") {
	$data['step'] = "none";
	file_put_contents("Data/$from_id/data.json",json_encode($data));
	respondMessage("🔰 به منوی اصلی خوش آمدید.\n\n👇🏻 یکی از گزینه های زیر را انتخاب کنید.", $menu);
}
elseif ($text == "📕 قوانین") {
	$help_rules_kb = json_encode([
		'inline_keyboard' => [
			[
				['text'=>'📕 قوانین','callback_data'=>'📕 قوانین'],
				['text'=>'📖 راهنما','callback_data'=>'📖 راهنما']
			],
			[
				['text'=>'🔙 بازگشت','callback_data'=>'🔙 بازگشت']
			]
		]
	]);
	respondMessage("📕 *قوانین* :\n\n🔞 هرگونه *مسائل خلاف شرع و مستهجن* ممنوع است.\n🚷 نقض *قوانین جمهوری اسلامی ایران* ممنوع است.\n🚯 ارسال پیام های مکرر و بیهوده (*SPAM*)  ممنوع است.\n\n⛔️ تخطی از موارد ذکر شده *مسدود شدن دائمی* شما را در پی خواهد داشت.", $help_rules_kb, 'markdown');
}
elseif ($text == "📖 راهنما" || strtolower($text) == '/help') {
	$help_rules_kb = json_encode([
		'inline_keyboard' => [
			[
				['text'=>'📕 قوانین','callback_data'=>'📕 قوانین'],
				['text'=>'📖 راهنما','callback_data'=>'📖 راهنما']
			],
			[
				['text'=>'🔙 بازگشت','callback_data'=>'🔙 بازگشت']
			]
		]
	]);
	respondMessage("📖 آموزش ایجاد ربات پیامرسان :\n\n1⃣ ابتدا به ربات @BotFather رفته و دستور /start را می فرستید.\n2⃣ حالا برای ساخت یک ربات جدید دستور /newbot را می فرستید.\nربات پیام زیر را برای شما می فرستد :\nAlright, a new bot. How are we going to call it? Please choose a name for your bot.\n3⃣ یک نام برای ربات خود انتخاب کنید و بفرستید.\nربات در پاسخ پیام زیر را میفرستد :\nGood. Now let's choose a username for your bot. It must end in bot. Like this, for example: TetrisBot or tetris_bot.\nربات در این پیام می گوید :« اکنون می بایست برای ربات خود یک نام کاربری انتخاب کنید. نام کاربری ای که انتخاب می کنید باید به کلمهٔ bot ختم شود. به عنوان مثال TetrisBot یا tetris_bot»\n4⃣ اگر نام کاربری ای که فرستادید به bot ختم نشده باشد ربات به صورت زیر پاسخ می دهد و می گوید :« نام کاربری حتما باید به کلمه bot ختم شود »\nSorry, the username must end in 'bot'. E.g. 'Tetris_bot' or 'Tetrisbot'\nاگر نام کاربری که فرستادید قبلا توسط فرد دیگری گرفته شده باشد ربات پاسخ زیر را برای شما می فرستد و می گوید :« این نام کاربری قبلا توسط فرد دیگری گرفته شده است، لطفا یک نام کاربری بدون مالک ارسال کنید»\nSorry, this username is already taken. Please try something different.", $help_rules_kb, '');
}
elseif ($text == "🔰 ساخت ربات") {
	$count_bot = count($data['bots']);
	if ( ($count_bot<$list['bot_count']) or $from_id == $admin) {
		$data['step'] = "create";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		$kb = json_encode(['inline_keyboard'=>[[['text'=>'🔙 بازگشت','callback_data'=>'🔙 بازگشت']]]]);
		respondMessage("🤖 توکن رباتت رو که از @BotFather گرفتی برام هدایت (فروارد) کن\n\n📕 اگه راهنمایی لازم داری دستور /help رو ارسال کن", $kb);
	}
	else {
		if ($list['bot_count'] < 1) {
			respondMessage("🎃 امکان ساخت ربات توسط مدیریت غیر فعال شده است.\n\n🤠 لطفا زمانی دیگر دوباره امتحان کنید.", $menu, 'markdown');
		}
		else {
			respondMessage("🎃 هر کاربر تنها می تواند *$list[bot_count]* ربات بسازد.\n\n🤖 شما اکنون *$count_bot* ربات دارید و امکان ساخت ربات های بیشتر از شما سلب شده است.\n\n🌈 برای ساخت رباتی جدید باید ربات های قدیمی خود را حذف کنید.", $menu, 'markdown');
		}
	}
}
elseif ($step == "create") {
	$count_bot = count($data['bots']);
	if ( ($count_bot<$list['bot_count']) or $from_id == $admin) {
		if (!preg_match('|(?<token>[0-9]+\:[a-zA-Z0-9\-\_]+)|ius', $text, $matches)) {
			sendMessage($chat_id, "⛔️ توکن ارسالی نامعتبر است.", null, $message_id, $back);
			exit();
		}
		$token = $matches['token'];
		$result = bot('getMe', [], $token);
		$ok = $result->ok ?? false;
		if ($ok) {
			$un = strtolower($result->result->username);
			if (!file_exists("Bots/$un/config.php")) {

				$pdo->exec("CREATE TABLE IF NOT EXISTS `{$un}_members` (
					`id` INT(255) NOT NULL AUTO_INCREMENT,
					`user_id` BIGINT(255) NOT NULL,
					`time` INT(255) NOT NULL,
					PRIMARY KEY (`id`)
				);");

				$prepared = $pdo->prepare("SELECT * FROM `bots` WHERE `username`='{$un}';");
				$prepared->execute();
				$fetch = $prepared->fetchAll();
				if (count($fetch) <= 0) {
					$pdo->exec("INSERT INTO `bots` (`admin`, `username`, `token`, `time`) VALUES ({$user_id}, '{$un}', '{$token}', UNIX_TIMESTAMP());");
				}

				$config = file_get_contents("Source/config.php");
				$config = str_replace("**ADMIN**", $from_id, $config);
				$config = str_replace("**TOKEN**", $token, $config);
				$config = str_replace("**URL**", "$host_folder/Bots/$un/", $config);
				mkdir("Bots/$un");
				mkdir("Bots/$un/data");
				copy('Source/index.php', "Bots/$un/bot.php");
				file_put_contents("Bots/$un/config.php", $config);
				// Clear old updates
				$delete_updates = bot('getUpdates', [], $token);
				if (isset($delete_updates->result) && count($delete_updates->result) > 0) {
					$count_updates = count($delete_updates->result) - 1;
					$last_update_id = $delete_updates->result[$count_updates]->update_id + 1;
					bot('getUpdates', ['offset' => $last_update_id], $token);
				}
				// Send success message to user
				$txt = "✅ ربات شما با موفقیت ساخته شد.\n💠 برای مشاهده امکانات ربات دستور /start را ارسال نمایید.\n\n📣 کانال : " . $main_channel;
				bot('sendMessage', ['chat_id' => $from_id, 'text' => $txt, 'disable_web_page_preview' => true], $token);
				// Set webhook
				bot('setWebhook', ['url' => "$host_folder/Bots/$un/bot.php", 'max_connections' => 1, 'allowed_updates' => json_encode(["message","callback_query","inline_query"])], $token);
				$data['step'] = "none";
				$data['bots'][] = "@$un";
				file_put_contents("Data/$from_id/data.json",json_encode($data));
				$keyboard = json_encode
				(
					[
						'inline_keyboard' => [
							[['text' => '🤖 @' . $un, 'url' => 'https://telegram.me/' . $un . '?start']],
							[['text' => '🔙 بازگشت', 'callback_data' => '🔙 بازگشت']]
						]
					]
				);
				sendMessage($chat_id, "✅ ربات شما با موفقیت به سرور ما متصل گردید.\n\n🤖 <a href='https://telegram.me/$un?start'>@$un</a>", 'html', $message_id, $keyboard);
				$first_name = str_replace(["<", ">"], null, $first_name);
				sendMessage($logchannel, "id: <code>$from_id</code>\n👤 کاربر <a href='tg://user?id=$from_id'>$first_name</a>\nربات خود را با یوزرنیم « @$un » ایجاد کرد.\n🤖 توکن ربات :\n<code>$token</code>", 'html', null);
			} else {
				$data['step'] = "none";
				file_put_contents("Data/$from_id/data.json",json_encode($data));
				sendMessage($chat_id, "⛔️ این ربات از قبل به سرور ما متصل بود.", null, $message_id, $menu);
			}
		} else {
			sendMessage($chat_id, "⛔️ توکن ارسالی نامعتبر است.", null, $message_id, $back);
		}
	}
	else {
		if ($list['bot_count'] < 1) {
			sendMessage($chat_id, "🎃 امکان ساخت ربات توسط مدیریت غیر فعال شده است.\n\n🤠 لطفا زمانی دیگر دوباره امتحان کنید.", 'markdown', $message_id, $menu);
		}
		else {
			sendMessage($chat_id, "🎃 هر کاربر تنها می تواند *$list[bot_count]* ربات بسازد.\n\n🤖 شما اکنون *$count_bot* ربات دارید و امکان ساخت ربات های بیشتر از شما سلب شده است.\n\n🌈 برای ساخت رباتی جدید باید ربات های قدیمی خود را حذف کنید.", 'markdown', $message_id, $menu);
		}
	}
}
elseif ($text == '🤖 ربات های من' || $text == '🔙 بازگشت به ربات ها') {
	if (!empty($data['bots'])) {
		$data['step'] = 'show_bot';
		file_put_contents("Data/{$from_id}/data.json", json_encode($data));

		$inline = [];
		foreach ($data['bots'] as $user_bot) {
			$inline[] = [ ['text' => "👉🏻🤖 {$user_bot}", 'callback_data' => "👉🏻🤖 {$user_bot}"] ];
		}
		$inline[] = [ ['text' => '🔙 بازگشت', 'callback_data' => '🔙 بازگشت'] ];
		$kb = json_encode(['inline_keyboard'=> $inline]);
		respondMessage("🔰 ربات مورد نظرتان را از لیست زیر انتخاب کنید.", $kb);
	} else {
		respondMessage("❌ شما هیچ رباتی نساخته اید.", $menu);
	}
}
elseif ($data['step'] == 'show_bot' && preg_match('#\@(?<bot>[a-zA-Z0-9\_]+bot)#usi', $text, $matches) || ($text == '🔙 بازگشت به ربات' && preg_match('#token\_(?<bot>.+)#', $data['step'], $matches))) {
	$bot = strtolower($matches['bot']);

	if (in_array("@{$bot}", $data['bots'])) {
		$data['step'] = "manage_{$bot}";
		file_put_contents("Data/{$from_id}/data.json", json_encode($data));
			
			$bot_management_keyboard = json_encode([
				'inline_keyboard' => [
					[
						['text' => '💾 پشتیبان گیری', 'callback_data' => "backup_{$bot}"],
						['text' => '🔰 اطلاعات', 'callback_data' => "info_{$bot}"]
					],
					[
						['text' => '🗑 حذف ربات', 'callback_data' => "delete_{$bot}"],
						['text' => '♻️ تغییر توکن', 'callback_data' => "token_{$bot}"]
					],
					[
						['text' => '🔙 بازگشت به ربات ها', 'callback_data' => '🔙 بازگشت به ربات ها']
					]
				]
			]);
			
			respondMessage("🤖 ربات @{$bot} انتخاب شد.
🔰 چه کاری می خواهید انجام دهید؟", $bot_management_keyboard);
		}
		else {
			respondMessage("❌ شما هیچ رباتی با نام کاربری @{$bot} ندارید.", $menu);
		}
	}
## Bot management is now handled by callback queries above
elseif (isset($update->message) && preg_match('#token\_(?<bot>.+)#', $data['step'], $matches) ) {
	$bot = $matches['bot'];

	if (in_array("@{$bot}", $data['bots'])) {
		if (preg_match('|(?<token>[0-9]+\:[a-zA-Z0-9\-\_]+)|ius', $text, $matches)) {
			$bot_token = $matches['token'];

			$get_bot = json_decode(file_get_contents("https://api.telegram.org/bot{$bot_token}/getMe"), true);
			if ($get_bot['ok'] == true) {
				if (strtolower($get_bot['result']['username']) == $bot) {
					$data['step'] = "manage_{$bot}";
					file_put_contents("Data/{$from_id}/data.json", json_encode($data));

					$folder_url = "{$host_folder}/Bots/{$bot}/";
					$bot_config = file_get_contents("Bots/{$bot}/config.php");
					$bot_config = file_get_contents('Source/config.php');
					$bot_config = str_replace('**ADMIN**', $from_id, $bot_config);
					$bot_config = str_replace('**TOKEN**', $bot_token, $bot_config);
					$bot_config = str_replace('**URL**', $folder_url, $bot_config);
					file_put_contents('Bots/' . $bot . '/config.php', $bot_config);

					file_get_contents("https://api.telegram.org/bot{$bot_token}/setWebhook?url={$folder_url}bot.php&max_connections=1&allowed_updates=[\"message\",\"callback_query\",\"inline_query\"]");

					bot('sendMessage', [
						'chat_id'=>$chat_id,
						'reply_to_message_id'=>$message_id,
						'text'=>"✅ توکن ربات @{$bot} تغییر کرد.",

					]);
				}
				else {
					sendMessage($chat_id, "❌ توکن باید مربوط به ربات @{$bot} باشد.
🚫 این توکن مربوط به ربات @{$get_bot['result']['username']} است.", null, $message_id);
				}
			}
			else {
				sendMessage($chat_id, "⛔️ توکن ارسالی نامعتبر است.", null, $message_id);
			}
		}
		else {
			sendMessage($chat_id, "⛔️ توکن ارسالی نامعتبر است.", null, $message_id);
		}
	}
	else {
		if (!empty($data['bots'])) {
			$data['step'] = 'show_bot';
			file_put_contents("Data/{$from_id}/data.json", json_encode($data));
	
			respondMessage("❌ ربات @{$bot} حذف شده است.", $menu);
		} else {
			$data['step'] = '';
			file_put_contents("Data/$from_id/data.json", json_encode($data));
			sendMessage($chat_id, "❌ ربات @{$bot} حذف شده است.", null, $message_id, $menu);
		}
	}
}
elseif (preg_match('#^nodelete\_(?<bot>.+)$#', $update->callback_query->data, $matches)) {
	bot('editMessagetext', [
		'chat_id'=>$chatid,
		'message_id'=>$messageid,
		'parse_mode'=>'html',
		'disable_web_page_preview'=>true,
		'text'=>"✅ شما از حذف کردن ربات @{$matches['bot']} منصرف شدید."
	]);
}
elseif (preg_match('#^delete\_(?<bot>.+)$#', $update->callback_query->data, $matches)) {
	$inline_keyboard = [
		[
			['text' => "❌ بله", 'callback_data' => "yesdelete_{$matches['bot']}"],
			['text' => "✅ خیر", 'callback_data' => "nodelete_{$matches['bot']}"]
		]
	];
	$inline_keyboard = json_encode([
		'inline_keyboard' => $inline_keyboard
	]);

	bot('editMessagetext', [
		'chat_id'=>$chatid,
		'message_id'=>$messageid,
		'parse_mode'=>'html',
		'disable_web_page_preview'=>true,
		'reply_markup' => $inline_keyboard,
		'text'=>"❓ آیا واقعا می خواهید ربات @{$matches['bot']} را حذف کنید؟"
	]);
	bot('AnswerCallbackQuery',
	[
		'callback_query_id'=>$update->callback_query->id,
		'text'=>''
	]);
	
}
elseif (preg_match('#^yesdelete\_(?<bot>.+)$#', $update->callback_query->data, $matches)) {
	$botid = $matches['bot'];

	if (in_array('@' . $botid, $data['bots'])) {
		$prepared = $pdo->prepare("SELECT * FROM `{$botid}_members`;");
		$prepared->execute();
		$fetch = $prepared->fetchAll(PDO::FETCH_ASSOC);
		file_put_contents("Bots/{$botid}/data/members.json", json_encode($fetch));
		$file_to_zip = array(
			"Bots/{$botid}/data/list.json",
			"Bots/{$botid}/data/data.json",
			"Bots/{$botid}/data/members.json"
		);
		$file_name = date('Y-m-d') . '_' . $botid . '_backup.zip';
		CreateZip($file_to_zip, $file_name, "{$botid}_147852369");
		$time = date('Y/m/d - H:i:s');

		if ((preg_match('#token\_(?<bot>.+)#', $data['step'], $matches) || preg_match('#manage\_(?<bot>.+)#', $data['step'], $matches) || $data['step'] == 'show_bot') && !empty( array_diff($data['bots'], ['@' . $botid]) )) {
			bot('sendDocument', [
				'chat_id' => $chat_id,
				'parse_mode' => 'html',
				'document' => $zipfile = new CURLFile($file_name),
				'caption' => "💾 نسخه پشتیبان\n\n🕰 <i>$time</i>\n\n👆🏻 این فایل شامل تمامی اطلاعات ربات @{$botid} است تا اگر دوباره خواستید رباتتان را به سرویس ما وصل کنید اطلاعات برگردانده شود."
			]);
			$data['step'] = 'show_bot';
		}
		elseif (preg_match('#token\_(?<bot>.+)#', $data['step'], $matches) || preg_match('#manage\_(?<bot>.+)#', $data['step'], $matches) || $data['step'] == 'show_bot') {
			bot('sendDocument', [
				'chat_id' => $chat_id,
				'parse_mode' => 'html',
				'document' => $zipfile = new CURLFile($file_name),
				'caption' => "💾 نسخه پشتیبان\n\n🕰 <i>$time</i>\n\n👆🏻 این فایل شامل تمامی اطلاعات ربات @{$botid} است تا اگر دوباره خواستید رباتتان را به سرویس ما وصل کنید اطلاعات برگردانده شود."
			]);
			$data['step'] = 'none';
		}

		// Send panel/menu as a separate message (no buttons attached to the document)
		sendMessage($chat_id, "👇🏻 برای ادامه مدیریت، از منوی زیر استفاده کنید.", null, null, $menu);

		unlink($file_name);
		unlink("Bots/{$botid}/data/members.json");

		
		$pdo->exec("DROP TABLE IF EXISTS `{$botid}_members`;");
		$prepare = $pdo->prepare("DELETE FROM `bots` WHERE `username`='{$botid}';");
		$prepare->execute();

		$prepare = $pdo->prepare("DELETE FROM `bots_sendlist` WHERE `bot_username`='{$botid}';");
		$prepare->execute();

		$config = file_get_contents("Bots/".$botid."/config.php");
		preg_match_all('/\$Token\s=\s"(.*?)";/', $config, $match);
		file_get_contents("https://api.telegram.org/bot".$match[1][0]."/deleteWebHook");
		deleteFolder("Bots/$botid");
		$search = array_search("@".$botid, $data['bots']);
		unset($data['bots'][$search]);
		$data['bots'] = array_values($data['bots']);
		file_put_contents("Data/$from_id/data.json",json_encode($data));

		bot('editMessagetext', [
			'chat_id'=>$chatid,
			'message_id'=>$messageid,
			'parse_mode'=>'html',
			'disable_web_page_preview'=>true,
			'text'=>"✅ ربات « @$botid » با موفقیت حذف گردید."
		]);
		bot('AnswerCallbackQuery',
		[
			'callback_query_id'=>$update->callback_query->id,
			'text'=>''
		]);

		$first_name = str_replace(["<", ">"], null, $first_name);
		sendMessage($logchannel, "id: <code>$from_id</code>\n👤 کاربر <a href='tg://user?id=$from_id'>$first_name</a>\nربات خود را با یوزرنیم « @$botid » از سرور حذف کرد.", 'html', null);
	}
	else {
		bot('editMessagetext', [
			'chat_id'=>$chatid,
			'message_id'=>$messageid,
			'parse_mode'=>'html',
			'disable_web_page_preview'=>true,
			'text'=>"❌ عملیات حذف ربات با مشکل مواجه شد."
		]);
	}
}
elseif ($text == "🌈 ثبت تبلیغ") {
	$inline_keyboard = json_encode([
			'inline_keyboard' => [
			[['text'=>"🌈 $support", 'url'=>'https://telegram.me/' . str_replace('@', '', $support)]],
			[['text'=>'🔙 بازگشت','callback_data'=>'🔙 بازگشت']]
		]
	]);
	respondMessage("👇🏻 برای ثبت تبلیغات خود برای نمایش در ربات های ساخته شده توسط این سرویس به ربات زیر مراجعه نمایید.", $inline_keyboard, 'markdown');
}
##------------------- Bot Management Callback Queries
elseif (preg_match('#^info\_(?<bot>.+)$#', $text, $matches)) {
	$bot = $matches['bot'];
	
	$bot_config = file_get_contents("Bots/{$bot}/config.php");
	preg_match('/\$Token\s=\s"(.*?)";/', $bot_config, $match);
	$bot_token = $match[1];
	$folder_url = "{$host_folder}/Bots/{$bot}/";

	$get_bot = json_decode(file_get_contents("https://api.telegram.org/bot{$bot_token}/getMe"), true);
	if ($get_bot['ok'] == true) {
		$can_join_groups = $get_bot['result']['can_join_groups'] == true ? '✅' : '❌';
		$can_read_all_group_messages = $get_bot['result']['can_read_all_group_messages'] == true ? '✅' : '❌';
		$supports_inline_queries = $get_bot['result']['supports_inline_queries'] == true ? '✅' : '❌';

		$webhook_info = json_decode(file_get_contents("https://api.telegram.org/bot{$bot_token}/getWebhookInfo"), true);

		if (isset($webhook_info['result']['pending_update_count'])) {
			$pending_update_count = "\n♻️ پیام های در صف انتظار : {$webhook_info['result']['pending_update_count']}";
		}
		else {
			$pending_update_count = '';
		}
		if (isset($webhook_info['result']['url']) && $webhook_info['result']['url'] != "{$folder_url}bot.php") {
			file_get_contents("https://api.telegram.org/bot{$bot_token}/setWebhook?url={$folder_url}bot.php&max_connections=1&allowed_updates=[\"message\",\"callback_query\",\"inline_query\"]");

			$answer_text = "✅ مشکل وبهوک ربات حل گردید.

📎 توکن ربات : {$bot_token}
🆔 شناسه عددی ربات : {$get_bot['result']['id']}
🤖 نام ربات : {$get_bot['result']['first_name']}
👤 نام کاربری ربات : @{$get_bot['result']['username']}
👥 امکان عضویت در گروه : {$can_join_groups}
🧐 امکان خواندن همه پیام های گروه : {$can_read_all_group_messages}
📥 پشتیبانی از حالت درون خطی : {$supports_inline_queries}{$pending_update_count}";
		}
		else {
			$answer_text = "📎 توکن ربات : {$bot_token}
🆔 شناسه عددی ربات : {$get_bot['result']['id']}
🤖 نام ربات : {$get_bot['result']['first_name']}
👤 نام کاربری ربات : @{$get_bot['result']['username']}
👥 امکان عضویت در گروه : {$can_join_groups}
🧐 امکان خواندن همه پیام های گروه : {$can_read_all_group_messages}
📥 پشتیبانی از حالت درون خطی : {$supports_inline_queries}{$pending_update_count}";
		}

		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text' => '🔙 بازگشت به ربات ها', 'callback_data' => '🔙 بازگشت به ربات ها']]
			]
		]);
		
		respondMessage($answer_text, $back_keyboard);
	}
	else {
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text' => '🔙 بازگشت به ربات ها', 'callback_data' => '🔙 بازگشت به ربات ها']]
			]
		]);
		
		respondMessage("❌ توکن ثبت شده برای ربات @{$bot} از کار افتاده است.
✅ لطفا توکن جدید رباتتان را از @BotFather دریافت کنید و با استفاده از دکمه «♻️ تغییر توکن» آنرا ثبت کنید.", $back_keyboard);
	}
}
elseif (preg_match('#^backup\_(?<bot>.+)$#', $text, $matches)) {
	$bot = $matches['bot'];
	
	$prepared = $pdo->prepare("SELECT * FROM `{$bot}_members`;");
	$prepared->execute();
	$fetch = $prepared->fetchAll(PDO::FETCH_ASSOC);
	file_put_contents("Bots/{$bot}/data/members.json", json_encode($fetch));
	$file_to_zip = array(
		"Bots/{$bot}/data/list.json",
		"Bots/{$bot}/data/data.json",
		"Bots/{$bot}/data/members.json"
	);
	$file_name = date('Y-m-d') . '_' . $bot . '_backup.zip';
	CreateZip($file_to_zip, $file_name, "{$bot}_147852369");
	$zipfile = new CURLFile($file_name);
	$time = date('Y/m/d - H:i:s');
	
	sendDocument($chat_id, $zipfile, "💾 نسخه پشتیبان\n\n🕰 <i>$time</i>");
	
	unlink($file_name);
	unlink("Bots/{$bot}/data/members.json");
}
elseif (preg_match('#^token\_(?<bot>.+)$#', $text, $matches)) {
	$bot = $matches['bot'];
	
	$bot_config = file_get_contents("Bots/{$bot}/config.php");
	preg_match('/\$Token\s=\s"(.*?)";/', $bot_config, $match);
	$bot_token = $match[1];

	$get_bot = json_decode(file_get_contents("https://api.telegram.org/bot{$bot_token}/getMe"), true);

	if ($get_bot['ok'] == true) {
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text' => '🔙 بازگشت به ربات ها', 'callback_data' => '🔙 بازگشت به ربات ها']]
			]
		]);
		
		respondMessage("❌ توکن ربات @{$bot} سالم است و نیاز به تغییر ندارد.", $back_keyboard);
	}
	else {
		$data['step'] = "token_{$bot}";
		file_put_contents("Data/{$from_id}/data.json", json_encode($data));

		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text' => '🔙 بازگشت به ربات ها', 'callback_data' => '🔙 بازگشت به ربات ها']]
			]
		]);
		
		respondMessage("🔰 لطفا توکن جدید ربات @{$bot} را ارسال کنید.", $back_keyboard);
	}
}
##------------------- Ads Management Callback Queries
elseif (preg_match('#^ads_main$#', $text)) {
	$data['step'] = "none";
	file_put_contents("Data/$from_id/data.json", json_encode($data));
	respondMessage("🧮 به بخش تبلیغات ربات خوش آمدید.\n✏️ لطفا یکی از دکمه های زیر را انتخاب کنید.", $ads_menu, 'markdown');
}
elseif (preg_match('#^ads_create$#', $text)) {
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (count($ads) > 5) {
		respondMessage("🚨 امکان ثبت بیش از 5 تبلیغ وجود ندارد.\n🔰 لطفا ابتدا از بخش « 🗑 حذف تبلیغ » اقدام به حذف برخی تبلیغات قدیمی نمایید.", $ads_menu, 'markdown');
	} else {
		$data['step'] = "setads";
		file_put_contents("Data/$from_id/data.json", json_encode($data));
		respondMessage("🔰 لطفا تبلیغ مورد نظر خود را بفرستید.", $backpanelads);
	}
}
elseif (preg_match('#^ads_list$#', $text)) {
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	$count = count($ads);
	if ($count < 1) {
		respondMessage('❗️ هیچ تبلیغی ثبت نشده است.', $ads_menu);
	} else {
		$ads_list_text = "📊 تعداد : $count\n\n";
		$inline_keyboard = [];
		foreach ($ads as $key => $ad) {
			$ads_list_text .= "🔦 کد : $key\n";
			$ads_list_text .= "🔦 نوع : " . str_replace(['video', 'photo', 'document', 'text'], ['🎥 ویدیو', '🌠 تصویر', '📎 فایل', '📃 متن'], $ad['type']) . "\n";
			$ads_list_text .= "🧭 تعداد بازدید : " . $ad['count'] . "\n";
			$ads_list_text .= "🔰 نمایش : " . ($ad['on'] == true ? '✅ بله' : '❌ خیر') . "\n";
			$ads_list_text .= "📌 دکمه شیشه ای : " . ($ad['keyboard'] == null ? '❌ ندارد' : '✅ دارد') . "\n";
			$ads_list_text .= "\n";
			$inline_keyboard[] = [
				['text' => "🗑 حذف تبلیغ کد $key", 'callback_data' => "ads_delete_$key"],
				['text' => ($ad['on'] ? '❌ غیرفعال' : '✅ فعال'), 'callback_data' => "ads_toggle_$key"]
			];
		}
		$inline_keyboard[] = [['text' => '🔙 بازگشت به تبلیغات', 'callback_data' => 'ads_main']];
		$reply_markup = json_encode(['inline_keyboard' => $inline_keyboard]);
		respondMessage($ads_list_text, $reply_markup);
	}
}
elseif (preg_match('#^ads_delete_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (!isset($ads[$ad_code])) {
		respondMessage('❗️ تبلیغ مورد نظر شما وجود ندارد.', $ads_menu);
	} else {
		$data['step'] = "delete-$ad_code";
		file_put_contents("Data/$from_id/data.json", json_encode($data));
		
		// نمایش اطلاعات تبلیغ در همین پیام
		$type_display = str_replace(['video', 'photo', 'document', 'text'], ['🎥 ویدیو', '🌠 تصویر', '📎 فایل', '📃 متن'], $ads[$ad_code]['type']);
		$has_keyboard = $ads[$ad_code]['keyboard'] != null ? '✅ دارد' : '❌ ندارد';
		$status = $ads[$ad_code]['on'] ? '✅ فعال' : '❌ غیرفعال';
		
		$preview_text = "🗑 حذف تبلیغ کد $ad_code\n\n";
		$preview_text .= "📋 جزئیات تبلیغ:\n";
		$preview_text .= "🔦 نوع: $type_display\n";
		$preview_text .= "📝 متن: " . mb_substr($ads[$ad_code]['text'], 0, 100) . (mb_strlen($ads[$ad_code]['text']) > 100 ? '...' : '') . "\n";
		$preview_text .= "🔘 دکمه شیشه‌ای: $has_keyboard\n";
		$preview_text .= "🎯 وضعیت: $status\n";
		$preview_text .= "📊 تعداد بازدید: " . $ads[$ad_code]['count'] . "\n\n";
		$preview_text .= "⚠️ آیا از حذف این تبلیغ مطمئن هستید؟";
		
		$delete_confirm_keyboard = json_encode([
			'inline_keyboard' => [
				[
					['text' => '👀 مشاهده تبلیغ', 'callback_data' => "ads_preview_$ad_code"]
				],
				[
					['text' => '🗑 بله، حذف کن', 'callback_data' => "ads_delete_confirm_$ad_code"],
					['text' => '❌ انصراف', 'callback_data' => 'ads_list']
				]
			]
		]);
		respondMessage($preview_text, $delete_confirm_keyboard);
	}
}
elseif (preg_match('#^ads_delete_confirm_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (isset($ads[$ad_code])) {
		unset($ads[$ad_code]);
		file_put_contents('Data/ads.json', json_encode($ads));
		respondMessage('✅ تبلیغ مورد نظر شما با موفقیت حذف شد.', $ads_menu);
	} else {
		respondMessage('❗️ تبلیغ مورد نظر شما وجود ندارد.', $ads_menu);
	}
	$data['step'] = "none";
	file_put_contents("Data/$from_id/data.json", json_encode($data));
}
elseif (preg_match('#^ads_toggle_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (isset($ads[$ad_code])) {
		$ads[$ad_code]['on'] = !$ads[$ad_code]['on'];
		file_put_contents('Data/ads.json', json_encode($ads));
		$status = $ads[$ad_code]['on'] ? 'فعال' : 'غیرفعال';
		respondMessage("✅ وضعیت نمایش تبلیغ تغییر یافت. حالت فعلی: $status", $ads_menu);
	} else {
		respondMessage('❗️ تبلیغ مورد نظر شما وجود ندارد.', $ads_menu);
	}
}
elseif (preg_match('#^ads_nokeyboard_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (isset($ads[$ad_code])) {
		$ads[$ad_code]['keyboard'] = null;
		file_put_contents('Data/ads.json', json_encode($ads));
		
		// نمایش پیش‌نمایش نهایی
		$type = $ads[$ad_code]['type'];
		$method = str_replace(['video', 'photo', 'document', 'text'], ['sendVideo', 'sendPhoto', 'sendDocument', 'sendMessage'], $type);
		$dataa = [
			'chat_id' => $chat_id,
			'parse_mode' => 'html'
		];
		if ($type == 'text') {
			$dataa['text'] = $ads[$ad_code]['text'];
			$dataa['disable_web_page_preview'] = true;
		} else {
			$dataa[$type] = 'https://telegram.me/' . str_replace('@', '', $public_logchannel) . '/' . $ads[$ad_code]['file_id'];
			$dataa['caption'] = $ads[$ad_code]['text'];
		}
		bot($method, $dataa);
		
		$final_confirm_keyboard = json_encode([
			'inline_keyboard' => [
				[
					['text' => '✅ تأیید و ثبت نهایی', 'callback_data' => "ads_final_confirm_$ad_code"],
					['text' => '❌ لغو', 'callback_data' => "ads_cancel_$ad_code"]
				]
			]
		]);
		respondMessage("👆🏻 تبلیغ مورد نظر به شرح بالا است (بدون دکمه شیشه‌ای).\n💠 آیا از ثبت نهایی آن مطمئن هستید؟", $final_confirm_keyboard);
		
		$data['step'] = "none";
		file_put_contents("Data/$from_id/data.json", json_encode($data));
	}
}
elseif (preg_match('#^ads_final_confirm_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (isset($ads[$ad_code])) {
		$ads[$ad_code]['on'] = true;
		file_put_contents('Data/ads.json', json_encode($ads));
		respondMessage("✅ تبلیغ مورد نظر شما با موفقیت ثبت شد.", $ads_menu);
	} else {
		respondMessage('❗️ خطا در ثبت تبلیغ.', $ads_menu);
	}
	$data['step'] = "none";
	file_put_contents("Data/$from_id/data.json", json_encode($data));
}
elseif (preg_match('#^ads_cancel_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (isset($ads[$ad_code])) {
		unset($ads[$ad_code]);
		file_put_contents('Data/ads.json', json_encode($ads));
	}
	respondMessage("❌ ثبت تبلیغ لغو شد.", $ads_menu);
	$data['step'] = "none";
	file_put_contents("Data/$from_id/data.json", json_encode($data));
}
elseif (preg_match('#^ads_preview_([0-9]+)$#', $text, $matches)) {
	$ad_code = $matches[1];
	$ads = json_decode(file_get_contents('Data/ads.json'), true);
	if (isset($ads[$ad_code])) {
		// نمایش تبلیغ واقعی
		$type = $ads[$ad_code]['type'];
		$method = str_replace(['video', 'photo', 'document', 'text'], ['sendVideo', 'sendPhoto', 'sendDocument', 'sendMessage'], $type);
		$dataa = [
			'chat_id' => $chat_id,
			'parse_mode' => 'html'
		];
		if ($type == 'text') {
			$dataa['text'] = $ads[$ad_code]['text'];
			$dataa['disable_web_page_preview'] = true;
		} else {
			$dataa[$type] = 'https://telegram.me/' . str_replace('@', '', $public_logchannel) . '/' . $ads[$ad_code]['file_id'];
			$dataa['caption'] = $ads[$ad_code]['text'];
		}
		if ($ads[$ad_code]['keyboard'] != null) {
			$dataa['reply_markup'] = json_encode($ads[$ad_code]['keyboard']);
		}
		bot($method, $dataa);
		
		// ارسال پیام توضیحی
		$back_to_delete_keyboard = json_encode([
			'inline_keyboard' => [
				[
					['text' => '🗑 حذف این تبلیغ', 'callback_data' => "ads_delete_$ad_code"],
					['text' => '🔙 بازگشت به لیست', 'callback_data' => 'ads_list']
				]
			]
		]);
		sendMessage($chat_id, "👆🏻 این تبلیغ به شرح بالا است.", null, null, $back_to_delete_keyboard);
	} else {
		respondMessage('❗️ تبلیغ مورد نظر شما وجود ندارد.', $ads_menu);
	}
}
##----------------------
if ($from_id == $admin && $chat_id > 0) {
	if ($text == "🔑 مدیریت" || $text == "🔙 بازگشت به مدیریت") {
		$data['step'] = "none";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		respondMessage("👇🏻یکی از دکمه های زیر را انتخاب کنید :", $panel);
	}
	elseif ($text == '🤖 تعداد مجاز') {
		$data['step'] = "count_bots";
		file_put_contents("Data/$from_id/data.json", json_encode($data));
		respondMessage("🤖 هر کاربر می تواند چند ربات بسازد؟\n👀 تعداد : $list[bot_count]\n🎃 لطفا یک عدد ارسال کنید.", $backpanel, 'markdown');
	}
	elseif ($step == 'count_bots') {
		$number = convert($text);
		if (!is_numeric($number)) {
			respondMessage("🎃 لطفا یک عدد ارسال کنید.", $backpanel, 'markdown');
		}
		else {
			$data['step'] = "none";
			file_put_contents("Data/$from_id/data.json",json_encode($data));
			$list['bot_count'] = $number;
			file_put_contents('Data/list.json', json_encode($list));
			respondMessage("👈🏻 محدودیت ساخت ربات بر روی $number عدد تنظیم گردید.", $panel);
		}
	}
	elseif ($text == '💠 تبلیغات' || $text == '🔙 بازگشت به تبلیغات') {
		$data['step'] = "none";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		respondMessage("🧮 به بخش تبلیغات ربات خوش آمدید.\n✏️ لطفا یکی از دکمه های زیر را انتخاب کنید.", $ads_menu, 'markdown');
	}
	## Old ads management code removed - replaced with callback query based system
	## Step 'setads' now handled when user starts creating an ad and sends content
	elseif ($step == 'setads') {
		$ad_code = time();
		$ads = json_decode(file_get_contents('Data/ads.json'), true);
		if (isset($message->video)) {
			$type = 'video';
			$file_id = bot('sendVideo', [
				'chat_id' => $public_logchannel,
				'video' => $message->video->file_id
			])->result->message_id;
		}
		elseif (isset($message->photo)) {
			$type = 'photo';
			$file_id = bot('sendPhoto', [
				'chat_id' => $public_logchannel,
				'photo' => $message->photo[count($message->photo)-1]->file_id
			])->result->message_id;
		}
		elseif (isset($message->document)) {
			$type = 'document';
			$file_id = bot('sendDocument', [
				'chat_id' => $public_logchannel,
				'document' => $message->document->file_id
			])->result->message_id;
		}
		elseif (isset($message->text)) {
			$type = 'text';
			$file_id = null;
		}
		else {
			respondMessage("🚨 تنها متن، تصویر، ویدیو و فایل قابل قبول هستند.", $backpanelads);
			exit();
		}
		$ads[$ad_code] = [];
		$ads[$ad_code]['type'] = $type;
		$ads[$ad_code]['text'] = (is_null($text) ? $caption : $text);
		$ads[$ad_code]['keyboard'] = null;
		$ads[$ad_code]['file_id'] = $file_id;
		$ads[$ad_code]['on'] = false;
		$ads[$ad_code]['count'] = 0;
		file_put_contents('Data/ads.json', json_encode($ads));
		$data['step'] = "setkeyboard-$ad_code";
		file_put_contents("Data/$from_id/data.json", json_encode($data));
		
		$inline_keyboard = json_encode([
			'inline_keyboard' => [
				[['text' => '🔴 بدون دکمه', 'callback_data' => "ads_nokeyboard_$ad_code"]],
				[['text' => '❌ لغو', 'callback_data' => "ads_cancel_$ad_code"]]
			]
		]);
		respondMessage("✅ تبلیغ شما آماده شد.\n🌐 حالا می توانید برای آن دکمه شیشه ای تعیین کنید.\n\n🍭 برای تنظیم دکمه شیشه‌ای به صورت زیر عمل کنید:\n\n`متن دکمه 1|لینک 1, متن دکمه 2|لینک 2`\n`متن دکمه 3|لینک 3`\n\n❗️ هر خط یک ردیف دکمه و هر کاما یک دکمه جدید در همان ردیف", $inline_keyboard, 'markdown');
	}
	elseif (preg_match('#^setkeyboard\-([0-9]+)$#', $step, $matches) && !isset($update->callback_query)) {
		$ad_code = $matches[1];
		$ads = json_decode(file_get_contents('Data/ads.json'), true);
		if (!isset($ads[$ad_code])) {
			respondMessage("❗️ خطا در پیدا کردن تبلیغ.", $ads_menu);
			$data['step'] = "none";
			file_put_contents("Data/$from_id/data.json", json_encode($data));
			exit();
		}
		
		$inline_keyboard = makeInlineKeyboard($text);
		if ($inline_keyboard === null) {
			respondMessage("❌ فرمت دکمه شیشه‌ای اشتباه است.\nلطفا دوباره تلاش کنید یا بدون دکمه ادامه دهید.", json_encode([
				'inline_keyboard' => [
					[['text' => '🔴 بدون دکمه', 'callback_data' => "ads_nokeyboard_$ad_code"]],
					[['text' => '❌ لغو', 'callback_data' => "ads_cancel_$ad_code"]]
				]
			]));
			exit();
		}
		
		$ads[$ad_code]['keyboard'] = $inline_keyboard;
		file_put_contents('Data/ads.json', json_encode($ads));
		
		// نمایش پیش‌نمایش نهایی
		$type = $ads[$ad_code]['type'];
		$method = str_replace(['video', 'photo', 'document', 'text'], ['sendVideo', 'sendPhoto', 'sendDocument', 'sendMessage'], $type);
		$dataa = [
			'chat_id' => $chat_id,
			'parse_mode' => 'html'
		];
		if ($type == 'text') {
			$dataa['text'] = $ads[$ad_code]['text'];
			$dataa['disable_web_page_preview'] = true;
		} else {
			$dataa[$type] = 'https://telegram.me/' . str_replace('@', '', $public_logchannel) . '/' . $ads[$ad_code]['file_id'];
			$dataa['caption'] = $ads[$ad_code]['text'];
		}
		if ($inline_keyboard != null) {
			$dataa['reply_markup'] = json_encode($inline_keyboard);
		}
		bot($method, $dataa);
		
		$final_confirm_keyboard = json_encode([
			'inline_keyboard' => [
				[
					['text' => '✅ تأیید و ثبت نهایی', 'callback_data' => "ads_final_confirm_$ad_code"],
					['text' => '❌ لغو', 'callback_data' => "ads_cancel_$ad_code"]
				]
			]
		]);
		respondMessage("👆🏻 تبلیغ مورد نظر به شرح بالا است (با دکمه شیشه‌ای).\n💠 آیا از ثبت نهایی آن مطمئن هستید؟", $final_confirm_keyboard);
		
		$data['step'] = "none";
		file_put_contents("Data/$from_id/data.json", json_encode($data));
	}
	elseif (preg_match('#\/(?:start uid\-?|info )(?<info>@?[a-zA-Z][a-zA-Z0-9\_]{4,32}|[0-9]{3,25})#i', $text, $matches)) {
		if (is_numeric($matches['info'])) {
			if (is_dir("Data/{$matches['info']}")) {
				$get_chat = bot('getChat',
				[
					'chat_id'=>$matches['info']
				]);
				$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
				$name = str_replace(['<', '>'], '', $name);
				$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$user['user_id']}";
				$user_name_mention = "<a href='$mention'>$name</a>";
				$user_data = json_decode(file_get_contents("Data/{$matches['info']}/data.json"), true);
				$user_count_bots = count($user_data['bots']);
				if ($user_count_bots > 0) {
					$user_bots = "\n";
					foreach ($user_data['bots'] as $user_bot) {
						$user_bot = str_replace('@', '', $user_bot);
						$prepared_bot = $pdo->prepare("SELECT * FROM `{$user_bot}_members`;");
						$prepared_bot->execute();
						$fetch_bot = $prepared_bot->fetchAll();
						$bot_count = number_format(count($fetch_bot));
						$user_bots .= "@{$user_bot} {$bot_count} members\n";
					}
				}
				sendMessage($chat_id, "👤 {$user_name_mention}\n🤖 {$user_count_bots}{$user_bots}", 'html', $message_id);
			}
			else {
				sendMessage($chat_id, "❌ کاربر مورد نظر شما وجود ندارد.", 'html', $message_id);
			}
		}
		else {
			$bot_username = trim(strtolower(str_replace('@', '', $matches['info'])));
			if (is_dir("Bots/{$bot_username}")) {
				$config = file_get_contents("Bots/{$bot_username}/config.php");
				preg_match('/\$Dev\s=\s"(.*?)";/', $config, $match);
				$Dev = $match[1];
				preg_match('/\$Token\s=\s"(.*?)";/', $config, $match);
				$token = $match[1];
				$prepared_bot = $pdo->prepare("SELECT * FROM `{$bot_username}_members`;");
				$prepared_bot->execute();
				$fetch_bot = $prepared_bot->fetchAll();
				$bot_count = number_format(count($fetch_bot));

				$prepared_vip = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$bot_username}';");
				$prepared_vip->execute();
				$fetch_vip = $prepared_vip->fetchAll();
				if (count($fetch_vip) > 0) {
					$vip_emoji = '🎖';
				}
				else {
					$vip_emoji = '';
				}
				$get_chat = bot('getChat',
				[
					'chat_id'=>$Dev
				]);
				$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
				$name = str_replace(['<', '>'], '', $name);
				$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$Dev}";
				$user_name_mention = "<a href='$mention'>$name</a>";


				sendMessage($chat_id, "{$vip_emoji}🤖 @{$bot_username}
📊 <b>{$bot_count}</b> کاربر
👤 {$user_name_mention}
🆔 <code>{$Dev}</code>
🔰 <code>{$token}</code>
💾 دریافت فایل پشتیبان : /backup_{$bot_username}", 'html', $message_id);
			}
			else {
				sendMessage($chat_id, "❌ ربات مورد نظر شما وجود ندارد.", 'html', $message_id);
			}
		}
	}
	elseif (preg_match('@/setvip (?<price>[1-9][0-9]+)@i', $text, $matches)) {
		file_put_contents('Data/vip-price.txt', $matches['price']);
		sendMessage($chat_id, "🚀 هزینه اشتراک ماهیانه بر روی {$matches['price']} تومان تنظیم گردید.");
	}
	elseif ($text == '📊 آمار کاربران') {
		$res = $pdo->query("SELECT * FROM `members` ORDER BY `id` DESC;");
		$fetch = $res->fetchAll();
		$count = count($fetch);
		$division_10 = ($count)/10;

		$count_format = number_format($count);
	
		$answer_text_array = [];
	
		$i = 1;
		foreach ($fetch as $user) {
			$get_chat = bot('getChat',
			[
				'chat_id'=>$user['user_id']
			]);
			$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
			$name = str_replace(['<', '>'], '', $name);
			$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$user['user_id']}";
			$user_name_mention = "<a href='$mention'>$name</a>";
			$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$user['user_id']}'>👤 {$i}</a>";

			$user_data = json_decode(file_get_contents("Data/{$user['user_id']}/data.json"), true);
			$user_count_bots = count($user_data['bots']);
			$user_bots = '';
			if ($user_count_bots > 0) {
				foreach ($user_data['bots'] as $user_bot) {
					$user_bot = str_replace('@', '', $user_bot);
					$prepared_bot = $pdo->prepare("SELECT * FROM `{$user_bot}_members`;");
					$prepared_bot->execute();
					$fetch_bot = $prepared_bot->fetchAll();
					$bot_count = number_format(count($fetch_bot));
					$user_bots .= "@{$user_bot} {$bot_count} members\n";
				}
			}
			
			$answer_text_array[] = "{$user_info_link} - {$user_name_mention}\n🆔 <code>{$user['user_id']}</code>\n🤖 <b>{$user_count_bots}</b>\n{$user_bots}🕰 " . jdate('Y/m/j H:i:s', $user['time']);
			if ($i >= 10) break;
			$i++;
		}
	
		$inline_keyboard = [];
		if ($division_10 > 1) {
			if ($division_10 <= 2) {
				$inline_keyboard[] = [
								['text'=>'«1»', 'callback_data'=>'goto_0_1'],
								['text'=>'2', 'callback_data'=>'goto_10_2']
				];
			}
			else {
				$inline_keyboard[0][0]['text'] = '«1»';
				$inline_keyboard[0][0]['callback_data'] = 'goto_0_1';

				for ($i = 1; ($i < myFloor($division_10) && $i < 4); $i++) {
					$inline_keyboard[0][$i]['text'] = ($i+1);
					$inline_keyboard[0][$i]['callback_data'] = 'goto_' . ($i*10) . '_' . ($i+1);
				}

				$inline_keyboard[0][$i]['text'] = (myFloor($division_10)+1);
				$inline_keyboard[0][$i]['callback_data'] = 'goto_' . (myFloor($division_10)*10) . '_' . (myFloor($division_10)+1);
			}
		}
		$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
		$reply_markup = json_encode(['inline_keyboard' => $inline_keyboard]);

		$load_server = sys_getloadavg()[0];
		$ram = convert_size(memory_get_peak_usage(true));

		respondMessage("⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n📊 تعداد کاربران : <b>$count_format</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array), $reply_markup, 'html');
	}
	elseif (preg_match('@goto\_(?<offset>[0-9]+)\_(?<page>[0-9]+)@', $update->callback_query->data, $matches)) {
		$offset = $matches['offset'];
		$page = $matches['page'];

		$res = $pdo->query("SELECT * FROM `members` ORDER BY `id` DESC;");
		$fetch = $res->fetchAll();
		$count = count($fetch);

		$count_format = number_format($count);

		$division_10 = ($count)/10;
		$floor = floor($division_10);
		$floor_10 = ($floor*10);
	
		##text
		$answer_text_array = [];
	
		$x = 1;
		$j = $offset + 1;
		for ($i = $offset; $i < $count; $i++) {
			$get_chat = bot('getChat',
			[
				'chat_id'=>$fetch[$i]['user_id']
			]);
			$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
			$name = str_replace(['<', '>'], '', $name);
			$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$fetch[$i]['user_id']}";
			$user_name_mention = "<a href='$mention'>$name</a>";
			$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$fetch[$i]['user_id']}'>👤 {$j}</a>";

			$user_data = json_decode(file_get_contents("Data/{$fetch[$i]['user_id']}/data.json"), true);
			$user_count_bots = count($user_data['bots']);
			$user_bots = '';
			if ($user_count_bots > 0) {
				foreach ($user_data['bots'] as $user_bot) {
					$user_bot = str_replace('@', '', $user_bot);
					$prepared_bot = $pdo->prepare("SELECT * FROM `{$user_bot}_members`;");
					$prepared_bot->execute();
					$fetch_bot = $prepared_bot->fetchAll();
					$bot_count = number_format(count($fetch_bot));
					$user_bots .= "@{$user_bot} {$bot_count} members\n";
				}
			}

			$answer_text_array[] = "{$user_info_link} - {$user_name_mention}\n🆔 <code>{$fetch[$i]['user_id']}</code>\n🤖 <b>{$user_count_bots}</b>\n{$user_bots}🕰 " . jdate('Y/m/j H:i:s', $fetch[$i]['time']);
			if ($x >= 10) break;
			$x++;
			$j++;
		}
	
		##keyboard
		$inline_keyboard = [];
	
		if ($division_10 <= 2) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "goto_10_2";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2]
			];
		}
		elseif ($division_10 <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "goto_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "goto_20_3";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3]
			];
		}
		elseif ($division_10 <= 4) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "goto_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "goto_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "goto_30_4";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4]
			];
		}
		elseif ($division_10 <= 5) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "goto_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "goto_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "goto_30_4";
	
			$text_5 = $page == 5 ? '«5»' : 5;
			$data_5 = "goto_40_5";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "goto_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "goto_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "goto_30_4";
	
			$text_5 = ($floor+1);
			$data_5 = "goto_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page >= ($floor-1)) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = $page == ($floor-2) ? '«' . $page . '»' : ($floor-2);
			$data_2 = 'goto_' . (($floor-3)*10) . '_' . ($floor-2);
	
			$text_3 = $page == ($floor-1) ? '«' . $page . '»' : ($floor-1);
			$data_3 = 'goto_' . (($floor-2)*10) . '_' . ($floor-1);
	
			$text_4 = $page == ($floor) ? '«' . $page . '»' : ($floor);
			$data_4 = 'goto_' . (($floor-1)*10) . '_' . ($floor);
	
			$text_5 = $page == ($floor+1) ? '«' . $page . '»' : ($floor+1);
			$data_5 = "goto_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		else {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "goto_0_1";
	
			$text_2 = ($page-1);
			$data_2 = 'goto_' . ($offset-10) . '_' . ($page-1);
	
			$text_3 = '«' . $page . '»';
			$data_3 = 'goto_' . $offset . '_' . $page;
	
			$text_4 = ($page+1);
			$data_4 = 'goto_' . ($offset+10) . '_' . ($page+1);
	
			$text_5 = ($floor+1);
			$data_5 = "goto_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
	
		$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
		$reply_markup = json_encode(
			[
				'inline_keyboard' => $inline_keyboard
			]
		);

		$load_server = sys_getloadavg()[0];
		$ram = convert_size(memory_get_peak_usage(true));

		bot('AnswerCallbackQuery',
		[
			'callback_query_id'=>$update->callback_query->id,
			'text'=>''
		]);

		bot('editMessagetext', [
			'chat_id'=>$chatid,
			'message_id'=>$messageid,
			'parse_mode'=>'html',
			'disable_web_page_preview'=>true,
			'text'=>"⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n📊 تعداد کاربران : <b>$count_format</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array),
			'reply_markup'=>$reply_markup
		]);
	}
	elseif ($text == '🤖 آمار ربات ها') {
		$res = $pdo->query("SELECT * FROM `bots` ORDER BY `time` DESC;");
		$fetch = $res->fetchAll();
		$count = count($fetch);
		$division_10 = ($count)/10;

		$count_format = number_format($count);
	
		$answer_text_array = [];
	
		$i = 1;
		foreach ($fetch as $user) {
			$prepared_bot = $pdo->prepare("SELECT * FROM `{$user['username']}_members`;");
			$prepared_bot->execute();
			$fetch_bot = $prepared_bot->fetchAll();
			$bot_count = number_format(count($fetch_bot));

			$prepared_vip = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$user['username']}';");
			$prepared_vip->execute();
			$fetch_vip = $prepared_vip->fetchAll();
			if (count($fetch_vip) > 0) {
				$vip_emoji = '🎖';
			}
			else {
				$vip_emoji = '';
			}
			$get_chat = bot('getChat',
			[
				'chat_id'=>$user['admin']
			]);
			$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
			$name = str_replace(['<', '>'], '', $name);
			$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$user['admin']}";
			$user_name_mention = "<a href='$mention'>$name</a>";
			$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$user['admin']}'>👤 </a>";
			$bot_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid-{$user['username']}'>{$i} - {$vip_emoji}🤖</a>";

			$bot_time = '🕰 ' . jdate('Y/m/j H:i:s', $user['time']);
			$answer_text_array[] = "{$bot_info_link} @{$user['username']}
📊 <b>{$bot_count}</b> کاربر
{$bot_time}
{$user_info_link}{$user_name_mention}
🆔 <code>{$user['admin']}</code>";
			if ($i >= 10) break;
			$i++;
		}
	
		$inline_keyboard = [];
		if ($division_10 > 1) {
			if ($division_10 <= 2) {
				$inline_keyboard[] = [
								['text'=>'«1»', 'callback_data'=>'bots_0_1'],
								['text'=>'2', 'callback_data'=>'bots_10_2']
				];
			}
			else {
				$inline_keyboard[0][0]['text'] = '«1»';
				$inline_keyboard[0][0]['callback_data'] = 'bots_0_1';

				for ($i = 1; ($i < myFloor($division_10) && $i < 4); $i++) {
					$inline_keyboard[0][$i]['text'] = ($i+1);
					$inline_keyboard[0][$i]['callback_data'] = 'bots_' . ($i*10) . '_' . ($i+1);
				}

				$inline_keyboard[0][$i]['text'] = (myFloor($division_10)+1);
				$inline_keyboard[0][$i]['callback_data'] = 'bots_' . (myFloor($division_10)*10) . '_' . (myFloor($division_10)+1);
			}
		}
		$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
		$reply_markup = json_encode(['inline_keyboard' => $inline_keyboard]);

		$load_server = sys_getloadavg()[0];
		$ram = convert_size(memory_get_peak_usage(true));

		respondMessage("⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n🤖 تعداد ربات ها : <b>$count_format</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array), $reply_markup, 'html');
	}
	elseif (preg_match('@bots\_(?<offset>[0-9]+)\_(?<page>[0-9]+)@', $update->callback_query->data, $matches)) {
		$offset = $matches['offset'];
		$page = $matches['page'];

		$res = $pdo->query("SELECT * FROM `bots` ORDER BY `id` DESC;");
		$fetch = $res->fetchAll();
		$count = count($fetch);

		$count_format = number_format($count);

		$division_10 = ($count)/10;
		$floor = floor($division_10);
		$floor_10 = ($floor*10);
	
		##text
		$answer_text_array = [];
	
		$x = 1;
		$j = $offset + 1;
		for ($i = $offset; $i < $count; $i++) {

			$prepared_bot = $pdo->prepare("SELECT * FROM `{$fetch[$i]['username']}_members`;");
			$prepared_bot->execute();
			$fetch_bot = $prepared_bot->fetchAll();
			$bot_count = number_format(count($fetch_bot));
			$prepared_vip = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$fetch[$i]['username']}';");
			$prepared_vip->execute();
			$fetch_vip = $prepared_vip->fetchAll();
			if (count($fetch_vip) > 0) {
				$vip_emoji = '🎖';
			}
			else {
				$vip_emoji = '';
			}
			$get_chat = bot('getChat',
			[
				'chat_id'=>$fetch[$i]['admin']
			]);
			$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
			$name = str_replace(['<', '>'], '', $name);
			$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$fetch[$i]['admin']}";
			$user_name_mention = "<a href='$mention'>$name</a>";
			$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$fetch[$i]['admin']}'>👤 </a>";
			$bot_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid-{$fetch[$i]['username']}'>{$i} - {$vip_emoji}🤖</a>";

			$bot_time = '🕰 ' . jdate('Y/m/j H:i:s', $fetch[$i]['time']);
			$answer_text_array[] = "{$bot_info_link} @{$fetch[$i]['username']}
📊 <b>{$bot_count}</b> کاربر
{$bot_time}
{$user_info_link}{$user_name_mention}
🆔 <code>{$fetch[$i]['admin']}</code>";
			if ($x >= 10) break;
			$x++;
			$j++;
		}
	
		##keyboard
		$inline_keyboard = [];
	
		if ($division_10 <= 2) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "bots_10_2";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2]
			];
		}
		elseif ($division_10 <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "bots_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "bots_20_3";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3]
			];
		}
		elseif ($division_10 <= 4) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "bots_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "bots_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "bots_30_4";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4]
			];
		}
		elseif ($division_10 <= 5) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "bots_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "bots_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "bots_30_4";
	
			$text_5 = $page == 5 ? '«5»' : 5;
			$data_5 = "bots_40_5";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "bots_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "bots_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "bots_30_4";
	
			$text_5 = ($floor+1);
			$data_5 = "bots_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page >= ($floor-1)) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = $page == ($floor-2) ? '«' . $page . '»' : ($floor-2);
			$data_2 = 'bots_' . (($floor-3)*10) . '_' . ($floor-2);
	
			$text_3 = $page == ($floor-1) ? '«' . $page . '»' : ($floor-1);
			$data_3 = 'bots_' . (($floor-2)*10) . '_' . ($floor-1);
	
			$text_4 = $page == ($floor) ? '«' . $page . '»' : ($floor);
			$data_4 = 'bots_' . (($floor-1)*10) . '_' . ($floor);
	
			$text_5 = $page == ($floor+1) ? '«' . $page . '»' : ($floor+1);
			$data_5 = "bots_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		else {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "bots_0_1";
	
			$text_2 = ($page-1);
			$data_2 = 'bots_' . ($offset-10) . '_' . ($page-1);
	
			$text_3 = '«' . $page . '»';
			$data_3 = 'bots_' . $offset . '_' . $page;
	
			$text_4 = ($page+1);
			$data_4 = 'bots_' . ($offset+10) . '_' . ($page+1);
	
			$text_5 = ($floor+1);
			$data_5 = "bots_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
	
		$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
		$reply_markup = json_encode(
			[
				'inline_keyboard' => $inline_keyboard
			]
		);

		$load_server = sys_getloadavg()[0];
		$ram = convert_size(memory_get_peak_usage(true));

		bot('AnswerCallbackQuery',
		[
			'callback_query_id'=>$update->callback_query->id,
			'text'=>''
		]);

		bot('editMessagetext', [
			'chat_id'=>$chatid,
			'message_id'=>$messageid,
			'parse_mode'=>'html',
			'disable_web_page_preview'=>true,
			'text'=>"⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n🤖 تعداد ربات ها : <b>$count_format</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array),
			'reply_markup'=>$reply_markup
		]);
	}
	elseif ($text == '🎖 لیست رباتهای ویژه') {
		$res = $pdo->query("SELECT * FROM `vip_bots` ORDER BY `start` DESC;");
		$fetch = $res->fetchAll();
		$count = count($fetch);
		$division_10 = ($count)/10;
		$count_format = number_format($count);
		if ($count < 1) {
			$back_keyboard = json_encode([
				'inline_keyboard' => [
					[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
				]
			]);
			respondMessage('❌ هیچ ربات ویژه ای وجود ندارد.', $back_keyboard);
		}
		else {
			$answer_text_array = [];
	
			$i = 1;
			foreach ($fetch as $user) {
				$prepared_bot = $pdo->prepare("SELECT * FROM `{$user['bot']}_members`;");
				$prepared_bot->execute();
				$fetch_bot = $prepared_bot->fetchAll();
				$bot_count = number_format(count($fetch_bot));

				$get_chat = bot('getChat',
				[
					'chat_id'=>$user['admin']
				]);
				$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
				$name = str_replace(['<', '>'], '', $name);
				$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$user['admin']}";
				$user_name_mention = "<a href='$mention'>$name</a>";
				$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$user['admin']}'>👤 </a>";

				$start_time = jdate('Y/m/j H:i:s', $user['start']);
				$end_time = jdate('Y/m/j H:i:s', $user['end']);
				$time_elapsed = timeElapsed($user['end']-time());

				$bot_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid-{$user['bot']}'> 🤖 </a>";
				
				$answer_text_array[] = "<b>{$i}</b> -{$bot_info_link}@{$user['bot']}
⏳ <b>{$start_time}</b>
🧭 {$time_elapsed}
⌛️ <b>{$end_time}</b>
📊 <b>{$bot_count}</b> کاربر
{$user_info_link}{$user_name_mention}
🆔 <code>{$user['admin']}</code>";
				if ($i >= 10) break;
				$i++;
			}
		
			$inline_keyboard = [];
			if ($division_10 > 1) {
				if ($division_10 <= 2) {
					$inline_keyboard[] = [
									['text'=>'«1»', 'callback_data'=>'vip_0_1'],
									['text'=>'2', 'callback_data'=>'vip_10_2']
					];
				}
				else {
					$inline_keyboard[0][0]['text'] = '«1»';
					$inline_keyboard[0][0]['callback_data'] = 'vip_0_1';

					for ($i = 1; ($i < myFloor($division_10) && $i < 4); $i++) {
						$inline_keyboard[0][$i]['text'] = ($i+1);
						$inline_keyboard[0][$i]['callback_data'] = 'vip_' . ($i*10) . '_' . ($i+1);
					}

					$inline_keyboard[0][$i]['text'] = (myFloor($division_10)+1);
					$inline_keyboard[0][$i]['callback_data'] = 'vip_' . (myFloor($division_10)*10) . '_' . (myFloor($division_10)+1);
				}
			}
			$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
			$reply_markup = json_encode(['inline_keyboard' => $inline_keyboard]);

			$load_server = sys_getloadavg()[0];
			$ram = convert_size(memory_get_peak_usage(true));

			respondMessage("⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n🎖 تعداد ربات های ویژه : <b>$count_format</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array), $reply_markup, 'html');
		}
	}
	elseif (preg_match('@^vip\_(?<offset>[0-9]+)\_(?<page>[0-9]+)$@', $update->callback_query->data, $matches)) {
		$offset = $matches['offset'];
		$page = $matches['page'];

		$res = $pdo->query("SELECT * FROM `vip_bots` ORDER BY `start` DESC;");
		$fetch = $res->fetchAll();
		$count = count($fetch);

		$count_format = number_format($count);

		$division_10 = ($count)/10;
		$floor = floor($division_10);
		$floor_10 = ($floor*10);
	
		##text
		$answer_text_array = [];
	
		$x = 1;
		$j = $offset + 1;
		for ($i = $offset; $i < $count; $i++) {

			$prepared_bot = $pdo->prepare("SELECT * FROM `{$fetch[$i]['bot']}_members`;");
			$prepared_bot->execute();
			$fetch_bot = $prepared_bot->fetchAll();
			$bot_count = number_format(count($fetch_bot));

			$get_chat = bot('getChat',
			[
				'chat_id'=>$fetch[$i]['admin']
			]);
			$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
			$name = str_replace(['<', '>'], '', $name);
			$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$fetch[$i]['admin']}";
			$user_name_mention = "<a href='$mention'>$name</a>";
			$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$fetch[$i]['admin']}'>👤 </a>";
			$bot_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid-{$fetch[$i]['bot']}'> 🤖 </a>";

			$start_time = jdate('Y/m/j H:i:s', $fetch[$i]['start']);
			$end_time = jdate('Y/m/j H:i:s', $fetch[$i]['end']);
			$time_elapsed = timeElapsed($fetch[$i]['end']-time());
			$answer_text_array[] = "<b>{$i}</b> -{$bot_info_link}@{$fetch[$i]['bot']}
⏳ <b>{$start_time}</b>
🧭 {$time_elapsed}
⌛️ <b>{$end_time}</b>
📊 <b>{$bot_count}</b> کاربر
{$user_info_link}{$user_name_mention}
🆔 <code>{$fetch[$i]['admin']}</code>";
			if ($x >= 10) break;
			$x++;
			$j++;
		}
	
		##keyboard
		$inline_keyboard = [];
	
		if ($division_10 <= 2) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "vip_10_2";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2]
			];
		}
		elseif ($division_10 <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "vip_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "vip_20_3";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3]
			];
		}
		elseif ($division_10 <= 4) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "vip_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "vip_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "vip_30_4";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4]
			];
		}
		elseif ($division_10 <= 5) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "vip_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "vip_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "vip_30_4";
	
			$text_5 = $page == 5 ? '«5»' : 5;
			$data_5 = "vip_40_5";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "vip_10_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "vip_20_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "vip_30_4";
	
			$text_5 = ($floor+1);
			$data_5 = "vip_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page >= ($floor-1)) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = $page == ($floor-2) ? '«' . $page . '»' : ($floor-2);
			$data_2 = 'vip_' . (($floor-3)*10) . '_' . ($floor-2);
	
			$text_3 = $page == ($floor-1) ? '«' . $page . '»' : ($floor-1);
			$data_3 = 'vip_' . (($floor-2)*10) . '_' . ($floor-1);
	
			$text_4 = $page == ($floor) ? '«' . $page . '»' : ($floor);
			$data_4 = 'vip_' . (($floor-1)*10) . '_' . ($floor);
	
			$text_5 = $page == ($floor+1) ? '«' . $page . '»' : ($floor+1);
			$data_5 = "vip_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		else {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "vip_0_1";
	
			$text_2 = ($page-1);
			$data_2 = 'vip_' . ($offset-10) . '_' . ($page-1);
	
			$text_3 = '«' . $page . '»';
			$data_3 = 'vip_' . $offset . '_' . $page;
	
			$text_4 = ($page+1);
			$data_4 = 'vip_' . ($offset+10) . '_' . ($page+1);
	
			$text_5 = ($floor+1);
			$data_5 = "vip_{$floor_10}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
	
		$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
		$reply_markup = json_encode(
			[
				'inline_keyboard' => $inline_keyboard
			]
		);

		$load_server = sys_getloadavg()[0];
		$ram = convert_size(memory_get_peak_usage(true));

		bot('AnswerCallbackQuery',
		[
			'callback_query_id'=>$update->callback_query->id,
			'text'=>''
		]);

		bot('editMessagetext', [
			'chat_id'=>$chatid,
			'message_id'=>$messageid,
			'parse_mode'=>'html',
			'disable_web_page_preview'=>true,
			'text'=>"⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n🎖 تعداد ربات های ویژه : <b>$count_format</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array),
			'reply_markup'=>$reply_markup
		]);
	}
	elseif ($text == '⛔️ لیست کاربران مسدود') {
		$blacklist_array = array_reverse($list['ban']);
		$count = count($blacklist_array);
		$count_format = number_format($count);
	
		if ($count < 1) {
			$back_keyboard = json_encode([
				'inline_keyboard' => [
					[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
				]
			]);
			respondMessage('❌ لیست کاربران مسدود خالی است.', $back_keyboard);
		}
		else {
			$division_20 = $count/20;
	
			$answer_text_array = [];
			$i = 1;
			foreach ($blacklist_array as $blacklist_user) {
				$get_chat = bot('getChat',
				[
					'chat_id'=>$blacklist_user
				]);
				$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
				$name = str_replace(['<', '>'], '', $name);
				$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$blacklist_user}";
				$answer_text_array[] = "<b>{$i}</b> - 🆔 <code>{$blacklist_user}</code>
👤 <a href='{$mention}'>{$name}</a>
/unban_{$blacklist_user}";
				if ($i >= 20) break;
				$i++;
			}
	
						$inline_keyboard = [];
			if ($division_20 > 1) {
				if ($division_20 <= 2) {
					$inline_keyboard[] = [
									['text'=>'«1»', 'callback_data'=>'blacklist_0_1'],
									['text'=>'2', 'callback_data'=>'blacklist_10_2']
					];
				}
				else {
					$inline_keyboard[0][0]['text'] = '«1»';
					$inline_keyboard[0][0]['callback_data'] = 'blacklist_0_1';
	
					for ($i = 1; ($i < myFloor($division_20) && $i < 4); $i++) {
						$inline_keyboard[0][$i]['text'] = ($i+1);
						$inline_keyboard[0][$i]['callback_data'] = 'blacklist_' . ($i*10) . '_' . ($i+1);
					}
	
					$inline_keyboard[0][$i]['text'] = (myFloor($division_20)+1);
					$inline_keyboard[0][$i]['callback_data'] = 'blacklist_' . (myFloor($division_20)*10) . '_' . (myFloor($division_20)+1);
				}
			}
			$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
			$reply_markup = json_encode(['inline_keyboard' => $inline_keyboard]);

			$load_server = sys_getloadavg()[0];
			$ram = convert_size(memory_get_peak_usage(true));

			respondMessage("⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n⛔️ تعداد کاربران مسدود : <b>{$count_format}</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array), $reply_markup, 'html');
		}
	}
	elseif (preg_match('@blacklist\_(?<offset>[0-9]+)\_(?<page>[0-9]+)@', $update->callback_query->data, $matches)) {
		$offset = $matches['offset'];
		$page = $matches['page'];
	
		$blacklist_array = array_reverse($list['ban']);
		$count = count($blacklist_array);
		$count_format = number_format($count);
		$division_20 = $count/20;
		$floor = floor($division_20);
		$floor_20 = $floor*20;
	
		##text
		$answer_text_array = [];
		$x = 1;
		$j = $offset + 1;
		for ($i = $offset; $i < $count; $i++) {
			$get_chat = bot('getChat',
			[
				'chat_id'=>$blacklist_array[$i]
			]);
			$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
			$name = str_replace(['<', '>'], '', $name);
			$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$blacklist_array[$i]}";
			$answer_text_array[] = "<b>{$j}</b> - 🆔 <code>{$blacklist_array[$i]}</code>
👤 <a href='{$mention}'>{$name}</a>
/unban_{$blacklist_array[$i]}";
			if ($x >= 20) break;
			$x++;
			$j++;
		}
	
		##keyboard
		$inline_keyboard = [];
	
		if ($division_20 <= 2) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "blacklist_20_2";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2]
			];
		}
		elseif ($division_20 <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "blacklist_20_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "blacklist_40_3";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3]
			];
		}
		elseif ($division_20 <= 4) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "blacklist_20_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "blacklist_40_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "blacklist_60_4";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4]
			];
		}
		elseif ($division_20 <= 5) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "blacklist_20_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "blacklist_40_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "blacklist_60_4";
	
			$text_5 = $page == 5 ? '«5»' : 5;
			$data_5 = "blacklist_80_5";
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page <= 3) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = $page == 2 ? '«2»' : 2;
			$data_2 = "blacklist_20_2";
	
			$text_3 = $page == 3 ? '«3»' : 3;
			$data_3 = "blacklist_40_3";
	
			$text_4 = $page == 4 ? '«4»' : 4;
			$data_4 = "blacklist_60_4";
	
			$text_5 = ($floor+1);
			$data_5 = "blacklist_{$floor_20}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		elseif ($page >= ($floor-1)) {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = $page == ($floor-2) ? '«' . $page . '»' : ($floor-2);
			$data_2 = 'blacklist_' . (($floor-3)*20) . '_' . ($floor-2);
	
			$text_3 = $page == ($floor-1) ? '«' . $page . '»' : ($floor-1);
			$data_3 = 'blacklist_' . (($floor-2)*20) . '_' . ($floor-1);
	
			$text_4 = $page == ($floor) ? '«' . $page . '»' : ($floor);
			$data_4 = 'blacklist_' . (($floor-1)*20) . '_' . ($floor);
	
			$text_5 = $page == ($floor+1) ? '«' . $page . '»' : ($floor+1);
			$data_5 = "blacklist_{$floor_20}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
		else {
			$text_1 = $page == 1 ? '«1»' : 1;
			$data_1 = "blacklist_0_1";
	
			$text_2 = ($page-1);
			$data_2 = 'blacklist_' . ($offset-20) . '_' . ($page-1);
	
			$text_3 = '«' . $page . '»';
			$data_3 = 'blacklist_' . $offset . '_' . $page;
	
			$text_4 = ($page+1);
			$data_4 = 'blacklist_' . ($offset+20) . '_' . ($page+1);
	
			$text_5 = ($floor+1);
			$data_5 = "blacklist_{$floor_20}_" . ($floor+1);
	
			$inline_keyboard[] = [
				['text' => $text_1, 'callback_data' => $data_1],
				['text' => $text_2, 'callback_data' => $data_2],
				['text' => $text_3, 'callback_data' => $data_3],
				['text' => $text_4, 'callback_data' => $data_4],
				['text' => $text_5, 'callback_data' => $data_5]
			];
		}
	
				$inline_keyboard[] = [['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']];
		$reply_markup = json_encode(
			[
				'inline_keyboard' => $inline_keyboard
			]
		);
	
		bot('AnswerCallbackQuery',
		[
			'callback_query_id'=>$update->callback_query->id,
			'text'=>''
		]);

		$load_server = sys_getloadavg()[0];
		$ram = convert_size(memory_get_peak_usage(true));

		bot('editMessagetext', [
			'chat_id'=>$chat_id,
			'message_id'=>$message_id,
			'parse_mode'=>'html',
			'disable_web_page_preview'=>true,
			'text'=>"⏱ بار روی هاست : <b>{$load_server}</b>\n🗃 رم مصرفی : <b>{$ram}</b>\n\n⛔️ تعداد کاربران مسدود : <b>{$count_format}</b>\n➖➖➖➖➖➖➖➖➖➖➖➖\n" . implode("\n➖➖➖➖➖➖➖➖➖➖➖➖\n", $answer_text_array),
			'reply_markup'=>$reply_markup
		]);
	}
	##-------------------
	elseif ($text == '➕ اشتراک ویژه' || $text == '🔙 بازگشت به + اشتراک ویژه') {
		$data['step'] = 'set_vip';
		file_put_contents("Data/{$from_id}/data.json", json_encode($data));
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
			]
		]);
		respondMessage('🔰 لطفا یوزرنیم ربات مورد نظرتان را ارسال کنید.', $back_keyboard);
	}
	elseif ($step == 'set_vip') {
		$bot_username = trim(strtolower(str_replace('@', '', $text)));
		if (is_dir("Bots/{$bot_username}")) {
			$data['step'] = "set_vip_{$bot_username}";
			file_put_contents("Data/{$from_id}/data.json", json_encode($data));
			$prepared = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$bot_username}';");
			$prepared->execute();
			$fetch = $prepared->fetchAll();
			if (count($fetch) > 0) {
				$get_chat = bot('getChat',
				[
					'chat_id'=>$fetch[0]['admin']
				]);
				$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
				$name = str_replace(['<', '>'], '', $name);
				$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$fetch[0]['admin']}";
				$user_name_mention = "<a href='$mention'>$name</a>";
				$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$fetch[0]['admin']}'>👤 </a>";
				$start_time = jdate('Y/m/j H:i:s', $fetch[0]['start']);
				$end_time = jdate('Y/m/j H:i:s', $fetch[0]['end']);
				$time_elapsed = timeElapsed($fetch[0]['end']-time());

				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'parse_mode'=>'html',
					'disable_web_page_preview'=>true,
					'text'=>"✅ اشتراک ویژه برای ربات @{$bot_username} فعال است.

⏳ <b>{$start_time}</b>
🧭 {$time_elapsed}
⌛️ <b>{$end_time}</b>
📊 <b>{$bot_count}</b> کاربر
{$user_info_link}{$user_name_mention}
🆔 <code>{$fetch[0]['admin']}</code>

🔰 می خواهید چند روز به آن اضافه کنید؟",
					'reply_markup'=>json_encode([
						'inline_keyboard'=>[
							[['text'=>'🔙 بازگشت به + اشتراک ویژه','callback_data'=>'🔙 بازگشت به + اشتراک ویژه']],
							[['text'=>'🔙 بازگشت به مدیریت','callback_data'=>'🔙 بازگشت به مدیریت']],
						]
					])
				]);
			}
			else {
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>"🎖 اشتراک ویژه برای ربات @{$bot_username} فعال نیست.

🔰 می خواهید اشتراک چند روزه برای آن فعال کنید؟",
					'reply_markup'=>json_encode([
						'inline_keyboard'=>[
							[['text'=>'🔙 بازگشت به + اشتراک ویژه','callback_data'=>'🔙 بازگشت به + اشتراک ویژه']],
							[['text'=>'🔙 بازگشت به مدیریت','callback_data'=>'🔙 بازگشت به مدیریت']],
						]
					])
				]);
			}
		}
		else {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>'❌ این ربات وجود ندارد.'
			]);
		}
	}
	elseif (preg_match('@^set\_vip\_(?<bot>.+)$@i', $step, $matches)) {
		$text = convert($text);
		$bot_username = $matches['bot'];
		$prepared = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$bot_username}';");
		$prepared->execute();
		$fetch = $prepared->fetchAll();
		if (!is_numeric($text) || ((int) $text) < 1) {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>'❌ لطفا یک مقدار معتبر وارد کنید.'
			]);
		}
		elseif (count($fetch) > 0) {
			$data['step'] = 'none';
			file_put_contents("Data/{$from_id}/data.json", json_encode($data));
			$config = file_get_contents("Bots/{$bot_username}/config.php");
			preg_match('/\$Dev\s=\s"(.*?)";/', $config, $match);
			$Dev = $match[1];
			preg_match('/\$Token\s=\s"(.*?)";/', $config, $match);
			$token = $match[1];

			$days = (int) $text;
			$second = $days*24*60*60;
			$new_end_time = $fetch[0]['end']+$second;
			$prepared = $pdo->prepare("UPDATE `vip_bots` SET `end`={$new_end_time}, alert=0 WHERE `bot`='{$bot_username}';");
			$prepared->execute();
			bot('sendMessage', [
				'chat_id'=>$Dev,
				'text'=>"✅ {$days} روز به زمان اشتراک ویژه ربات شما اضافه گردید."
			], $token);
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"✅ {$days} روز به زمان اشتراک ویژه ربات @{$bot_username} اضافه گردید.",
				'reply_markup'=>$panel
			]);
		}
		else {
			$data['step'] = 'none';
			file_put_contents("Data/{$from_id}/data.json", json_encode($data));
			$config = file_get_contents("Bots/{$bot_username}/config.php");
			preg_match('/\$Dev\s=\s"(.*?)";/', $config, $match);
			$Dev = $match[1];
			preg_match('/\$Token\s=\s"(.*?)";/', $config, $match);
			$token = $match[1];

			$days = (int) $text;
			$second = $days*24*60*60;
			$end_time = time()+$second;
			$prepare = $pdo->prepare("INSERT INTO `vip_bots` (`admin`, `bot`, `start`, `end`, `alert`) VALUES ('{$Dev}', '{$bot_username}', UNIX_TIMESTAMP(), '{$end_time}', 0);");
			$prepare->execute();

			bot('sendMessage', [
				'chat_id'=>$Dev,
				'text'=>"✅ اشتراک ویژه {$days} روزه برای ربات شما فعال گردید."
			], $token);
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"✅ اشتراک ویژه {$days} روزه برای ربات @{$bot_username} فعال گردید.",
				'reply_markup'=>$panel
			]);
		}
	}
	elseif ($text == '➖ اشتراک ویژه' || $text == '🔙 بازگشت به - اشتراک ویژه') {
		$data['step'] = 'del_vip';
		file_put_contents("Data/{$from_id}/data.json", json_encode($data));
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
			]
		]);
		respondMessage('🔰 لطفا یوزرنیم ربات مورد نظرتان را ارسال کنید.', $back_keyboard);
	}
	elseif ($step == 'del_vip') {
		$bot_username = trim(str_replace('@', '', strtolower($text)));
		if (is_dir("Bots/{$bot_username}")) {
			$prepared = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$bot_username}';");
			$prepared->execute();
			$fetch = $prepared->fetchAll();
			if (count($fetch) > 0) {
				$data['step'] = "del_vip_{$bot_username}";
				file_put_contents("Data/{$from_id}/data.json", json_encode($data));
				$get_chat = bot('getChat',
				[
					'chat_id'=>$fetch[0]['admin']
				]);
				$name = isset($get_chat->result->last_name) ? $get_chat->result->first_name . ' ' . $get_chat->result->last_name : $get_chat->result->first_name;
				$name = str_replace(['<', '>'], '', $name);
				$mention = isset($get_chat->result->username) ? 'https://telegram.me/' . $get_chat->result->username : "tg://user?id={$fetch[0]['admin']}";
				$user_name_mention = "<a href='$mention'>$name</a>";
				$user_info_link = "<a href='https://telegram.me/" . str_replace('@', '', $main_bot) . "?start=uid{$fetch[0]['admin']}'>👤 </a>";
				$start_time = jdate('Y/m/j H:i:s', $fetch[0]['start']);
				$end_time = jdate('Y/m/j H:i:s', $fetch[0]['end']);
				$time_elapsed = timeElapsed($fetch[0]['end']-time());

				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'parse_mode'=>'html',
					'disable_web_page_preview'=>true,
					'text'=>"✅ اشتراک ویژه برای ربات @{$bot_username} فعال است.

⏳ <b>{$start_time}</b>
🧭 {$time_elapsed}
⌛️ <b>{$end_time}</b>
📊 <b>{$bot_count}</b> کاربر
{$user_info_link}{$user_name_mention}
🆔 <code>{$fetch[0]['admin']}</code>

🔰 می خواهید چند روز از آن کم کنید؟",
					'reply_markup'=>json_encode([
						'inline_keyboard'=>[
							[['text'=>'🔙 بازگشت به - اشتراک ویژه','callback_data'=>'🔙 بازگشت به - اشتراک ویژه']],
							[['text'=>'🔙 بازگشت به مدیریت','callback_data'=>'🔙 بازگشت به مدیریت']],
						]
					])
				]);
			}
			else {
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>"❌ اشتراک ویژه برای ربات @{$bot_username} فعال نیست."
				]);
			}
		}
		else {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>'❌ این ربات وجود ندارد.'
			]);
		}
	}
	elseif (preg_match('@^del\_vip\_(?<bot>.+)$@i', $step, $matches)) {
		$text = convert($text);
		$bot_username = $matches['bot'];
		$prepared = $pdo->prepare("SELECT * FROM `vip_bots` WHERE `bot`='{$bot_username}';");
		$prepared->execute();
		$fetch = $prepared->fetchAll();
		if (!is_numeric($text) || ((int) $text) < 1) {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>'❌ لطفا یک مقدار معتبر وارد کنید.'
			]);
		}
		elseif (count($fetch) > 0) {
			$data['step'] = 'none';
			file_put_contents("Data/{$from_id}/data.json", json_encode($data));
			$days = (int) $text;
			$second = $days*24*60*60;
			$new_end_time = $fetch[0]['end']-$second;
			if ($new_end_time <= time()) {
				$config = file_get_contents("Bots/{$bot_username}/config.php");
				preg_match('/\$Token\s=\s"(.*?)";/', $config, $match);
				$token = $match[1];
				$prepare = $pdo->prepare("DELETE FROM `vip_bots` WHERE `bot`='{$bot_username}';");
				$prepare->execute();
				bot('sendMessage', [
					'chat_id'=>$fetch[0]['admin'],
					'text'=>"⚠️ اشتراک ویژه ربات شما حذف گردید."
				], $token);
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>"⚠️ اشتراک ویژه ربات @{$bot_username} حذف گردید.",
					'reply_markup'=>$panel
				]);
			}
			else {
				$data['step'] = 'none';
				file_put_contents("Data/{$from_id}/data.json", json_encode($data));
				$config = file_get_contents("Bots/{$bot_username}/config.php");
				preg_match('/\$Token\s=\s"(.*?)";/', $config, $match);
				$token = $match[1];
				$prepared = $pdo->prepare("UPDATE `vip_bots` SET `end`={$new_end_time}, alert=0 WHERE `bot`='{$bot_username}';");
				$prepared->execute();
				bot('sendMessage', [
					'chat_id'=>$fetch[0]['admin'],
					'text'=>"⚠️ {$days} روز از زمان اشتراک ویژه ربات شما کسر گردید."
				], $token);
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>"⚠️ {$days} روز از زمان اشتراک ویژه ربات @{$bot_username} کسر گردید.",
					'reply_markup'=>$panel
				]);
			}
		}
		else {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"❌ اشتراک ویژه برای ربات @{$bot_username} فعال نیست."
			]);
		}
	}
	##-------------------
	elseif ($text == '🔖 پیام همگانی') {
		$prepared = $pdo->prepare("SELECT * FROM `sendlist` WHERE `type`!='f2a';");
		$prepared->execute();
		$fetch = $prepared->fetchAll();
		if (count($fetch) > 0) {
			$back_keyboard = json_encode([
				'inline_keyboard' => [
					[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
				]
			]);
			respondMessage("❌ هنوز پیام قبلی شما در صف ارسال همگانی قرار دارد و برای کاربران ربات ارسال نشده است.
	
👇🏻 برای ثبت پیام همگانی جدید، ابتدا پیام همگانی قبلی را با استفاده از دستور زیر لغو کنید و یا اینکه منتظر بمانید تا پیام ارسال شدن آنرا دریافت نمایید.
	
/determents2a_{$fetch[0]['time']}", $back_keyboard);
		}
		else {
			$user_data = json_decode(file_get_contents("Data/$from_id/data.json"), true);
			$user_data['step'] = 's2a';
			file_put_contents("Data/{$from_id}/data.json", json_encode($user_data));
	
			$back_keyboard = json_encode([
				'inline_keyboard' => [
					[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
				]
			]);
			respondMessage('📩 پیام مورد نظرتان را برای ارسال همگانی بفرستید.
🔴 شما می توانید از متغیر های زیر استفاده کنید.

▪️`FULL-NAME` 👉🏻 نام کامل کاربر
▫️`F-NAME` 👉🏻 نام کاربر
▪️`L-NAME` 👉🏻 نام خانوادگی کاربر
▫️`U-NAME` 👉🏻 نام کاربری کاربر 
▪️`TIME` 👉🏻 زمان به وقت ایران
▫️`DATE` 👉🏻 تاریخ
▪️`TODAY` 👉🏻 روز هفته', $back_keyboard, 'markdown');
		}
	}
	elseif ($step == 's2a') {
		$prepared = $pdo->prepare("SELECT * FROM `sendlist` WHERE `type`!='f2a';");
		$prepared->execute();
		$fetch = $prepared->fetchAll();
		if (count($fetch) > 0) {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"❌ هنوز پیام قبلی شما در صف ارسال همگانی قرار دارد و برای کاربران ربات ارسال نشده است.
	
👇🏻 برای ثبت پیام همگانی جدید، ابتدا پیام همگانی قبلی را با استفاده از دستور زیر لغو کنید و یا اینکه منتظر بمانید تا پیام ارسال شدن آنرا دریافت نمایید.
	
/determents2a_{$fetch[0]['time']}"
			]);
		}
		else {
			if (isset($update->message->media_group_id)) {
				$is_file = is_file('Data/album-' . $update->message->media_group_id . '.json');
				$media_group = json_decode(@file_get_contents('Data/album-' . $update->message->media_group_id . '.json'), true);
		
				$media_type = isset($update->message->video) ? 'video' : 'photo';
				$media_file_id = isset($update->message->video) ? $update->message->video->file_id : $update->message->photo[count($update->message->photo)-1]->file_id;
				$media_group[] = [
					'type' => $media_type,
					'media' => $media_file_id,
					'caption' => isset($update->message->caption) ? $update->message->caption : ''
				];
		
				file_put_contents('Data/album-' . $update->message->media_group_id . '.json', json_encode($media_group));
		
				$data = [
					'media_group_id'=>$update->message->media_group_id
				];
		
				$type = 'media_group';
				if ($is_file) exit();
		
			}
			elseif (isset($update->message->photo)) {
				$data = [
					'file_id'=>$update->message->photo[count($update->message->photo)-1]->file_id
				];
				$type = 'photo';
			}
			elseif (isset($update->message->video)) {
				$data = [
					'file_id'=>$update->message->video->file_id
				];
				$type = 'video';
			}
			elseif (isset($update->message->animation)) {
				$data = [
					'file_id'=>$update->message->animation->file_id
				];
				$type = 'animation';
			}
			elseif (isset($update->message->audio)) {
				$data = [
					'file_id'=>$update->message->audio->file_id
				];
				$type = 'audio';
			}
			elseif (isset($update->message->document)) {
				$data = [
					'file_id'=>$update->message->document->file_id
				];
				$type = 'document';
			}
			elseif (isset($update->message->video_note)) {
				$data = [
					'file_id'=>$update->message->video_note->file_id
				];
				$type = 'video_note';
			}
			elseif (isset($update->message->voice)) {
				$data = [
					'file_id'=>$update->message->voice->file_id
				];
				$type = 'voice';
			}
			elseif (isset($update->message->sticker)) {
				$data = [
					'file_id' => $update->message->sticker->file_id
				];
				$type = 'sticker';
			}
			elseif (isset($update->message->contact)) {
				$data = [
					'phone_number' => $update->message->contact->phone_number,
					'phone_first' => $update->message->contact->first_name,
					'phone_last' => $update->message->contact->last_name
				];
				$type = 'contact';
			}
			elseif (isset($update->message->location)) {
				$data = [
					'longitude' => $update->message->location->longitude,
					'latitude' => $update->message->location->latitude
				];
				$type = 'location';
			}
			elseif (isset($update->message->text)) {
				$data = [
					'text' => utf8_encode($update->message->text)
				];
				$type = 'text';
			}
			else {
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>'❌ این پیام پشتیبانی نمی شود.
🔰 لطفا یک چیز دیگر ارسال نمایید.'
				]);
				exit();
			}
			$user_data = json_decode(file_get_contents("Data/$from_id/data.json"), true);
			$user_data['step'] = '';
			file_put_contents("Data/{$from_id}/data.json", json_encode($user_data));

			$caption = ( isset($update->caption) ? $update->caption : (isset($update->message->caption) ? $update->message->caption : '') );
			$data['caption'] = utf8_encode($caption);
			$data = json_encode($data);
			$time = time();
		
			$sql = "INSERT INTO `sendlist` (`user_id`, `offset`, `time`, `type`, `data`, `caption`) VALUES (:user_id, :offset, :time, :type, :data, :caption);";
			$prepare = $pdo->prepare($sql);
			$prepare->execute(['user_id'=>$user_id, 'offset'=>0, 'time'=>$time, 'type'=>$type, 'data'=>$data, 'caption'=>$caption]);
		
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"✅ پیام مورد نظر شما در صف ارسال همگانی قرار گرفت.
				
👇🏻 برای لغو ارسالی همگانی این پیام دستور زیر را بفرستید.
/determents2a_{$time}",
				'reply_markup'=>$panel
			]);
		}
	}
	elseif (isset($update->message->media_group_id) && is_file('Data/album-' . $update->message->media_group_id . '.json')) {
		$media_group = json_decode(@file_get_contents('Data/album-' . $update->message->media_group_id . '.json'), true);
	
		$media_type = isset($update->message->video) ? 'video' : 'photo';
		$media_file_id = isset($update->message->video) ? $update->message->video->file_id : $update->message->photo[count($update->message->photo)-1]->file_id;
		$media_group[] = [
			'type' => $media_type,
			'media' => $media_file_id,
			'caption' => isset($update->message->caption) ? $update->message->caption : ''
		];
	
		file_put_contents('Data/album-' . $update->message->media_group_id . '.json', json_encode($media_group));
	}
	elseif ($text == '🚀 هدایت همگانی') {
		$prepared = $pdo->prepare("SELECT * FROM `sendlist` WHERE `type`='f2a';");
		$prepared->execute();
		$fetch = $prepared->fetchAll();
		if (count($fetch) > 0) {
			$back_keyboard = json_encode([
				'inline_keyboard' => [
					[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
				]
			]);
			respondMessage("❌ هنوز پیام قبلی شما در صف هدایت همگانی قرار دارد و برای کاربران ربات هدایت نشده است.
	
👇🏻 برای ثبت هدایت همگانی جدید، ابتدا هدایت همگانی قبلی را با استفاده از دستور زیر لغو کنید و یا اینکه منتظر بمانید تا پیام هدایت شدن آنرا دریافت نمایید.

/determentf2a_{$fetch[0]['time']}", $back_keyboard);
		}
		else {
			$user_data = json_decode(file_get_contents("Data/$from_id/data.json"), true);
			$user_data['step'] = 'f2a';
			file_put_contents("Data/{$from_id}/data.json", json_encode($user_data));
	
			$back_keyboard = json_encode([
				'inline_keyboard' => [
					[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
				]
			]);
			respondMessage('🚀 پیام مورد نظرتان را برای هدایت همگانی بفرستید.', $back_keyboard);
		}
	}
	elseif ($step == 'f2a') {
		$prepared = $pdo->prepare("SELECT * FROM `sendlist` WHERE `type`='f2a';");
		$prepared->execute();
		$fetch = $prepared->fetchAll();
		if (count($fetch) > 0) {
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"❌ هنوز پیام قبلی شما در صف هدایت همگانی قرار دارد و برای کاربران ربات هدایت نشده است.
	
👇🏻 برای ثبت هدایت همگانی جدید، ابتدا هدایت همگانی قبلی را با استفاده از دستور زیر لغو کنید و یا اینکه منتظر بمانید تا پیام هدایت شدن آنرا دریافت نمایید.

/determentf2a_{$fetch[0]['time']}"
			]);
		}
		else {
			$user_data = json_decode(file_get_contents("Data/$from_id/data.json"), true);
			$user_data['step'] = '';
			file_put_contents("Data/{$from_id}/data.json", json_encode($user_data));
	
			$sql = "INSERT INTO `sendlist` (`user_id`, `offset`, `time`, `type`, `data`, `caption`) VALUES (:user_id, :offset, :time, :type, :data, :caption);";
			$prepare = $pdo->prepare($sql);
	
			$data = [
				'message_id' => $message_id,
				'from_chat_id' => $chat_id
			];
			$time = time();
			$prepare->execute(['user_id'=>$user_id, 'offset'=>0, 'time'=>$time, 'type'=>'f2a', 'data'=>json_encode($data), 'caption'=>'']);
			
			bot('sendMessage', [
				'chat_id'=>$chat_id,
				'reply_to_message_id'=>$message_id,
				'text'=>"✅ پیام مورد نظر شما در صف هدایت همگانی قرار گرفت.
	
👇🏻 برای لغو هدایت همگانی این پیام دستور زیر را بفرستید.
/determentf2a_{$time}",
				'reply_markup'=>$panel
			]);
		}
	}
	elseif (preg_match('@\/determent(?<type>f2a|s2a|gift)\_(?<time>[0-9]+)@i', $text, $matches)) {
		$type = $matches['type'];
		$time = $matches['time'];
		if ($type == 's2a') {
			$prepared = $pdo->prepare("SELECT * FROM `sendlist` WHERE `type`!='f2a' AND `time`=:time;");
			$prepared->execute(['time' => $time]);
			$fetch = $prepared->fetchAll();
			if (count($fetch) > 0) {
				$prepare = $pdo->prepare("DELETE FROM `sendlist` WHERE `user_id`={$user_id} AND `time`=:time;");
				$prepare->execute(['time' => $time]);
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>'✅ پیام مورد نظر شما از صف ارسال همگانی خارج شد.'
				]);
			}
			else {
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>'❌ هیچ پیامی با این شناسه وجود ندارد.'
				]);
			}
		}
		elseif ($type == 'f2a') {
			$prepared = $pdo->prepare("SELECT * FROM `sendlist` WHERE `type`='f2a' AND `time`=:time;");
			$prepared->execute(['time' => $time]);
			$fetch = $prepared->fetchAll();
			if (count($fetch) > 0) {
				$prepare = $pdo->prepare("DELETE FROM `sendlist` WHERE `user_id`={$user_id} AND `time`=:time;");
				$prepare->execute(['time' => $time]);
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>'✅ پیام مورد نظر شما از صف هدایت همگانی خارج شد.'
				]);
			}
			else {
				bot('sendMessage', [
					'chat_id'=>$chat_id,
					'reply_to_message_id'=>$message_id,
					'text'=>'❌ هیچ پیامی با این شناسه وجود ندارد.'
				]);
			}
		}
	}
	##-------------------
	elseif (preg_match('|/backup\s?\_?@?(?<bot>[a-zA-Z0-9\_]+bot)|ius', $text, $matches)) {
		$botid = strtolower($matches['bot']);
		if (is_dir("Bots/$botid/")) {
			$prepared = $pdo->prepare("SELECT * FROM `{$botid}_members`;");
			$prepared->execute();
			$fetch = $prepared->fetchAll(PDO::FETCH_ASSOC);
			file_put_contents("Bots/{$botid}/data/members.json", json_encode($fetch));
			$file_to_zip = array(
				"Bots/{$botid}/data/list.json",
				"Bots/{$botid}/data/data.json",
				"Bots/{$botid}/data/members.json"
			);
			$file_name = date('Y-m-d') . '_' . $botid . '_backup.zip';
			CreateZip($file_to_zip, $file_name, "{$botid}_147852369");
			$time = date('Y/m/d - H:i:s');
			bot('sendDocument', [
				'chat_id' => $chat_id,
				'parse_mode' => 'html',
				'document' => $zipfile = new CURLFile($file_name),
				'caption' => "💾 نسخه پشتیبان\n\n🕰 <i>$time</i>\n\n👆🏻 این فایل شامل تمامی اطلاعات ربات @{$botid} است."
			]);
			sendMessage($chat_id, "👇🏻 برای ادامه مدیریت، از منوی زیر استفاده کنید.", null, null, $menu);
			unlink($file_name);
			unlink("Bots/{$botid}/data/members.json");
		}
		else {
			sendMessage($chat_id, "❌ هیچ رباتی با یوزرنیم @$botid وجود ندارد.", 'markdown', $message_id, $backpanel);
		}
	}
	elseif ($text == "✖️ حذف ربات") {
		$data['step'] = "deletebot";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
			]
		]);
		respondMessage("🤖 یوزرنیم ربات مورد نظر خود را ارسال نمایید.", $back_keyboard, 'markdown');
	}
	elseif ($step == "deletebot" and isset($text)) {
		$data['step'] = "none";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		$id = strtolower(trim(str_replace("@", null, $text)));
		$botid = $id;
		if (is_dir("Bots/$id/")) {
			$prepared = $pdo->prepare("SELECT * FROM `{$botid}_members`;");
			$prepared->execute();
			$fetch = $prepared->fetchAll(PDO::FETCH_ASSOC);
			file_put_contents("Bots/{$botid}/data/members.json", json_encode($fetch));
			$file_to_zip = array(
				"Bots/{$botid}/data/list.json",
				"Bots/{$botid}/data/data.json",
				"Bots/{$botid}/data/members.json"
			);
			$file_name = date('Y-m-d') . '_' . $botid . '_backup.zip';
			CreateZip($file_to_zip, $file_name, "{$botid}_147852369");
			$time = date('Y/m/d - H:i:s');
			bot('sendDocument', [
				'chat_id' => $chat_id,
				'parse_mode' => 'html',
				'document' => $zipfile = new CURLFile($file_name),
				'caption' => "💾 نسخه پشتیبان\n\n🕰 <i>$time</i>\n\n👆🏻 این فایل شامل تمامی اطلاعات ربات @{$botid} است."
			]);
			sendMessage($chat_id, "👇🏻 برای ادامه مدیریت، از منوی زیر استفاده کنید.", null, null, $menu);
			unlink($file_name);
			unlink("Bots/{$botid}/data/members.json");

			$config = file_get_contents('Bots/' . $id . '/config.php');
			preg_match_all('/\$Dev\s=\s"(.*?)";/', $config, $match);
			preg_match_all('/\$Token\s=\s"(.*?)";/', $config, $matchh);
			file_get_contents("https://api.telegram.org/bot".$matchh[1][0]."/deleteWebHook");
			$sdminn = $match[1][0];
			$data = json_decode(file_get_contents('Data/' . $sdminn . '/data.json'), true);
			$search = array_search('@' . $id, $data['bots']);
			unset($data['bots'][$search]);
			$data['bots'] = array_values($data['bots']);
			file_put_contents('Data/' . $sdminn . '/data.json', json_encode($data));
			sendMessage($sdminn, "🤖 ربات شما « @{$id} » توسط مدیریت حذف گردید.", null, $message_id, $panel);
			deleteFolder('Bots/' . $id . '/');
			respondMessage("🤖 ربات « @{$id} » با موفقیت حذف گردید.", $panel);

			$pdo->exec("DROP TABLE IF EXISTS `{$id}_members`;");
			$prepare = $pdo->prepare("DELETE FROM `bots` WHERE `username`='{$id}';");
			$prepare->execute();

			$prepare = $pdo->prepare("DELETE FROM `bots_sendlist` WHERE `bot_username`='{$id}';");
			$prepare->execute();
		} else {
			respondMessage("❌ هیچ رباتی با یوزرنیم « @{$id} » یافت نشد.", $panel);
		}
	}
	elseif ($text == "🔒 مسدود کردن") {
		$data['step'] = "banuser";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
			]
		]);
		respondMessage("👤 شناسه تلگرامی کاربر مورد نظر خود را ارسال نمایید.", $back_keyboard, 'markdown');
	}
	elseif ($step == "banuser" and is_numeric($text)) {
		$data['step'] = '';
		file_put_contents("Data/$from_id/data.json", json_encode($data));
		if ($text == $from_id) {
			respondMessage("⛔️ شما نمی توانید خودتان را مسدود کنید.", $panel, 'markdown');
		}
		elseif (!in_array($text, $list['ban'])) {
			$user_bots = json_decode(file_get_contents('Data/' . $text . '/data.json'), true)['bots'];
			if (count($user_bots) > 0) {
				foreach ($user_bots as $bot) {
					$bot = str_replace('@', '', $bot);
					$botid = $bot;
					$prepared = $pdo->prepare("SELECT * FROM `{$botid}_members`;");
					$prepared->execute();
					$fetch = $prepared->fetchAll(PDO::FETCH_ASSOC);
					file_put_contents("Bots/{$botid}/data/members.json", json_encode($fetch));
					$file_to_zip = array(
						"Bots/{$botid}/data/list.json",
						"Bots/{$botid}/data/data.json",
						"Bots/{$botid}/data/members.json"
					);
					$file_name = date('Y-m-d') . '_' . $botid . '_backup.zip';
					CreateZip($file_to_zip, $file_name, "{$botid}_147852369");
					$time = date('Y/m/d - H:i:s');
					bot('sendDocument', [
						'chat_id' => $chat_id,
						'parse_mode' => 'html',
						'document' => $zipfile = new CURLFile($file_name),
						'caption' => "💾 نسخه پشتیبان\n\n🕰 <i>$time</i>\n\n👆🏻 این فایل شامل تمامی اطلاعات ربات @{$botid} است.",
						'reply_markup' => $keyboard
					]);
					unlink($file_name);
					unlink("Bots/{$bot}/data/members.json");

					sendMessage($text, "🤖 ربات « @{$bot} » توسط مدیریت حذف گردید.");
					deleteFolder('Bots/' . $bot . '/');
					$config = file_get_contents('Bots/' . $bot . '/config.php');
					preg_match('/\$Token\s=\s"(.*?)";/', $config, $matches);
					file_get_contents('https://api.telegram.org/bot' . $matches[1] . '/deleteWebhook');

					$pdo->exec("DROP TABLE IF EXISTS `{$bot}_members`;");
					$prepare = $pdo->prepare("DELETE FROM `bots` WHERE `username`='{$bot}';");
					$prepare->execute();

					$prepare = $pdo->prepare("DELETE FROM `bots_sendlist` WHERE `bot_username`='{$bot}';");
					$prepare->execute();
				}
			}
			deleteFolder('Data/' . $text . '/');

			$list['ban'][] = $text;
			file_put_contents('Data/list.json', json_encode($list));
			sendMessage($text, "❌ شما مسدود شدید و دیگر ربات به پیام های شما جواب نخواهد داد.", null, null, $remove);
			respondMessage("⛔️ کاربر « [$text](tg://user?id=$text) » با موفقیت مسدود شد.", $panel, 'markdown');
		}
		else {
			respondMessage("⛔️ کاربر « [$text](tg://user?id=$text) » از قبل مسدود است.", $panel, 'markdown');
		}
	}
	elseif ($text == "🔓 آزاد کردن") {
		$data['step'] = "unbanuser";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		$back_keyboard = json_encode([
			'inline_keyboard' => [
				[['text'=>'🔙 بازگشت به مدیریت', 'callback_data'=>'🔙 بازگشت به مدیریت']]
			]
		]);
		respondMessage("👤 شناسه تلگرامی کاربر مورد نظر خود را ارسال نمایید.", $back_keyboard, 'markdown');
	}
	elseif ($step == "unbanuser" and is_numeric($text)) {
		$data['step'] = "none";
		file_put_contents("Data/$from_id/data.json",json_encode($data));
		if (in_array($text, $list['ban'])) {
			$search = array_search($text, $list['ban']);
			unset($list['ban'][$search]);
			$list['ban'] = array_values($list['ban']);
			file_put_contents("Data/list.json",json_encode($list, true));
			respondMessage("✅ کاربر « [$text](tg://user?id=$text) » با موفقیت آزاد شد.", $panel, 'markdown');
			sendMessage($text, "✅ شما آزاد شدید.\n\n💠 دستور /start را ارسال نمایید.", 'markdown', null);
		}
		else
		sendMessage($text, '❌ این کاربر در لیست سیاه نیست.', 'markdown', null);
	}
	elseif (preg_match("|\/unban([\_\s])([0-9]+)|i", $text, $match)) {
		if (in_array($match[2], $list['ban'])) {
			$search = array_search($match[2], $list['ban']);
			unset($list['ban'][$search]);
			$list['ban'] = array_values($list['ban']);
			file_put_contents("Data/list.json",json_encode($list, true));
			sendMessage($chat_id, "✅ کاربر « [$match[2]](tg://user?id=$match[2]) » با موفقیت آزاد شد.", 'markdown', null, $panel);
			sendMessage($match[2], "✅ شما آزاد شدید.\n\n💠 دستور /start را ارسال نمایید.", 'markdown', null, $menu);
		}
		else
		sendMessage($chat_id, '❌ این کاربر در لیست سیاه نیست.', 'markdown', null);
	}
}

@unlink('error_log');