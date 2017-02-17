jQuery(document).ready(function() {
	//Double click on element quantity
	jQuery('body').on('dblclick', '.inline_edit', function() {
        var el = jQuery(this);

		//Get current values
        el.removeClass('inline_edit');

		//Current value
        el.find('.inline_edit_icon').remove();
		var value = el.html();
		
		//Current element ID
		var id = el.data('id');
		var what = el.data('what');
		var inputtype = el.data('inputtype') || 'text';
        inputtype = inputtype.toLowerCase();
        var func = el.data('url-func');
        var successFunc = el.data('success-func') || false;
        var errorFunc = el.data('error-func') || false;

        //For select options
        var optfunc = el.data('options-func');
        var v = el.attr('data-options-selected') || 0;

		//Check input type
		if (typeof inputtype == 'undefined') {
			inputtype = 'text';
		}

		//Create html element and focus it
        var e;
        if (inputtype == 'textarea') {
            e = jQuery('<textarea></textarea>').html(value).trigger('htmlchanged');
        } else if (inputtype == 'select') {
            var options = [];
            options = window[optfunc](what);

            e = jQuery('<select></select>')
                .data('select-options', options);

            var selected = '';
            for (k in options) {
                selected = '';
                if (k == v) {
                    selected = ' selected="selected"';
                }
                e.append('<option value="' + k + '"' + selected + '>' + options[k] + '</option>');
            }
        } else {
            e = jQuery('<input />').attr('type', inputtype).val(value);
        }

        e
        .attr('data-url-func', func)
        .attr('data-what', what)
        .attr('data-id', id)
        .attr('data-value', value)
        .attr('data-success-func', successFunc)
        .attr('data-error-func', errorFunc)
        .addClass('inline_input')
        .addClass('form-control')
        .addClass('width-100');

        el.html(e).trigger('htmlchanged');
		el.children().focus();
	});

    //On mouse click focus out
    jQuery('body').on('focusout', '.inline_input', function() {
        inline_edit_eventFunc(jQuery(this), jQuery(this).parent());
    });
    jQuery('body').on('keyup', 'input.inline_input, textarea.inline_input', function(e) {
        //On enter
        if (e.keyCode == 13) {
            inline_edit_eventFunc(jQuery(this), jQuery(this).parent());
        }
    });

    //On resize event
    jQuery(window).resize(function() {
        HandleModalHeight();
    });
    jQuery('.modal').on('htmlchanged', function() {
        //HandleModalHeight();
    });

    //Inline edit indication
    jQuery('body').on('mouseover mouseout', '.inline_edit', function(e) {
        if (e.type == 'mouseover') {
            if (jQuery(this).find('.inline_input').length == 0) {
                jQuery(this).append('<i class="fa inline_edit_icon fa-pencil-square-o pull-right"></i>');   
            }
        } else {
            jQuery(this).find('.inline_edit_icon').remove();
        }
    });

	//Create event function for edit
	var inline_edit_eventFunc = function(obj, parentobj) {
		//Get values
		var id = obj.data('id');
		var value = obj.val();
		var prevvalue = obj.data('value');
		var what = obj.data('what');
		var func = obj.data('url-func');

        var successFunc = obj.data('success-func') || false;
        var errorFunc = obj.data('error-func') || false;

		//Format url
		var url = window[func](obj);
		url += '?:what=:value'.replace(':what', what).replace(':value', value);

        //In case input type was select box
        var selectOptions = obj.data('select-options') || false;
        var defaultVal = value;
        if (selectOptions) {
            value = selectOptions[value];
        }

        //Remove icon
        jQuery(this).parent().find('.inline_edit_icon').remove();

		//Make a post method
		jQuery.ajax({
			method: 'POST',
			url: url
		})
        .done(function(data) {
			if (data.Success) {
                parentobj.attr('data-options-selected', defaultVal);
				parentobj.html(value).trigger('htmlchanged');
            	showNotifySuccess(ComponentStorage.texts.row_edit_ok);
                if (successFunc) {
                    window[successFunc](obj, what, defaultVal);
                }
			} else {
				obj.parent().html(prevvalue).trigger('htmlchanged');
            	showNotifyDanger(ComponentStorage.texts.row_edit_error);
                if (errorFunc) {
                    window[errorFunc](obj, what, defaultVal);
                }
			}
            
		})
        .fail(function(resp, textStatus, errorThrown) {
			obj.parent().html(prevvalue).trigger('htmlchanged');
            showNotifyDanger(ComponentStorage.texts.row_edit_error);
            if (errorFunc) {
                window[errorFunc](obj, what, defaultVal);
            }
		})
        .always(function() {
            parentobj.addClass('inline_edit');
        });
	};

    //Handle modals
    HandleModalHeight();

	//Enable bootstrap dropdown
	jQuery('.dropdown-toggle').dropdown();
	jQuery('[title!=""]').tooltip();

    //Close flash messages
    jQuery('body').on('click', '.storage-alert .close', function(e) {
        //First fade out
        jQuery(this).parent().parent().fadeTo('slow', 0.005, function() {
            //Slide up
            jQuery(this).slideUp();
        });
    });
    
    //Inline ajax, ex. for syncing elements from order
    jQuery('body').on('click', 'a.ajax_inline', function(e) {
        DoAjaxInline(jQuery(this), e, {});
    });

	//Ajax for clicks on elements quantity
	jQuery('body').on('click', 'a.element_quantity_set', function(e) {
        //Prevent default behaviour
        e.preventDefault();

        //Get itself
        thisElement = jQuery(this);

        //Get element ID to update
        var elementID = jQuery(this).data('element');

        //Get target element where to update
        var targetID = jQuery('#element_quantity_' + elementID);

        //Messages
        var okText = jQuery(this).data('text-success') || ComponentStorage.texts.row_edit_ok;
        var errorText = jQuery(this).data('text-error') || ComponentStorage.texts.row_edit_error;

        //Make ajax request
        jQuery.ajax({
            'url': jQuery(this).attr('href'),
            'method': 'GET'
        })
        .done(function(data) {
        	if (data != 'ERROR') {
                targetID.html(data).trigger('htmlchanged');

                //Hide image link
                if (data == '0') {
                    //thisElement.hide();
                }
                showNotifySuccess(okText);
            } else {
                showNotifyDanger(errorText);
            }
        })
        .fail(function(resp, textStatus, errorThrown) {
            showNotifyDanger(errorText);
        });
    });

/*
	//Event function for datepicker
	datepickerevent = function(e) {
    	//Add current time to input
    	target = jQuery(e.currentTarget);

    	//Get current time
    	currdate = new Date();

    	//Add time
    	val = target.val() + ' ' + ((currdate.getHours() < 10) ? '0' : '') + currdate.getHours() + ':' + ((currdate.getMinutes() < 10) ? '0' : '') + currdate.getMinutes() + ':' + ((currdate.getSeconds() < 10) ? '0' : '') + currdate.getSeconds();

    	//Write to target
    	target.val(val);
	};

	//Enable datepicker
    jQuery('.datepicker').datepicker(jQuery.extend(ComponentStorage.datepicker, {
    	format: 'yyyy-mm-dd'
		//start: 'year'
	})).on('hide', datepickerevent).on('changeDate', datepickerevent);
*/

	//Init select2 box
	InitSelect2();

    //Select all option
    jQuery('body').on('click', '.select-all', function(e) {
        e.preventDefault();

        var selectTarget = jQuery(this).data('select') || false;
        if (!selectTarget) {
            return true;
        }

        //Get target
        selectTarget = '#' + selectTarget;

        //Select all
        jQuery(selectTarget + ' > option').prop('selected', 'selected');
        jQuery(selectTarget).trigger('change');
    });

    //Deselect all option
    jQuery('body').on('click', '.select-none', function(e) {
        e.preventDefault();

        var selectTarget = jQuery(this).data('select') || false;
        if (!selectTarget) {
            return true;
        }

        //Get target
        selectTarget = '#' + selectTarget;

        //Deselect all
        jQuery(selectTarget + ' > option').removeAttr('selected');
        jQuery(selectTarget).trigger('change');
    });

    //Handle clicks with confirm message
    jQuery('body').on('click', 'a[data-confirm]', function(e) {
        //Get element
        e = jQuery(this);

        //Set "Confirm" button link
        jQuery('#modal_confirm a.button_confirm').attr('href', e.attr('href'));

        //Set message
        jQuery('#modal_confirm .modal-body').html(e.data('confirm')).trigger('htmlchanged');

        //Show modal
        jQuery('#modal_confirm').modal({keyboard: true});

        //Return false
        return false;
    });

    //Check on delete icons for ajax
    jQuery('body').on('click', 'a.delete', function(e) {
        if (e.isDefaultPrevented()) {
            return;
        }
        var e = jQuery(this);

        //Add spinning class
        e.children().addClass('fa-spin');

        //Delete via ajax
        jQuery.ajax({
            url: e.attr('href'),
            method: 'GET'
        })
        .done(function(data) {
            //Process delete request
            e.parents('tr').fadeOut('slow', function() {
                StorageTables
                    .row(e.parents('tr'))
                    .remove()
                    .draw();
            });

            //Show notification
            showNotifySuccess(ComponentStorage.texts.row_delete_ok);
        })
        .fail(function(resp, textStatus, errorThrown) {
            //Show notification
            showNotifyDanger(ComponentStorage.texts.row_delete_error);
        });
    });

    //When button with submit if clicked on form, add attribute
    jQuery('body').on('click', 'form [type=submit]', function() {
        jQuery('[type=submit]', jQuery(this).parents('form')).removeAttr('clicked');
        jQuery(this).attr('clicked', 'true');
    });

    //Enable ajax calls for paginations in doctor/patient archive and list of all patients
    jQuery('body').on('click', '.pagination li a, a.ajax', function(e) {
        DoAjax(jQuery(this), e);
    });
    jQuery('body').on('submit', 'form.ajax', function(e) {
        DoAjax(jQuery(this), e);
    });

    //Ajax start function
    ajaxStartFunction = function() {
        //Show main loading image on all ajax calls
        jQuery('#ajax_loading').show();
    };

    //Ajax complete function
    ajaxCompleteFunction = function() {
        //Hide main loading image after all ajax calls
        jQuery('#ajax_loading').hide();

        //Init jquery elements
        initjQueryElements();

        //Check for modal changes
        HandleModalHeight();
    };

    //Ajax start and end functions
    jQuery(document).ajaxStart(ajaxStartFunction);
    jQuery(document).ajaxComplete(ajaxCompleteFunction);

    //Handle modal opening
    jQuery(document).on('click', '[data-modal]', function() {
        //Get modal ID
        modalID = jQuery(this).data('modal');

        //Open modal window
        jQuery('#' + modalID).modal('show');
    });

    //Close flash messages on click
    jQuery(document).on('click', '.flash-message', function() {
        jQuery(this).fadeTo('slow', 0.005, function() {
            jQuery(this).slideUp();
        });
    });

    //Check for form submit
    jQuery('body').on('click', 'button, .btn', function(e) {
        var formId = jQuery(this).data('submit-form') || false;
        if (formId) {
            e.preventDefault();
            jQuery('#' + formId).submit();
            return false;
        }
    });

    //Reinit everything
    function initjQueryElements() {
        //Enable tooltip    
        jQuery('[title!=""]').tooltip({
            placement: 'top'
        });

        //Select 2 plugin
        if (jQuery.fn.select2 != undefined) {
            jQuery('select:not(#share_input_select):not(.filter)').select2();
        }

        //Bind dropdown
        jQuery('.dropdown-toggle').dropdown();

        //Process left menu
        if (window['HandleLeftMenu'] != undefined) {
            HandleLeftMenu();
        }

        //Init date picker range
        if (jQuery.fn.daterangepicker != undefined) {        
            jQuery('.daterangepickerinput')
                .daterangepicker(jQuery.extend(ComponentStorage.daterangepicker, {
                    singleDatePicker: false,
                    showDropdowns: false
                }), function(start, end, label) {})
                .on('cancel.daterangepicker', function(ev, picker) {
                    jQuery(this).val('');
                })
                .on('apply.daterangepicker', function(ev, picker) {
                    jQuery(this).val(picker.startDate.format(ComponentStorage.daterangepicker.locale.format) + ComponentStorage.daterangepicker.locale.separator + picker.endDate.format(ComponentStorage.daterangepicker.locale.format));
                });

            jQuery('.daterangepickersingleinput')
                .daterangepicker(jQuery.extend(ComponentStorage.daterangepicker, {
                    singleDatePicker: true,
                    showDropdowns: true
                }), function(start, end, label) {})
                .on('cancel.daterangepicker', function(ev, picker) {
                    jQuery(this).val('');
                })
                .on('apply.daterangepicker', function(ev, picker) {
                    jQuery(this).val(picker.startDate.format(ComponentStorage.daterangepicker.locale.format));
                });
        }

        //Destroy data table
        if (typeof StorageTables != 'undefined') {
            //StorageTables.destroy();
        }

        //Data tables
        if (jQuery.fn.DataTable != undefined) {
            jQuery('table').not('.table-mysql').each(function() {
                var el = jQuery(this);
                if (!jQuery.fn.DataTable.isDataTable(el)) {

                    //Fill settings
                    var settings = {
                        aLengthMenu: [30, 50, 100, 500],
                        oLanguage: ComponentStorage.oLanguage,
                        oPaginate: ComponentStorage.oPaginate,
                        responsive: true,
                        processing: el.data('processing') || false,
                        serverSide: el.data('serverside') || false,
                        ajax: el.data('ajax') || '',
                        aaSorting: [],
                        dom: '<"top"flp<"clear">>rt<"bottom"ip<"clear">>',
                        fnRowCallback: function(nRow, aData, iDisplayIndex) {
                            if (el.attr('id') == 'table_elements') {
                                var q = aData.quantity_number;
                                var qw = aData.quantity_warning_number;

                                if (q == 0) {
                                    jQuery(nRow).addClass('danger');
                                } else if (q < qw) {
                                    jQuery(nRow).addClass('warning');
                                }
                            }
                            return nRow;
                        }
                    };

                    //Columns
                    var ar = new Array();
                    jQuery('thead tr th', el).each(function() {
                        var columnName = jQuery(this).data('column') || false;
                        var columnClass = jQuery(this).data('class') || false;
                        var s = {};
                        if (columnName) {
                            s.data = columnName;
                        }
                        if (columnClass) {
                            s.className = columnClass;
                        }
                        ar.push(s);
                    });
                    if (ar.length > 0) {
                        settings.columns = ar;
                    }

                    //Set up server params
                    var urlparams = el.data('urlparams');
                    if (el.data('urlparams') != undefined) {
                        //Set up params
                        settings.fnServerParams = function(data) {
                            var params = el.data('urlparams');
                            for (k in params) {
                                data[k] = params[k];
                            }
                        } 
                    }
                    
                    //Initialize table
                    StorageTables = el.DataTable(settings);
                }
            });
/*
            var detailRows = [];
            jQuery('table tbody').on('click', 'tr td', function() {
                var tr = $(this).closest('tr');
                var row = StorageTables.row(tr);
                var idx = $.inArray(tr.attr('id'), detailRows);

                if (row.child.isShown()) {
                    tr.removeClass( 'details' );
                    row.child.hide();

                    // Remove from the 'open' array
                    detailRows.splice( idx, 1 );
                } else {
                    tr.addClass('details');
                    row.child('test').show();

                    // Add to the 'open' array
                    if (idx === -1) {
                        detailRows.push(tr.attr('id'));
                    }
                }
            });
*/
        }
    }

    //Update events in date and time
    setInterval(function() {
        jQuery('.event_datetime').each(function() {
            var date = jQuery(this).data('datetime') || false;
            if (!date) {
                return false;
            }
        });
    }, 1000);

    //Init first time
    initjQueryElements();
});

//Init select2 module
function InitSelect2() {
    if (jQuery.fn.select2 != undefined) {     
        //Set multiselect
        jQuery('span.select2').remove();
        jQuery('select').each(function() {
            var ins = jQuery(this).data('select2');
            if (ins != undefined) {
                jQuery(this).select2('destroy');
            }
            jQuery(this).select2();
        });  
    }
}

//Show success message
function showNotifySuccess(message) {
	jQuery.notify({'message': message}, {'type': 'success'});
}

//Show danger message
function showNotifyDanger(message) {
	jQuery.notify({'message': message}, {'type': 'danger'});
}

function RemoveFromDomWithAnimation(el) {
    jQuery(el).fadeTo('fast', 0.005, function() {
        jQuery(this).slideUp('fast', function() {
            jQuery(this).remove();
        });
    });
}

//Current url is 
currenturl = window.location.href;

//Function to perform AJAX request
function DoAjax(jQueryElement, e) {
    //Get node name
    var nodename = jQueryElement.prop('nodeName').toUpperCase();

    //Find children A tag
    //Check if element is A
    var is_form = false;
    var is_a = false;

    //Set element
    var element = jQueryElement;    

    //Node name is a link?
    if (nodename == 'A') {
        is_a = true;        
    } else if (nodename == 'FORM') {
        is_form = true;
    }

    //Ajax method
    var ajax_method = element.attr('method');

    //Default method is GET
    if (typeof ajax_method == 'undefined') {
        //Check for data method
        ajax_method = element.data('method');

        //If still undefined
        if (typeof ajax_method == 'undefined') {
            ajax_method = 'GET';
        }
    }
    ajax_method = ajax_method.toUpperCase();

    //Check if we are form
    if (is_form) {
        //Get action of form
        formaction = element.attr('action');
        if (typeof formaction == 'undefined') {
            //Use current active URL
            formaction = window.location.href;
        }

        //Add parameters of FORM
        parameters = element.serializeArray();
        form_data = parameters;

        //Set link
        link = formaction;

        //In case of GET method, add all form elements to GET
        if (ajax_method == 'GET') {
            //Explode link 
            link = formaction.split('?');
            link = link[0];

            //Check for parameters in form
            if (parameters.length > 0) {
                //Add link separator
                link += '?';

                //Go through elements
                for (var i = 0; i < parameters.length; i++) {
                    //Add value to url
                    link += parameters[i].name + '=' + parameters[i].value;

                    //Check for AND character
                    if (i != (parameters.length - 1)) {
                        link += '&';
                    }
                }
            }
        }
    } else {
        //We are A, use its link
        link = element.attr('href');
    }

    //Check link
    if (link == '#') {
        e.preventDefault();
        return false;
    }

    //Get loading target
    var target = element.data('ajax-target');
    if (typeof target == 'undefined') {
        target = 'ajax_target_div';
    }

    //Check if target html should be cleared
    if (element.data('removecontent') == 'yes') {
        //Clear data
        jQuery('#' + target).html('').trigger('htmlchanged');
    }

    //Will be request in modal?
    var isModal = element.data('modal') || false;

    //Check for overlay
    var show_loading_overlay = element.data('showloadingoverlay') || '';
    if (show_loading_overlay != 'no' && !isModal) {
        show_loading_overlay = true;
    } else {
        show_loading_overlay = false;
    }

    //Clear content in case of modal window
    if (isModal) {
        jQuery('#' + target).empty();
    }

    //Set loading overlay ID
    loading_overlay = target + '_loading_overlay';

    //Check if AJAX target div exists
    if (jQuery('#' + target).length == 0) {
        return true;
    }

    //Add div for loading 
    if (!jQuery('#' + loading_overlay).length) {
        jQuery('#' + target).append('<div class="loading_overlay' + (show_loading_overlay == false ? ' loading_overlay_transparent' : '') + '" id="' + loading_overlay + '"><div><i class="fa fa-refresh fa-spin"></i></div></div>');  
    }

    //Set target as ID
    target = '#' + target;
    loading_overlay = '#' + loading_overlay;

    //Check for changing browser URL
    changeurl = element.data('changebrowserlink');
    if (typeof changeurl == 'undefined' || changeurl != 'no') {
        changeurl = true;
    } else {
        changeurl = false;
    }

    //Prevent default
    if (typeof e != 'undefined') {
        e.preventDefault();
    }

    //text for "Please wait" before changing
    textBeforeChange = false;

    //Ajax options
    ajaxoptions = {
        'url': link,
        'method': ajax_method,
        'timeout': 45000,
        //'async': false,
        'beforeSend': function(jqXHR) {
            //Check for modal
            modalID = jQuery(this).data('modal');
            if (modalID) {
                jQuery('#' + modalID).empty();
            }

            //Show main loading
            jQuery(loading_overlay).show();

            //Get heights
            parentheight = jQuery(loading_overlay).parent().height();
            overlayheight = jQuery(loading_overlay).height();
            winheight = jQuery(window).height();
            winoffset = jQuery(window).scrollTop();
            parentoffset = jQuery(loading_overlay).parent().offset().top;

            //Loading element
            loading_element = jQuery(loading_overlay).find('div');

            //Fix height if loading is greater than target element
            if ((overlayheight - 20) > parentheight) {
                jQuery(loading_overlay).parent().css({
                    'min-height': overlayheight
                });
            }

            //Set loading to some fixed offset
            if (jQuery(loading_overlay).parent().height() > winheight) {
                //Setting new position for loading image

                //jQuery(loading_overlay)
                loading_element.css({
                    'bottom': 'auto',
                    'top': winoffset + winheight / 2 - parentoffset - loading_element.height() / 2
                });
            }

            //Change url if needed
            if (changeurl && currenturl != link) {
                //Set browser URL
                window.history.pushState('Page', 'Link changed', link);

                //Save link
                currenturl = link;
            }

            //Set please wait text
            if (is_form) {
                //Only one child element with submit can be for setting it to "Please wait" text
                var children = element.children().find('button[type=submit]');
                if (children.length == 1) {
                    textBeforeChange = children.html();
                    children.html(ComponentStorage.texts.buttonLoading).trigger('htmlchanged');
                }
            } else if (is_a && element.hasClass('btn')) {
                textBeforeChange = element.html();
                element.html(ComponentStorage.texts.buttonLoading).trigger('htmlchanged');
            }
        }
    };

    //If form, check trigger button and data
    if (is_form) {
        var triggerbtn = jQuery('button[type=submit][clicked=true]');
        if (triggerbtn.length == 1) {
            var currText = jQuery(triggerbtn).html();
            var loadingText = jQuery(triggerbtn).data('loading-text');
            if (loadingText != undefined) {
                triggerbtn.html(loadingText).trigger('htmlchanged');
            }
            jQuery(triggerbtn).attr('clicked', false);
        }
    }

    //Check for form and POST method, add data
    if (is_form && ajax_method == 'POST') {
        ajaxoptions.data = form_data;

        //Check for trigger btn
        if (typeof triggerbtn != 'undefined') {
            var o = {};
            var triggername = jQuery(triggerbtn).attr('name') || 'buttonNoName';
            if (triggername != 'undefined') {
                ajaxoptions.data.push({'name': triggername, 'value': true});
            }
        }
    }

    //Callbacks
    var callbackSuccess = element.data('ajax-success') || false;
    var callbackError = element.data('ajax-error') || false;

    //Make an Ajax call
    jQuery.ajax(ajaxoptions)
        .done(function(data, textStatus, jqXHR) {
            if (typeof data == 'string') {

            }
            //If refresh meta was sent
            if (data.indexOf('<script data-type="refresh"') == 0) {
                //Put data to container
                jQuery(target).append(data);
            } else {
                //Put data to container
                jQuery(target).html(data).trigger('htmlchanged');
                
                //Ajax has done with request
                jQuery(loading_overlay).hide();
            }

            //Nice checkboxes only inside 
            if (typeof jQuery(target + ' input[type=radio]').screwDefaultButtons != 'undefined') {
                //jQuery(target + ' input[type=radio]').screwDefaultButtons(ComponentStorage.screwDefaultButtonsSettings);
            }

            //Check success
            if (callbackSuccess) {
                window[callbackSuccess](element, data, jqXHR);
            }
        })
        .fail(function(resp, textStatus, errorThrown) {

            //Ajax has done with request
            jQuery(loading_overlay).hide();

            //Set title
            if (resp.statusText == 'timeout') {
                jQuery('#modal_error .modal-body #modal-error-title').html(ComponentStorage.texts.timeoutText).trigger('htmlchanged');  
            }

            //Set message for modal window
            jQuery('#modal_error .modal-body').html(resp.responseText).trigger('htmlchanged');

            //Close all opened modals
            //jQuery('.modal').modal('hide');

            //Show modal for error
            //jQuery('#modal_error').modal({keyboard: true});

            //Failed, set back original text for ajax trigger
            if (textBeforeChange != false) {              
                if (is_form) {
                    //Only one child element with submit can be for setting it to "Please wait" text
                    var children = element.children().find('button[type=submit]');
                    if (children.length == 1) {
                        children.html(textBeforeChange).trigger('htmlchanged');
                    }
                } else if (is_a && element.hasClass('btn')) {
                    element.html(textBeforeChange).trigger('htmlchanged');
                } 
            }

            //Check callback
            if (callbackError) {
                window[callbackError](element, jqXHR);
            }
        }).always(function(resp) {

        });

    //Prevent default behaviour
    return false;
}

function DoAjaxInline(el, event, data) {
    //Format url
    var el = jQuery(el);
    var url = el.attr('href');

    //Messages
    var okText = el.data('text-success') || ComponentStorage.texts.row_edit_ok;
    var errorText = el.data('text-error') || ComponentStorage.texts.row_edit_error;

    //Callbacks
    var callbackSuccess = el.data('ajax-success') || false;
    var callbackError = el.data('ajax-error') || false;

    //Request method
    var requestMethod = el.data('ajax-method') || 'GET';

    //Request data
    data = data || {};

    //Create request options
    var options = {
        method: requestMethod,
        url: url,
        data: data
    };

    //Make a post method
    jQuery.ajax(options)
    .done(function(data, textStatus, jqXHR) {
        if (data.Success) {
            showNotifySuccess(okText);
            if (callbackSuccess) {
                window[callbackSuccess](el, data, jqXHR);
            }
        } else {
            showNotifyDanger(errorText);
            if (callbackError) {
                window[callbackError](el, jqXHR);
            }
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        showNotifyDanger(errorText);
        if (callbackError) {
            window[callbackError](el, jqXHR);
        }
    });
}

//Handles modal
function HandleModalHeight() {
    var modal = jQuery('.modal');
    modal.each(function() {
        modal = jQuery(this);

        var dialog = jQuery('.modal-dialog', modal);
        var header = jQuery('.modal-header', modal);
        var body = jQuery('.modal-body', modal);
        var footer = jQuery('.modal-footer', modal);

        var offset = dialog.offset();
        var sub = 65;
        if (offset) {
            sub = 2 * offset.top + 5;
        }
        sub += header.outerHeight();
        sub += footer.outerHeight();

        body.css({
            'max-height': jQuery(window.top).height() - sub
        });
    });
}

//Formates filesize
function format_filesize(bytes) { 
    var units = ['B', 'kB', 'MB', 'GB', 'TB'];
    var pow;
    bytes = Math.max(bytes, 0);

    //Get units
    pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024)); 
    pow = Math.min(pow, units.length - 1);

    //Format bytes
    bytes /= Math.pow(1024, pow);

    //Output formatted data
    return Math.round(bytes) + ' ' + units[pow];
}

//Fix for auto reloading on jabuka
//used for reloading back page when ajax is used
window.setTimeout(function() {
    window.addEventListener('popstate', function() {
        window.location.reload();
    });
}, 1000);
