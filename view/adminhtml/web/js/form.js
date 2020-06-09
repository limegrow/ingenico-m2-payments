require([
    'jquery',
    'prototype',
    'mage/adminhtml/events',
    'mage/adminhtml/form'
], function(jQuery){

    FormElementDependenceController.prototype.getSelectValues = function(select) {
        var result = [];
        var options = select && select.options;
        var opt;
        for (var i=0, iLen=options.length; i<iLen; i++) {
            opt = options[i];
            if (opt.selected) {
                result.push(opt.value);
            }
        }
        return result;
    };
    
    FormElementDependenceController.prototype.trackChange = function (e, idTo, valuesFrom) {
        // define whether the target should show up
        var shouldShowUp = true,
            idFrom, from, values, isInArray, isNegative, headElement, isInheritCheckboxChecked, target, inputs,
            isAnInputOrSelect, currentConfig, rowElement, fromId, radioFrom;
    
        for (idFrom in valuesFrom) { //eslint-disable-line guard-for-in
            from = $(idFrom);
            
            // Custom Multiselect processing
            if (from && from.tagName === 'SELECT' && from.className.indexOf('multiselect') > -1) {
                var elementValues = this.getSelectValues(from);
                if (!from || !jQuery(elementValues).filter(valuesFrom[idFrom].values).length) {
                    shouldShowUp = false;
                }
            // Standard processing  
            }else if (from) {
                values = valuesFrom[idFrom].values;
                isInArray = values.indexOf(from.value) != -1; //eslint-disable-line
                isNegative = valuesFrom[idFrom].negative;
    
                if (!from || isInArray && isNegative || !isInArray && !isNegative) {
                    shouldShowUp = false;
                }
            // Check if radio button
            } else {
                values = valuesFrom[idFrom].values;
                fromId = $(idFrom + values[0]);
    
                if (fromId) {
                    radioFrom = $$('[name="' + fromId.name + '"]:checked');
                    isInArray = radioFrom.length > 0 && values.indexOf(radioFrom[0].value) !== -1;
                    isNegative = valuesFrom[idFrom].negative;
    
                    if (!radioFrom || isInArray && isNegative || !isInArray && !isNegative) {
                        shouldShowUp = false;
                    }
                }
            }
        }
    
        // toggle target row
        headElement = $(idTo + '-head');
        isInheritCheckboxChecked = $(idTo + '_inherit') && $(idTo + '_inherit').checked;
        target = $(idTo);
        
        // Target won't always exist (for example, if field type is "label")
        if (target) {
            inputs = target.up(this._config['levels_up']).select('input', 'select', 'td');
            isAnInputOrSelect = ['input', 'select'].indexOf(target.tagName.toLowerCase()) != -1; //eslint-disable-line
    
            if (target.type === 'fieldset') {
                inputs = target.select('input', 'select', 'td');
            }
        } else {
            inputs = false;
            isAnInputOrSelect = false;
        }
    
        if (shouldShowUp) {
            currentConfig = this._config;
    
            if (inputs) {
                inputs.each(function (item) {
                    // don't touch hidden inputs (and Use Default inputs too), bc they may have custom logic
                    if ((!item.type || item.type != 'hidden') && !($(item.id + '_inherit') && $(item.id + '_inherit').checked) && //eslint-disable-line
                        !(currentConfig['can_edit_price'] != undefined && !currentConfig['can_edit_price']) //eslint-disable-line
                    ) {
                        item.disabled = false;
                        jQuery(item).removeClass('ignore-validate');
                    }
                });
            }
    
            if (headElement) {
                headElement.show();
    
                if (headElement.hasClassName('open') && target) {
                    target.show();
                } else if (target) {
                    target.hide();
                }
            } else {
                if (target) {
                    target.show();
                }
    
                if (isAnInputOrSelect && !isInheritCheckboxChecked) {
                    if (target) {
                        if (target.getAttribute('readonly')) {
                            target.disabled = true;
                        } else {
                            target.disabled = false;
                        }
                    }
    
                    jQuery('#' + idTo).removeClass('ignore-validate');
                }
            }
        } else {
            if (inputs) {
                inputs.each(function (item) {
                    // don't touch hidden inputs (and Use Default inputs too), bc they may have custom logic
                    if ((!item.type || item.type != 'hidden') && //eslint-disable-line eqeqeq
                        !($(item.id + '_inherit') && $(item.id + '_inherit').checked)
                    ) {
                        item.disabled = true;
                        jQuery(item).addClass('ignore-validate');
                    }
                });
            }
    
            if (headElement) {
                headElement.hide();
            }
    
            if (target) {
                target.hide();
            }
    
            if (isAnInputOrSelect && !isInheritCheckboxChecked) {
                if (target) {
                    target.disabled = true;
                }
                jQuery('#' + idTo).addClass('ignore-validate');
            }
    
        }
        rowElement = $('row_' + idTo);
    
        if (rowElement == undefined && target) { //eslint-disable-line eqeqeq
            rowElement = target.up(this._config['levels_up']);
    
            if (target.type === 'fieldset') {
                rowElement = target;
            }
        }
    
        if (rowElement) {
            if (shouldShowUp) {
                rowElement.show();
            } else {
                rowElement.hide();
            }
        }
    };
});