<?php

namespace FL;

class IMAPSearchString {

    const _ALL = "ALL";
    const _ANSWERED = "ANSWERED";
    const _BCC = "BCC";
    const _BEFORE = "BEFORE";
    const _BODY = "BODY";
    const _CC = "CC";
    const _DELETED = "DELETED";
    const _FLAGGED = "FLAGGED";
    const _FROM = "FROM";
    const _KEYWORD = "KEYWORD";
    const _NEW = "NEW";
    const _OLD = "OLD";
    const _ON = "ON";
    const _RECENT = "RECENT";
    const _SEEN = "SEEN";
    const _SINCE = "SINCE";
    const _SUBJECT = "SUBJECT";
    const _TEXT = "TEXT";
    const _TO = "TO";
    const _UNANSWERED = "UNANSWERED";
    const _UNDELETED = "UNDELETED";
    const _UNFLAGGED = "UNFLAGGED";
    const _UNKEYWORD = "UNKEYWORD";
    const _UNSEEN = "UNSEEN";
        /*
          ALL - return all messages matching the rest of the criteria
          ANSWERED - match messages with the \\ANSWERED flag set
          BCC "string" - match messages with "string" in the Bcc: field
          BEFORE "date" - match messages with Date: before "date"
          BODY "string" - match messages with "string" in the body of the message
          CC "string" - match messages with "string" in the Cc: field
          DELETED - match deleted messages
          FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
          FROM "string" - match messages with "string" in the From: field
          KEYWORD "string" - match messages with "string" as a keyword
          NEW - match new messages
          OLD - match old messages
          ON "date" - match messages with Date: matching "date"
          RECENT - match messages with the \\RECENT flag set
          SEEN - match messages that have been read (the \\SEEN flag is set)
          SINCE "date" - match messages with Date: after "date"
          SUBJECT "string" - match messages with "string" in the Subject:
          TEXT "string" - match messages with text "string"
          TO "string" - match messages with "string" in the To:
          UNANSWERED - match messages that have not been answered
          UNDELETED - match messages that are not deleted
          UNFLAGGED - match messages that are not flagged
          UNKEYWORD "string" - match messages that do not have the keyword "string"
          UNSEEN - match messages which have not been read yet
         */

    private $params = null;

    public static function getInstance($params = null) {
        $class = __CLASS__;
        return new $class($params);
    }

    public function __construct($params) {
        $this->params = $params;
        return $this;
    }
    
    private function getParametersWithoutValues() {
        return array(   IMAPSearchString::_ALL, IMAPSearchString::_ANSWERED, IMAPSearchString::_DELETED,
                        IMAPSearchString::_FLAGGED, IMAPSearchString::_NEW, IMAPSearchString::_OLD,
                        IMAPSearchString::_RECENT, IMAPSearchString::_SEEN, IMAPSearchString::_UNANSWERED,
                        IMAPSearchString::_UNDELETED, IMAPSearchString::_UNFLAGGED, IMAPSearchString::_UNKEYWORD,
                        IMAPSearchString::_UNSEEN);
    }
    
    private function getParametersWithValues() {
        return array(   IMAPSearchString::_BCC, IMAPSearchString::_BEFORE, IMAPSearchString::_BODY,
                        IMAPSearchString::_CC, IMAPSearchString::_FROM, IMAPSearchString::_KEYWORD,
                        IMAPSearchString::_ON, IMAPSearchString::_SINCE, IMAPSearchString::_SUBJECT,
                        IMAPSearchString::_TEXT, IMAPSearchString::_TO);
    }

    public function toString() {
        $returnvalue = "";
        if (is_array($this->params)) {
            foreach($this->params as $param => $value) {
                if (in_array($param, $this->getParametersWithoutValues())) {
                    $returnvalue .= $param . " ";
                } else {
                    if (in_array($param, $this->getParametersWithValues())) {
                        $returnvalue .= $param . ' "' . $value . '" ';
                    }
                }
                
            }
        }
        return $returnvalue;
    }

}
