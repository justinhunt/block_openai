/**
 * a helper to manage ajax openai calls
 *
 * @module     block_openai/ajaxhelper
 * @class      ajaxhelper
 * @package    block_openai
 * @copyright  2023 Justin Hunt <poodllsupport@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log','core/ajax'],
    function($, log, Ajax) {

        "use strict"; // jshint ;_;

        log.debug('ajax helper: initialising');

        return {
            //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
            init: function (props) {
                this.registerEvents();

            },

            registerEvents: function(){
                //$('.writemodelanswer').on('click',function() {
                $('.btn.btn-secondary.ml-0').on('click',function() {
log.debug('clicked')
                    var unitindex = $(this).data('unitindex');
                    var prompt = $('#id_prompts_' + unitindex + '  option:selected').text();
                    var topic = $('input[name="dquestionid[' + unitindex + ']"]:checked').parent().text();
                    var topicresponse = $('#id_solomodelanswer_' + unitindex);
                    //push results to server
                    Ajax.call([{
                        methodname: 'block_openai_fetch_completion',
                        args: {
                            completiontask: 'basicchat',
                            taskparam1: prompt,
                            taskparam2: topic,
                            taskparam3: 'empty',
                            taskparam4: 'empty',
                        }
                        }])[0].then(function(resp){
                            topicresponse.text(resp);
                            //write value of ret to text area
                            log.debug(resp);
                        }
                    );


                });
            }

        };//end of return value
    });