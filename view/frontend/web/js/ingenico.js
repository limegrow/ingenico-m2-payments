require(['jquery'],function ($) {
    'use strict';
    const Ingenico = {
        init: function () {
            $('.payment-logo').click(function () {
                let formContainer = $(this).parent().find('.form-container');
                if (formContainer.length) {
                    formContainer.slideToggle(400);
                }
                let iframe = $(this).parent().find('iframe');
                if (iframe.length) {
                    if (iframe.is(':visible')) {
                        $('.ingenico-confirmation li iframe').slideUp();
                    } else {
                        $('.ingenico-confirmation li iframe').slideUp();
                        iframe.slideDown();
                    }

                    // Load iframe
                    if (!iframe.data('loaded')) {
                        let src = iframe.data('src');
                        iframe.prop('src', src);
                        iframe[0].onload = function () {
                            iframe.data('loaded', 'true');
                        };
                    }
                }
            });
        },
        setEnvironment: function () {
            const data = Ingenico.getBrowserData();
            for (let elem in data) {
                Ingenico.setCookie(elem, data[elem], 2);
            }
        },
        getBrowserData: function () {
            return {
                'browserColorDepth': window.screen.colorDepth,
                'browserJavaEnabled': navigator.javaEnabled(),
                'browserLanguage': navigator.language,
                'browserScreenHeight': window.screen.height,
                'browserScreenWidth': window.screen.width,
                'browserTimeZone': (new Date()).getTimezoneOffset()
            };
        },
        setCookie: function (name, value, days) {
            let d = new Date;
            d.setTime(d.getTime() + 24*60*60*1000*days);
            document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
        },
    };
    $(document).ready(function () {
        Ingenico.init();
        Ingenico.setEnvironment();
    });
});
