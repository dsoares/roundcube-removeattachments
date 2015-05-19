<?php

/**
 * RemoveAttachments
 *
 * Plugin to allow the removal of attachments from messages
 *
 * @version 0.1
 * @author Philip Weir
 */
class removeattachments extends rcube_plugin
{
	public $task = 'mail';

	function init()
	{
		$rcmail = rcmail::get_instance();
		$this->add_texts('localization', array('removeoneconfirm', 'removeallconfirm', 'removing'));
		$this->include_script('removeattachments.js');

		$this->add_hook('template_object_messageattachments', array($this, 'attachment_removelink'));
		$this->register_action('plugin.removeattachments.remove_attachments', array($this, 'remove_attachments'));
	}

	function attachment_removelink($p)
	{
	  $rcmail = rcmail::get_instance();

	  $links = preg_split('/<li[^>]*>/', $p['content']);

		for ($i = 1; $i < count($links); $i++) {
			if (preg_match('/part:\'([0-9\.]+)\'/', $links[$i], $matches)) {
			  $remove = $this->api->output->button(array('command' => 'plugin.removeattachments.remove_one', 'prop' => $matches[1], 'image' => $this->url(null) . $this->local_skin_path() . '/del.png', 'title' => 'removeattachments.removeattachment'));
				$links[$i] = str_replace('</li>', '&nbsp;' . $remove . '</li>', $links[$i]);
			}
		}
		$p['content'] = join('<li>', $links);

		// when there are multiple attachments allow delete all
		if (substr_count($p['content'], '<li>') > 1) {
			$link = html::tag('li', null,
				$this->api->output->button(array('command' => 'plugin.removeattachments.remove_all', 'image' => $this->url(null) . $this->local_skin_path() . '/del_all.png', 'title' => 'removeattachments.removeall', 'style' => 'padding-left:8px'))
				);

			$p['content'] = preg_replace('/(<ul[^>]*>)/', '$1' . $link, $p['content']);
		}

		return $p;
	}

	function remove_attachments()
	{
		$rcmail = rcmail::get_instance();
		$imap = $rcmail->imap;
		$MESSAGE = new rcube_message(get_input_value('_uid', RCUBE_INPUT_GET));
		$headers = $this->_parse_headers($imap->get_raw_headers($MESSAGE->uid));

		// set message charset as default
		if (!empty($MESSAGE->headers->charset))
			$imap->set_charset($MESSAGE->headers->charset);

		// Remove old MIME headers
		unset($headers['MIME-Version']);
		unset($headers['Content-Type']);

		$MAIL_MIME = new Mail_mime($rcmail->config->header_delimiter());
		$MAIL_MIME->headers($headers);

		if ($MESSAGE->has_html_part()) {
			$body = $MESSAGE->first_html_part();
			$MAIL_MIME->setHTMLBody($body);
		}

		$body = $MESSAGE->first_text_part();
		$MAIL_MIME->setTXTBody($body, false, true);

		foreach ($MESSAGE->attachments as $attachment) {
			if ($attachment->mime_id != get_input_value('_part', RCUBE_INPUT_GET) && get_input_value('_part', RCUBE_INPUT_GET) != '-1') {
				$MAIL_MIME->addAttachment(
					$MESSAGE->get_part_content($attachment->mime_id),
					$attachment->mimetype,
					$attachment->filename,
					false,
					$attachment->encoding,
					$attachment->disposition,
					'', $attachment->charset
				);
			}
		}

		foreach ($MESSAGE->mime_parts as $attachment) {
			if (!empty($attachment->content_id)) {
				// covert CID to Mail_MIME format
				$attachment->content_id = str_replace('<', '', $attachment->content_id);
				$attachment->content_id = str_replace('>', '', $attachment->content_id);

				if (empty($attachment->filename))
					$attachment->filename = $attachment->content_id;

				$MESSAGE_body = $MAIL_MIME->getHTMLBody();
				$dispurl = 'cid:' . $attachment->content_id;
				$MESSAGE_body = str_replace($dispurl, $attachment->filename, $MESSAGE_body);
				$MAIL_MIME->setHTMLBody($MESSAGE_body);

				$MAIL_MIME->addHTMLImage(
					$MESSAGE->get_part_content($attachment->mime_id),
					$attachment->mimetype,
					$attachment->filename,
					false
				);
			}
		}

		// encoding settings for mail composing
		$MAIL_MIME->setParam('head_encoding', $MESSAGE->headers->encoding);
		$MAIL_MIME->setParam('head_charset', $MESSAGE->headers->charset);

		foreach ($MESSAGE->mime_parts as $mime_id => $part) {
			$mimetype = strtolower($part->ctype_primary . '/' . $part->ctype_secondary);

			if ($mimetype == 'text/html') {
				$MAIL_MIME->setParam('text_encoding', $part->encoding);
				$MAIL_MIME->setParam('html_charset', $part->charset);
			}
			else if ($mimetype == 'text/plain') {
				$MAIL_MIME->setParam('html_encoding', $part->encoding);
				$MAIL_MIME->setParam('text_charset', $part->charset);
			}
		}

		$saved = $imap->save_message($_SESSION['mbox'], $MAIL_MIME->getMessage());
		write_log("debug","saved=".$saved);
		if ($saved) {
			$imap->delete_message($MESSAGE->uid);

			// Assume the one we just added has the highest UID
			$uids = $imap->conn->fetchUIDs($imap->mod_mailbox($_SESSION['mbox']));
			$uid = end($uids);

			// set flags
			foreach ($MESSAGE->headers->flags as $flag)
				$imap->set_flag($uid, strtoupper($flag), $_SESSION['mbox']);

			$this->api->output->command('display_message', $this->gettext('attachmentremoved'), 'confirmation');
			$this->api->output->command('removeattachments_reload', $uid);
		}
		else {
			$this->api->output->command('display_message', $this->gettext('removefailed'), 'error');
		}

		$this->api->output->send();
	}

	private function _parse_headers($headers)
	{
		$a_headers = array();
		$headers = preg_replace('/\r?\n(\t| )+/', ' ', $headers);
		$lines = explode("\n", $headers);
		$c = count($lines);

		for ($i=0; $i<$c; $i++) {
			if ($p = strpos($lines[$i], ': ')) {
				$field = substr($lines[$i], 0, $p);
				$value = trim(substr($lines[$i], $p+1));
				$a_headers[$field] = $value;
			}
		}

		return $a_headers;
	}
}

?>