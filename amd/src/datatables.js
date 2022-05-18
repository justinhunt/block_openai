define(['jquery', 'core/log', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js'], function ($, log, datatables) {
    "use strict"; // jshint ;_;

    /*
    This file contains class and ID definitions.
     */

    log.debug('Poodll Classroom Datatables helper: initialising');

    return {
        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function (props) {
            //pick up opts from html
            var thetable = $('#' + props.tableid);
            thetable.DataTable(props.tableprops);
        },

        getDataTable: function(tableid){
            return $('#' + tableid).DataTable();
        }

    };//end of return value
});