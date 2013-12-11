<?php namespace Iyoworks\Support;

class Str extends \Illuminate\Support\Str {
	const ALPHA_NUM = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const ALPHA = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const HEXDEC = '0123456789abcdef';
	const NUMERIC = '0123456789';
	const NOZERO = '123456789';
	const DISTINCT = '2345679ACDEFHJKLMNPRSTUVWXYZ';

    public static function guid($namespace = '', $pool = Str::ALPHA_NUM)
    {
        $uid = static::superRandom(32, null, $pool);
        $guid = substr($uid,  0,  8) .
            '-' . substr($uid,  8,  4) .
            '-' . substr($uid, 12,  4) .
            '-' . substr($hash, 16,  4) .
            '-' . substr($uid, 20, 12);
        return $namespace.$guid;
    }

	public static function secureRandom($length = 42, $pool = Str::ALPHA_NUM)
	{
		// We'll check if the user has OpenSSL installed with PHP. If they do
		// we'll use a better method of getting a random string. Otherwise, we'll
		// fallback to a reasonably reliable method.
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			// We generate twice as many bytes here because we want to ensure we have
			// enough after we base64 encode it to get the length we need because we
			// take out the "/", "+", and "=" characters.
			$bytes = openssl_random_pseudo_bytes($length * 2);

			// We want to stop execution if the key fails because, well, that is bad.
			if ($bytes === false)
			{
				throw new RuntimeException('Unable to generate random string.');
			}

			return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
		}

		return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
	}

	public static function superRandom($length = 36, $prefix = null, $pool = Str::ALPHA_NUM, $forceLength = true)
	{
		if ($forceLength) $length -= strlen($prefix);
		$token = "";
		$max   = strlen( $pool );
		for ( $i = 0; $i < $length; $i++ ) {
			$token .= $pool[static::cryptoRandSecure( 0, $max )];
		}
		
		return $prefix.$token;
	}

	protected static function cryptoRandSecure( $min, $max )
	{
		$range = $max - $min;
			// not so random...
		if ( $range < 0 ) return $min; 
		$log    = log( $range, 2 );
			// length in bytes
		$bytes  = (int) ( $log / 8 ) + 1; 
			// length in bits
		$bits   = (int) $log + 1; 
			// set all lower bits to 1
		$filter = (int) ( 1 << $bits ) - 1; 
		do {
			$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
				// discard irrelevant bits
			$rnd = $rnd & $filter; 
		} while ( $rnd >= $range );
		return $min + $rnd;
	}

}
