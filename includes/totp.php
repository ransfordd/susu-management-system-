<?php
class TOTP {
	public static function base32Decode(string $b32): string {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$bits = '';
		$b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
		for ($i=0; $i<strlen($b32); $i++) {
			$val = strpos($alphabet, $b32[$i]);
			$bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
		}
		$data = '';
		for ($i=0; $i+8 <= strlen($bits); $i += 8) {
			$data .= chr(bindec(substr($bits, $i, 8)));
		}
		return $data;
	}

	public static function hotp(string $secretBin, int $counter, int $digits = 6): string {
		$binCounter = pack('N*', 0) . pack('N*', $counter);
		$hash = hash_hmac('sha1', $binCounter, $secretBin, true);
		$offset = ord(substr($hash, -1)) & 0x0F;
		$truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;
		$code = $truncated % (10 ** $digits);
		return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
	}

	public static function totp(string $secretB32, int $timeStep = 30, int $digits = 6, int $t0 = 0): string {
		$counter = (int)floor((time() - $t0) / $timeStep);
		$secretBin = self::base32Decode($secretB32);
		return self::hotp($secretBin, $counter, $digits);
	}

	public static function verify(string $secretB32, string $code, int $window = 1): bool {
		$secretBin = self::base32Decode($secretB32);
		$counter = (int)floor(time() / 30);
		for ($i=-$window; $i<=$window; $i++) {
			if (hash_equals(self::hotp($secretBin, $counter + $i), $code)) return true;
		}
		return false;
	}
}






