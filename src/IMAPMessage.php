<?php

namespace FL\src;

use FL\StringTool;

class IMAPMessageHeader {
    /*
      toaddress - full to: line, up to 1024 characters
      to - an array of objects from the To: line, with the following properties: personal, adl, mailbox, and host
      fromaddress - full from: line, up to 1024 characters
      from - an array of objects from the From: line, with the following properties: personal, adl, mailbox, and host
      ccaddress - full cc: line, up to 1024 characters
      cc - an array of objects from the Cc: line, with the following properties: personal, adl, mailbox, and host
      bccaddress - full bcc: line, up to 1024 characters
      bcc - an array of objects from the Bcc: line, with the following properties: personal, adl, mailbox, and host
      reply_toaddress - full Reply-To: line, up to 1024 characters
      reply_to - an array of objects from the Reply-To: line, with the following properties: personal, adl, mailbox, and host
      senderaddress - full sender: line, up to 1024 characters
      sender - an array of objects from the Sender: line, with the following properties: personal, adl, mailbox, and host
      return_pathaddress - full Return-Path: line, up to 1024 characters
      return_path - an array of objects from the Return-Path: line, with the following properties: personal, adl, mailbox, and host
      remail -
      date - The message date as found in its headers
      Date - Same as date
      subject - The message subject
      Subject - Same as subject
      in_reply_to -
      message_id -
      newsgroups -
      followup_to -
      references -
      Recent - R if recent and seen, N if recent and not seen, ' ' if not recent.
      Unseen - U if not seen AND not recent, ' ' if seen OR not seen and recent
      Flagged - F if flagged, ' ' if not flagged
      Answered - A if answered, ' ' if unanswered
      Deleted - D if deleted, ' ' if not deleted
      Draft - X if draft, ' ' if not draft
      Msgno - The message number
      MailDate -
      Size - The message size
      udate - mail message date in Unix time
      fetchfrom - from line formatted to fit fromlength characters
      fetchsubject - subject line formatted to fit subjectlength characters
     */

    const MAILADDRESSALL = "MA_ALL";
    const MAILADDRESSSENDER = "MA_SENDER";
    const MAILADDRESSTO = "MA_TO";
    const MAILADDRESSCC = "MA_CC";
    const MAILADDRESSBCC = "MA_BCC";

    private $date = null;
    private $subject = null;
    private $message_id = null;
    private $size = null;
    private $sender = array();
    private $receiver = array();
    private $receiverCC = array();
    private $receiverBCC = array();
    private $fullheader = null;

    public function __construct($header) {
        $this->fullheader = $header;
        $headerarray = ObjectTool::getInstance($header);
        $this->date = $headerarray->getItemWithKey("date");
        $this->subject = $headerarray->getItemWithKey("subject");
        $this->message_id = $headerarray->getItemWithKey("message_id");
        $this->size = $headerarray->getItemWithKey("Size");
        $this->sender = $this->parseEmailAddressArray($headerarray->getItemWithKey("from"));
        $this->receiver = $this->parseEmailAddressArray($headerarray->getItemWithKey("to"));
        $this->receiverCC = $this->parseEmailAddressArray($headerarray->getItemWithKey("cc"));
        $this->receiverBCC = $this->parseEmailAddressArray($headerarray->getItemWithKey("bcc"));
    }

    private function cleanupMailAddress($mailaddress) {
        $returnvalue = str_replace("'", "", $mailaddress);
        $returnvalue = str_replace('"', "", $mailaddress);
        return $returnvalue;
    }

    private function parseEmailAddressArray($emailaddressinfo) {
        $returnvalue = array();
        if (is_array($emailaddressinfo)) {
            foreach ($emailaddressinfo as $idx => $emailaddress) {
                $emailinfoarray = ObjectTool::getInstance($emailaddress);
                $personal = $emailinfoarray->getItemWithKey("personal");
                $mailbox = $emailinfoarray->getItemWithKey("mailbox");
                $host = $emailinfoarray->getItemWithKey("host");
                if (strlen(trim($personal)) == 0) {
                    if (strlen(trim($mailbox)) > 0 && strlen(trim($host)) == 0) {
                        $personal = $mailbox;
                        $mailbox = "";
                    }
                }
                if (StringTool::getInstance($personal)->startsWith("=")) {
                    $personal = $mailbox;
                }
                $tmpmailaddress = $this->cleanupMailAddress($mailbox . "@" . $host);
                if (isValidMailAddress($tmpmailaddress)) {
                    $returnvalue[] = array("name" => $personal, "emailaddress" => $tmpmailaddress);
                }
            }
        }
        return $returnvalue;
    }

    private function emailAddressToMailToLink($mailaddress, $extraclass = "") {
        $returnvalue = "";
        if (is_array($mailaddress)) {
            $name = "";
            $email = "";
            if (array_key_exists("name", $mailaddress)) {
                $name = $mailaddress["name"];
            }
            if (array_key_exists("emailaddress", $mailaddress)) {
                $email = $this->cleanupMailAddress($mailaddress["emailaddress"]);
            }
            $displayname = "";
            if (strlen(trim($name)) == 0) {
                $displayname = StringTool::getInstance($email)->replace(" ", "&nbsp;")->toString();
            } else {
                $displayname = StringTool::getInstance($name)->replace(" ", "&nbsp;")->toString();
            }
            $returnvalue .= "<a title=\"" . $displayname . "\" class=\"emailaddress " . $extraclass . "\" href=\"mailto:" . $email . "\">";
            $returnvalue .= StringTool::getInstance($displayname)->getFirst(20, true)->toString();
            $returnvalue .= "</a> "; // the extra space is important, otherwise DBPanelMailArchive concatenates all mailaddress on ONE line, no auto linesplitting
        }
        return $returnvalue;
    }

    public function getDate() {
        return $this->date;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function getMessage_id() {
        return $this->message_id;
    }

    public function getSize() {
        return $this->size;
    }

    public function getSenders() {
        return $this->sender;
    }

    public function getSendersAsString() {
        $returnvalue = "";
        foreach ($this->sender as $emailaddress) {
            $returnvalue .= "[" . $emailaddress["emailaddress"] . "]";
        }
        return $returnvalue;
    }

    public function getAllMailAdresses($which = IMAPMessageHeader::MAILADDRESSALL) {
        $returnvalue = array();
        if ($which == IMAPMessageHeader::MAILADDRESSALL || $which == IMAPMessageHeader::MAILADDRESSSENDER) {
            foreach ($this->sender as $emailaddress) {
                $returnvalue[] = $emailaddress["emailaddress"];
            }
        }
        if ($which == IMAPMessageHeader::MAILADDRESSALL || $which == IMAPMessageHeader::MAILADDRESSTO) {
            foreach ($this->receiver as $emailaddress) {
                $returnvalue[] = $emailaddress["emailaddress"];
            }
        }
        if ($which == IMAPMessageHeader::MAILADDRESSALL || $which == IMAPMessageHeader::MAILADDRESSCC) {
            foreach ($this->receiverCC as $emailaddress) {
                $returnvalue[] = $emailaddress["emailaddress"];
            }
        }
        if ($which == IMAPMessageHeader::MAILADDRESSALL || $which == IMAPMessageHeader::MAILADDRESSBCC) {
            foreach ($this->receiverBCC as $emailaddress) {
                $returnvalue[] = $emailaddress["emailaddress"];
            }
        }
        return array_unique($returnvalue);
    }

    public function getSendersAsMailToLinks() {
        $returnvalue = "";
        foreach ($this->sender as $emailaddress) {
            $returnvalue .= $this->emailAddressToMailToLink($emailaddress, "mailfrom");
        }
        return $returnvalue;
    }

    public function getReceivers() {
        return $this->receiver;
    }

    public function getReceiversAsMailToLinks() {
        $returnvalue = "";
        foreach ($this->receiver as $emailaddress) {
            $returnvalue .= $this->emailAddressToMailToLink($emailaddress, "mailto");
        }
        return $returnvalue;
    }

    public function getCCReceivers() {
        return $this->receiverCC;
    }

    public function getCCReceiversAsMailToLinks() {
        $returnvalue = "";
        foreach ($this->receiverCC as $emailaddress) {
            $returnvalue .= $this->emailAddressToMailToLink($emailaddress, "mailcc");
        }
        return $returnvalue;
    }

    public function getBCCReceivers() {
        return $this->receiverBCC;
    }

    public function getBCCReceiversAsMailToLinks() {
        $returnvalue = "";
        foreach ($this->receiverBCC as $emailaddress) {
            $returnvalue .= $this->emailAddressToMailToLink($emailaddress, "mailbcc");
        }
        return $returnvalue;
    }

    public function getFullheader() {
        return $this->fullheader;
    }

}

class IMAPMessageBody {

    private $body = null;
    private $bodyhtml = null;
    private $ishtmlmessage = false;
    private $summary = null;

    public function __construct($body, $bodyhtml) {
        $this->body = $body;
        $this->bodyhtml = $bodyhtml;
        if (strlen(trim($this->bodyhtml)) > 0) {
            $this->ishtmlmessage = true;
            if (strlen(trim($this->body)) == 0) {
                $convertor = StringTool::getInstance($this->getBodyHTML());
                $convertor->htmlToPlainText();
                $this->body = $convertor->toString();
            }
        }
        $this->summary = $this->summarize();
    }

    public function getBody() {
        return $this->body;
    }

    public function getBodyHTML($returnplainbodyifempty = true) {
        if (strlen(trim($this->bodyhtml)) == 0) {
            return $this->body;
        }
        return $this->bodyhtml;
    }

    public function getSummary() {
        return $this->summary;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function setSummary($summary) {
        $this->summary = $summary;
    }

    public function summarize() {
        return StringTool::getInstance($this->getBody())->removeExcessiveWhiteSpace()->getFirst(250)->toString();
    }

}

class IMAPMessage {

    const CHECKFORDUPLICATES = "CFD";
    const DONTCHECKFORDUPLICATES = "DCFD";
    
    private $connection = null;
    private $index = null;
    private $uid = null;
    private $messageindex = null;
    private $header = null;
    private $body = null;
    private $structure = null;
    private $hasattachments = false;
    private $attachments = array();
    private $attachmentstats = "";
    private $messageloaded = false;
    
    // added afeys 20190923
    private $origheader = null;  // used for the copyToOtherMailbox function
    private $origheaderinfo = null;  // ...
    private $origbody = null; // ...

    public function __construct($connection, $index) {
        $this->connection = $connection;
        $this->index = $index;
        $this->uid = imap_uid($connection->getConnection(), $index);
        $this->messageindex = $index;
        $this->header = new \FL\IMAPMessageHeader(imap_headerinfo($connection->getConnection(), $index));
//echo "<pre>";print_r($this->header);echo"</pre>";        
        $this->structure = imap_fetchstructure($connection->getConnection(), $index);

        // added afeys 20190923
        $this->origheader = imap_fetchheader($connection->getConnection(), $index);
        $this->origheaderinfo = imap_headerinfo($connection->getConnection(), $index);
        $this->origbody = imap_body($connection->getConnection(), $index);
        
        $body = "";
        $bodyhtml = "";
        $attachmenttypecounter = array();
        if (is_object($this->structure)) {
            $this->messageloaded = true;
            if (property_exists($this->structure, "parts")) {
                $flattenedParts = $this->flattenParts($this->structure->parts);
                foreach ($flattenedParts as $partNumber => $part) {
                    switch ($part->type) {
                        case 0:     // the HTML or plain text part of the email
                            $message = $this->getPart($connection->getConnection(), $index, $partNumber, $part->encoding);
                            if ($part->subtype = "HTML") {
                                $bodyhtml = $message;
                            } else {
                                $body = $message;                    // now do something with the message, e.g. render it
                            }
                            break;
                        case 1:     // multi-part headers, can ignore
                            break;
                        case 2:     // attached message headers, can ignore
                            break;
                        case 3:     // application  (case 3 - 7 are performed, in case there is no break)
                        case 4:     // audio  (case 4 - 7 are performed, in case there is no break)
                        case 5:     // image    (see above)
                        case 6:     // video
                        case 7:     // other
                            $filename = $this->getFilenameFromPart($part);
                            if ($filename) {
                                // it's an attachment
                                $this->hasattachments = true;
                                $attachment = $this->getPart($connection->getConnection(), $index, $partNumber, $part->encoding);
                                $this->attachments[] = array("filename" => $filename, "date" => $this->header->getDate(), "mailbox" => $connection->getMailBox(), "folder" => $connection->getFolderName(), "index" => $index, "user" => $connection->getUser(), "password" => $connection->getPassword());
                                // now do something with the attachment, e.g. save it somewhere
                                $suffix = \FL\StringHelper::getInstance($filename)->getEverythingAfterLast(".")->toString();
                                if (strlen(trim($suffix)) > 0) {
                                    if (array_key_exists($suffix, $attachmenttypecounter)) {
                                        $attachmenttypecounter[$suffix] += 1;
                                    } else {
                                        $attachmenttypecounter[$suffix] = 1;
                                    }
                                }
                            } else {
                                // don't know what it is
                            }
                            break;
                    }
                }
            }
        }
        foreach ($attachmenttypecounter as $type => $counter) {
            $this->attachmentstats .= "(" . $counter . " x " . $type . ")";
        }
        $this->body = new \FL\IMAPMessageBody($body, $bodyhtml);
        return $this;
    }

    public function getMailInfo() {
        $returnvalue = "";
        $returnvalue .= "Subject: " . $this->getHeader()->getSubject() . "<br>";
        $returnvalue .= "Date: " . print_r($this->getHeader()->getDate(), true) . "<br>";
        $returnvalue .= "Sender: " . implode(",", $this->getHeader()->getAllMailAdresses(IMAPMessageHeader::MAILADDRESSSENDER)) . "<br>";
        $returnvalue .= "Receivers: " . implode(",", $this->getHeader()->getAllMailAdresses(IMAPMessageHeader::MAILADDRESSTO)) . "<br>";
        $returnvalue .= "ReceiversCC: " . implode(",", $this->getHeader()->getAllMailAdresses(IMAPMessageHeader::MAILADDRESSCC)) . "<br>";
        $returnvalue .= "ReceiversBCC: " . implode(",", $this->getHeader()->getAllMailAdresses(IMAPMessageHeader::MAILADDRESSBCC)) . "<br>";
        $returnvalue .= "UniqId: " . $this->generateUniqueIdentifier() . "<br>";
        return $returnvalue;
    }
    
    public function getUID() {
        return $this->uid;
    }

    public function generateUniqueIdentifier() {
        // this generates a somewhat unique identifier based on datetime received, sender, subject, and a hash of the body
        /*        echo "<pre>sender1: ";
          print_r($this->getHeader()->getSendersAsString());
          echo "</pre>";
         */
        $senders = hash('crc32', $this->getHeader()->getSendersAsString());
        $datetime = hash('crc32', $this->getHeader()->getDate());
        $subject = hash('crc32', $this->getHeader()->getSubject());
        $bodyhash = hash('crc32', $this->getBody()->getBodyHTML()); // crc32 is sufficient for this, as security is no concern here. It is faster than sha & md5
        /*        echo "<pre>Senders:";
          print_r($senders);
          echo "</pre>";
          echo "<pre>datetime:";
          print_r($datetime);
          echo "</pre>";
          echo "<pre>subject:";
          print_r($subject);
          echo "</pre>";
          echo "<pre>bodyhash:";
          print_r($bodyhash);
          echo "</pre>";
         */
        return $senders . "-" . $datetime . "-" . $subject . "-" . $bodyhash;
    }

    public function getAttachmentStats() {
        return $this->attachmentstats;
    }

    public function saveAttachment($filenametosave, $savetofullpath) {
        if ($this->hasattachments) {
            if (property_exists($this->structure, "parts")) {
                $flattenedParts = $this->flattenParts($this->structure->parts);
                foreach ($flattenedParts as $partNumber => $part) {
                    switch ($part->type) {
                        case 0:     // the HTML or plain text part of the email
                            break;
                        case 1:     // multi-part headers, can ignore
                            break;
                        case 2:     // attached message headers, can ignore
                            break;
                        case 3:     // application  (case 3 - 7 are performed, in case there is no break)
                        case 4:     // audio  (case 4 - 7 are performed, in case there is no break)
                        case 5:     // image    (see above)
                        case 6:     // video
                        case 7:     // other
                            $filename = $this->getFilenameFromPart($part);
                            if ($filename) {
                                // it's an attachment
                                if ($filename == $filenametosave) {
                                    $attachment = $this->getPart($this->connection->getConnection(), $this->index, $partNumber, $part->encoding);
                                    file_put_contents($savetofullpath, $attachment);
                                }
                            } else {
                                // don't know what it is
                            }
                            break;
                    }
                }
            }
        }
        return $this;
    }

    private function flattenParts($messageParts, $flattenedParts = array(), $prefix = '', $index = 1, $fullPrefix = true) {
        foreach ($messageParts as $part) {
            $flattenedParts[$prefix . $index] = $part;
            if (isset($part->parts)) {
                if ($part->type == 2) {
                    $flattenedParts = $this->flattenParts($part->parts, $flattenedParts, $prefix . $index . '.', 0, false);
                } elseif ($fullPrefix) {
                    $flattenedParts = $this->flattenParts($part->parts, $flattenedParts, $prefix . $index . '.');
                } else {
                    $flattenedParts = $this->flattenParts($part->parts, $flattenedParts, $prefix);
                }
                unset($flattenedParts[$prefix . $index]->parts);
            }
            $index++;
        }

        return $flattenedParts;
    }

    private function getPart($connection, $messageNumber, $partNumber, $encoding) {
        $data = imap_fetchbody($connection, $messageNumber, $partNumber);
        switch ($encoding) {
            case 0: return $data; // 7BIT
            case 1: return $data; // 8BIT
            case 2: return $data; // BINARY
            case 3: return base64_decode($data); // BASE64
            case 4: return quoted_printable_decode($data); // QUOTED_PRINTABLE
            case 5: return $data; // OTHER
        }
    }

    private function getFilenameFromPart($part) {
        $filename = '';
        if ($part->ifdparameters) {
            foreach ($part->dparameters as $object) {
                if (strtolower($object->attribute) == 'filename') {
                    $filename = $object->value;
                }
            }
        }
        if (!$filename && $part->ifparameters) {
            foreach ($part->parameters as $object) {
                if (strtolower($object->attribute) == 'name') {
                    $filename = $object->value;
                }
            }
        }
        return $filename;
    }

    function getAttachments() {
        return $this->attachments;
    }

    public function getMessageindex() {
        return $this->messageindex;
    }

    public function getHeader() {
        return $this->header;
    }

    public function getBody() {
        return $this->body;
    }

    public function getStructure() {
        return $this->structure;
    }

    function getHasattachments() {
        return $this->hasattachments;
    }

    public function isMessageLoaded() {
        return $this->messageloaded;
    }

    public function setMessageindex($messageindex) {
        $this->messageindex = $messageindex;
    }

    public function setHeader($header) {
        $this->header = $header;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function setStructure($structure) {
        $this->structure = $structure;
    }

    public function delete() {
        // TODO
        if (imap_delete($this->connection->getConnection(), $this->index) == true) {
            echo "delete successfull";
        } else {
            echo "delete failed";
        }
    }
    public function compare($compareToMsg) {
        $returnvalue = false;
        if ($compareToMsg instanceof IMAPMessage) {
            if ($this->generateUniqueIdentifier() == $compareToMsg->generateUniqueIdentifier()) {
                $returnvalue = true;
            }
        }
        return $returnvalue;
    }
    public function isAlreadyInFolder($connection, $folder) {
        
    }
//    public function getUID() {
//        return imap_uid($this->connection->getConnection(), $this->index);
//    }
    public function copyToFolder($connection, $folder) {
        echo "message to move: " . $this->messageindex . "<br>";
//        $imapresult = imap_mail_copy($connection, $this->messageindex, $folder, CP_UID);
        //$imapresult = imap_mail_copy($connection, $this->getUID(), $folder, CP_UID);
                $imapresult = imap_mail_copy($connection, $this->messageindex, $folder);
        if ($imapresult == false) {
            echo "<hr>errors<pre>" . print_r(imap_errors()) . "</pre><hr><br>";
            echo "<hr>alerts<pre>" . print_r(imap_alerts()) . "</pre><hr><br>";
            error_log(imap_last_error());
            throw new \Exception("IMAP connection lost");
        }
        return $imapresult;
    }
    public function moveToFolder($connection, $folder) {
        echo "message to move: " . $this->messageindex . "<br>";
        // WARNING: doesn't work ok -> mail is moved and moved back a few seconds later.... very weird problem
//        echo "function moveToFolder;<br>";
//        echo "connection = " . print_r($connection, true) . ", index = ". $this->messageindex . ", folder = " . $folder . "<br>";
        $imapresult = imap_mail_move($connection, $this->messageindex, $folder, CP_UID);
//        $imapresult = imap_mail_move($connection, $this->messageindex, $folder, CP_UID);
//        $imapresult = imap_mail_move($connection, $this->getUID(), $folder, CP_UID);
//                $imapresult = imap_mail_move($connection, $this->messageindex, $folder);
        if ($imapresult == false) {
            echo "<hr>errors<pre>" . print_r(imap_errors()) . "</pre><hr><br>";
            echo "<hr>alerts<pre>" . print_r(imap_alerts()) . "</pre><hr><br>";
            error_log(imap_last_error());
            throw new \Exception("IMAP connection lost");
        }
        return $imapresult;
    }

    public function copyToOtherMailbox($host, $user, $pwd, $mailboxfolder, $checkforduplicates = IMAPMessage::DONTCHECKFORDUPLICATES) {
        $destmbox = imap_open($host, $user, $pwd);

        //  imap_reopen($destmbox, $host.$mailboxfolder);
        //  $folders = imap_list($destmbox, $host, "*");
        //  echo "<pre>folders=";print_r($folders);echo"</pre>";

/*        $header = imap_fetchheader($this->connection->getConnection(), $this->messageindex);
        $headerinfo = imap_headerinfo($this->connection->getConnection(), $this->messageindex);
        $body = imap_body($this->connection->getConnection(), $this->messageindex);
*/      
        $header = $this->origheader;
        $headerinfo = $this->origheaderinfo;
        $body = $this->origbody;
        
        $options = array();
        // Note to self: some sources mention a double backslash is needed....
        if (isset($header->Unseen) && !trim($header->Unseen)) {
            $options[] = '\Seen';
        }
        if (isset($header->Answered) && !trim($header->Answered)) {
            $options[] = '\Answered';
        }
        if (isset($header->Flagged) && !trim($header->Flagged)) {
            $options[] = '\Flagged';
        }
        if (isset($header->Deleted) && !trim($header->Deleted)) {
            $options[] = '\Deleted';
        }
        if (isset($header->Draft) && !trim($header->Draft)) {
            $options[] = '\Draft';
        }
        $messageOptions = implode(' ', $options);
        $messageDate = date('d-M-Y H:i:s O', $headerinfo->udate);

        if (imap_append($destmbox, $host . $mailboxfolder, $header . "\r\n" . $body, $messageOptions, $messageDate)) {
            
        }
        imap_close($destmbox);
    }

}
