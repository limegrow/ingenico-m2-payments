var Ogone;

require(
[
   	'jquery',
   	'jquery/ui',
   	'Magento_Ui/js/modal/modal'
],
function(
	$,
	modal
){
	'use strict';
	
	Ogone = {
		
		pattern : /[a-zA-Z0-9_\-\+\.]/,
		
	    getRandomByte : function()
	    {
	        if(window.crypto && window.crypto.getRandomValues)
	        {
	            var result = new Uint8Array(1);
	            window.crypto.getRandomValues(result);
	            return result[0];
	        }
	        else if(window.msCrypto && window.msCrypto.getRandomValues)
	        {
	            var result = new Uint8Array(1);
	            window.msCrypto.getRandomValues(result);
	            return result[0];
	        }
	        else
	        {
	            return Math.floor(Math.random() * 256);
	        }
	    },
	
	    generateHash : function(field, length)
	    {
	        let generated_hash = Array.apply(null, {'length': length})
	            .map(function()
	            {
	                var result;
	                while(true)
	                {
	                    result = String.fromCharCode(this.getRandomByte());
	                    if(this.pattern.test(result))
	                    {
	                        return result;
	                    }
	                }
	            }, this)
	            .join('');
	
	        document.getElementById(field).value = generated_hash;
	    },
	    
		copyValue: function (field, copy_response_container) {
		    let el = document.createElement('textarea');
		    let value = document.getElementById(field).value;
		    el.value = value;
		    el.setAttribute('readonly', '');
		    el.style = {position: 'absolute', left: '-9999px'};
		    document.body.appendChild(el);
		    el.select();
		    document.execCommand('copy');
		    document.body.removeChild(el);
		    $(".copy-response[data-copy='" + copy_response_container +"']").slideDown();
		    setTimeout(function(){
		        $(".copy-response[data-copy='" + copy_response_container +"']").slideUp();
		    }, 3000);
		},
		
		copyLink: function (link, copy_response_container) {
		    var el = document.createElement('textarea');
		    el.value = link;
		    el.setAttribute('readonly', '');
		    el.style = {position: 'absolute', left: '-9999px'};
		    document.body.appendChild(el);
		    el.select();
		    document.execCommand('copy');
		    document.body.removeChild(el);
		    $(".copy-response[data-copy='" + copy_response_container +"']").slideDown();
		    setTimeout(function(){
		        $(".copy-response[data-copy='" + copy_response_container +"']").slideUp();
		    }, 3000);
		},
		
		toggleView: function (button, field) {
			var input = $('#' + field);
            if (input.attr('type') == 'password') {
                $(button).find('span').text($.mage.__('form.connection.button.hide'));
                input.attr('type', 'text');
            } else {
                $(button).find('span').text($.mage.__('form.connection.button.show'))
                input.attr('type', 'password');
            }
		},
		
		showModal: function(content_container_id, modal_title)
		{
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                clickableOverlay: true,
                title: modal_title,
                buttons: [{
                    text: $.mage.__('ingenico.button.label2'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

			var contentEl = $('#'+content_container_id);
			contentEl.modal(options).modal("openModal");
		},
		
		initSlider: function(element_id, min_val, max_val)
		{
			if($('#' + element_id).length === 0){
				return;
			}
			
			var value = $('#' + element_id).val().split('-'),
				input_id = '#' + element_id,
				slider_id = '#' + element_id + '_range',
				installments_amount_min = value[0],
				installments_amount_max = value[1]
				;
			
			$(slider_id).slider({
			    range: true,
			    min: min_val,
			    max: max_val,
			    values: [ installments_amount_min, installments_amount_max ],
			    slide: function( event, ui ) {
			        let min_value = ui.values[0];
			        let max_value = ui.values[1];
			        let input_value = min_value + '-' + max_value;
			        $(input_id).val(input_value);
			
			        $(slider_id + ' .ui-slider-handle span').remove();
			        let handler = $(slider_id + ' .ui-slider-handle');
			        handler.eq(0).append("<span id='min'>" + min_value +"</span>");
			        handler.eq(1).append("<span id='max'>" + max_value +"</span>");
			    }
			});
			
			let amount_handler = $(slider_id + ' .ui-slider-handle');
			amount_handler.eq(0).append("<span id='min'>" + installments_amount_min +"</span>");
			amount_handler.eq(1).append("<span id='max'>" + installments_amount_max +"</span>");
		}
	};
	
	
	// element initializations
	$(document).ready(function(){
		
		// init modals
        $('.modal-link').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            var content_container_id = $(this).data('modal-id');
            var modal_title = $(this).data('modal-title');
            if (content_container_id == 'payment-methods-list') {
                Ogone.getPaymentMethodModal();
            }
            
            Ogone.showModal(content_container_id, modal_title);
        });
        
        // init sliders
        Ogone.initSlider('ingenico_instalments_general_count_flexible', 1, 12);
        Ogone.initSlider('ingenico_instalments_general_interval_flexible', 1, 180);
        Ogone.initSlider('ingenico_instalments_general_downpayment_flexible', 1, 100);
        
	});
});
