<?php

define('CLIENT_ID', '???');
define('CLIENT_SECRET', '???');
define('REDIRECT_URI', "http://{$_SERVER ['HTTP_HOST']}/");

require __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setRedirectUri(REDIRECT_URI);
$client->addScope(Google_Service_Drive::DRIVE);

if (isset($_REQUEST['code'])) {
    $token = $client->authenticate($_REQUEST['code']);
    $_SESSION['accessToken'] = $token;
    header('Location: ' . REDIRECT_URI);
} elseif (!isset($_SESSION['accessToken'])) {
    header('Location: ' . $client->createAuthUrl());
}

$client->setAccessToken($_SESSION['accessToken']);

$service = new Google_Service_Drive($client);

if (!empty($_POST['files'])) {
    $zip = new ZipArchive;
    $zipFile = $zip->open('./test.zip', ZipArchive::CREATE);
    foreach ($_POST['files'] as $fileId => $fileName) {
        $content = $service->files->get($fileId, ['alt' => 'media']);
        $body = $content->getBody();
        $fileBody = '';
        while (!$body->eof()) {
            $fileBody .= $body;
        }
        $zip->addFromString($fileName, $fileBody);
        $service->files->delete($fileId);
    }
    $zip->close();
    header('Content-Disposition: attachment; filename="test.zip"');
    readfile('./test.zip');
    unlink('./test.zip');
    exit;
}

$results = $service->files->listFiles([
    'pageSize' => 5,
    'fields' => 'files(id, name, parents, fileExtension, mimeType)'
]);

echo '<form method="post">';

if (count($results->getFiles()) == 0) {
    echo 'No files found.<br>';
} else {
    echo 'Files:<br>';
    foreach ($results->getFiles() as $file) {
        echo '<input type="checkbox" name="files[' . $file->getId() . ']" value="' . $file->getName() . '"> ' . $file->getName();
    }
    echo '<div><input type="submit" value="Download"></div>';
}

echo '</form>';
