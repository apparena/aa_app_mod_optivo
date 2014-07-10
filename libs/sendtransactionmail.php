<?php
use \Apparena\App;

defined('_VALID_CALL') or die('Direct Access is not allowed.');
// Transaction mail configuration
define('ADDITIONAL_KEY_AMOUNT', 20);
define('TBL_AUTH_USER', 'mod_auth_user');
define('TBL_AUTH_DATA', 'mod_auth_user_data');
define('TBL_AUTH_PWLOST', 'mod_auth_pwlost');

function api_encode($value)
{
    return urlencode(utf8_decode($value));
}

$instance   = \Apparena\Api\Instance::init();
$url        = $url = $instance->data->fb_canvas_url . \Apparena\App::$i_id . '/' . \Apparena\App::$locale . '/';
$api_url    = 'https://api.broadmail.de/http/form/' . OPTIVO_AUTH . '/sendtransactionmail';
$api_params = array();
$skip       = false;

try
{
    if (empty($_POST['recipient']))
    {
        throw new \Exception('No email recipient provided in ' . __FILE__);
    }
    $recipient = $_POST['recipient'];
    unset($_POST['recipient']);

    if (empty($_POST['mailtype']))
    {
        throw new \Exception('No mailing type provided in ' . __FILE__);
    }
    $mailtype = $_POST['mailtype'];
    unset($_POST['mailtype']);

    if ($mailtype === 'pwlost')
    {
        include_once('pwlost.php');
    }

    if ($mailtype === 'welcome')
    {
        if (__c('mail_activated') !== '1')
        {
            throw new \Exception('mail_activated is not activated in ' . __FILE__);
        }
        $url = $instance->data->share_url;
    }

    if ($mailtype === 'greetingcard')
    {
        if (__c('greetingcard_activated') !== '1')
        {
            throw new \Exception('greetingcard_activated is not activated in ' . __FILE__);
        }
    }

    if ($mailtype === 'reminder_optin' || $mailtype === 'nl_optin')
    {
        if ($mailtype === 'reminder_optin' && __c('reminder_activated') !== '1')
        {
            throw new \Exception('reminder_activated is not activated in ' . __FILE__);
        }
        if ($mailtype === 'reminder_optin' && __c('disable_reminder_optin_mail') === '1')
        {
            throw new \Exception('reminder optin is diabled in ' . __FILE__);
        }
        if ($mailtype === 'nl_optin' && __c('mod_newsletter_activated') === '0')
        {
            throw new \Exception('mod_newsletter_activated is not activated in ' . __FILE__);
        }
        if ($mailtype === 'nl_optin' && __c('disable_newsletter_optin_mail') === '1')
        {
            throw new \Exception('newsletter optin is diabled in ' . __FILE__);
        }
        include_once('optin.php');
    }

    // continue request url building
    $mail_id = __c('mail_template_' . strtolower($mailtype));

    // set default values
    $api_params['bmRecipientId'] = $recipient;
    $api_params['bmMailingId']   = $mail_id;
    $api_params['var2']          = __c('wizard_company_name'); // company name
    $api_params['var3']          = $url; // url
    $api_params['var6']          = __c('app_base_color'); // app base color
    $api_params['var7']          = __c('mail_template_subject_' . $mailtype); // subject
    $api_params['var9']          = __c('wizard_company_name'); // company name again as sender
    $api_params['var16']         = __c('mail_header', 'src'); // header image
    $api_params['var17']         = __c('mail_template_content_' . $mailtype); // content
    $api_params['var18']         = __c('mail_footer'); // footer

    // overwrite values with ajax call values
    for ($counter = 0; $counter <= ADDITIONAL_KEY_AMOUNT; $counter++)
    {
        $key = 'var' . $counter;
        if (isset($_POST[$key]))
        {
            $api_params[$key] = $_POST[$key];
        }
    }

    if ($skip === false)
    {
        // curl call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $api_params);
        $return['return_message'] = curl_exec($ch);
        $debug_curl_info          = curl_getinfo($ch);
        curl_close($ch);

        // Call Url and send transaction mail
        if (defined('ENV_MODE') && ENV_MODE !== 'production')
        {
            $return['mail_type']         = $mailtype;
            $return['mail_id']           = $mail_id;
            $return['debug_curl_info']   = $debug_curl_info;
            $return['debug_curl_params'] = $api_params;
        }
        unset($api_params);
        //$return['return_message'] = file_get_contents($api_url . $api_params);
        if (strpos($return['return_message'], "enqueued") !== false)
        {
            $return['code']    = 200;
            $return['status']  = 'success';
            $return['message'] = 'mail successfully sent';
        }
    }
}
catch (\Exception $e)
{
    // prepare return data
    $return['code']      = $e->getCode();
    $return['status']    = 'error';
    $return['message']   = $e->getMessage();
    $return['trace']     = $e->getTrace();
    $return['mail_type'] = $mailtype;
}
catch (\PDOException $e)
{
    // prepare return data for database errors
    $return['code']    = $e->getCode();
    $return['status']  = 'error';
    $return['message'] = $e->getMessage();
    $return['trace']   = $e->getTrace();
}