<?php

define('MIMEMAIL_HTML', 1);
define('MIMEMAIL_ATTACH', 2);
define('MIMEMAIL_TEXT', 3);

class MIMEMail
{
    var $plaintext;
    var $output;
    var $headers;
    var $boundary;

    function MIMEMail()
    {
        $this->output = '';
        $this->headers = '';
        $this->boundary = md5(microtime());
        $this->plaintext = 0;
    }

    function send($from, $to, $subject)
    {
        $this->endMessage($from);
        return mail($to, $subject, $this->output, $this->headers);
    }

    function addHeader($name, $value)
    {
        $this->headers .= "{$name}: {$value}\r\n";
    }

    function writePartHeader($type, $name, $mime='application/octet-stream')
    {
        $this->output .= "--{$this->boundary}\r\n";
        switch($type)
        {
            case MIMEMAIL_HTML:
                $this->output .= "Content-type: {$name}; charset=\"iso8859-1\"\r\n";
                break;

            case MIMEMAIL_ATTACH:
                $this->output .= "Content-disposition: attachment; filename=\"{$name}\"\r\n";
                $this->output .= "Content-type: {$mime}; name=\"{$name}\"\r\n";
                $this->output .= "Content-transfer-encoding: base64\r\n";
                break;
        }

        $this->output .= "\r\n";
    }

    function endMessage($from)
    {
        if(!$this->plaintext)
        {
            $this->output .= "--{$this->boundary}--\r\n";

            $this->headers .= "MIME-Version: 1.0\r\n";
            $this->headers .= "Content-type: multipart/mixed; boundary={$this->boundary}\r\n";
            $this->headers .= "Content-length: ".strlen($this->output)."\r\n";
        }
        
        $this->headers .= "From: {$from}\r\n";
        $this->headers .= "X-Mailer: Pete's MIME-Mail\r\n\r\n";
    }

    function getContents()    { return $this->headers . $this->output; }
    function getBody()        { return $this->output; }
    function getHeaders()    { return $this->headers; }
    function getBoundary()    { return $this->boundary; }

    function setBody($b) { $this->output = $b; }
    
    function add($type, $name, $value='')
    {
        switch($type)
        {
            case MIMEMAIL_TEXT:
                $this->plaintext = (strlen($this->output))?0:1;
                $this->output = "{$name}\r\n" . $this->output;
                break;

            case MIMEMAIL_HTML:
                $this->plaintext = 0;
                $this->writePartHeader($type, "text/html");
                $this->output .= "{$name}\r\n";
                break;

            case MIMEMAIL_ATTACH:
                $this->plaintext = 0;
                if(is_file($value))
                {
                    $mime = trim(exec('file -bi '.escapeshellarg($value)));
                    if($mime) $this->writePartHeader($type, $name, $mime);
                    else $this->writePartHeader($type, $name);

                    $b64 = base64_encode(file_get_contents($value));

                    $i=0;
                    while($i < strlen($b64))
                    {
                        $this->output .= substr($b64, $i, 64);
                        $this->output .= "\r\n";
                        $i+=64;
                    }
                }
                break;
        }
    }
};

/*

USAGE 

include('mimemail.php');

$m = new MIMEMail();

// Provide the message body
$m->add(MIMEMAIL_TEXT, 'An example email message.');

// Attach file 'icons/txt.gif', and call it 'text-icon.gif' in the email
$m->add(MIMEMAIL_ATTACH, 'text-icon.gif', '/var/www/icons/txt.gif');

// Send to the author
$m->send('noreply@example.com', '"Pete Davis" <pd@pete-davis.info>', 'Test message');

*/
?>