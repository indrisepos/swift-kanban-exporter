<?php
/**
 * This class can add WSSecurity authentication support to SOAP clients
 * implemented with the PHP 5 SOAP extension.
 *
 * It extends the PHP 5 SOAP client support to add the necessary XML tags to
 * the SOAP client requests in order to authenticate on behalf of a given
 * user with a given password.
 *
 * This class was tested with Axis and WSS4J servers.
 *
 * @author Roger Veciana - http://www.phpclasses.org/browse/author/233806.html
 * @author John Kary <johnkary@gmail.com>
 * @see http://stackoverflow.com/questions/2987907/how-to-implement-ws-security-1-1-in-php5
 */
class WSSoapClient extends \SoapClient
{
    /**
     * WS-Security Username
     * @var string
     */
    private $username;

    /**
     * WS-Security Password
     * @var string
     */
    private $password;

    /**
     * Set WS-Security credentials
     *
     * @param string $username
     * @param string $password
     */
    public function __setUsernameToken($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Overwrites the original method adding the security header. As you can
     * see, if you want to add more headers, the method needs to be modified.
     */
    public function __soapCall($function_name, $arguments, $options=null, $input_headers=null, &$output_headers=null)
    {
//        var_dump($this->generateWSSecurityHeader());die;
        return parent::__soapCall($function_name, $arguments, $options, $this->generateWSSecurityHeader());
    }

    /**
     * Generate password digest
     *
     * Using the password directly may work also, but it's not secure to
     * transmit it without encryption. And anyway, at least with
     * axis+wss4j, the nonce and timestamp are mandatory anyway.
     *
     * @return string   base64 encoded password digest
     */
    /**
     * Generates WS-Security headers
     *
     * @return \SoapHeader
     */
    private function generateWSSecurityHeader()
    {
        $this->timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $xml = '
<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
    <wsse:UsernameToken>
        <wsse:Username>' . $this->username . '</wsse:Username>
        <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $this->password . '</wsse:Password>
        <wsu:Created xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">' . $this->timestamp . '</wsu:Created>
    </wsse:UsernameToken>
</wsse:Security>
';

        return new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
            'Security',
            new \SoapVar($xml, XSD_ANYXML),
            true
        );
    }
}