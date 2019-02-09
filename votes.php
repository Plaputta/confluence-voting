<?php
if (!isset($_GET['page_id']) || !isset($_GET['user_uid']) || !isset($_GET['option_key'])) {
    http_response_code(400);
    exit;
}

$db = substr($url["path"], 1);
$url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$server = $url["host"];
$username = $url["user"];
$password = $url["pass"];
$db = substr($url["path"], 1);

$conn = new mysqli($server, $username, $password, $db);

if($conn->connect_errno > 0){
    die('Unable to connect to database [' . $conn->connect_error . ']');
}

if (isset($_GET['vote'])) {
    $statement = $conn->prepare("INSERT INTO `votes` (`id`, `page_id`, `user_uid`, `option_key`, `timestamp`, `vote`) VALUES (NULL, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `timestamp` = VALUES(`timestamp`), `vote` = IF(`vote`='0',1,0);");
    $timestamp = time();
    $vote = 1;
    $statement->bind_param('sssii', $_GET['page_id'], $_GET['user_uid'], $_GET['option_key'], $timestamp, $vote);
    $statement->execute();
    $statement->free_result();
}

$statement = $conn->prepare("SELECT `user_uid`, `vote` FROM `votes` WHERE `page_id` = ? AND `option_key` = ?");
$statement->bind_param('ss', $_GET['page_id'], $_GET['option_key']);
$votes = 0;
$user_vote = null;
if ($statement->execute()) {
    $statement->bind_result($user_uid, $vote);
    while ($statement->fetch()) {
        if ($user_uid == $_GET['user_uid']) {
            $user_vote = $vote;
        }
        if ($vote == 1) {
            $votes++;
        }
    }
    $statement->free_result();
}
$conn->close();

header('Content-Type: image/png');

if ($user_vote === null) {
    $img = imagecreatefrompng('vote-none.png');
} else if ($user_vote == 1) {
    $img = imagecreatefrompng('vote-up.png');
} else {
    $img = imagecreatefrompng('vote-down.png');
}

imagestring($img, 5, 22, 2, strval($votes), imagecolorallocate($img, 0,0,0));
imagepng($img);
