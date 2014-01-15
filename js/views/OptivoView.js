define([
    'ViewExtend',
    'underscore',
    'backbone'
], function (View, _, Backbone) {
    'use strict';

    return function () {
        View.namespace = 'optivo';

        View.code = Backbone.View.extend({

            initialize: function () {
                _.bindAll(this, 'sendTransactionMail', 'responseHandler');
            },

            sendTransactionMail: function (mailSettings, callback) {
                var that = this;

                if (typeof callback !== 'function') {
                    callback = function (resp) {
                        // do nothing
                    };
                }
                this.callback = callback;

                require(['modules/optivo/js/models/OptivoModel'], function (OptivoModel) {
                    var optivoModel = OptivoModel().init({
                        id: mailSettings.mailtype,
                        attributes:mailSettings
                    });

                    that.ajax(optivoModel.attributes, false, function (resp) {
                        that.responseHandler(resp);
                    });

                    that.log('action', 'app_mail_send', {
                        auth_uid:      _.uid,
                        auth_uid_temp: _.uid_temp,
                        code:          6001,
                        data_obj:      {
                            type:                     mailSettings.mailtype,
                            mail_activated:           _.c('mail_activated'),
                            reminder_activated:       _.c('reminder_activated'),
                            mod_newsletter_activated: _.c('mod_newsletter_activated'),
                            greetingcard_activated:   _.c('greetingcard_activated')
                        }
                    });
                    OptivoModel().remove();
                });

                return true;
            },

            responseHandler: function (res) {
                var that = this;
                require(['modules/notification/js/views/NotificationView'], function (NotificationView) {
                    if (_.aa.env.mode === 'dev') {
                        NotificationView().init().setOptions({
                            title:       'DEV MESSAGE: Mailsending',
                            description: 'Send Status:' + res.data.status + '<br>Type:' + res.data.mail_type + ' - Mail ID:' + res.data.mail_id + ' - Message:' + res.data.message,
                            type:        'notice',
                            position:    'stack-topleft',
                            delay:       8000
                        }).show();
                    }
                    that.callback(res);
                });
            }
        });

        return View;
    }
});