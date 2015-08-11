<?php

/**
 * A security library for PHP.
 *
 * This file is part of the Improved Security project.
 * <a href="http://www.improved-security.com/">http://www.improved-security.com/</a>
 *
 * @copyright Copyright (C) Kai Sellgren 2010
 * @package Improved Security
 * @since Version 1.00
 * @license http://opensource.org/licenses/lgpl-3.0.html GNU Lesser General Public License
 */

/**
 * A class to generate strong random data and provide useful methods.
 *
 * @since 1.00
 */
class Security_Randomizer
{
	private static $chiSquareZMax = 6.0;
	private static $chiSquareLogSqrtPi = 0.5723649429247000870717135;
	private static $chiSquareISqrtPi = 0.5641895835477562869480795;
	private static $chiSquareBigX = 20.0;
	private static $entropyNumEvents = 0;
	private static $entropyTokenFreqs = array();
	private static $entropyTokenProbs = array();
	private static $entropyData;
	private static $entropyFreData;
	private static $entropyValue = 0;

	/**
	 * This is a static class and requires no initialization.
	 */
	private function __constructor()
	{
	}

	/**
	 * No cloning available.
	 */
	private function __clone()
	{
	}

	/**
	 * Returns a random token in hex format.
	 *
	 * @param int $length
	 * @return string
	 */
	public static function getRandomToken($length)
	{
		return bin2hex(Security_Randomizer::getRandomBytes($length));
	}

	/**
	 * Returns a random boolean value.
	 *
	 * @return bool
	 */
	public static function getRandomBoolean()
	{
		$randomByte = Security_Randomizer::getRandomBytes(1);
		return (ord($randomByte) % 2) ? true : false;
	}

	/**
	 * Returns a random string based on the given length and the character set.
	 *
	 * @param int $length
	 * @param string $charset
	 * @return string
	 */
	public static function getRandomString($length, $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
	{
		$string = '';
		for ($a = 0; $a < $length; $a++)
		{
			$string .= $charset[Security_Randomizer::getRandomInteger(0,strlen((binary) $charset)-1)];
		}
		return $string;
	}

	/**
	 * Returns a random integer.
	 *
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	public static function getRandomInteger($min, $max)
	{
		if ($min > $max)
			throw new Exception('The maximum value cannot be smaller than the minimum value.');

		// First we need to determine how many bytes we need to construct $min-$max range.
		$difference = $max-$min;
		$bytesNeeded = ceil($difference/256);

		$randomBytes = Security_Randomizer::getRandomBytes($bytesNeeded);
		$sum = 0;
		for ($a = 0; $a < $bytesNeeded; $a++)
			$sum += ord($randomBytes[$a]);
		$sum = $sum % ($difference + 1);

		return $sum + $min;
	}

	/**
	 * Returns a random float between 0 and 1.
	 *
	 * @return float
	 */
	public static function getRandomFloat()
	{
		// The maximum precision on this platform.
		$maximumPrecision = strlen('' . 1/3) - 2;

		$float = '';
		for ($a = 0; $a < $maximumPrecision; $a++)
			$float .= Security_Randomizer::getRandomInteger(0,9);

		// All numbers are 0.
		if (array_sum(str_split($float)) == 0)
			$float = (Security_Randomizer::getRandomBoolean() ? '1.' : '0.') . $float;
		else
			$float = '0.' . $float;

		return (float) $float;
	}

	/**
	 * Returns a random GUID.
	 *
	 * @return string
	 */
	public static function getRandomGUID()
	{
		$hex = strtoupper(bin2hex(Security_Randomizer::getRandomBytes(16)));
		return substr($hex,0,8) . '-' . substr($hex,8,4) . '-' . substr($hex,12,4) . '-' . substr($hex,16,4) . '-' . substr($hex,20,12);
	}

	/**
	 * This method will generate strong random data for cryptographic use.
	 *
	 * @param int $length
	 * @return binary
	 */
	public static function getRandomBytes($length)
	{
		$length = (int) $length;
		if ($length < 1)
			throw new Exception('Length cannot be less than 1.');

		// Works on systems that have OpenSSL installed and OpenSSL extension loaded.
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$random = openssl_random_pseudo_bytes($length, $strong);
			if ($strong)
				return (binary) $random;
		}

		// Only execute on unix based systems
		if (DIRECTORY_SEPARATOR === '/') {

			// Works on Sun Solaris, Unix and Linux systems.
			$fp = @fopen('/dev/urandom', 'rb');
			if ($fp)
			{
				$random = fread($fp, $length);
				fclose($fp);
				return (binary) $random;
			}
		}

		// Works on Windows x86.
		if (class_exists('COM'))
		{
			try
			{
				$csp = new COM('CAPICOM.Utilities.1');
				// We are stripping because sometimes the method appends newlines?
				$random = substr((binary) base64_decode($csp->getrandom($length, 0)), 0, $length);
				unset($csp);
				return (binary) $random;
			}
			catch (Exception $e)
			{
			}
		}

		// PHP has a bug that prevents you from creating a byte array via variants. Thus, no CSP support for Windows x64.
		// If someone is able to circumvent this problem, please email me.
		// Could work on Windows x64.
		if (false)
		{
			if (class_exists('DOTNET'))
			{
				try
				{
					$csp = new DOTNET("mscorlib", "System.Security.Cryptography.RNGCryptoServiceProvider");
					$array = array_fill(0, $length, null);
					$variant = new VARIANT($array, VT_ARRAY | VT_UI1);
					$csp->GetBytes($variant);
					unset($csp);
					return (binary) implode('', $array);
				}
				catch (Exception $e)
				{
				}
			}
		}

		// This is random data from OpenSSL that was marked as "weak". It's better than mt_rand().
		if (isset($random))
		{
			return (binary) $random;
		}

		// Falling back to PHP's mt_rand() and the rest if nothing worked.
		// This basically means we are either on a Windows x64 system without OpenSSL or on a really weird system. :|
		$random = '';
		$backtrace = debug_backtrace();
		$stat = stat($backtrace[0]['file']); // Using the name of the caller script.
		for ($a = 0; $a < $length; $a++)
		{
			// Since we can't use any good random generators, we need to use as many poor "random" sources as possible.
			$source = mt_rand(); // Weak pseudo random.
			$source += microtime(true); // Non-random and is poor in tight loops - yes, like this one.
			// $source += uniqid('', true); // The only real reason to use uniqid() here is due to its use of the internal LCG.
			$source += memory_get_usage(); // Has a weak avalance effect and is predictable.
			$source += getmypid(); // Non-random and doesn't change until the next request.
			$source += $stat[7] + substr($stat[8], -3, 3) + substr($stat[9], -3, 3) + substr($stat[10], -3, 3); // File stats.

			// Let's make it a byte.
			$random .= chr($source % 255);
		}
		return (binary) $random;
	}

	/**
	 * Calculates the arithmetic mean for the given data.
	 *
	 * @param mixed $data
	 * @return float
	 */
	public static function calculateArithmeticMean($data)
	{
		$value = 0;
		for ($a = 0, $b = strlen((binary) $data); $a < $b; $a++)
			$value += ord($data[$a]);
		return $value/$b;
	}

	/**
	 * Calculates the amount of entropy in the given data.
	 *
	 * @param mixed $data
	 * @return float
	 */
	public static function calculateEntropy($data)
	{
		self::$entropyNumEvents = 0;
		self::$entropyTokenFreqs = array();
		self::$entropyTokenProbs = array();
		self::$entropyValue = 0;

		self::$entropyData = $data;
		self::$entropyNumEvents = strlen((binary) self::$entropyData);

		self::entropyFrequencies();

		foreach (self::$entropyTokenFreqs as $token => $frequency)
		{
			self::$entropyTokenProbs[$token] = $frequency / self::$entropyNumEvents;
			self::$entropyValue += self::$entropyTokenProbs[$token] * log(self::$entropyTokenProbs[$token], 2);
		}
		self::$entropyValue = -self::$entropyValue;

		return self::$entropyValue;
	}

	/**
	 * Calculates PI with Monte Carlo using the given data.
	 *
	 * @param mixed $data
	 * @return float
	 */
	public static function calculateMonteCarlo($random)
	{
		$monten = 6;
		$inCirc = pow(pow(256.0,$monten / 2) - 1, 2.0);
		$mp = 0;
		$mCount = 0;
		$inMont = 0;
		$data = '';
		$caret = 0;
		$size = strlen((binary) $random);

		while (true)
		{
			if ($caret >= $size)
				break;
			$data .= substr((binary) $random, $caret, 1);
			$caret += 1;
			$mp++;
			if ($mp >= $monten && strlen((binary) $data) == 6) // every 6th, = 6 bytes
			{
				$mp = 0;
				$mCount++;
				$montex = $montey = 0;
				for ($mj = 0;$mj < $monten / 2; $mj++)
				{
					$montex = ($montex * 256.0) + ord($data[$mj]);
					$montey = ($montey * 256.0) + ord($data[($monten / 2) + $mj]);
				}
				if ((($montex * $montex) + ($montey * $montey)) <= $inCirc)
					$inMont++;
				$data = '';
			}
		}

		$montePi = 4.0 * ($inMont / $mCount);
		return $montePi;
	}

	/**
	 * Calculates random exceeding for the Chi Square value of the given data.
	 * This is the most important single factor when considering data to be random.
	 *
	 * @param mixed $data
	 * @return float
	 */
	public static function calculateChiSquare($data)
	{
		$cCount = array();
		for ($a = 0, $b = strlen((binary) $data); $a < $b; $a++)
		{
			if (isset($cCount[ord($data[$a])]))
				$cCount[ord($data[$a])]++;
			else
				$cCount[ord($data[$a])] = 1;
		}

		$expC = strlen((binary) $data)/256;
		$chiSq = 0;
		for ($i = 0; $i < 256; $i++)
		{
			$a = (isset($cCount[$i]) ? $cCount[$i] : 0) - $expC;
			$chiSq += ($a * $a) / $expC;
		}

		return (self::chiSquarePoChiSq(round($chiSq, 2), 255) * 100);
	}

	/**
	 * A private method for Chi Square calculations.
	 *
	 * @param float $x
	 * @return float
	 */
	private static function chiSquareEx($x)
	{
		return (($x < -self::$chiSquareBigX) ? 0.0 : exp($x));
	}

	/**
	 * A private method for Chi Square calculations.
	 *
	 * @param float $ax
	 * @param float $df
	 * @return float
	 */
	private static function chiSquarePoChiSq($ax, $df)
	{
		$x = $ax;
		if ($x <= 0.0 || $df < 1)
			return 1.0;

		$a = 0.5 * $x;
		$even = (2 * (int) ($df / 2)) == $df;

		if ($df > 1)
			$y = self::chiSquareEx(-$a);

		$s = ($even ? $y : (2.0 * self::chiSquarePoZ(-sqrt($x))));

		if ($df > 2)
		{
			$x = 0.5 * ($df - 1.0);
			$z = ($even ? 1.0 : 0.5);
			if ($a > self::$chiSquareBigX)
			{
				$e = ($even ? 0.0 : self::$chiSquareLogSqrtPi);
				$c = log($a);
				while ($z <= $x)
				{
					$e = log($z) + $e;
					$s += self::chiSquareEx($c * $z - $a - $e);
					$z += 1.0;
				}
				return ($s);
			}
			else
			{
				$e = ($even ? 1.0 : (self::$chiSquareISqrtPi / sqrt($a)));
				$c = 0.0;
				while ($z <= $x)
				{
					$e = $e * ($a / $z);
					$c = $c + $e;
					$z += 1.0;
				}
				return ($c * $y + $s);
			}
		}
		else
			return $s;
	}

	/**
	 * A private method for Chi Square calculations.
	 *
	 * @param float $z
	 * @return float
	 */
	private static function chiSquarePoZ($z)
	{
		if ($z == 0.0)
			$x = 0.0;
		else
		{
			$y = 0.5 * abs($z);
			if ($y >= (self::$chiSquareZMax * 0.5))
				$x = 1.0;
			else if ($y < 1.0)
			{
				$w = $y * $y;
				$x = ((((((((0.000124818987 * $w
				-0.001075204047) * $w +0.005198775019) * $w
				-0.019198292004) * $w +0.059054035642) * $w
				-0.151968751364) * $w +0.319152932694) * $w
				-0.531923007300) * $w +0.797884560593) * $y * 2.0;
			}
			else
			{
				$y -= 2.0;
				$x = (((((((((((((-0.000045255659 * $y
				+0.000152529290) * $y -0.000019538132) * $y
				-0.000676904986) * $y +0.001390604284) * $y
				-0.000794620820) * $y -0.002034254874) * $y
				+0.006549791214) * $y -0.010557625006) * $y
				+0.011630447319) * $y -0.009279453341) * $y
				+0.005353579108) * $y -0.002141268741) * $y
				+0.000535310849) * $y +0.999936657524;
			}
		}
		return ($z > 0.0 ? (($x + 1.0) * 0.5) : ((1.0 - $x) * 0.5));
	}

	/**
	 * A private method for returning a byte of a certain position.
	 *
	 * @param integer $x
	 * @return binary
	 */
	private static function entropyDataPosition($x)
	{
		return substr((binary) self::$entropyData, $x, 1);
	}

	/**
	 * A private method for calculating frequencies.
	 *
	 * @return void
	 */
	private static function entropyFrequencies()
	{
		$tokenFreqs = array();
		for ($i = 0; $i < self::$entropyNumEvents; $i++)
		{
			$tmp = ord(self::entropyDataPosition($i));
			if (isset($tokenFreqs[$tmp]))
				$tokenFreqs[$tmp]++;
			else
				$tokenFreqs[$tmp] = 1;
		}
		self::$entropyTokenFreqs = $tokenFreqs;
	}
}