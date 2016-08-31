<?php
namespace SideKit\Util;
/**
 * Class Uuid
 *
 * @author Antonio Ramirez <hola@2amigos.us>
 * @package SideKit\Util
 */
class Uuid
{
    /** @var callable */
    private $generator;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->generator = PHP_MAJOR_VERSION === 7
            ? 'random_bytes'
            : 'openssl_random_pseudo_bytes';
    }

    /**
     * @return string
     */
    public function generate()
    {
        $data = call_user_func($this->generator, 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
