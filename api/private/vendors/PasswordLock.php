<?php

// modified by pizzaboxer
// im lazy and just threw all the required classes and stuff into this one file lol

declare(strict_types=1);

namespace Defuse\Crypto
{
    use Defuse\Crypto\Exception as Ex;

    final class Key
    {
        const KEY_CURRENT_VERSION = "\xDE\xF0\x00\x00";
        const KEY_BYTE_SIZE       = 32;

        private $key_bytes;

        public static function createNewRandomKey()
        {
            return new Key(Core::secureRandom(self::KEY_BYTE_SIZE));
        }

        public static function loadFromAsciiSafeString($saved_key_string, $do_not_trim = false)
        {
            if (!$do_not_trim) {
                $saved_key_string = Encoding::trimTrailingWhitespace($saved_key_string);
            }
            $key_bytes = Encoding::loadBytesFromChecksummedAsciiSafeString(self::KEY_CURRENT_VERSION, $saved_key_string);
            return new Key($key_bytes);
        }

        public function saveToAsciiSafeString()
        {
            return Encoding::saveBytesToChecksummedAsciiSafeString(
                self::KEY_CURRENT_VERSION,
                $this->key_bytes
            );
        }

        public function getRawBytes()
        {
            return $this->key_bytes;
        }

        private function __construct($bytes)
        {
            Core::ensureTrue(
                Core::ourStrlen($bytes) === self::KEY_BYTE_SIZE,
                'Bad key length.'
            );
            $this->key_bytes = $bytes;
        }

    }

    final class KeyOrPassword
    {
        const PBKDF2_ITERATIONS    = 100000;
        const SECRET_TYPE_KEY      = 1;
        const SECRET_TYPE_PASSWORD = 2;

        private $secret_type = 0;

        private $secret;

        public static function createFromKey(Key $key)
        {
            return new KeyOrPassword(self::SECRET_TYPE_KEY, $key);
        }

        public static function createFromPassword($password)
        {
            return new KeyOrPassword(self::SECRET_TYPE_PASSWORD, $password);
        }

        public function deriveKeys($salt)
        {
            Core::ensureTrue(
                Core::ourStrlen($salt) === Core::SALT_BYTE_SIZE,
                'Bad salt.'
            );

            if ($this->secret_type === self::SECRET_TYPE_KEY) {
                Core::ensureTrue($this->secret instanceof Key);
                /**
                 * @psalm-suppress PossiblyInvalidMethodCall
                 */
                $akey = Core::HKDF(
                    Core::HASH_FUNCTION_NAME,
                    $this->secret->getRawBytes(),
                    Core::KEY_BYTE_SIZE,
                    Core::AUTHENTICATION_INFO_STRING,
                    $salt
                );
                /**
                 * @psalm-suppress PossiblyInvalidMethodCall
                 */
                $ekey = Core::HKDF(
                    Core::HASH_FUNCTION_NAME,
                    $this->secret->getRawBytes(),
                    Core::KEY_BYTE_SIZE,
                    Core::ENCRYPTION_INFO_STRING,
                    $salt
                );
                return new DerivedKeys($akey, $ekey);
            } elseif ($this->secret_type === self::SECRET_TYPE_PASSWORD) {
                Core::ensureTrue(\is_string($this->secret));
                /* Our PBKDF2 polyfill is vulnerable to a DoS attack documented in
                 * GitHub issue #230. The fix is to pre-hash the password to ensure
                 * it is short. We do the prehashing here instead of in pbkdf2() so
                 * that pbkdf2() still computes the function as defined by the
                 * standard. */

                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                $prehash = \hash(Core::HASH_FUNCTION_NAME, $this->secret, true);

                $prekey = Core::pbkdf2(
                    Core::HASH_FUNCTION_NAME,
                    $prehash,
                    $salt,
                    self::PBKDF2_ITERATIONS,
                    Core::KEY_BYTE_SIZE,
                    true
                );
                $akey = Core::HKDF(
                    Core::HASH_FUNCTION_NAME,
                    $prekey,
                    Core::KEY_BYTE_SIZE,
                    Core::AUTHENTICATION_INFO_STRING,
                    $salt
                );
                /* Note the cryptographic re-use of $salt here. */
                $ekey = Core::HKDF(
                    Core::HASH_FUNCTION_NAME,
                    $prekey,
                    Core::KEY_BYTE_SIZE,
                    Core::ENCRYPTION_INFO_STRING,
                    $salt
                );
                return new DerivedKeys($akey, $ekey);
            } else {
                throw new Ex\EnvironmentIsBrokenException('Bad secret type.');
            }
        }

        private function __construct($secret_type, $secret)
        {
            // The constructor is private, so these should never throw.
            if ($secret_type === self::SECRET_TYPE_KEY) {
                Core::ensureTrue($secret instanceof Key);
            } elseif ($secret_type === self::SECRET_TYPE_PASSWORD) {
                Core::ensureTrue(\is_string($secret));
            } else {
                throw new Ex\EnvironmentIsBrokenException('Bad secret type.');
            }
            $this->secret_type = $secret_type;
            $this->secret = $secret;
        }
    }

    final class Core
    {
        const HEADER_VERSION_SIZE               = 4;
        const MINIMUM_CIPHERTEXT_SIZE           = 84;

        const CURRENT_VERSION                   = "\xDE\xF5\x02\x00";

        const CIPHER_METHOD                     = 'aes-256-ctr';
        const BLOCK_BYTE_SIZE                   = 16;
        const KEY_BYTE_SIZE                     = 32;
        const SALT_BYTE_SIZE                    = 32;
        const MAC_BYTE_SIZE                     = 32;
        const HASH_FUNCTION_NAME                = 'sha256';
        const ENCRYPTION_INFO_STRING            = 'DefusePHP|V2|KeyForEncryption';
        const AUTHENTICATION_INFO_STRING        = 'DefusePHP|V2|KeyForAuthentication';
        const BUFFER_BYTE_SIZE                  = 1048576;

        const LEGACY_CIPHER_METHOD              = 'aes-128-cbc';
        const LEGACY_BLOCK_BYTE_SIZE            = 16;
        const LEGACY_KEY_BYTE_SIZE              = 16;
        const LEGACY_HASH_FUNCTION_NAME         = 'sha256';
        const LEGACY_MAC_BYTE_SIZE              = 32;
        const LEGACY_ENCRYPTION_INFO_STRING     = 'DefusePHP|KeyForEncryption';
        const LEGACY_AUTHENTICATION_INFO_STRING = 'DefusePHP|KeyForAuthentication';

        public static function incrementCounter($ctr, $inc)
        {
            Core::ensureTrue(
                Core::ourStrlen($ctr) === Core::BLOCK_BYTE_SIZE,
                'Trying to increment a nonce of the wrong size.'
            );

            Core::ensureTrue(
                \is_int($inc),
                'Trying to increment nonce by a non-integer.'
            );

            // The caller is probably re-using CTR-mode keystream if they increment by 0.
            Core::ensureTrue(
                $inc > 0,
                'Trying to increment a nonce by a nonpositive amount'
            );

            Core::ensureTrue(
                $inc <= PHP_INT_MAX - 255,
                'Integer overflow may occur'
            );

            /*
             * We start at the rightmost byte (big-endian)
             * So, too, does OpenSSL: http://stackoverflow.com/a/3146214/2224584
             */
            for ($i = Core::BLOCK_BYTE_SIZE - 1; $i >= 0; --$i) {
                $sum = \ord($ctr[$i]) + $inc;

                /* Detect integer overflow and fail. */
                Core::ensureTrue(\is_int($sum), 'Integer overflow in CTR mode nonce increment');

                $ctr[$i] = \pack('C', $sum & 0xFF);
                $inc     = $sum >> 8;
            }
            return $ctr;
        }

        public static function secureRandom($octets)
        {
            self::ensureFunctionExists('random_bytes');
            try {
                return \random_bytes($octets);
            } catch (\Exception $ex) {
                throw new Ex\EnvironmentIsBrokenException(
                    'Your system does not have a secure random number generator.'
                );
            }
        }

        public static function HKDF($hash, $ikm, $length, $info = '', $salt = null)
        {
            static $nativeHKDF = null;
            if ($nativeHKDF === null) {
                $nativeHKDF = \is_callable('\\hash_hkdf');
            }
            if ($nativeHKDF) {
                if (\is_null($salt)) {
                    $salt = '';
                }
                return \hash_hkdf($hash, $ikm, $length, $info, $salt);
            }

            $digest_length = Core::ourStrlen(\hash_hmac($hash, '', '', true));

            // Sanity-check the desired output length.
            Core::ensureTrue(
                !empty($length) && \is_int($length) && $length >= 0 && $length <= 255 * $digest_length,
                'Bad output length requested of HDKF.'
            );

            // "if [salt] not provided, is set to a string of HashLen zeroes."
            if (\is_null($salt)) {
                $salt = \str_repeat("\x00", $digest_length);
            }

            // HKDF-Extract:
            // PRK = HMAC-Hash(salt, IKM)
            // The salt is the HMAC key.
            $prk = \hash_hmac($hash, $ikm, $salt, true);

            // HKDF-Expand:

            // This check is useless, but it serves as a reminder to the spec.
            Core::ensureTrue(Core::ourStrlen($prk) >= $digest_length);

            // T(0) = ''
            $t          = '';
            $last_block = '';
            for ($block_index = 1; Core::ourStrlen($t) < $length; ++$block_index) {
                // T(i) = HMAC-Hash(PRK, T(i-1) | info | 0x??)
                $last_block = \hash_hmac(
                    $hash,
                    $last_block . $info . \chr($block_index),
                    $prk,
                    true
                );
                // T = T(1) | T(2) | T(3) | ... | T(N)
                $t .= $last_block;
            }

            // ORM = first L octets of T
            /** @var string $orm */
            $orm = Core::ourSubstr($t, 0, $length);
            Core::ensureTrue(\is_string($orm));
            return $orm;
        }

        public static function hashEquals($expected, $given)
        {
            static $native = null;
            if ($native === null) {
                $native = \function_exists('hash_equals');
            }
            if ($native) {
                return \hash_equals($expected, $given);
            }

            // We can't just compare the strings with '==', since it would make
            // timing attacks possible. We could use the XOR-OR constant-time
            // comparison algorithm, but that may not be a reliable defense in an
            // interpreted language. So we use the approach of HMACing both strings
            // with a random key and comparing the HMACs.

            // We're not attempting to make variable-length string comparison
            // secure, as that's very difficult. Make sure the strings are the same
            // length.
            Core::ensureTrue(Core::ourStrlen($expected) === Core::ourStrlen($given));

            $blind           = Core::secureRandom(32);
            $message_compare = \hash_hmac(Core::HASH_FUNCTION_NAME, $given, $blind);
            $correct_compare = \hash_hmac(Core::HASH_FUNCTION_NAME, $expected, $blind);
            return $correct_compare === $message_compare;
        }

        public static function ensureConstantExists($name)
        {
            Core::ensureTrue(\defined($name));
        }

        public static function ensureFunctionExists($name)
        {
            Core::ensureTrue(\function_exists($name));
        }

        public static function ensureTrue($condition, $message = '')
        {
            if (!$condition) {
                throw new Ex\EnvironmentIsBrokenException($message);
            }
        }

        /*
         * We need these strlen() and substr() functions because when
         * 'mbstring.func_overload' is set in php.ini, the standard strlen() and
         * substr() are replaced by mb_strlen() and mb_substr().
         */

        public static function ourStrlen($str)
        {
            static $exists = null;
            if ($exists === null) {
                $exists = \extension_loaded('mbstring') && \ini_get('mbstring.func_overload') !== false && (int)\ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING;
            }
            if ($exists) {
                $length = \mb_strlen($str, '8bit');
                Core::ensureTrue($length !== false);
                return $length;
            } else {
                return \strlen($str);
            }
        }

        public static function ourSubstr($str, $start, $length = null)
        {
            static $exists = null;
            if ($exists === null) {
                $exists = \extension_loaded('mbstring') && \ini_get('mbstring.func_overload') !== false && (int)\ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING;
            }

            // This is required to make mb_substr behavior identical to substr.
            // Without this, mb_substr() would return false, contra to what the
            // PHP documentation says (it doesn't say it can return false.)
            $input_len = Core::ourStrlen($str);
            if ($start === $input_len && !$length) {
                return '';
            }

            if ($start > $input_len) {
                return false;
            }

            // mb_substr($str, 0, NULL, '8bit') returns an empty string on PHP 5.3,
            // so we have to find the length ourselves. Also, substr() doesn't
            // accept null for the length.
            if (! isset($length)) {
                if ($start >= 0) {
                    $length = $input_len - $start;
                } else {
                    $length = -$start;
                }
            }

            if ($length < 0) {
                throw new \InvalidArgumentException(
                    "Negative lengths are not supported with ourSubstr."
                );
            }

            if ($exists) {
                $substr = \mb_substr($str, $start, $length, '8bit');
                // At this point there are two cases where mb_substr can
                // legitimately return an empty string. Either $length is 0, or
                // $start is equal to the length of the string (both mb_substr and
                // substr return an empty string when this happens). It should never
                // ever return a string that's longer than $length.
                if (Core::ourStrlen($substr) > $length || (Core::ourStrlen($substr) === 0 && $length !== 0 && $start !== $input_len)) {
                    throw new Ex\EnvironmentIsBrokenException(
                        'Your version of PHP has bug #66797. Its implementation of
                        mb_substr() is incorrect. See the details here:
                        https://bugs.php.net/bug.php?id=66797'
                    );
                }
                return $substr;
            }

            return \substr($str, $start, $length);
        }

        public static function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
        {
            // Type checks:
            if (! \is_string($algorithm)) {
                throw new \InvalidArgumentException(
                    'pbkdf2(): algorithm must be a string'
                );
            }
            if (! \is_string($password)) {
                throw new \InvalidArgumentException(
                    'pbkdf2(): password must be a string'
                );
            }
            if (! \is_string($salt)) {
                throw new \InvalidArgumentException(
                    'pbkdf2(): salt must be a string'
                );
            }
            // Coerce strings to integers with no information loss or overflow
            $count += 0;
            $key_length += 0;

            $algorithm = \strtolower($algorithm);
            Core::ensureTrue(
                \in_array($algorithm, \hash_algos(), true),
                'Invalid or unsupported hash algorithm.'
            );

            // Whitelist, or we could end up with people using CRC32.
            $ok_algorithms = [
                'sha1', 'sha224', 'sha256', 'sha384', 'sha512',
                'ripemd160', 'ripemd256', 'ripemd320', 'whirlpool',
            ];
            Core::ensureTrue(
                \in_array($algorithm, $ok_algorithms, true),
                'Algorithm is not a secure cryptographic hash function.'
            );

            Core::ensureTrue($count > 0 && $key_length > 0, 'Invalid PBKDF2 parameters.');

            if (\function_exists('hash_pbkdf2')) {
                // The output length is in NIBBLES (4-bits) if $raw_output is false!
                if (! $raw_output) {
                    $key_length = $key_length * 2;
                }
                return \hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
            }

            $hash_length = Core::ourStrlen(\hash($algorithm, '', true));
            $block_count = \ceil($key_length / $hash_length);

            $output = '';
            for ($i = 1; $i <= $block_count; $i++) {
                // $i encoded as 4 bytes, big endian.
                $last = $salt . \pack('N', $i);
                // first iteration
                $last = $xorsum = \hash_hmac($algorithm, $last, $password, true);
                // perform the other $count - 1 iterations
                for ($j = 1; $j < $count; $j++) {
                    $xorsum ^= ($last = \hash_hmac($algorithm, $last, $password, true));
                }
                $output .= $xorsum;
            }

            if ($raw_output) {
                return (string) Core::ourSubstr($output, 0, $key_length);
            } else {
                return Encoding::binToHex((string) Core::ourSubstr($output, 0, $key_length));
            }
        }
    }

    final class Encoding
    {
        const CHECKSUM_BYTE_SIZE     = 32;
        const CHECKSUM_HASH_ALGO     = 'sha256';
        const SERIALIZE_HEADER_BYTES = 4;

        public static function binToHex($byte_string)
        {
            $hex = '';
            $len = Core::ourStrlen($byte_string);
            for ($i = 0; $i < $len; ++$i) {
                $c = \ord($byte_string[$i]) & 0xf;
                $b = \ord($byte_string[$i]) >> 4;
                $hex .= \pack(
                    'CC',
                    87 + $b + ((($b - 10) >> 8) & ~38),
                    87 + $c + ((($c - 10) >> 8) & ~38)
                );
            }
            return $hex;
        }

        public static function hexToBin($hex_string)
        {
            $hex_pos = 0;
            $bin     = '';
            $hex_len = Core::ourStrlen($hex_string);
            $state   = 0;
            $c_acc   = 0;

            while ($hex_pos < $hex_len) {
                $c        = \ord($hex_string[$hex_pos]);
                $c_num    = $c ^ 48;
                $c_num0   = ($c_num - 10) >> 8;
                $c_alpha  = ($c & ~32) - 55;
                $c_alpha0 = (($c_alpha - 10) ^ ($c_alpha - 16)) >> 8;
                if (($c_num0 | $c_alpha0) === 0) {
                    throw new Ex\BadFormatException(
                        'Encoding::hexToBin() input is not a hex string.'
                    );
                }
                $c_val = ($c_num0 & $c_num) | ($c_alpha & $c_alpha0);
                if ($state === 0) {
                    $c_acc = $c_val * 16;
                } else {
                    $bin .= \pack('C', $c_acc | $c_val);
                }
                $state ^= 1;
                ++$hex_pos;
            }
            return $bin;
        }

        public static function trimTrailingWhitespace($string = '')
        {
            $length = Core::ourStrlen($string);
            if ($length < 1) {
                return '';
            }
            do {
                $prevLength = $length;
                $last = $length - 1;
                $chr = \ord($string[$last]);

                /* Null Byte (0x00), a.k.a. \0 */
                // if ($chr === 0x00) $length -= 1;
                $sub = (($chr - 1) >> 8 ) & 1;
                $length -= $sub;
                $last -= $sub;

                /* Horizontal Tab (0x09) a.k.a. \t */
                $chr = \ord($string[$last]);
                // if ($chr === 0x09) $length -= 1;
                $sub = (((0x08 - $chr) & ($chr - 0x0a)) >> 8) & 1;
                $length -= $sub;
                $last -= $sub;

                /* New Line (0x0a), a.k.a. \n */
                $chr = \ord($string[$last]);
                // if ($chr === 0x0a) $length -= 1;
                $sub = (((0x09 - $chr) & ($chr - 0x0b)) >> 8) & 1;
                $length -= $sub;
                $last -= $sub;

                /* Carriage Return (0x0D), a.k.a. \r */
                $chr = \ord($string[$last]);
                // if ($chr === 0x0d) $length -= 1;
                $sub = (((0x0c - $chr) & ($chr - 0x0e)) >> 8) & 1;
                $length -= $sub;
                $last -= $sub;

                /* Space */
                $chr = \ord($string[$last]);
                // if ($chr === 0x20) $length -= 1;
                $sub = (((0x1f - $chr) & ($chr - 0x21)) >> 8) & 1;
                $length -= $sub;
            } while ($prevLength !== $length && $length > 0);
            return (string) Core::ourSubstr($string, 0, $length);
        }

        /*
         * SECURITY NOTE ON APPLYING CHECKSUMS TO SECRETS:
         *
         *      The checksum introduces a potential security weakness. For example,
         *      suppose we apply a checksum to a key, and that an adversary has an
         *      exploit against the process containing the key, such that they can
         *      overwrite an arbitrary byte of memory and then cause the checksum to
         *      be verified and learn the result.
         *
         *      In this scenario, the adversary can extract the key one byte at
         *      a time by overwriting it with their guess of its value and then
         *      asking if the checksum matches. If it does, their guess was right.
         *      This kind of attack may be more easy to implement and more reliable
         *      than a remote code execution attack.
         *
         *      This attack also applies to authenticated encryption as a whole, in
         *      the situation where the adversary can overwrite a byte of the key
         *      and then cause a valid ciphertext to be decrypted, and then
         *      determine whether the MAC check passed or failed.
         *
         *      By using the full SHA256 hash instead of truncating it, I'm ensuring
         *      that both ways of going about the attack are equivalently difficult.
         *      A shorter checksum of say 32 bits might be more useful to the
         *      adversary as an oracle in case their writes are coarser grained.
         *
         *      Because the scenario assumes a serious vulnerability, we don't try
         *      to prevent attacks of this style.
         */

        public static function saveBytesToChecksummedAsciiSafeString($header, $bytes)
        {
            // Headers must be a constant length to prevent one type's header from
            // being a prefix of another type's header, leading to ambiguity.
            Core::ensureTrue(
                Core::ourStrlen($header) === self::SERIALIZE_HEADER_BYTES,
                'Header must be ' . self::SERIALIZE_HEADER_BYTES . ' bytes.'
            );

            return Encoding::binToHex(
                $header .
                $bytes .
                \hash(
                    self::CHECKSUM_HASH_ALGO,
                    $header . $bytes,
                    true
                )
            );
        }

        public static function loadBytesFromChecksummedAsciiSafeString($expected_header, $string)
        {
            // Headers must be a constant length to prevent one type's header from
            // being a prefix of another type's header, leading to ambiguity.
            Core::ensureTrue(
                Core::ourStrlen($expected_header) === self::SERIALIZE_HEADER_BYTES,
                'Header must be 4 bytes.'
            );

            /* If you get an exception here when attempting to load from a file, first pass your
               key to Encoding::trimTrailingWhitespace() to remove newline characters, etc.      */
            $bytes = Encoding::hexToBin($string);

            /* Make sure we have enough bytes to get the version header and checksum. */
            if (Core::ourStrlen($bytes) < self::SERIALIZE_HEADER_BYTES + self::CHECKSUM_BYTE_SIZE) {
                throw new Ex\BadFormatException(
                    'Encoded data is shorter than expected.'
                );
            }

            /* Grab the version header. */
            $actual_header = (string) Core::ourSubstr($bytes, 0, self::SERIALIZE_HEADER_BYTES);

            if ($actual_header !== $expected_header) {
                throw new Ex\BadFormatException(
                    'Invalid header.'
                );
            }

            /* Grab the bytes that are part of the checksum. */
            $checked_bytes = (string) Core::ourSubstr(
                $bytes,
                0,
                Core::ourStrlen($bytes) - self::CHECKSUM_BYTE_SIZE
            );

            /* Grab the included checksum. */
            $checksum_a = (string) Core::ourSubstr(
                $bytes,
                Core::ourStrlen($bytes) - self::CHECKSUM_BYTE_SIZE,
                self::CHECKSUM_BYTE_SIZE
            );

            /* Re-compute the checksum. */
            $checksum_b = \hash(self::CHECKSUM_HASH_ALGO, $checked_bytes, true);

            /* Check if the checksum matches. */
            if (! Core::hashEquals($checksum_a, $checksum_b)) {
                throw new Ex\BadFormatException(
                    "Data is corrupted, the checksum doesn't match"
                );
            }

            return (string) Core::ourSubstr(
                $bytes,
                self::SERIALIZE_HEADER_BYTES,
                Core::ourStrlen($bytes) - self::SERIALIZE_HEADER_BYTES - self::CHECKSUM_BYTE_SIZE
            );
        }
    }

    final class DerivedKeys
    {
        private $akey = '';

        private $ekey = '';

        public function getAuthenticationKey()
        {
            return $this->akey;
        }

        public function getEncryptionKey()
        {
            return $this->ekey;
        }

        public function __construct($akey, $ekey)
        {
            $this->akey = $akey;
            $this->ekey = $ekey;
        }
    }

    class Crypto
    {
        public static function encrypt($plaintext, $key, $raw_binary = false)
        {
            if (!\is_string($plaintext)) {
                throw new \TypeError(
                    'String expected for argument 1. ' . \ucfirst(\gettype($plaintext)) . ' given instead.'
                );
            }
            if (!($key instanceof Key)) {
                throw new \TypeError(
                    'Key expected for argument 2. ' . \ucfirst(\gettype($key)) . ' given instead.'
                );
            }
            if (!\is_bool($raw_binary)) {
                throw new \TypeError(
                    'Boolean expected for argument 3. ' . \ucfirst(\gettype($raw_binary)) . ' given instead.'
                );
            }
            return self::encryptInternal(
                $plaintext,
                KeyOrPassword::createFromKey($key),
                $raw_binary
            );
        }

        public static function encryptWithPassword($plaintext, $password, $raw_binary = false)
        {
            if (!\is_string($plaintext)) {
                throw new \TypeError(
                    'String expected for argument 1. ' . \ucfirst(\gettype($plaintext)) . ' given instead.'
                );
            }
            if (!\is_string($password)) {
                throw new \TypeError(
                    'String expected for argument 2. ' . \ucfirst(\gettype($password)) . ' given instead.'
                );
            }
            if (!\is_bool($raw_binary)) {
                throw new \TypeError(
                    'Boolean expected for argument 3. ' . \ucfirst(\gettype($raw_binary)) . ' given instead.'
                );
            }
            return self::encryptInternal(
                $plaintext,
                KeyOrPassword::createFromPassword($password),
                $raw_binary
            );
        }

        public static function decrypt($ciphertext, $key, $raw_binary = false)
        {
            if (!\is_string($ciphertext)) {
                throw new \TypeError(
                    'String expected for argument 1. ' . \ucfirst(\gettype($ciphertext)) . ' given instead.'
                );
            }
            if (!($key instanceof Key)) {
                throw new \TypeError(
                    'Key expected for argument 2. ' . \ucfirst(\gettype($key)) . ' given instead.'
                );
            }
            if (!\is_bool($raw_binary)) {
                throw new \TypeError(
                    'Boolean expected for argument 3. ' . \ucfirst(\gettype($raw_binary)) . ' given instead.'
                );
            }
            return self::decryptInternal(
                $ciphertext,
                KeyOrPassword::createFromKey($key),
                $raw_binary
            );
        }

        public static function decryptWithPassword($ciphertext, $password, $raw_binary = false)
        {
            if (!\is_string($ciphertext)) {
                throw new \TypeError(
                    'String expected for argument 1. ' . \ucfirst(\gettype($ciphertext)) . ' given instead.'
                );
            }
            if (!\is_string($password)) {
                throw new \TypeError(
                    'String expected for argument 2. ' . \ucfirst(\gettype($password)) . ' given instead.'
                );
            }
            if (!\is_bool($raw_binary)) {
                throw new \TypeError(
                    'Boolean expected for argument 3. ' . \ucfirst(\gettype($raw_binary)) . ' given instead.'
                );
            }
            return self::decryptInternal(
                $ciphertext,
                KeyOrPassword::createFromPassword($password),
                $raw_binary
            );
        }

        private static function encryptInternal($plaintext, KeyOrPassword $secret, $raw_binary)
        {
            RuntimeTests::runtimeTest();

            $salt = Core::secureRandom(Core::SALT_BYTE_SIZE);
            $keys = $secret->deriveKeys($salt);
            $ekey = $keys->getEncryptionKey();
            $akey = $keys->getAuthenticationKey();
            $iv     = Core::secureRandom(Core::BLOCK_BYTE_SIZE);

            $ciphertext = Core::CURRENT_VERSION . $salt . $iv . self::plainEncrypt($plaintext, $ekey, $iv);
            $auth       = \hash_hmac(Core::HASH_FUNCTION_NAME, $ciphertext, $akey, true);
            $ciphertext = $ciphertext . $auth;

            if ($raw_binary) {
                return $ciphertext;
            }
            return Encoding::binToHex($ciphertext);
        }

        private static function decryptInternal($ciphertext, KeyOrPassword $secret, $raw_binary)
        {
            RuntimeTests::runtimeTest();

            if (! $raw_binary) {
                try {
                    $ciphertext = Encoding::hexToBin($ciphertext);
                } catch (Ex\BadFormatException $ex) {
                    throw new Ex\WrongKeyOrModifiedCiphertextException(
                        'Ciphertext has invalid hex encoding.'
                    );
                }
            }

            if (Core::ourStrlen($ciphertext) < Core::MINIMUM_CIPHERTEXT_SIZE) {
                throw new Ex\WrongKeyOrModifiedCiphertextException(
                    'Ciphertext is too short.'
                );
            }

            // Get and check the version header.
            /** @var string $header */
            $header = Core::ourSubstr($ciphertext, 0, Core::HEADER_VERSION_SIZE);
            if ($header !== Core::CURRENT_VERSION) {
                throw new Ex\WrongKeyOrModifiedCiphertextException(
                    'Bad version header.'
                );
            }

            // Get the salt.
            /** @var string $salt */
            $salt = Core::ourSubstr(
                $ciphertext,
                Core::HEADER_VERSION_SIZE,
                Core::SALT_BYTE_SIZE
            );
            Core::ensureTrue(\is_string($salt));

            // Get the IV.
            /** @var string $iv */
            $iv = Core::ourSubstr(
                $ciphertext,
                Core::HEADER_VERSION_SIZE + Core::SALT_BYTE_SIZE,
                Core::BLOCK_BYTE_SIZE
            );
            Core::ensureTrue(\is_string($iv));

            // Get the HMAC.
            /** @var string $hmac */
            $hmac = Core::ourSubstr(
                $ciphertext,
                Core::ourStrlen($ciphertext) - Core::MAC_BYTE_SIZE,
                Core::MAC_BYTE_SIZE
            );
            Core::ensureTrue(\is_string($hmac));

            // Get the actual encrypted ciphertext.
            /** @var string $encrypted */
            $encrypted = Core::ourSubstr(
                $ciphertext,
                Core::HEADER_VERSION_SIZE + Core::SALT_BYTE_SIZE +
                    Core::BLOCK_BYTE_SIZE,
                Core::ourStrlen($ciphertext) - Core::MAC_BYTE_SIZE - Core::SALT_BYTE_SIZE -
                    Core::BLOCK_BYTE_SIZE - Core::HEADER_VERSION_SIZE
            );
            Core::ensureTrue(\is_string($encrypted));

            // Derive the separate encryption and authentication keys from the key
            // or password, whichever it is.
            $keys = $secret->deriveKeys($salt);

            if (self::verifyHMAC($hmac, $header . $salt . $iv . $encrypted, $keys->getAuthenticationKey())) {
                $plaintext = self::plainDecrypt($encrypted, $keys->getEncryptionKey(), $iv, Core::CIPHER_METHOD);
                return $plaintext;
            } else {
                throw new Ex\WrongKeyOrModifiedCiphertextException(
                    'Integrity check failed.'
                );
            }
        }

        protected static function plainEncrypt($plaintext, $key, $iv)
        {
            Core::ensureConstantExists('OPENSSL_RAW_DATA');
            Core::ensureFunctionExists('openssl_encrypt');
            /** @var string $ciphertext */
            $ciphertext = \openssl_encrypt(
                $plaintext,
                Core::CIPHER_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            Core::ensureTrue(\is_string($ciphertext), 'openssl_encrypt() failed');

            return $ciphertext;
        }

        protected static function plainDecrypt($ciphertext, $key, $iv, $cipherMethod)
        {
            Core::ensureConstantExists('OPENSSL_RAW_DATA');
            Core::ensureFunctionExists('openssl_decrypt');

            /** @var string $plaintext */
            $plaintext = \openssl_decrypt(
                $ciphertext,
                $cipherMethod,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            Core::ensureTrue(\is_string($plaintext), 'openssl_decrypt() failed.');

            return $plaintext;
        }

        protected static function verifyHMAC($expected_hmac, $message, $key)
        {
            $message_hmac = \hash_hmac(Core::HASH_FUNCTION_NAME, $message, $key, true);
            return Core::hashEquals($message_hmac, $expected_hmac);
        }
    }

    class RuntimeTests extends Crypto
    {
        public static function runtimeTest()
        {
            // 0: Tests haven't been run yet.
            // 1: Tests have passed.
            // 2: Tests are running right now.
            // 3: Tests have failed.
            static $test_state = 0;

            if ($test_state === 1 || $test_state === 2) {
                return;
            }

            if ($test_state === 3) {
                /* If an intermittent problem caused a test to fail previously, we
                 * want that to be indicated to the user with every call to this
                 * library. This way, if the user first does something they really
                 * don't care about, and just ignores all exceptions, they won't get
                 * screwed when they then start to use the library for something
                 * they do care about. */
                throw new Ex\EnvironmentIsBrokenException('Tests failed previously.');
            }

            try {
                $test_state = 2;

                Core::ensureFunctionExists('openssl_get_cipher_methods');
                if (\in_array(Core::CIPHER_METHOD, \openssl_get_cipher_methods()) === false) {
                    throw new Ex\EnvironmentIsBrokenException(
                        'Cipher method not supported. This is normally caused by an outdated ' .
                        'version of OpenSSL (and/or OpenSSL compiled for FIPS compliance). ' .
                        'Please upgrade to a newer version of OpenSSL that supports ' .
                        Core::CIPHER_METHOD . ' to use this library.'
                    );
                }

                RuntimeTests::AESTestVector();
                RuntimeTests::HMACTestVector();
                RuntimeTests::HKDFTestVector();

                RuntimeTests::testEncryptDecrypt();
                Core::ensureTrue(Core::ourStrlen(Key::createNewRandomKey()->getRawBytes()) === Core::KEY_BYTE_SIZE);

                Core::ensureTrue(Core::ENCRYPTION_INFO_STRING !== Core::AUTHENTICATION_INFO_STRING);
            } catch (Ex\EnvironmentIsBrokenException $ex) {
                // Do this, otherwise it will stay in the "tests are running" state.
                $test_state = 3;
                throw $ex;
            }

            // Change this to '0' make the tests always re-run (for benchmarking).
            $test_state = 1;
        }

        private static function testEncryptDecrypt()
        {
            $key  = Key::createNewRandomKey();
            $data = "EnCrYpT EvErYThInG\x00\x00";

            // Make sure encrypting then decrypting doesn't change the message.
            $ciphertext = Crypto::encrypt($data, $key, true);
            try {
                $decrypted = Crypto::decrypt($ciphertext, $key, true);
            } catch (Ex\WrongKeyOrModifiedCiphertextException $ex) {
                // It's important to catch this and change it into a
                // Ex\EnvironmentIsBrokenException, otherwise a test failure could trick
                // the user into thinking it's just an invalid ciphertext!
                throw new Ex\EnvironmentIsBrokenException();
            }
            Core::ensureTrue($decrypted === $data);

            // Modifying the ciphertext: Appending a string.
            try {
                Crypto::decrypt($ciphertext . 'a', $key, true);
                throw new Ex\EnvironmentIsBrokenException();
            } catch (Ex\WrongKeyOrModifiedCiphertextException $e) { /* expected */
            }

            // Modifying the ciphertext: Changing an HMAC byte.
            $indices_to_change = [
                0, // The header.
                Core::HEADER_VERSION_SIZE + 1, // the salt
                Core::HEADER_VERSION_SIZE + Core::SALT_BYTE_SIZE + 1, // the IV
                Core::HEADER_VERSION_SIZE + Core::SALT_BYTE_SIZE + Core::BLOCK_BYTE_SIZE + 1, // the ciphertext
            ];

            foreach ($indices_to_change as $index) {
                try {
                    $ciphertext[$index] = \chr((\ord($ciphertext[$index]) + 1) % 256);
                    Crypto::decrypt($ciphertext, $key, true);
                    throw new Ex\EnvironmentIsBrokenException();
                } catch (Ex\WrongKeyOrModifiedCiphertextException $e) { /* expected */
                }
            }

            // Decrypting with the wrong key.
            $key        = Key::createNewRandomKey();
            $data       = 'abcdef';
            $ciphertext = Crypto::encrypt($data, $key, true);
            $wrong_key  = Key::createNewRandomKey();
            try {
                Crypto::decrypt($ciphertext, $wrong_key, true);
                throw new Ex\EnvironmentIsBrokenException();
            } catch (Ex\WrongKeyOrModifiedCiphertextException $e) { /* expected */
            }

            // Ciphertext too small.
            $key        = Key::createNewRandomKey();
            $ciphertext = \str_repeat('A', Core::MINIMUM_CIPHERTEXT_SIZE - 1);
            try {
                Crypto::decrypt($ciphertext, $key, true);
                throw new Ex\EnvironmentIsBrokenException();
            } catch (Ex\WrongKeyOrModifiedCiphertextException $e) { /* expected */
            }
        }

        private static function HKDFTestVector()
        {
            // HKDF test vectors from RFC 5869

            // Test Case 1
            $ikm    = \str_repeat("\x0b", 22);
            $salt   = Encoding::hexToBin('000102030405060708090a0b0c');
            $info   = Encoding::hexToBin('f0f1f2f3f4f5f6f7f8f9');
            $length = 42;
            $okm    = Encoding::hexToBin(
                '3cb25f25faacd57a90434f64d0362f2a' .
                '2d2d0a90cf1a5a4c5db02d56ecc4c5bf' .
                '34007208d5b887185865'
            );
            $computed_okm = Core::HKDF('sha256', $ikm, $length, $info, $salt);
            Core::ensureTrue($computed_okm === $okm);

            // Test Case 7
            $ikm    = \str_repeat("\x0c", 22);
            $length = 42;
            $okm    = Encoding::hexToBin(
                '2c91117204d745f3500d636a62f64f0a' .
                'b3bae548aa53d423b0d1f27ebba6f5e5' .
                '673a081d70cce7acfc48'
            );
            $computed_okm = Core::HKDF('sha1', $ikm, $length, '', null);
            Core::ensureTrue($computed_okm === $okm);
        }

        private static function HMACTestVector()
        {
            // HMAC test vector From RFC 4231 (Test Case 1)
            $key     = \str_repeat("\x0b", 20);
            $data    = 'Hi There';
            $correct = 'b0344c61d8db38535ca8afceaf0bf12b881dc200c9833da726e9376c2e32cff7';
            Core::ensureTrue(
                \hash_hmac(Core::HASH_FUNCTION_NAME, $data, $key) === $correct
            );
        }

        private static function AESTestVector()
        {
            // AES CTR mode test vector from NIST SP 800-38A
            $key = Encoding::hexToBin(
                '603deb1015ca71be2b73aef0857d7781' .
                '1f352c073b6108d72d9810a30914dff4'
            );
            $iv        = Encoding::hexToBin('f0f1f2f3f4f5f6f7f8f9fafbfcfdfeff');
            $plaintext = Encoding::hexToBin(
                '6bc1bee22e409f96e93d7e117393172a' .
                'ae2d8a571e03ac9c9eb76fac45af8e51' .
                '30c81c46a35ce411e5fbc1191a0a52ef' .
                'f69f2445df4f9b17ad2b417be66c3710'
            );
            $ciphertext = Encoding::hexToBin(
                '601ec313775789a5b7a7f504bbf3d228' .
                'f443e3ca4d62b59aca84e990cacaf5c5' .
                '2b0930daa23de94ce87017ba2d84988d' .
                'dfc9c58db67aada613c2dd08457941a6'
            );

            $computed_ciphertext = Crypto::plainEncrypt($plaintext, $key, $iv);
            Core::ensureTrue($computed_ciphertext === $ciphertext);

            $computed_plaintext = Crypto::plainDecrypt($ciphertext, $key, $iv, Core::CIPHER_METHOD);
            Core::ensureTrue($computed_plaintext === $plaintext);
        }
    }
}

namespace Defuse\Crypto\Exception
{
    class CryptoException extends \Exception
    {
    }

    class WrongKeyOrModifiedCiphertextException extends \Defuse\Crypto\Exception\CryptoException
    {
    }
}

namespace ParagonIE\ConstantTime
{
    interface EncoderInterface
    {
        public static function encode(string $binString): string;
        public static function decode(string $encodedString, bool $strictPadding = false): string;
    }

    abstract class Binary
    {
        public static function safeStrlen(string $str): int
        {
            if (\function_exists('mb_strlen')) {
                return (int) \mb_strlen($str, '8bit');
            } else {
                return \strlen($str);
            }
        }

        public static function safeSubstr(
            string $str,
            int $start = 0,
            $length = null
        ): string {
            if ($length === 0) {
                return '';
            }
            if (\function_exists('mb_substr')) {
                return \mb_substr($str, $start, $length, '8bit');
            }
            // Unlike mb_substr(), substr() doesn't accept NULL for length
            if ($length !== null) {
                return \substr($str, $start, $length);
            } else {
                return \substr($str, $start);
            }
        }
    }

    abstract class Base64 implements EncoderInterface
    {
        public static function encode(string $src): string
        {
            return static::doEncode($src, true);
        }

        protected static function doEncode(string $src, bool $pad = true): string
        {
            $dest = '';
            $srcLen = Binary::safeStrlen($src);
            // Main loop (no padding):
            for ($i = 0; $i + 3 <= $srcLen; $i += 3) {
                /** @var array<int, int> $chunk */
                $chunk = \unpack('C*', Binary::safeSubstr($src, $i, 3));
                $b0 = $chunk[1];
                $b1 = $chunk[2];
                $b2 = $chunk[3];

                $dest .=
                    static::encode6Bits(               $b0 >> 2       ) .
                    static::encode6Bits((($b0 << 4) | ($b1 >> 4)) & 63) .
                    static::encode6Bits((($b1 << 2) | ($b2 >> 6)) & 63) .
                    static::encode6Bits(  $b2                     & 63);
            }
            // The last chunk, which may have padding:
            if ($i < $srcLen) {
                /** @var array<int, int> $chunk */
                $chunk = \unpack('C*', Binary::safeSubstr($src, $i, $srcLen - $i));
                $b0 = $chunk[1];
                if ($i + 1 < $srcLen) {
                    $b1 = $chunk[2];
                    $dest .=
                        static::encode6Bits($b0 >> 2) .
                        static::encode6Bits((($b0 << 4) | ($b1 >> 4)) & 63) .
                        static::encode6Bits(($b1 << 2) & 63);
                    if ($pad) {
                        $dest .= '=';
                    }
                } else {
                    $dest .=
                        static::encode6Bits( $b0 >> 2) .
                        static::encode6Bits(($b0 << 4) & 63);
                    if ($pad) {
                        $dest .= '==';
                    }
                }
            }
            return $dest;
        }

        protected static function encode6Bits(int $src): string
        {
            $diff = 0x41;

            // if ($src > 25) $diff += 0x61 - 0x41 - 26; // 6
            $diff += ((25 - $src) >> 8) & 6;

            // if ($src > 51) $diff += 0x30 - 0x61 - 26; // -75
            $diff -= ((51 - $src) >> 8) & 75;

            // if ($src > 61) $diff += 0x2b - 0x30 - 10; // -15
            $diff -= ((61 - $src) >> 8) & 15;

            // if ($src > 62) $diff += 0x2f - 0x2b - 1; // 3
            $diff += ((62 - $src) >> 8) & 3;

            return \pack('C', $src + $diff);
        }
    }
}

namespace ParagonIE\PasswordLock
{
    use \Defuse\Crypto\Crypto;
    use \Defuse\Crypto\Key;
    use \ParagonIE\ConstantTime\Base64;

    class PasswordLock
    {
        public static function hashAndEncrypt(string $password, Key $aesKey): string
        {
            if (!\is_string($password)) {
                throw new \InvalidArgumentException(
                    'Password must be a string.'
                );
            }
            $hash = \password_hash(
                Base64::encode(
                    \hash('sha384', $password, true)
                ),
                PASSWORD_ARGON2ID
            );
            if ($hash === false) {
                throw new \Exception("Unknown hashing error.");
            }
            return Crypto::encrypt($hash, $aesKey);
        }

        public static function decryptAndVerify(string $password, string $ciphertext, Key $aesKey): bool
        {
            if (!\is_string($password)) {
                throw new \InvalidArgumentException(
                    'Password must be a string.'
                );
            }
            if (!\is_string($ciphertext)) {
                throw new \InvalidArgumentException(
                    'Ciphertext must be a string.'
                );
            }
            $hash = Crypto::decrypt(
                $ciphertext,
                $aesKey
            );
            return \password_verify(
                Base64::encode(
                    \hash('sha384', $password, true)
                ),
                $hash
            );
        }

        public static function rotateKey(string $ciphertext, Key $oldKey, Key $newKey): string
        {
            $plaintext = Crypto::decrypt($ciphertext, $oldKey);
            return Crypto::encrypt($plaintext, $newKey);
        }
    }
}