<?php
namespace TYPO3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functions for determining the mime and media types from filenames
 *
 * Inspired by René Fritz DAM extension for TYPO3 v4
 *
 */
class FileTypes {

	/**
	 * A map of file extensions to mime types
	 *
	 * @var array
	 */
	private static $extensionToMimeType = array(
		'au'	=> 'audio/basic',
		'snd'	=> 'audio/basic',
		'mid'	=> 'audio/midi',
		'midi'	=> 'audio/midi',
		'kar'	=> 'audio/midi',
		'mpga'	=> 'audio/mpeg',
		'mpega'	=> 'audio/mpeg',
		'm3u'	=> 'audio/mpegurl',
		'sid'	=> 'audio/prs.sid',
		'aifc'	=> 'audio/x-aiff',
		'aif'	=> 'audio/x-aiff',
		'aiff'	=> 'audio/x-aiff',
		'faif'	=> 'audio/x-aiff',
		'pae'	=> 'audio/x-epac',
		'gsm'	=> 'audio/x-gsm',
		'uni'	=> 'audio/x-mod',
		'mtm'	=> 'audio/x-mod',
		'mod'	=> 'audio/x-mod',
		's3m'	=> 'audio/x-mod',
		'it'	=> 'audio/x-mod',
		'stm'	=> 'audio/x-mod',
		'ult'	=> 'audio/x-mod',
		'xm'	=> 'audio/x-mod',
		'mp2'	=> 'audio/x-mpeg',
		'mp3'	=> 'audio/x-mpeg',
		'wax'	=> 'audio/x-ms-wax',
		'wma'	=> 'audio/x-ms-wma',
		'pac'	=> 'audio/x-pac',
		'ram'	=> 'audio/x-pn-realaudio',
		'ra'	=> 'audio/x-pn-realaudio',
		'rm'	=> 'audio/x-pn-realaudio',
		'wav'	=> 'audio/x-wav',

		'z' 	=> 'encoding/x-compress',
		'gz'	=> 'encoding/x-gzip',

		'bmp'	=> 'image/bitmap',
		'gif'	=> 'image/gif',
		'ief'	=> 'image/ief',
		'jpg'	=> 'image/jpeg',
		'jpeg'	=> 'image/jpeg',
		'jpe'	=> 'image/jpeg',
		'pcx'	=> 'image/pcx',
		'png'	=> 'image/png',
		'tiff'	=> 'image/tiff',
		'tif'	=> 'image/tiff',
		'wbmp'	=> 'image/vnd.wap.wbmp',
		'ras'	=> 'image/x-cmu-raster',
		'cdr'	=> 'image/x-coreldraw',
		'pat'	=> 'image/x-coreldrawpattern',
		'cdt'	=> 'image/x-coreldrawtemplate',
		'cpt'	=> 'image/x-corelphotopaint',
		'jng'	=> 'image/x-jng',
		'pcd'	=> 'image/x-photo-cd',
		'pnm'	=> 'image/x-portable-anymap',
		'pbm'	=> 'image/x-portable-bitmap',
		'pgm'	=> 'image/x-portable-graymap',
		'ppm'	=> 'image/x-portable-pixmap',
		'rgb'	=> 'image/x-rgb',
		'xbm'	=> 'image/x-xbitmap',
		'xpm'	=> 'image/x-xpixmap',
		'xwd'	=> 'image/x-xwindowdump',

		'iges'	=> 'model/iges',
		'igs'	=> 'model/iges',
		'msh'	=> 'model/mesh',
		'silo'	=> 'model/mesh',
		'mesh'	=> 'model/mesh',
		'vrml'	=> 'model/vrml',
		'wrl'	=> 'model/vrml',

		'vfb'	=> 'text/calendar',
		'ifb'	=> 'text/calendar',
		'ics'	=> 'text/calendar',
		'csv'	=> 'text/comma-separated-values',
		'css'	=> 'text/css',
		'patch'	=> 'text/diff',
		'html'	=> 'text/html',
		'xhtml'	=> 'text/html',
		'htm'	=> 'text/html',
		'shtml'	=> 'text/html',
		'mml'	=> 'text/mathml',
		'log'	=> 'text/plain',
		'txt'	=> 'text/plain',
		'po'	=> 'text/plain',
		'asc'	=> 'text/plain',
		'diff'	=> 'text/plain',
		'text'	=> 'text/plain',
		'rtx'	=> 'text/richtext',
		'sgml'	=> 'text/sgml',
		'sgm'	=> 'text/sgml',
		'tsv'	=> 'text/tab-separated-values',
		'wml'	=> 'text/vnd.wap.wml',
		'wmls'	=> 'text/vnd.wap.wmlscript',
		'hxx'	=> 'text/x-c++hdr',
		'hpp'	=> 'text/x-c++hdr',
		'h++'	=> 'text/x-c++hdr',
		'hh'	=> 'text/x-c++hdr',
		'cc'	=> 'text/x-c++src',
		'c++'	=> 'text/x-c++src',
		'cpp'	=> 'text/x-c++src',
		'cxx'	=> 'text/x-c++src',
		'h' 	=> 'text/x-chdr',
		'c' 	=> 'text/x-csrc',
		'java'	=> 'text/x-java',
		'pas'	=> 'text/x-pascal',
		'p' 	=> 'text/x-pascal',
		'etx'	=> 'text/x-setext',
		'tk'	=> 'text/x-tcl',
		'ltx'	=> 'text/x-tex',
		'sty'	=> 'text/x-tex',
		'cls'	=> 'text/x-tex',
		'vcs'	=> 'text/x-vcalendar',
		'vcf'	=> 'text/x-vcard',
		'xsl'	=> 'text/xml',
		'xml'	=> 'text/xml',

		'dl'	=> 'video/dl',
		'gl'	=> 'video/gl',
		'mpg'	=> 'video/mpeg',
		'mpeg'	=> 'video/mpeg',
		'mpe'	=> 'video/mpeg',
		'qt'	=> 'video/quicktime',
		'mov'	=> 'video/quicktime',
		'mxu'	=> 'video/vnd.mpegurl',
		'iff'	=> 'video/x-anim',
		'anim3'	=> 'video/x-anim',
		'anim7'	=> 'video/x-anim',
		'anim'	=> 'video/x-anim',
		'anim5'	=> 'video/x-anim',
		'flc'	=> 'video/x-flc',
		'fli'	=> 'video/x-fli',
		'mng'	=> 'video/x-mng',
		'asx'	=> 'video/x-ms-asf',
		'asf'	=> 'video/x-ms-asf',
		'wm'	=> 'video/x-ms-wm',
		'wmv'	=> 'video/x-ms-wmv',
		'wmx'	=> 'video/x-ms-wmx',
		'wvx'	=> 'video/x-ms-wvx',
		'avi'	=> 'video/x-msvideo',
		'avx'	=> 'video/x-rad-screenplay',
		'mv'	=> 'video/x-sgi-movie',
		'movi'	=> 'video/x-sgi-movie',
		'movie'	=> 'video/x-sgi-movie',
		'vcr'	=> 'video/x-sunvideo',

		'ez'	=> 'application/andrew-inset',
		'cu'	=> 'application/cu-seeme',
		'csm'	=> 'application/cu-seeme',
		'tsp'	=> 'application/dsptype',
		'fif'	=> 'application/fractals',
		'spl'	=> 'application/futuresplash',
		'hqx'	=> 'application/mac-binhex40',
		'mdb'	=> 'application/msaccess',
		'xls'	=> 'application/msexcel',
		'xlw'	=> 'application/msexcel',
		'hlp'	=> 'application/mshelp',
		'ppt'	=> 'application/mspowerpoint',
		'mpx'	=> 'application/msproject',
		'mpw'	=> 'application/msproject',
		'mpp'	=> 'application/msproject',
		'mpt'	=> 'application/msproject',
		'mpc'	=> 'application/msproject',
		'doc'	=> 'application/msword',
		'so'	=> 'application/octet-stream',
		'bin'	=> 'application/octet-stream',
		'exe'	=> 'application/octet-stream',
		'oda'	=> 'application/oda',
		'pdf'	=> 'application/pdf',
		'pgp'	=> 'application/pgp-signature',
		'eps'	=> 'application/postscript',
		'ai'	=> 'application/postscript',
		'ps'	=> 'application/postscript',
		'rtf'	=> 'application/rtf',
		'smi'	=> 'application/smil',
		'smil'	=> 'application/smil',
		'xlb'	=> 'application/vnd.ms-excel',
		'pot'	=> 'application/vnd.ms-powerpoint',
		'pps'	=> 'application/vnd.ms-powerpoint',
		'sxc'	=> 'application/vnd.sun.xml.calc',
		'stc'	=> 'application/vnd.sun.xml.calc.template',
		'sxd'	=> 'application/vnd.sun.xml.draw',
		'std'	=> 'application/vnd.sun.xml.draw.template',
		'sxi'	=> 'application/vnd.sun.xml.impress',
		'sti'	=> 'application/vnd.sun.xml.impress.template',
		'sxm'	=> 'application/vnd.sun.xml.math',
		'sxw'	=> 'application/vnd.sun.xml.writer',
		'sxg'	=> 'application/vnd.sun.xml.writer.global',
		'stw'	=> 'application/vnd.sun.xml.writer.template',
		'vsd'	=> 'application/vnd.visio',
		'wbxml'	=> 'application/vnd.wap.wbxml',
		'wmlc'	=> 'application/vnd.wap.wmlc',
		'wmlsc'	=> 'application/vnd.wap.wmlscriptc',
		'wp5'	=> 'application/wordperfect5.1',
		'wk'	=> 'application/x-123',
		'aw'	=> 'application/x-applix',
		'bcpio'	=> 'application/x-bcpio',
		'vcd'	=> 'application/x-cdlink',
		'pgn'	=> 'application/x-chess-pgn',
		'Z' 	=> 'application/x-compress',
		'cpio'	=> 'application/x-cpio',
		'csh'	=> 'application/x-csh',
		'deb'	=> 'application/x-debian-package',
		'dcr'	=> 'application/x-director',
		'dxr'	=> 'application/x-director',
		'dir'	=> 'application/x-director',
		'dms'	=> 'application/x-dms',
		'dot'	=> 'application/x-dot',
		'dvi'	=> 'application/x-dvi',
		'fmr'	=> 'application/x-fmr',
		'pcf'	=> 'application/x-font',
		'pcf.Z'	=> 'application/x-font',
		'gsf'	=> 'application/x-font',
		'pfb'	=> 'application/x-font',
		'pfa'	=> 'application/x-font',
		'fr'	=> 'application/x-fr',
		'gnumeric'	=> 'application/x-gnumeric',
		'tgz'	=> 'application/x-gtar',
		'gtar'	=> 'application/x-gtar',
		'hdf'	=> 'application/x-hdf',
		'pht'	=> 'application/x-httpd-php',
		'php'	=> 'application/x-httpd-php',
		'phtml'	=> 'application/x-httpd-php',
		'php3'	=> 'application/x-httpd-php3',
		'php3p'	=> 'application/x-httpd-php3-preprocessed',
		'phps'	=> 'application/x-httpd-php3-source',
		'php4'	=> 'application/x-httpd-php4',
		'ica'	=> 'application/x-ica',
		'class'	=> 'application/x-java',
		'js'	=> 'application/x-javascript',
		'chrt'	=> 'application/x-kchart',
		'kil'	=> 'application/x-killustrator',
		'skd'	=> 'application/x-koan',
		'skt'	=> 'application/x-koan',
		'skp'	=> 'application/x-koan',
		'skm'	=> 'application/x-koan',
		'kpr'	=> 'application/x-kpresenter',
		'kpt'	=> 'application/x-kpresenter',
		'ksp'	=> 'application/x-kspread',
		'kwt'	=> 'application/x-kword',
		'kwd'	=> 'application/x-kword',
		'latex'	=> 'application/x-latex',
		'lha'	=> 'application/x-lha',
		'lzh'	=> 'application/x-lzh',
		'lzx'	=> 'application/x-lzx',
		'frm'	=> 'application/x-maker',
		'book'	=> 'application/x-maker',
		'fbdoc'	=> 'application/x-maker',
		'fm'	=> 'application/x-maker',
		'frame'	=> 'application/x-maker',
		'fb'	=> 'application/x-maker',
		'maker'	=> 'application/x-maker',
		'mif'	=> 'application/x-mif',
		'mi'	=> 'application/x-mif',
		'wmd'	=> 'application/x-ms-wmd',
		'wmz'	=> 'application/x-ms-wmz',
		'bat'	=> 'application/x-msdos-program',
		'com'	=> 'application/x-msdos-program',
		'dll'	=> 'application/x-msdos-program',
		'msi'	=> 'application/x-msi',
		'nc'	=> 'application/x-netcdf',
		'cdf'	=> 'application/x-netcdf',
		'proxy'	=> 'application/x-ns-proxy-autoconfig',
		'o' 	=> 'application/x-object',
		'ogg'	=> 'application/x-ogg',
		'oza'	=> 'application/x-oz-application',
		'perl'	=> 'application/x-perl',
		'pm'	=> 'application/x-perl',
		'pl'	=> 'application/x-perl',
		'qxd'	=> 'application/x-quark-xpress-3',
		'rpm'	=> 'application/x-redhat-package-manager',
		'sh'	=> 'application/x-sh',
		'shar'	=> 'application/x-shar',
		'swf'	=> 'application/x-shockwave-flash',
		'swfl'	=> 'application/x-shockwave-flash',
		'sit'	=> 'application/x-stuffit',
		'tar'	=> 'application/x-tar',
		'tcl'	=> 'application/x-tcl',
		'tex'	=> 'application/x-tex',
		'gf'	=> 'application/x-tex-gf',
		'pk'	=> 'application/x-tex-pk',
		'PK'	=> 'application/x-tex-pk',
		'texinfo'	=> 'application/x-texinfo',
		'texi'	=> 'application/x-texinfo',
		'tki'	=> 'application/x-tkined',
		'tkined'	=> 'application/x-tkined',
		'%' 	=> 'application/x-trash',
		'sik'	=> 'application/x-trash',
		'~' 	=> 'application/x-trash',
		'old'	=> 'application/x-trash',
		'bak'	=> 'application/x-trash',
		'tr'	=> 'application/x-troff',
		'roff'	=> 'application/x-troff',
		't' 	=> 'application/x-troff',
		'man'	=> 'application/x-troff-man',
		'me'	=> 'application/x-troff-me',
		'ms'	=> 'application/x-troff-ms',
		'zip'	=> 'application/x-zip-compressed',
		'xht'	=> 'application/xhtml+xml',
	);

	/**
	 * A map of mime types to file extensions
	 *
	 * @var array
	 */
	private static $mimeTypeToExtension = array(
		'text/plain' => 'txt',
		'text/html' => 'html',
		'text/json' => 'json',
		'application/xhtml+xml' => 'html',
		'application/json' => 'json',
		'application/xml' => 'xml',
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/tiff' => 'tiff',
	);

	/**
	 * A map of filename extensions to media type
	 * Inspired by Dublin Core
	 *
	 * @var array
	 */
	private static $extensionToMediaType = array(
		'ogg'=> 'audio',

		'txt'=> 'text',
		'doc'=> 'text',
		'dot'=> 'text',
		'pdf'=> 'text',
		'ps'=> 'text',
		'wp5'=> 'text',
		'rtf'=> 'text',
		'dvi'=> 'text',
		'html' => 'text',

		'ai'=> 'image',
		'eps'=> 'image',
		'png' => 'image',

		'csv'=> 'dataset',
		'xls'=> 'dataset',
		'xlb'=> 'dataset',
		'mdb'=> 'dataset',
		'zip'=> 'dataset',
		'wk'=> 'dataset',

		'ttf'=> 'font',
		'pfa'=> 'font',
		'pfb'=> 'font',
		'gsf'=> 'font',
		'pcf'=> 'font',
		'pcf.Z'=> 'font',

		'max'=> 'model',
		'3ds'=> 'model',

		'gtar'=> 'collection',
		'tgz'=> 'collection',
		'tar'=> 'collection',
		'lha'=> 'collection',
		'lzh'=> 'collection',
		'lzx'=> 'collection',
		'hqx'=> 'collection',
		'rpm'=> 'collection',
		'shar'=> 'collection',
		'sit'=> 'collection',
		'deb'=> 'collection',

		'com'=> 'software',
		'exe'=> 'software',
		'bat'=> 'software',
		'dll'=> 'software',
		'pl'=> 'software',
		'pm'=> 'software',

		'swf'=> 'interactive',
		'swfl'=> 'interactive',
		'ppt'=> 'interactive',
		'pps'=> 'interactive',
		'pot'=> 'interactive',
	);

	/**
	 * Returns a mime type based on the filename extension
	 *
	 * @param  string $filename Filename to determine the mime type for
	 * @return string
	 */
	static public function getMimeTypeFromFilename($filename) {
		$pathinfo = pathinfo($filename);
		if (!isset($pathinfo['extension'])) {
			return 'application/octet-stream';
		} else {
			return isset(self::$extensionToMimeType[$pathinfo['extension']]) ? self::$extensionToMimeType[$pathinfo['extension']] : 'application/octet-stream';
		}
	}

	/**
	 * Returns a filename extension (aka "format") based on the given mime type.
	 *
	 * @param string $mimeType Mime type
	 * @return string filename extension
	 */
	static public function getFilenameExtensionFromMimeType($mimeType) {
		return isset(self::$mimeTypeToExtension[$mimeType]) ? self::$mimeTypeToExtension[$mimeType] : '';
	}

	/**
	 * Returns a media type based on the filename extension
	 *
	 * @param  string $filename Filename to determine the media type for
	 * @return string
	 */
	static public function getMediaTypeFromFilename($filename) {
		$pathinfo = pathinfo($filename);
		if (!isset($pathinfo['extension'])) {
			return '';
		} else {
			return isset(self::$extensionToMediaType[$pathinfo['extension']]) ? self::$extensionToMediaType[$pathinfo['extension']] : '';
		}
	}
}
?>