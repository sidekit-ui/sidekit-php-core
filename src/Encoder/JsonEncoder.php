<?php
namespace SideKit\Ui\Encoder;

use SideKit\Ui\Exception\InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SimpleXMLElement;

/**
 * Class JsonEncoder
 *
 * @author Antonio Ramirez <hola@2amigos.us>
 * @package SideKit\Ui\Encoder
 */
class JsonEncoder
{
    /**
     * List of JSON Error messages assigned to constant names for better handling of version differences
     * @var array
     */
    private $errorMessages = [
        'JSON_ERROR_DEPTH' => 'The maximum stack depth has been exceeded.',
        'JSON_ERROR_STATE_MISMATCH' => 'Invalid or malformed JSON.',
        'JSON_ERROR_CTRL_CHAR' => 'Control character error, possibly incorrectly encoded.',
        'JSON_ERROR_SYNTAX' => 'Syntax error.',
        'JSON_ERROR_UTF8' => 'Malformed UTF-8 characters, possibly incorrectly encoded.', // PHP 5.3.3
        'JSON_ERROR_RECURSION' => 'One or more recursive references in the value to be encoded.', // PHP 5.5.0
        'JSON_ERROR_INF_OR_NAN' => 'One or more NAN or INF values in the value to be encoded', // PHP 5.5.0
        'JSON_ERROR_UNSUPPORTED_TYPE' => 'A value of a type that cannot be encoded was given', // PHP 5.5.0
    ];

    /**
     * Encodes the given value into a JSON string.
     *
     * @param mixed $value the data to be encoded.
     * @param integer $options the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>. Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     *
     * @return string the encoding result.
     * @throws InvalidArgumentException if there is any encoding error.
     */
    public function encode($value, $options = 320)
    {
        $value = $this->processData($value);
        set_error_handler(
            function () {
                $this->handleJsonError(JSON_ERROR_SYNTAX);
            },
            E_WARNING
        );
        $json = json_encode($value, $options);
        restore_error_handler();
        $this->handleJsonError(json_last_error());

        return $json;
    }

    /**
     * Decodes the given JSON string into a PHP data structure.
     *
     * @param string $json the JSON string to be decoded
     * @param boolean $asArray whether to return objects in terms of associative arrays.
     *
     * @return mixed the PHP data
     * @throws InvalidArgumentException if there is any decoding error
     */
    public function decode($json, $asArray = true)
    {
        if (is_array($json)) {
            throw new InvalidArgumentException('Invalid JSON data.');
        } elseif ($json === null || $json === '') {
            return null;
        }
        $decode = json_decode((string)$json, $asArray);
        $this->handleJsonError(json_last_error());

        return $decode;
    }

    /**
     * Encodes the given value into a JSON string HTML-escaping entities so it is safe to be embedded in HTML code.
     *
     * @param mixed $value the data to be encoded
     *
     * @return string the encoding result
     * @throws InvalidArgumentException if there is any encoding error
     */
    public function htmlEncode($value)
    {
        return $this->encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );
    }


    /**
     * Handles [[encode()]] and [[decode()]] errors by throwing exceptions with the respective error message.
     *
     * @param integer $lastError error code from [json_last_error()](http://php.net/manual/en/function.json-last-error.php).
     *
     * @throws InvalidArgumentException if there is any encoding/decoding error.
     */
    protected function handleJsonError($lastError)
    {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }

        $availableErrors = [];
        foreach ($this->errorMessages as $const => $message) {
            if (defined($const)) {
                $availableErrors[constant($const)] = $message;
            }
        }

        if (isset($availableErrors[$lastError])) {
            throw new InvalidArgumentException($availableErrors[$lastError], $lastError);
        }

        throw new InvalidArgumentException('Unknown JSON encoding/decoding error.');
    }

    /**
     * Pre-processes the data before sending it to `json_encode()`.
     *
     * @param mixed $data the data to be processed
     *
     * @return mixed the processed data
     */
    protected function processData($data)
    {
        if (is_object($data)) {
            if ($data instanceof JsonSerializable) {
                $data = $data->jsonSerialize();
            } elseif ($data instanceof SimpleXMLElement) {
                $data = (array)$data;
            } else {
                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;
            }

            if ($data === []) {
                return new stdClass();
            }
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = $this->processData($value);
                }
            }
        }

        return $data;
    }
}