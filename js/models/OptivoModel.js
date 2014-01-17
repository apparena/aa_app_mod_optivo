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
                module: 'optivo'
            }
        });

        return Model;
    }
});