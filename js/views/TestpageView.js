define([
    'ViewExtend',
    'jquery',
    'underscore',
    'backbone',
    'modules/notification/js/views/NotificationView',
    'modules/optivo/js/views/OptivoView',
    'text!modulesSrc/optivo/templates/testpage.html'
], function (View, $, _, Backbone, NotificationView, OptivoView, TestpageTemplate) {
    'use strict';

    return function () {
        View.namespace = 'optivo';

        View.code = Backbone.View.extend({
            el: $('.content-wrapper'),

            events: {
                'click .senddemo': 'send'
            },

            initialize: function () {
                _.bindAll(this, 'render', 'send', 'callback');

                if (_.isUndefined(_.singleton.view.notification)) {
                    _.singleton.view.notification = new NotificationView();
                }
                this.notification = _.singleton.view.notification;

                this.render();
            },

            render: function () {
                var that = this,
                    data = {
                        welcome:       'danger',
                        optinnl:       'danger',
                        optinreminder: 'danger',
                        pwlost:        'success'
                    };

                if (_.c('mail_activated') === '1') {
                    data.welcome = 'success';
                }

                if (_.c('reminder_activated') === '1') {
                    data.optinreminder = 'success';
                }

                if (_.c('mod_newsletter_activated') === '1') {
                    data.optinnl = 'success';
                }

                this.$el.html(_.template(TestpageTemplate, data));
                _.delay(function () {
                    that.goTo('call/optivo', false);
                }, 2000);
            },

            send: function (elem) {
                var that = this,
                    callback = function (resp) {
                        that.callback(resp);
                    };

                if (_.isUndefined(_.singleton.model.login) || _(_.singleton.model.login.get('email')).isBlank()) {
                    this.notification.setOptions({
                        title:       'Error',
                        description: 'Please login first!',
                        type:        'error',
                        position:    'stack-bottomright'
                    }).show();

                    return false;
                }

                if (_.isUndefined(_.singleton.view.optivo)) {
                    _.singleton.view.optivo = new OptivoView();
                }

                _.singleton.view.optivo.sendTransactionMail({
                    'recipient': _.singleton.model.login.get('email'),
                    'uid':       _.singleton.model.login.get('uid'),
                    'mailtype':  $(elem.currentTarget).data('template')
                }, callback);

                return this;
            },

            callback: function (resp) {
                // show notice
                if (_(resp).isUndefined() === false && resp.data.status === 'success' && resp.data.code === 200) {
                    this.notification.setOptions({
                        title:       'Success',
                        description: 'Mail was sent',
                        type:        'info',
                        position:    'stack-topright'
                    }, true);
                } else {
                    this.notification.setOptions({
                        title:       'Error',
                        description: 'Mail was NOT sent. See into console for response.',
                        type:        'error',
                        position:    'stack-topright'
                    }, true);
                    _.debug.log('error on sending', resp);
                }
                this.notification.show();
            }
        });

        return View;
    }
});