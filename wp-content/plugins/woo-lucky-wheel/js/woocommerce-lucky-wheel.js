(function ($) {
    "use strict";
    if (typeof _wlwl_get_email_params === "undefined" || !_wlwl_get_email_params?.coupon_type){
        return;
    }
    let wheel_params = _wlwl_get_email_params;
    let wd_width= window.innerWidth, wd_height= window.innerHeight , width,center, cv, ctx;
    let is_mobile = wd_width < 760 , mobile_enable= wheel_params?.wlwl_mobile_enable;
    if (is_mobile && !mobile_enable){
        return;
    }
    let slices = wheel_params.coupon_type.length;
    let sliceDeg = 360 / slices;
    let deg = -(sliceDeg / 2);
    $(window).on('resize', function () {
        let new_size = window.innerWidth;
        if (!wd_width){
            wd_width = new_size;
        }
        if (wd_width == new_size) {
            return;
        }
        wd_width = new_size;
        wd_height= window.innerHeight;
        is_mobile = wd_width < 760;
        if (mobile_enable || !is_mobile) {
            $(document.body).trigger('wlwl-render-popup-wheel');
        }else {
            $('.wlwl_lucky_wheel_wrap').removeClass('wlwl_lucky_wheel_active');
        }
    });
    $(document).ready(function ($) {
        setTimeout(function () {
            $(document.body).trigger('wlwl-render-popup-wheel');
        }, 100);
        $(document.body).on('wlwl-render-popup-wheel', function () {
            if (getCookie('wlwl_cookie')){
                return;
            }
            $('.wlwl_lucky_wheel_wrap').addClass('wlwl_lucky_wheel_active');
            width = wd_width > wd_height ? wd_height : wd_width;
            width = is_mobile ? parseInt(width * 0.6 + 16) : parseInt(wheel_params.wheel_size * (width * 0.55 + 16) / 100);
            if ($('.wlwl_lucky_wheel_content_rendered').length){
                drawWheel();
            }else {
                drawPopupIcon();
                switch (wheel_params.intent){
                    case 'popup_icon':
                        setTimeout(function () {
                            $('.wlwl_wheel_icon').addClass('wlwl_show');
                        }, wheel_params.show_wheel * 1000);
                        break;
                    case 'show_wheel':
                        setTimeout(function () {
                            $('.wlwl_wheel_icon').trigger('click');
                        }, wheel_params.show_wheel * 1000);
                        break;
                }
            }
        });
        $(document).on('click','.woocommerce-lucky-wheel-popup-icon',function (){
            if (!$('.wlwl_lucky_wheel_wrap.wlwl_lucky_wheel_content_rendered').length){
                $('.wlwl_lucky_wheel_wrap').addClass('wlwl_lucky_wheel_content_rendered');
                drawWheel(true);
                return;
            }
            if (!$('.wlwl_lucky_wheel_wrap').hasClass('wlwl_lucky_wheel_active')){
                return;
            }
            $('.wlwl_wheel_icon').removeClass('wlwl_show');
            $('.wlwl-overlay').show();
            $('html').addClass('wlwl-html');
            $('.wlwl_lucky_wheel_content').addClass('lucky_wheel_content_show');
        });
        $(document).on('click','.wlwl-close-wheel , .wlwl-close, .wlwl-overlay',function (){
            $('html').removeClass('wlwl-html');
            $('.wlwl-overlay').hide();
            setCookie('wlwl_cookie', 'closed', wheel_params.time_if_close);
            $('.wlwl_lucky_wheel_content').removeClass('lucky_wheel_content_show');
            if (! wheel_params.hide_popup ) {
                $('.wlwl_wheel_icon').addClass('wlwl_show');
            }
        });
        $(document).on('click','.wlwl-never-again span',function (){
            setCookie('wlwl_cookie', 'never_show_again', 30 * 24 * 60 * 60);
            $('.wlwl_wheel_icon').addClass('wlwl_show');
            $('.wlwl-overlay').hide();
            $('html').removeClass('wlwl-html');
            $('.wlwl_lucky_wheel_content').removeClass('lucky_wheel_content_show');
        });
        $(document).on('click','.wlwl-reminder-later-a',function (){
            setCookie('wlwl_cookie', 'reminder_later', 24 * 60 * 60);
            $('.wlwl_wheel_icon').addClass('wlwl_show');
            $('.wlwl-overlay').hide();
            $('html').removeClass('wlwl-html');
            $('.wlwl_lucky_wheel_content').removeClass('lucky_wheel_content_show');
        });
        $(document).on('click','.wlwl-hide-after-spin',function (){
            $('.wlwl-overlay').hide();
            $('html').removeClass('wlwl-html');
            $('.wlwl_lucky_wheel_content').removeClass('lucky_wheel_content_show');
            $('.wlwl_wheel_spin').css({'margin-left': '0', 'transition': '2s'});
        });
        $(document).on('keypress', function (e) {
            if ($('.wlwl_lucky_wheel_content').hasClass('lucky_wheel_content_show') && e.keyCode === 13) {
                $('#wlwl_chek_mail').trigger('click');
            }
        });
        $(document).on('click','#wlwl_chek_mail',function (){
            if (!$('.wlwl_lucky_wheel_wrap').hasClass('wlwl_lucky_wheel_active')){
                return;
            }
            $('#wlwl_error_mail,#wlwl_error_name,#wlwl_error_mobile,#wlwl_warring_recaptcha').html('');
            $('.wlwl-required-field').removeClass('wlwl-required-field');
            if (wheel_params.gdpr && !$('.wlwl-gdpr-checkbox-wrap input[type="checkbox"]').prop('checked')) {
                $('#wlwl_error_mail').html(wheel_params.gdpr_warning);
                return false;
            }

            let wlwl_email = $('#wlwl_player_mail').val();
            let wlwl_name = $('#wlwl_player_name').val();
            let qualified = true;

            if (wheel_params.custom_field_name_enable && (!is_mobile || wheel_params.custom_field_name_enable_mobile)
                && wheel_params.custom_field_name_required  && !wlwl_name) {
                $('#wlwl_error_name').html(wheel_params.custom_field_name_message);
                $('.wlwl_field_name').addClass('wlwl-required-field');
                qualified = false;
            }

            if (!wlwl_email) {
                $('#wlwl_player_mail').prop('disabled', false).focus();
                $('#wlwl_error_mail').html(wheel_params.empty_email_warning);
                $('.wlwl_field_email').addClass('wlwl-required-field');
                qualified = false;
            }
            if (qualified === false) {
                return false;
            }

            $(this).unbind();
            $('.wlwl-overlay').unbind();
            $('#wlwl_player_mail').prop('disabled', true);
            if (getCookie('wlwl_cookie') === "" || getCookie('wlwl_cookie') === 'closed') {
                if (isValidEmailAddress(wlwl_email) ) {
                    $('#wlwl_chek_mail').addClass('wlwl-adding');
                    $.ajax({
                        type: 'post',
                        dataType: 'json',
                        url: wheel_params.ajaxurl,
                        data: {
                            origin_prize: wheel_params.coupon_type,
                            current_currency: wheel_params?.current_currency,
                            user_email: wlwl_email,
                            user_name: wlwl_name,
                            is_desktop: !is_mobile ? 1: '',
                            _woocommerce_lucky_wheel_nonce: wheel_params.nonce,
                        },
                        success: function (response) {
                            if (response.allow_spin === 'yes') {
                                $('.wlwl-show-again-option').hide();
                                $('.wlwl-close-wheel').hide();
                                $('.wlwl-hide-after-spin').show();
                                spins_wheel(response.stop_position, response.result_notification, response.result);
                                let wlwl_show_again = wheel_params.show_again;
                                let wlwl_show_again_unit = wheel_params.show_again_unit;
                                switch (wlwl_show_again_unit) {
                                    case 'm':
                                        wlwl_show_again *= 60;
                                        break;
                                    case 'h':
                                        wlwl_show_again *= 60 * 60;
                                        break;
                                    case 'd':
                                        wlwl_show_again *= 60 * 60 * 24;
                                        break;
                                    default:
                                }
                                setCookie('wlwl_cookie', wlwl_email, wlwl_show_again);
                            } else {
                                $('#wlwl_chek_mail').removeClass('wlwl-adding');
                                $('#wlwl_player_mail').prop('disabled', false);
                                $('#wlwl_error_mail').html(response.warning||response.allow_spin||'');
                            }

                        }
                    });
                } else {
                    $('#wlwl_player_mail').prop('disabled', false).focus();
                    $('#wlwl_error_mail').html(wheel_params.invalid_email_warning);
                    $('.wlwl_field_email').addClass('wlwl-required-field');
                }
            } else {
                $('#wlwl_error_mail').html(wheel_params.limit_time_warning);
                $('#wlwl_player_mail').prop('disabled', false);
            }
        });
    });
    function spins_wheel(stop_position, result_notification, result) {
        let canvas_1 = $('#wlwl_canvas');
        let canvas_3 = $('#wlwl_canvas2');
        let default_css = '';
        if (window.devicePixelRatio) {
            default_css = 'width:' + width + 'px;height:' + width + 'px;';
        }
        canvas_1.attr('style', default_css);
        canvas_3.attr('style', default_css);
        let stop_deg = 360 - sliceDeg * stop_position;
        let wlwl_spinning_time = wheel_params.spinning_time;
        let wheel_stop = wheel_params.wheel_speed * 360 * wlwl_spinning_time + stop_deg;
        let css = default_css + '-moz-transform: rotate(' + wheel_stop + 'deg);-webkit-transform: rotate(' + wheel_stop + 'deg);-o-transform: rotate(' + wheel_stop + 'deg);-ms-transform: rotate(' + wheel_stop + 'deg);transform: rotate(' + wheel_stop + 'deg);';
        css += '-webkit-transition: transform ' + wlwl_spinning_time + 's ease;-moz-transition: transform ' + wlwl_spinning_time + 's ease;-ms-transition: transform ' + wlwl_spinning_time + 's ease;-o-transition: transform ' + wlwl_spinning_time + 's ease;transition: transform ' + wlwl_spinning_time + 's ease;';
        canvas_1.attr('style', css);
        canvas_3.attr('style', css);
        setTimeout(function () {
            css = default_css + 'transform: rotate(' + stop_deg + 'deg);';
            canvas_1.attr('style', css);
            canvas_3.attr('style', css);
            $('.wlwl_lucky_wheel_content').addClass('wlwl-finish-spinning');
            $('.wlwl-overlay').off().on('click', function () {
                $('html').removeClass('wlwl-html');
                $(this).hide();

                $('.wlwl_lucky_wheel_content').removeClass('lucky_wheel_content_show');
                $('.wlwl_wheel_spin').css({'margin-left': '0', 'transition': '2s'});
            });
            $('.wlwl_user_lucky').html('<div class="wlwl-frontend-result">' + result_notification + '</div>').fadeIn(300);
            let wlwl_auto_close = parseInt(wheel_params.auto_close);
            if (wlwl_auto_close > 0) {
                setTimeout(function () {
                    $('.wlwl-overlay').trigger('click');
                }, wlwl_auto_close * 1000);
            }
        }, parseInt(wlwl_spinning_time * 1000))
    }
    function setCookie(cname, cvalue, expire) {
        let d = new Date();
        d.setTime(d.getTime() + (expire * 1000));
        let expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }
    function isValidEmailAddress(emailAddress) {
        let pattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/i;
        return pattern.test(emailAddress);
    }
    function getCookie(cname) {
        let name = cname + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }
    function drawSlice(deg, color) {
        ctx.beginPath();
        ctx.fillStyle = color;
        ctx.moveTo(center, center);
        let r = center;
        if (center !== 32) {
            if (width <= 480) {
                r = width / 2 - 10;
            } else {
                r = width / 2 - 14;
            }
        }
        ctx.arc(center, center, r, deg2rad(deg), deg2rad(deg + sliceDeg));
        ctx.lineTo(center, center);
        ctx.fill();
    }
    function deg2rad(deg) {
        return deg * Math.PI / 180;
    }
    function drawPopupIcon() {
        cv = document.getElementById('wlwl_popup_canvas');
        if (!cv) {
            return;
        }
        ctx = cv.getContext('2d');
        center = 32;
        for (let k = 0; k < slices; k++) {
            drawSlice(deg, wheel_params.bg_color[k]);
            deg += sliceDeg;
        }
        drawPopupIconPoint(wheel_params.wheel_center_color);
        drawBorder(wheel_params.wheel_border_color, wheel_params.wheel_dot_color, 4, 1, 0);
    }
    function drawPopupIconPoint(color) {
        ctx.save();
        ctx.beginPath();
        ctx.fillStyle = color;
        ctx.arc(center, center, 8, 0, 2 * Math.PI);
        ctx.fill();
        ctx.restore();
    }
    function drawPoint(deg, color) {
        ctx.save();
        ctx.beginPath();
        ctx.fillStyle = color;
        ctx.shadowBlur = 1;
        ctx.shadowOffsetX = 8;
        ctx.shadowOffsetY = 8;
        ctx.shadowColor = 'rgba(0,0,0,0.2)';
        ctx.arc(center, center, width / 8, 0, 2 * Math.PI);
        ctx.fill();
        ctx.clip();
        ctx.restore();
    }
    function drawBorder(borderC, dotC, lineW, dotR, des, shadColor='') {
        ctx.beginPath();
        ctx.strokeStyle = borderC;
        ctx.lineWidth = lineW;
        if (shadColor) {
            ctx.shadowBlur = 1;
            ctx.shadowOffsetX = 8;
            ctx.shadowOffsetY = 8;
            ctx.shadowColor = shadColor;
        }
        ctx.arc(center, center, center, 0, 2 * Math.PI);
        ctx.stroke();
        let x_val, y_val, deg;
        deg = sliceDeg / 2;
        let center1 = center - des;
        for (let i = 0; i < slices; i++) {
            ctx.beginPath();
            ctx.fillStyle = dotC;
            x_val = center + center1 * Math.cos(deg * Math.PI / 180);
            y_val = center - center1 * Math.sin(deg * Math.PI / 180);
            ctx.arc(x_val, y_val, dotR, 0, 2 * Math.PI);
            ctx.fill();
            deg += sliceDeg;
        }
    }
    function drawText(deg, text, color) {
        let font_text_wheel = 'Helvetica',
            wheel_text_size = parseInt(width / 28) * parseInt(wheel_params.font_size) / 100;
        if (typeof wheel_params.font_text_wheel !== 'undefined' && wheel_params.font_text_wheel !== '') {
            font_text_wheel = wheel_params.font_text_wheel;
        }
        ctx.save();
        ctx.translate(center, center);
        ctx.rotate(deg2rad(deg));
        ctx.textAlign = "right";
        ctx.fillStyle = color;
        ctx.font = '200 ' + wheel_text_size + 'px ' + font_text_wheel;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;
        text = text.replace(/&#(\d{1,4});/g, function (fullStr, code) {
            return String.fromCharCode(code);
        });
        text = text.replace(/&nbsp;/g, ' ');
        let reText = text.split('\/n'), text1 = '', text2 = '';
        if (reText.length > 1) {
            text1 = reText[0];
            text2 = reText.splice(1, reText.length - 1);
            text2 = text2.join('');
        } else {
            reText = text.split('\\n');
            if (reText.length > 1) {
                text1 = reText[0];
                text2 = reText.splice(1, reText.length - 1);
                text2 = text2.join('');
            }
        }
        if (text1.trim() !== "" && text2.trim() !== "") {
            ctx.fillText(text1.trim(), 7 * center / 8, -(wheel_text_size * 1 / 4));
            ctx.fillText(text2.trim(), 7 * center / 8, wheel_text_size * 3 / 4);
        } else {
            ctx.fillText(text.replace(/\\n/g, '').replace(/\/n/g, ''), 7 * center / 8, wheel_text_size / 2 - 2);
        }
        ctx.restore();
    }
    async function drawCanvas(canvas_id){
        if (!canvas_id){
            return;
        }
        cv = document.getElementById(canvas_id);
        if (!cv){
            return;
        }
        ctx = cv.getContext('2d');
        cv.width = width;
        cv.height = width;
        if (window.devicePixelRatio) {
            $(cv).attr({
                'width': width * window.devicePixelRatio,
                'height': width * window.devicePixelRatio
            });
            $(cv).css({'width': width , 'height': width});
            ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
        }
        switch (canvas_id){
            case 'wlwl_canvas':
                for (let i = 0; i < slices; i++) {
                    drawSlice(deg, wheel_params.bg_color[i]);
                    drawText(deg + sliceDeg / 2, wheel_params.label[i], wheel_params.slice_text_color);
                    deg += sliceDeg;
                }
                break;
            case 'wlwl_canvas1':
                drawPoint(deg, wheel_params.wheel_center_color);
                if (width <= 480) {
                    drawBorder(wheel_params.wheel_border_color, 'rgba(0,0,0,0)', 20, 4, 5, 'rgba(0,0,0,0.2)');
                } else {
                    drawBorder(wheel_params.wheel_border_color, 'rgba(0,0,0,0)', 30, 6, 7, 'rgba(0,0,0,0.2)');
                }
                break;
            case 'wlwl_canvas2':
                if (width <= 480) {
                    drawBorder('rgba(0,0,0,0)', wheel_params.wheel_dot_color, 20, 4, 5, 'rgba(0,0,0,0)');
                } else {
                    drawBorder('rgba(0,0,0,0)', wheel_params.wheel_dot_color, 30, 6, 7, 'rgba(0,0,0,0)');
                }
                break;
        }
    }
    async function drawWheel(show_popup = false){
        center = (width) / 2;
        await drawCanvas('wlwl_canvas');
        await drawCanvas('wlwl_canvas1');
        await drawCanvas('wlwl_canvas2');
        design_wheel_with_custom_width();
        if (show_popup){
            $('.woocommerce-lucky-wheel-popup-icon').trigger('click');
        }
    }
    function design_wheel_with_custom_width(){
        $('.wlwl_wheel_spin').css({'width': width + 'px', 'height': width + 'px'});
        $('.wlwl_lucky_wheel_content').removeClass('wlwl_lucky_wheel_content_mobile lucky_wheel_content_tablet');
        if (!is_mobile) {
            let max_width = 'on' === wheel_params.show_full_wheel ? (width + 600) : (0.6 * width + 600);
            $('.wlwl_lucky_wheel_content').css({'max-width': Math.min(max_width, wd_width) + 'px'});
            if (wd_width < 1024 || ((max_width + 28) >= wd_width)){
                $('.wlwl_lucky_wheel_content').addClass('lucky_wheel_content_tablet');
            }
        }else {
            $('.wlwl_lucky_wheel_content').addClass('wlwl_lucky_wheel_content_mobile');
        }
        if ((is_mobile && !wheel_params.custom_field_name_enable_mobile) ){
            $('.wlwl_field_name_wrap').hide();
        }else {
            $('.wlwl_field_name_wrap').show();
        }
        let inline_css = '.wlwl_lucky_wheel_content:not(.wlwl_lucky_wheel_content_mobile) .wheel-content-wrapper .wheel_content_left{min-width:' + (width + 35) + 'px}';
        // inline_css += '.wlwl_lucky_wheel_content.wlwl_lucky_wheel_content_mobile .wheel_description{min-height:' + $('.wheel_description').css('height') + '}';
        inline_css += '.wlwl_pointer:before{font-size:' + parseInt(width / 4) + 'px !important; }';
        if (!$('#wlwl_lucky_wheel_custom_inline_css').length){
            $('head').append('<style id="wlwl_lucky_wheel_custom_inline_css"></style>');
        }
        $('#wlwl_lucky_wheel_custom_inline_css').html(inline_css);
    }
}(jQuery));
