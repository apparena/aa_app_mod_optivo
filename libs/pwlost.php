<?php
defined('_VALID_CALL') or die('Direct Access is not allowed.');

// check if recipient exist
$sql = "SELECT
                user.uid,
                data.firstname,
                data.lastname,
                data.additional
            FROM
                " . TBL_AUTH_USER . " AS user
            LEFT JOIN
                " . TBL_AUTH_DATA . " AS data
                ON data.auth_uid = user.uid
            WHERE
                user.user = :email
            AND i_id = :i_id
            LIMIT 1
            ";

$stmt = $db->prepare($sql);
$stmt->bindParam(':email', $recipient, PDO::PARAM_STR);
$stmt->bindParam(':i_id', $i_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0)
{
    $user   = $stmt->fetchObject();
    $secret = md5($user->additional . $recipient . uniqid($i_id));
    unset($stmt);

    if ($mailtype === 'pwlost')
    {
        $url .= '#page/participate/password/' . $secret;
        // ok recipient exists, create DB entry and send mail
        $sql = "INSERT INTO
                        " . TBL_AUTH_PWLOST . "
                    SET
                        auth_uid = :auth_uid,
                        secret = :secret
                    ON DUPLICATE KEY UPDATE
                        secret = :secret
                    ";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':auth_uid', $user->uid, PDO::PARAM_INT);
        $stmt->bindParam(':secret', $secret, PDO::PARAM_STR);
        $stmt->execute();
    }
}
else
{
    $return['message'] = 'E-Mail is not stored in database. We have no additional informations. Mail was not send. Maybe Social Connect user?';
    // skip mailsending
    $skip = true;
}
