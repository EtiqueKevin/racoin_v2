<?php
(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('cli only');

// Get the config.ini file
$config = parse_ini_file(__DIR__ . '/../config/config.ini');


// Run queries with mysqli
$mysqli = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

function executeScript($mysqli, $filename): void
{
    $sql = file_get_contents($filename);
    $mysqli->multi_query($sql);
    while(mysqli_more_results($mysqli))
    {
        mysqli_next_result($mysqli);
    }

}
executeScript($mysqli, __DIR__ . '/create_schema.sql');
executeScript($mysqli, __DIR__ . '/import_data.sql');

$mysqli->close();

echo 'Database created and data imported successfully' . PHP_EOL;
