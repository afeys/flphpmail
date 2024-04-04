<?php

namespace FL;

use FL\Message;
use FL\MessageType;

class IMAPConnection {

    const DEFAULTNUMBEROFMESSAGESPERPAGE = 30;
    const SORTMOSTRECENTFIRST = "MRF";
    const SORTOLDESTFIRST = "OF";

    private $mailbox = null;
    private $foldername = null;
    private $user = null;
    private $password = null;
    private $connection = null;
    private $result = true;
    private $firstmessage = null; // the idx number of the first message loaded
    private $lastmessage = null; // the idx number of the last message loaded
    private $messagecount = null;
    private $messages = array();
    private $currentsortorder = null;
    private $currentpage = null;
    private $currentnumperpage = null;
    private $maxpages = null;
    private $errormessage = null;

    public function __construct($mailbox, $user, $password) {
        $this->mailbox = $mailbox;
        $this->user = $user;
        $this->password = $password;
        $this->connection = \imap_open($mailbox, $user, $password);
        return $this;
    }

    public function __destruct() {
        if ($this->connection !== null) {
            $this->close();
        }
    }

    public function reConnect() {
        $this->connection = \imap_open($this->mailbox, $this->user, $this->password);
        return $this;
    }

    public function close() {
        \imap_errors();
        if (\imap_close($this->connection, CL_EXPUNGE)) {
            $this->mailbox = null;
            $this->user = null;
            $this->password = null;
            $this->connection = null;
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    function getMailbox() {
        return $this->mailbox;
    }

    function getFoldername() {
        return $this->foldername;
    }

    function getUser() {
        return $this->user;
    }

    function getPassword() {
        return $this->password;
    }

    function getFirstMessageNr() {
        return $this->firstmessage;
    }

    function getLastMessageNr() {
        return $this->lastmessage;
    }

    function getMessageCount() {
        return $this->messagecount;
    }
    function getErrorMessage() {
        return $this->errormessage;
    }

    function getCurrentSortOrder() {
        return $this->currentsortorder;
    }

    function getCurrentPage() {
        return $this->currentpage;
    }

    function getCurrentNumPerPage() {
        return $this->currentnumperpage;
    }

    function getMaxPages() {
        return $this->maxpages;
    }

    public function openFolder($foldername) {
        $this->foldername = $foldername;
        $this->result = \imap_reopen($this->connection, $this->mailbox . $foldername);
        return $this;
    }

    public function searchMessages($searchcriteria = null, $page = null, $numperpage = IMAPConnection::DEFAULTNUMBEROFMESSAGESPERPAGE) {
        $searchstring = $searchcriteria->toString();
        $this->errormessage = $searchstring;
        $msgnos = \imap_search($this->connection, $searchstring);
        if ($msgnos == false) {
            $this->errormessage .= "search returned false";
            $this->messagecount = 0;
            $this->messages = array();
            $this->firstmessage = 0;
            $this->lastmessage = $this->messagecount;
            $this->maxpages = 0;
            $this->currentsortorder = IMAPConnection::SORTMOSTRECENTFIRST;
            $this->currentpage = 0;
            $this->currentnumperpage = $numperpage;
        }
        if (is_array($msgnos)) {
            $this->errormessage .= "search returned messages : " . print_r($msgnos,true);
            $this->messagecount = count($msgnos);
            $this->messages = array();
            $this->firstmessage = 1;
            $this->lastmessage = $this->messagecount;
            $this->maxpages = null;

            if ($page !== null) {
                $this->maxpages = round($this->messagecount / $numperpage + 0.5, 0);
                if ($page <= $this->maxpages) {
                    $this->firstmessage = ($page - 1) * $numperpage + 1;
                    $this->lastmessage = $page * $numperpage;
                    if ($this->lastmessage > $this->messagecount) {
                        $this->lastmessage = $this->messagecount;
                    }
                }
            }

            for ($i = $this->lastmessage; $i >= $this->firstmessage; $i--) {
                $this->messages[] = (new IMAPMessage($this, $msgnos[$i - 1]));
            }

            $this->currentsortorder = IMAPConnection::SORTMOSTRECENTFIRST;
            $this->currentpage = $page;
            $this->currentnumperpage = $numperpage;
        }
        return $this->messages;
    }

    public function loadMessages($sortorder = IMAPConnection::SORTMOSTRECENTFIRST, $page = null, $numperpage = IMAPConnection::DEFAULTNUMBEROFMESSAGESPERPAGE) {
        $this->messagecount = \imap_num_msg($this->connection);

        $this->messages = array();
        $this->firstmessage = 1;
        $this->lastmessage = $this->messagecount;
        $this->maxpages = null;

        if ($page !== null) {
            $this->maxpages = round($this->messagecount / $numperpage + 0.5, 0);
            if ($page <= $this->maxpages) {
                $this->firstmessage = ($page - 1) * $numperpage + 1;
                $this->lastmessage = $page * $numperpage;
                if ($this->lastmessage > $this->messagecount) {
                    $this->lastmessage = $this->messagecount;
                }
            }
        }
        if ($sortorder == IMAPConnection::SORTMOSTRECENTFIRST) {
            for ($i = $this->lastmessage; $i >= $this->firstmessage; $i--) {
                $this->messages[] = (new IMAPMessage($this, $i));
            }
        } else {
            for ($i = $this->firstmessage; $i <= $this->lastmessage; $i++) {
                $this->messages[] = (new IMAPMessage($this, $i));
            }
        }

        $this->currentsortorder = $sortorder;
        $this->currentpage = $page;
        $this->currentnumperpage = $numperpage;
        return $this;
    }

    public function getMessages($sortorder = IMAPConnection::SORTMOSTRECENTFIRST, $page = null, $numperpage = IMAPConnection::DEFAULTNUMBEROFMESSAGESPERPAGE) {
        if ($sortorder !== $this->currentsortorder || $page !== $this->currentpage || $numperpage !== $this->currentnumperpage) {
            $this->loadMessages($sortorder, $page, $numperpage);
        }
        return $this->messages;
    }


    public function createFolder($foldername) {
        /*
         * Found = 0
For k = oAccount.IMAPFolders.ItemByName("Inbox").Subfolders.count -1 to  0 step -1
   if oAccount.IMAPFolders.ItemByName("Inbox").Subfolders.Item(k).name = SentFolderArchive then
      Found = 1
      Exit For
   End if
Next
if Found = 0 oAccount.IMAPFolders.ItemByName("Inbox").Subfolders.Add(SentFolderArchive)
         */
        $returnvalue = null;
        if ($this->connection !== null) {
            try {
                if (\imap_createmailbox($this->connection, $this->mailbox . "/" . \imap_utf7_encode($foldername))) {
                    // successfully created mailbox
                } else {
                    throw new \Exception("IMAP: couldn't create folder " . $foldername . " due to error: " . \imap_last_error());
//                echo "<pre>";print_r(imap_errors());echo"</pre>";
                }
            } catch (Exception $e) {
                // TODO do something with this
            }
        }
        return $returnvalue;
    }
    public function getAvailableFolders() {
        // Example: 
        // [0] => Array
        //  (
        //    [longname] => {10.31.13.29/ssl/novalidate-cert}Drafts
        //    [shortname] => Drafts
        //  )
        $returnvalue = array();
        $folders = array();
        if ($this->connection !== null) {
            $folders = \imap_list($this->connection, $this->mailbox, "*");
        }
        if (is_array($folders)) {
            foreach ($folders as $folder) {
                $shortname = str_replace($this->mailbox, '', $folder);
                $returnvalue[] = array("longname" => $folder, "shortname" => $shortname);
            }
        }
        return $returnvalue;
    }

}
