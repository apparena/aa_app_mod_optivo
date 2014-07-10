define([
    'ModelExtend',
    'underscore',
    'backbone'
], function (Model, _, Backbone) {
    'use strict';

    return function () {
        Model.namespace = 'optivo';

        Model.code = Backbone.Model.extend({
            defaults: {
                action: 'sendtransactionmail',
                module: 'aa_app_mod_optivo'
            }
        });

        return Model;
    }
});