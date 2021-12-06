<?php
namespace App\Classes\payment\pasargad;
use App\Classes\payment\pasargad\RSA;
class RSAProcessor {
    private $public_key = null;
    private $private_key = null;
    private $modulus = null;
    private $key_length = "1024";
    private $RSA;
    private $privateKey = '<RSAKeyValue><Modulus>u9QiaSVNXNcO2Ist3TJOPLKhNsV84FVNByhctGGWDpUhwrTx7x1gc+HM8PCnDTXHpmPzo08yQNqt0gjFPHjo1zitu4DIWilOmYeMNXRYYpn5mmZiBlCIkw7z7mc0kPoVLAdExk6FU0R8q8cy5xREbJpEfvLm+aj5Y+BtfPLokm0=</Modulus><Exponent>AQAB</Exponent><P>6iKdX28GXw5KjbthcCwnX9+NvFJuZ1Nn4uLUEAw0j/3eM3Zp5Fg97FJILBoXlU8pPQKj8h+YESD6UBp1eqHDQw==</P><Q>zV58S6HN/IVgDFxG72o13a57gpTBOV+KEFF88R8s5+mhDyLzD4s8Vf/IJfV3xcJeOckKMleMAYE9JlKYnTmAjw==</Q><DP>xZsRV0pNBkz5f0V2p0Wctb3n0dmAdJRgSY1HjYO/mQeaUbTPCnmvSZTodNBQtyNomqVv2RnxLgO3P4QVQrrkIQ==</DP><DQ>eZHEDFV1BVXCvK5nQ1RhHKAr9umt1BOtO+mxB19ICuSu9bHfpkTq65GlXmsHgqaDdrt+cLyIYV+q3iOoufGPGw==</DQ><InverseQ>ElTK3vHaTTYISddW9YQPOZlEWB7A/Xn3oV+y5SDPg3vAOegmhNGrE9qekJB1XaIgqCLTU6A71NXLhDOBrDHNcw==</InverseQ><D>jEsT9MN2+Gxt21KBzGFBzNaD0fxKnOk54qnELLtjMLs1f1BWEQs5OvUidajareRInsCzf3ytBYIRKPuCDvwktSyJ4MtYC+oxwTq9vo8NqKFyevYpK2gkwfSO+Ar5u3GZmh1ABy46C3QxzPH+lwxutnX7TMOVBs0HidYXQrX9R4U=</D></RSAKeyValue>';

    public function __construct($xmlRsakey = null, $type = null)
    {
        $this->RSA = new RSA();

        $xmlObj = null;
        if ($xmlRsakey == null) {
            $xmlObj = simplexml_load_file("xmlfile/RSAKey.xml");
        } elseif ($type == RSAKeyType::XMLFile) {
            $xmlObj = simplexml_load_file($xmlRsakey);
        } else {
            $xmlObj = simplexml_load_string($xmlRsakey);
        }

        $this->modulus = $this->RSA->binary_to_number(base64_decode($xmlObj->Modulus));
        $this->public_key = $this->RSA->binary_to_number(base64_decode($xmlObj->Exponent));
        $this->private_key = $this->RSA->binary_to_number(base64_decode($xmlObj->D));
        $this->key_length = strlen(base64_decode($xmlObj->Modulus)) * 8;
    }

    public function encrypt($data)
    {
        return base64_encode($this->RSA->rsa_encrypt($data, $this->public_key, $this->modulus, $this->key_length));
    }

    public function dencrypt($data)
    {
        return $this->RSA->rsa_decrypt($data, $this->private_key, $this->modulus, $this->key_length);
    }

    public function sign($data)
    {
        return $this->RSA->rsa_sign($data, $this->private_key, $this->modulus, $this->key_length);
    }

    public function verify($data)
    {
        return $this->RSA->rsa_verify($data, $this->public_key, $this->modulus, $this->key_length);
    }
}

class RSAKeyType
{
    const XMLFile = 0;
    const XMLString = 1;
}