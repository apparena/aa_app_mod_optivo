<?php
if (empty($_POST['uid']) || !is_numeric($_POST['uid']))
{
    throw new \Exception('No uid for optin provided in ' . __FILE__);
}

// generate key secret for url
$secret = md5($i_id . $mailtype . uniqid() . time());

// store key in database
$sql = "INSERT INTO
            mod_optivo_optin
        SET
            auth_uid = :auth_uid,
            secret = :secret,
            type = :type
        ";

$type = str_replace('_optin', '', $mailtype);

$stmt = $db->prepare($sql);
$stmt->bindParam(':auth_uid', $_POST['uid'], PDO::PARAM_INT);
$stmt->bindParam(':secret', $secret, PDO::PARAM_STR, 32);
$stmt->bindParam(':type', $type, PDO::PARAM_STR);
$stmt->execute();

// generate url
$instance = \Apparena\Api\Instance::init();
$url      = $instance->data->fb_canvas_url . \Apparena\App::$i_id . '/' . \Apparena\App::$locale . '/optin/' . $secret . '/';