<?php

/*
 * This file is part of the PHP IMAP2 package.
 *
 * (c) Francesco Bianco <bianco@javanile.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcyMailing\Libraries\Imap2;

class Mail
{
    /**
     * Copy specified messages to a mailbox.
     *
     * @param $imap
     * @param $messageNums
     * @param $mailbox
     * @param $flags
     *
     * @return false|mixed
     */
    public static function copy($imap, $messageNums, $mailbox, $flags = 0)
    {
        if (!is_a($imap, Connection::class)) {
            return Errors::invalidImapConnection(debug_backtrace(), 1, false);
        }

        if ($flags & CP_MOVE) {
            return Mail::move($imap, $messageNums, $mailbox, $flags);
        }

        $client = $imap->getClient();

        if (!($flags & CP_UID)) {
            $messageNums = ImapHelpers::idToUid($imap, $messageNums);
        }

        $from = $imap->getMailboxName();
        $to = $mailbox;

        return $client->copy($messageNums, $from, $to);
    }

    /**
     * Move specified messages to a mailbox.
     *
     * @param $imap
     * @param $messageNums
     * @param $mailbox
     * @param $flags
     *
     * @return false|mixed
     */
    public static function move($imap, $messageNums, $mailbox, $flags = 0)
    {
        if (!is_a($imap, Connection::class)) {
            return Errors::invalidImapConnection(debug_backtrace(), 1, false);
        }

        $client = $imap->getClient();
        #$client->setDebug(true);

        if (!($flags & CP_UID)) {
            $messageNums = ImapHelpers::idToUid($imap, $messageNums);
        }

        return $client->move($messageNums, $imap->getMailboxName(), $mailbox);
    }
}
