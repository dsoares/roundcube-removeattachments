/**
 * RemoveAttachments plugin script
 */

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
		// register commands
		rcmail.register_command('plugin.removeattachments.remove_one', function(part) {
			if (confirm(rcmail.gettext('removeoneconfirm','removeattachments'))) {
				var lock = rcmail.set_busy(true, 'removeattachments.removing');
				rcmail.http_request('plugin.removeattachments.remove_attachments', '_mbox=' + urlencode(rcmail.env.mailbox) + '&_uid=' + rcmail.env.uid + '&_part=' + part, lock);
			}
		}, true);

		rcmail.register_command('plugin.removeattachments.remove_all', function() {
			if (confirm(rcmail.gettext('removeallconfirm','removeattachments'))) {
				var lock = rcmail.set_busy(true, 'removeattachments.removing');
				rcmail.http_request('plugin.removeattachments.remove_attachments', '_mbox=' + urlencode(rcmail.env.mailbox) + '&_uid=' + rcmail.env.uid + '&_part=-1', lock);
			}
		}, true);
	})
}

rcmail.removeattachments_reload = function(uid) {
	if (rcmail.env.action=='preview') {
	    var removeattachments_trigger = function(props) {
		parent.rcmail.message_list.select_row(uid);
		parent.rcmail.removeEventListener('listupdate', removeattachments_trigger);
	    }
	
	    parent.rcmail.list_mailbox(rcmail.env.mailbox, rcmail.env.current_page);
	}
	else {
		rcmail.show_message(uid);
	}
}
