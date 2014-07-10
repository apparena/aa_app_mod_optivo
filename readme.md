# App-Arena.com App Module Optivo
* **Github:** https://github.com/apparena/aa_app_mod_optivo
* **Docs:**   http://www.appalizr.com/index.php/optivo.html
* This is a module of the [aa_app_template](https://github.com/apparena/aa_app_template)

## Module job
Optivo API to send transaction mailings. Needed for sign-up mails or newsletter double opt-ins and other ones. Otherwise you can import CSV files directly to Optivo, to send more than one mail at the same time. To send reminder mails, it exist an export/import tool. You can find more information to do this, on the single pages.

## Important information
**Note: Before you can store any mailings, you must install the database tables!**

> To export user data for own mailings in a different mailing tool, you need **libssh2-php** on your server to connect over an SSH-SFTP connection. Additionally you need a SSH-Key-Pair with an authentication password. The public one must be stored on the Optivo server and the private and public one on your product server. The information about the paths and the password must be implemented into the app config **(source/configs/config.php)**

### Transaktiontemplate overview
| mailtype / config id | Description |
|----|----|
| greetingcard | Greetingcard mailtemplate with selected greetingcard and text |
| nl_optin | Newsletter opt-in mailing with a link to activate the newsletter |
| welcome | Welcome mail, after participation |
| pwlost | Password lost request mail |
| reminder_optin | Reminder opt-in mail with a link to activate the reminder mailing |

### Dependencies
* [aa_app_mod_notification](https://github.com/apparena/aa_app_mod_notification)

### Important functions
* **sendTransactionMail** - description
    * mailSettings - JSON object with mail settings: recipient (e-mail address), mailtype (string), uid (user id, only for optin mails)
    * callback - Callback function that is fired after ajax response. Not required.

### Demo links
* \#page/optivo

### Examples

#### Send transaction mailing (basic)
```javascript
define(['modules/aa_app_mod_optivo/js/views/OptivoView'], function (OptivoView) {});
```
Settings for transaction mailing over the Optivo API. An Optivo account is required! Please think of the settings in the config file. See the overview for more information.

```javascript
OptivoView().init().sendTransactionMail({
    'recipient': 'user@domain.de',
    'mailtype':  'nl_optin',
    'uid':       _.uid
});
```

#### Send password lost mailing
```javascript
require([
    'modules/aa_app_mod_optivo/js/views/OptivoView',
    'modules/aa_app_mod_auth/js/models/PasswordLostModel'
], function (OptivoView, PasswordLostModel) {
    var optivo = OptivoView().init(),
        passwordLostModel;

    optivo.sendTransactionMail(mailSettings, function (resp) {
        that.callbackHandler(resp);
        passwordLostModel = PasswordLostModel().init();
        passwordLostModel.set('email', form_data.email);
        passwordLostModel.save();
    });
});
```

#### Use callback with notification
Send newsletter e-mail and show a notification on response.
```javascript
require([
    'modules/aa_app_mod_optivo/js/views/OptivoView'
], function (OptivoView) {
	var optivo = OptivoView().init();
	optivo.sendTransactionMail({
		'recipient': 'user@domain.de',
		'mailtype':  'nl_optin',
		'uid':       _.uid
	}, function(resp){
		var that = this;
		require([
			'modules/aa_app_mod_facebook/js/views/FacebookView',
			'modules/aa_app_mod_notification/js/views/NotificationView'
		], function (FacebookView, NotificationView) {
			var facebook = FacebookView().init();

			// define notification position in facebook tabs. works also on normal pages
			facebook.getScrollPosition(function (position) {
				// define default notification message
				var options = {
					title:       _.t('msg_mail_pwlost_title_error'),
					description: _.t('msg_mail_pwlost_desc_error'),
					type:        'error'
				};

				// overwrite message, if status is success
				if (resp.data.status === 'success') {
					options = {
						title:       _.t('msg_mail_pwlost_title_success'),
						description: _.t('msg_mail_pwlost_desc_success'),
						type:        'success'
					};
				}

				// define notification position
				if (position !== false) {
					options.before_open = function (pnotify) {
						pnotify.css({
							'top':  position.top,
							'left': 810 - pnotify.width()
						});
					};
					options.position = '';
				}

				// show notification
				NotificationView().init().setOptions(options, true).show();
			});
		});
	});
});
```

### Load module with require
```
modules/aa_app_mod_optivo/js/views/OptivoView
```

### Export reminder mailinglist
* `./source/modules/aa_app_mod_optivo/libs/cli/export.php?debug=1`
* `./source/modules/aa_app_mod_optivo/libs/cli/export_test.php`

> Export user data for reminder mailings as CSV file, with automatically upload on the Optivo server. Call one of the path in a command line. The output file is stored under **./source/tmp/exports/**

> With debug=1 you can get some debug outputs in your browser. This parameter is not working in the command line! You can test the export in a command line with the test file **export_test.php**.

> This function required the package **libssh2-php** on your server to open a connection over **SSH2-SFTP**. This connection is required to upload the CSV file on the Optivo server.

#### App-Manager config values
| config | default | description |
|--------|--------|--------|
| mail_activated | 0 | checkbox to activate the mailing function in your app |
| reminder_activated | 0 | checkbox to activate the user reminder mailing forms |
| disable_reminder_optin_mail | 0 | checkbox - disabled internal mail sending (reminder), to collect only email address |
| mod_newsletter_activated | 0 | checkbox to activate the newsletter mailing forms |
| disable_newsletter_optin_mail | 0 | checkbox - disabled internal mail sending (newsletter), to collect only email address |
| greetingcard_activated | 0 | checkbox to activate the greetingcard mailing forms |
| wizard_company_name | empty | company name |
| app_base_color | empty | app base color, to style newsletter and basic app layout |
| mail_header | empty | mail header image |
| mail_footer | empty | mail image footer |
| mail_template_greetingcard | 65081777125 | optivo template id for greetingcard mailing |
| mail_template_nl_optin | 64746206569 | optivo template id for newsletter opt-in mailing |
| mail_template_welcome | 61787723049 | optivo template id for welcome mailing |
| mail_template_pwlost | 63029900217 | optivo template id for password lost mailing |
| mail_template_reminder_optin | 65081775836 | optivo template id for reminder mailing |
| mail_template_subject_greetingcard | empty | subject for greetingcard mailing |
| mail_template_subject_nl_optin | empty | subject for newsletter opt-in mailing |
| mail_template_subject_welcome | empty | subject for welcome mailing |
| mail_template_subject_pwlost | empty | subject for password lost mailing |
| mail_template_subject_reminder_optin | empty | subject for reminder mailing |
| mail_template_content_greetingcard | empty | html - content for greetingcard mailing |
| mail_template_content_nl_optin | empty | html - content for graatingcard mailing |
| mail_template_content_welcome | empty | html - content for welcome mailing |
| mail_template_content_pwlost | empty | html - content for password lost mailing |
| mail_template_content_reminder_optin | empty | html - content for reminder mailing |