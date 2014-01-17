/*global define: false */
define([
    'jquery',
    'underscore',
    'modulesSrc/optivo/js/views/TestpageView'
], function ($, _, TestpageView) {
    return function () {
        //_.debug.log('optivo testpage');
        if (_.aa.env.mode !== 'product') {
            var testpageView = new TestpageView();
        } else {
            //_.debug.log('redirect');
            _.router.navigate('', {trigger: true});
        }
    };
});