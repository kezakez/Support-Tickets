jQuery(document).ready(function() {
	try {
        jQuery('div.suptic.form > form').ajaxForm({
            beforeSubmit: supticBeforeSubmit,
            dataType: 'json',
			complete: supticProcessJson
        });
    } catch (e) {
    }

    try {
        jQuery('div.suptic > form').each(function(i, n) {
            supticToggleSubmit(jQuery(n));
        });
    } catch (e) {
    }
});

function supticBeforeSubmit(formData, jqForm, options) {
	supticClearResponseOutput();
	jQuery('img.ajax-loader', jqForm[0]).css({ visibility: 'visible' });

    formData.push({name: '_suptic_is_ajax_call', value: 1});
    jQuery(jqForm[0]).append('<input type="hidden" name="_suptic_is_ajax_call" value="1" />');
  
	return true;
}

function supticNotValidTip(into, message) {
  jQuery(into).append('<span class="suptic-not-valid-tip">' + message + '</span>');
	jQuery('span.suptic-not-valid-tip').mouseover(function() {
		jQuery(this).fadeOut('fast');
	});
	jQuery(into).find(':input').mouseover(function() {
		jQuery(into).find('.suptic-not-valid-tip').not(':hidden').fadeOut('fast');
	});
	jQuery(into).find(':input').focus(function() {
		jQuery(into).find('.suptic-not-valid-tip').not(':hidden').fadeOut('fast');
	});
}

function supticProcessJson(resp) {
	var data = JSON.parse(resp.responseText);
	data.into = "div.suptic";
	var supticResponseOutput = jQuery(data.into).find('div.suptic-response-output');

	supticClearResponseOutput();

	if (data.redirect) {
		supticResponseOutput.addClass('suptic-redirecting');
		supticResponseOutput.append(data.message).slideDown('fast');
		data.redirect = data.redirect.replace('&amp;', '&');
		window.location = data.redirect;
	}

	if (data.invalids) {
		jQuery.each(data.invalids, function(i, n) {
			supticNotValidTip(jQuery(data.into).find(n.into), n.message);
		});
		supticResponseOutput.addClass('suptic-validation-errors');
	}
	if (data.captcha) {
		jQuery.each(data.captcha, function(i, n) {
			jQuery(data.into).find(':input[name="' + i + '"]').clearFields();
			jQuery(data.into).find('img.suptic-captcha-' + i).attr('src', n);
			var match = /([0-9]+)\.(png|gif|jpeg)$/.exec(n);
			jQuery(data.into).find('input:hidden[name="_suptic_captcha_challenge_' + i + '"]').attr('value', match[1]);
		});
	}
    if (data.quiz) {
        jQuery.each(data.quiz, function(i, n) {
            jQuery(data.into).find(':input[name="' + i + '"]').clearFields();
            jQuery(data.into).find(':input[name="' + i + '"]').siblings('span.suptic-quiz-label').text(n[0]);
            jQuery(data.into).find('input:hidden[name="_suptic_quiz_answer_' + i + '"]').attr('value', n[1]);
        });
    }
	if (1 == data.spam) {
		supticResponseOutput.addClass('suptic-spam-blocked');
	}
	if (1 == data.error) {
		supticResponseOutput.addClass('suptic-form-ng');
	}
	supticResponseOutput.append(data.message).slideDown('fast');
}

function supticClearResponseOutput() {
	jQuery('div.suptic-response-output').hide().empty().removeClass('suptic-form-ng suptic-validation-errors suptic-spam-blocked');
	jQuery('span.suptic-not-valid-tip').remove();
	jQuery('img.ajax-loader').css({ visibility: 'hidden' });
}

// Toggle submit button
function supticToggleSubmit(form) {
    var submit = jQuery(form).find('input:submit');
    if (! submit.length) return;

    var acceptances = jQuery(form).find('input:checkbox.suptic-acceptance');
    if (! acceptances.length) return;

    submit.removeAttr('disabled');
    acceptances.each(function(i, n) {
        n = jQuery(n);
        if (n.hasClass('suptic-invert') && n.is(':checked') || ! n.hasClass('suptic-invert') && ! n.is(':checked'))
        submit.attr('disabled', 'disabled');
    });
}

// Exclusive checkbox
function supticExclusiveCheckbox(elem) {
    jQuery(elem.form).find('input:checkbox[name="' + elem.name + '"]').not(elem).removeAttr('checked');
}