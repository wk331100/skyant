<?php
namespace App\Libs;

class Aes
{
    private $_cipher;// 加密方式
    private $_key = 'OrE67Dgh8bJ36mmNyMmKIgEWB';// 密钥
    private $_options = 0;// options 是以下标记的按位或： OPENSSL_RAW_DATA 、 OPENSSL_ZERO_PADDING
    private $_iv = '2t}~*e[<AcZqzOr*';// 非null的初始化向量

    /**
     * 初始化加密参数
     * AES constructor.
     * @param string $key
     * @param string $cipher
     * @param int $options
     * @param string $iv
     */
    public function __construct( string $key = '', string $cipher = 'AES-256-CBC', int $options = 0, string $iv = '')
    {
        $this->_cipher = $cipher;
        $this->_options = $options;
        if(empty($key)){
            $key = $this->_key;
        }
        $this->_key = $key . '_skyant';
    }

    public function encrypt($plaintext)
    {
        $ciphertext = openssl_encrypt($plaintext, $this->_cipher, $this->_key, $this->_options, $this->_iv);
        return $ciphertext;
    }

    public function decrypt($ciphertext)
    {
        $original_plaintext = openssl_decrypt($ciphertext, $this->_cipher, $this->_key, $this->_options, $this->_iv);
        return $original_plaintext;
    }
}