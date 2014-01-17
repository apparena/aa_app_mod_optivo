<?php
//http://apps.de/advent/source/modules/optivo/libs/cli/export.php?debug=1
//https://www.adventskalender.co/modules/optivo/libs/cli/export.php?debug=1
if (file_exists('../../../../configs/config.php'))
{
    require_once '../../../../configs/config.php';

    //if (defined('ENV_MODE') && ENV_MODE === 'dev')
    //{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(-1);
    //}
}
else
{
    throw new Exception('Config file not exist. Please rename config_sample.php to config.php in /configs/ and fill it with live ;)');
}
require_once '../../../../libs/AppArena/Utils/class.database.php';

try
{
    $db = new \com\apparena\utils\database\Database($db_user, $db_host, $db_name, $db_pass, $db_option);
    // set all returned value keys to lower cases
    $db->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    // return all query requests automatically as object
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    $separator    = ';';
    $file = '../../../../tmp/export/';
    $current_date = new DateTime('now', new DateTimeZone($aa_default_timezone));
    $api          = array();
    $door_id      = array();

    $content                  = array();
    $content['bmRecipientId'] = 'bmRecipientId';
    $content['var2']          = 'var2'; // company name
    $content['var3']          = 'var3'; // url
    $content['var6']          = 'var6'; // app base color
    $content['var7']          = 'var7'; // subject
    $content['var9']          = 'var9'; // company name again as sender
    $content['var16']         = 'var16'; // header image
    $content['var17']         = 'var17'; // content (door image)
    $content['var18']         = 'var18'; // footer
    $content                  = implode($separator, $content) . PHP_EOL;
    file_put_contents($file . OPTIVO_UPLOAD_REMINDER_FILE, $content);

    $chunk = 100;
    $last  = 0;
    do
    {
        $sql = "SELECT
                    CONCAT(data.firstname, data.lastname) AS  name,
                    data.email,
                    user.i_id
                FROM
                    mod_auth_user AS user
                RIGHT JOIN
                    mod_auth_user_data AS data
                    ON data.auth_uid = user.uid
                WHERE
                    data.optin_reminder = 1
                ORDER BY
                    user.i_id
                LIMIT
                    " . $last . ", " . $chunk . "
                ";

        $result = $db->query($sql);
        $users  = $result->fetchAll();
        $last += $result->rowCount();

        foreach ($users AS $user)
        {
            if (!empty($_GET['debug']))
            {
                echo '<pre>';
                print_r($user);
                echo '</pre>';
            }
            $i_id = $user->i_id;

            if (!isset($api[$i_id]))
            {
                $api[$i_id]['config']   = json_decode(file_get_contents('https://manager.app-arena.com/api/v1/instances/' . $i_id . '/config.json?limit=0'), true);
                $api[$i_id]['instance'] = json_decode(file_get_contents('https://manager.app-arena.com/api/v1/instances/' . $i_id . '.json'), true);
            }
            $config   = $api[$i_id]['config']['data'];
            $instance = $api[$i_id]['instance']['data'];

            //echo '<pre>';
            //print_r($api[$i_id]);
            //echo '</pre>';

            // check if instance is active
            if ($instance['active'] !== '1')
            {
                continue;
            }

            // get active door ID
            if (!isset($door_id[$i_id]))
            {
                $door_id[$i_id] = null;
                for ($count = 1; $count <= $config['door_amount']['value']; $count++)
                {
                    $door_date = new DateTime($config['door_' . $count . '_validity_period_start']['value'], new DateTimeZone($aa_default_timezone));

                    if (!empty($_GET['debug']))
                    {
                        echo '<pre>';
                        $check = '<span style="color:red;">false</span>';
                        if ($door_date->format('d-m-Y') === $current_date->format('d-m-Y'))
                        {
                            $check = '<span style="color:green;">TRUE</span>';
                        }
                        print_r($door_date->format('d-m-Y') . ' === ' . $current_date->format('d-m-Y') . ' - ' . $check);
                        echo '</pre>';
                    }

                    if ($door_date->format('d-m-Y') === $current_date->format('d-m-Y'))
                    {
                        $door_id[$i_id] = $count;
                        break;
                    }
                }
            }

            if ($door_id[$i_id] === null)
            {
                continue;
            }

            // create new data line
            $door_type    = $config['door_' . $door_id[$i_id] . '_type']['value'];
            $company_name = $config['wizard_company_name']['value'];
            $door_title   = $config['door_' . $door_id[$i_id] . '_type_' . $door_type . '_title']['value'];
            $subject      = $config['mail_template_subject_reminder']['value'];
            $subject      = str_replace('{DOOR_TITLE}', $door_title, $subject);
            $subject      = str_replace('{DOOR_ID}', $door_id[$i_id], $subject);

            // create new data line
            $content                  = array();
            $content['bmRecipientId'] = $user->email;
            $content['var2']          = $company_name;
            $content['var3']          = $instance['fb_canvas_url'] . "share.php?i_id=" . $i_id;
            //$content['var6']          = $config['app_base_color']['value'];
            $content['var6']  = $config['door_' . $door_id[$i_id] . '_type_' . $door_type . '_image']['src'];
            $content['var7']          = $subject;
            $content['var9']          = $company_name;
            $content['var16'] = $config['mail_header']['src'];
            $content['var17'] = $config['door_' . $door_id[$i_id] . '_type_' . $door_type . '_desc']['value'];
            $content['var18']         = $config['mail_footer']['value'];

            foreach ($content AS $key => $value)
            {
                $content[$key] = '"' . $value . '"';
            }

            $content = implode($separator, $content) . PHP_EOL;
            echo utf8_decode($content);
            file_put_contents($file . OPTIVO_UPLOAD_REMINDER_FILE, utf8_decode($content), FILE_APPEND);
        }
    }
    while ($result->rowCount() > 0);

    /*echo '<pre>';
    print_r('done, start upload');
    echo '</pre>';*/

    // start upload
    $connection = ssh2_connect(OPTIVO_SERVER, OPTIVO_PORT, array('hostkey' => 'ssh-rsa'));
    if (ssh2_auth_pubkey_file($connection, OPTIVO_USER, OPTIVO_PUBLIC_KEY, OPTIVO_PRIVATE_KEY))
    {
        /*echo '<pre>';
        print_r('Public Key Authentication Successful');
        echo '</pre>';*/

        // upload file with sftp
        $sftp        = ssh2_sftp($connection);
        $remote_file = OPTIVO_UPLOAD_PATH . '/' . OPTIVO_UPLOAD_REMINDER_FILE;
        $local_file  = $file . OPTIVO_UPLOAD_REMINDER_FILE;

        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w');
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

        // flush buffers/close session and stream
        @fclose($stream);
        ssh2_exec($connection, 'exit');
    }
    else
    {
        throw new Exception('Public Key Authentication Failed');
    }

    exit(1);
}
catch (PDOException $e)
{
    if (defined('ENV_MODE') && ENV_MODE !== 'product')
    {
        echo '<pre>';
        print_r($e->getMessage());
        echo '</pre>';
    }
    exit(0);
}