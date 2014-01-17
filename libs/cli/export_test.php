<?php
//http://apps.de/advent/source/modules/aa_app_mod_optivo/libs/cli/export_test.php
//https://www.adventskalender.co/modules/aa_app_mod_optivo/libs/cli/export_test.php
if (file_exists('../../../../configs/config.php'))
{
    require_once '../../../../configs/config.php';

    if (defined('ENV_MODE') && ENV_MODE === 'dev')
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(-1);
    }
}
else
{
    throw new Exception('Config file not exist. Please rename config_sample.php to config.php in /configs/ and fill it with live ;)');
}

$file = '../../../../tmp/export/';

$connection = ssh2_connect(OPTIVO_SERVER, OPTIVO_PORT, array('hostkey' => 'ssh-rsa'));
if (ssh2_auth_pubkey_file($connection, OPTIVO_USER, OPTIVO_PUBLIC_KEY, OPTIVO_PRIVATE_KEY))
{
    echo '<pre>';
    print_r('Public Key Authentication Successful');
    echo '</pre>';

    // upload file with sftp
    $sftp        = ssh2_sftp($connection);
    $remote_file = OPTIVO_UPLOAD_PATH . '/' . OPTIVO_UPLOAD_REMINDER_FILE;
    $local_file  = $file . OPTIVO_UPLOAD_REMINDER_FILE;
    $stream      = @fopen("ssh2.sftp://$sftp$remote_file", 'w');

    if (!$stream)
    {
        throw new Exception("Could not open file: $remote_file");
    }

    $data_to_send = @file_get_contents($local_file);
    if ($data_to_send === false)
    {
        throw new Exception("Could not open local file: $local_file.");
    }

    if (@fwrite($stream, $data_to_send) === false)
    {
        throw new Exception("Could not send data from file: $local_file.");
    }

    @fclose($stream);

    // flush buffers/close session
    ssh2_exec($connection, 'exit');
}
else
{
    throw new Exception('Public Key Authentication Failed');
}