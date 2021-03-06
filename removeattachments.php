<?php
/**
 * Roundcube Plugin RemoveAttachments.
 *
 * Copyright (C) Diana Soares
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License (with exceptions
 * for skins & plugins) as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/.
 */
/**
 * Roundcube plugin removeattachments.
 *
 * Roundcube plugin to allow the removal of attachments from a message.
 * Original code from Philip Weir.
 *
 * @version 0.3.1
 * @author Diana Soares
 */
class removeattachments extends rcube_plugin
{
    public $task = 'mail';

    /**
     * Plugin initialization.
     */
    public function init()
    {
        $this->add_texts('localization', ['removeoneconfirm', 'removeallconfirm', 'removing']);
        $this->include_script('removeattachments.js');

        $this->add_hook('template_container', [$this, 'add_removeone_link']);
        $this->add_hook('template_object_messageattachments', [$this, 'add_removeall_link']);
        $this->register_action('plugin.removeattachments.remove_attachments', [$this, 'remove_attachments']);
    }

    /**
     * Handler to place a link in the attachmentmenu (template container)
     * for each attachment to trigger the removal of the selected attachment.
     *
     * @param  array $p Hook arguments
     * @return array Hook arguments
     */
    public function add_removeone_link($p)
    {
        if ($p['name'] == 'attachmentmenu') {
            $link = $this->api->output->button([
                'type' => 'link',
                'id' => 'attachmentmenuremove',
                'command' => 'plugin.removeattachments.removeone',
                'class' => 'removelink icon active',
                'content' => html::tag(
                    'span',
                    ['class' => 'icon cross'],
                    rcube::Q($this->gettext('removeattachments.removeattachment'))
                ),
            ]);

            $p['content'] .= html::tag('li', ['role' => 'menuitem'], $link);
        }

        return $p;
    }

    /**
     * Handler to place a link in the messageAttachments (template object)
     * to trigger the removal of all attachments.
     *
     * @param  array $p Hook arguments
     * @return array Hook arguments
     */
    public function add_removeall_link($p)
    {
        // when there are multiple attachments allow delete all
        if (substr_count($p['content'], ' id="attach') > 1) {
            $link = $this->api->output->button([
                'type' => 'link',
                'command' => 'plugin.removeattachments.removeall',
                'content' => rcube::Q($this->gettext('removeattachments.removeall')),
                'title' => 'removeattachments.removeall',
                'class' => 'button removeattachments',
            ]);

            if (rcmail::get_instance()->config->get('skin') == 'classic') {
                //$p['content'] = preg_replace('/(<ul[^>]*>)/', '$1' . $link, $p['content']);
                $p['content'] = str_replace('</ul>', html::tag('li', null, $link) . '</ul>', $p['content']);
            }
            else {
                $p['content'] .= $link;
            }

            $this->include_stylesheet($this->local_skin_path() . '/removeattachments.css');
        }

        return $p;
    }

    /**
     * Remove attachments from a message.
     */
    public function remove_attachments()
    {
        $rcmail = rcmail::get_instance();
        $imap = $rcmail->storage;
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_GET);

        $MESSAGE = new rcube_message($uid);

        if (empty($MESSAGE)) {
            $this->api->output->command('display_message', $this->gettext('messageopenerror'), 'error');
            $this->api->output->send();

            return;
        }

        $headers = $this->_parse_headers($imap->get_raw_headers($MESSAGE->uid));

        // set message charset as default
        if (!empty($MESSAGE->headers->charset)) {
            $imap->set_charset($MESSAGE->headers->charset);
        }

        // Remove old MIME headers
        unset(
            $headers['MIME-Version'],
            $headers['Content-Type']
        );

        $MAIL_MIME = new Mail_mime($rcmail->config->header_delimiter());
        $MAIL_MIME->headers($headers);

        $part = null;
        if ($MESSAGE->has_html_part(true, $part)) {
            $body = $MESSAGE->get_part_content($part->mime_id, null, true);
            $MAIL_MIME->setHTMLBody($body);
        }

        $body = $MESSAGE->first_text_part();
        $MAIL_MIME->setTXTBody($body, false, true);

        $_part = rcube_utils::get_input_value('_part', rcube_utils::INPUT_GET);

        foreach ($MESSAGE->attachments as $attachment) {
            if ($attachment->mime_id != $_part && $_part != '-1') {
                $MAIL_MIME->addAttachment(
                    $MESSAGE->get_part_content($attachment->mime_id),
                    $attachment->mimetype,
                    $attachment->filename,
                    false,
                    $attachment->encoding,
                    $attachment->disposition,
                    '',
                    $attachment->charset
                );
            }
        }

        foreach ($MESSAGE->mime_parts as $attachment) {
            if (!empty($attachment->content_id)) {
                // covert CID to Mail_MIME format
                $attachment->content_id = str_replace('<', '', $attachment->content_id);
                $attachment->content_id = str_replace('>', '', $attachment->content_id);

                if (empty($attachment->filename)) {
                    $attachment->filename = $attachment->content_id;
                }

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

        $saved = $imap->save_message(
            $_SESSION['mbox'], $MAIL_MIME->getMessage(),
            '', false, [], $MESSAGE->headers->date
        );

        if ($saved) {
            $imap->delete_message($MESSAGE->uid);

            // Assume the one we just added has the highest UID
            //dsoares $uids = $imap->conn->fetchUIDs($imap->mod_mailbox($_SESSION['mbox']));
            //dsoares $uid = end($uids);
            $uid = $saved; //dsoares

            // set flags
            foreach ($MESSAGE->headers->flags as $flag) {
                $imap->set_flag($uid, strtoupper($flag), $_SESSION['mbox']);
            }

            // by default, mark message as seen.
            $imap->set_flag($uid, 'SEEN', $_SESSION['mbox']);

            $this->api->output->command('display_message', $this->gettext('attachmentremoved'), 'confirmation');
            $this->api->output->command('removeattachments_reload', $uid);
        }
        else {
            $this->api->output->command('display_message', $this->gettext('removefailed'), 'error');
        }

        $this->api->output->send();
    }

    /**
     * Parse message headers.
     *
     * @param array $headers The message headers
     */
    private function _parse_headers($headers)
    {
        $a_headers = [];
        $headers = preg_replace('/\r?\n(\t| )+/', ' ', $headers);
        $lines = explode("\n", $headers);
        $nlines = count($lines);

        for ($i = 0; $i < $nlines; $i++) {
            if ($pos = strpos($lines[$i], ': ')) {
                $field = substr($lines[$i], 0, $pos);
                $value = trim(substr($lines[$i], $pos + 1));
                $a_headers[$field] = $value;
            }
        }

        return $a_headers;
    }
}
