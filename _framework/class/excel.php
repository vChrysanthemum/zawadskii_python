<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* A class for reading Microsoft Excel Spreadsheets.
*
* Originally developed by Vadim Tkachenko under the name PHPExcel_uReader.
*(http://sourceforge.net/projects/phpexcelreader)
* Based on the Java version by Andy Khan(http://www.andykhan.com).  Now
* maintained by David Sanders.  Reads only Biff 7 and Biff 8 formats.
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   Spreadsheet
* @package    Spreadsheet_Excel_Reader
* @author     Vadim Tkachenko <vt@apachephp.com>
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id: reader.php 19 2007-03-13 12:42:41Z shangxiao $
* @link       http://pear.php.net/package/Spreadsheet_Excel_Reader
* @see        OLE, Spreadsheet_Excel_Writer
*/


//require_once_once 'PEAR.php';
//require_once_once 'Spreadsheet/Excel/Reader/OLERead.php';
define('NUM_BIG_BLOCK_DEPOT_BLOCKS_POS', 0x2c);
define('SMALL_BLOCK_DEPOT_BLOCK_POS', 0x3c);
define('ROOT_START_BLOCK_POS', 0x30);
define('BIG_BLOCK_SIZE', 0x200);
define('SMALL_BLOCK_SIZE', 0x40);
define('EXTENSION_BLOCK_POS', 0x44);
define('NUM_EXTENSION_BLOCK_POS', 0x48);
define('PROPERTY_STORAGE_BLOCK_SIZE', 0x80);
define('BIG_BLOCK_DEPOT_BLOCKS_POS', 0x4c);
define('SMALL_BLOCK_THRESHOLD', 0x1000);
// property storage offsets
define('SIZE_OF_NAME_POS', 0x40);
define('TYPE_POS', 0x42);
define('START_BLOCK_POS', 0x74);
define('SIZE_POS', 0x78);
define('IDENTIFIER_OLE', pack("CCCCCCCC" , 0xd0 , 0xcf , 0x11 , 0xe0 , 0xa1 , 0xb1 , 0x1a , 0xe1));

//echo 'ROOT_START_BLOCK_POS = '.ROOT_START_BLOCK_POS."\n";

//echo bin2hex($data[ROOT_START_BLOCK_POS])."\n";
//echo "a=";
//echo $data[ROOT_START_BLOCK_POS];
//function log

function Get_uInt4d ($data, $pos)
{
	$value = ord($data[$pos]) |(ord($data[$pos+1])	<< 8) |(ord($data[$pos+2]) << 16) |(ord($data[$pos+3]) << 24);
	if ($value>=4294967294)
	{
		$value=-2;
	}
	return $value;
}


class OLERead {
    var $data = '';
    
    
    function OLERead () {
        
        
    }
    
    function read ($s_uFile_uName) {
        
    	// check if file exist and is readable(Darko Miljanovic)
    	if ( ! is_readable($s_uFile_uName)) {
    		$this->error = 1;
    		return false;
    	}
    	
    	$this->data = @file_get_contents($s_uFile_uName);
    	if ( ! $this->data) { 
    		$this->error = 1; 
    		return false; 
   		}
   		//echo IDENTIFIER_OLE;
   		//echo 'start';
   		if (substr($this->data, 0, 8) != IDENTIFIER_OLE) {
    		$this->error = 1; 
    		return false; 
   		}
        $this->num_uBig_uBlock_uDepot_uBlocks = Get_uInt4d($this->data, NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
        $this->sbd_uStart_uBlock = Get_uInt4d($this->data, SMALL_BLOCK_DEPOT_BLOCK_POS);
        $this->root_uStart_uBlock = Get_uInt4d($this->data, ROOT_START_BLOCK_POS);
        $this->extension_uBlock = Get_uInt4d($this->data, EXTENSION_BLOCK_POS);
        $this->num_uExtension_uBlocks = Get_uInt4d($this->data, NUM_EXTENSION_BLOCK_POS);
        
	/*
        echo $this->num_uBig_uBlock_uDepot_uBlocks." ";
        echo $this->sbd_uStart_uBlock." ";
        echo $this->root_uStart_uBlock." ";
        echo $this->extension_uBlock." ";
        echo $this->num_uExtension_uBlocks." ";
        */
        //echo "sbd_uStart_uBlock = $this->sbd_uStart_uBlock\n";
        $big_uBlock_uDepot_uBlocks = array();
        $pos = BIG_BLOCK_DEPOT_BLOCKS_POS;
       // echo "pos = $pos";
	$bbd_uBlocks = $this->num_uBig_uBlock_uDepot_uBlocks;
        
            if ($this->num_uExtension_uBlocks != 0) {
                $bbd_uBlocks =(BIG_BLOCK_SIZE - BIG_BLOCK_DEPOT_BLOCKS_POS)/4; 
            }
        
        for ($i = 0; $i < $bbd_uBlocks; $i++) {
              $big_uBlock_uDepot_uBlocks[$i] = Get_uInt4d($this->data, $pos);
              $pos += 4;
        }
        
        
        for ($j = 0; $j < $this->num_uExtension_uBlocks; $j++) {
            $pos =($this->extension_uBlock + 1) * BIG_BLOCK_SIZE;
            $blocks_uTo_uRead = min($this->num_uBig_uBlock_uDepot_uBlocks - $bbd_uBlocks, BIG_BLOCK_SIZE / 4 - 1);

            for ($i = $bbd_uBlocks; $i < $bbd_uBlocks + $blocks_uTo_uRead; $i++) {
                $big_uBlock_uDepot_uBlocks[$i] = Get_uInt4d($this->data, $pos);
                $pos += 4;
            }   

            $bbd_uBlocks += $blocks_uTo_uRead;
            if ($bbd_uBlocks < $this->num_uBig_uBlock_uDepot_uBlocks) {
                $this->extension_uBlock = Get_uInt4d($this->data, $pos);
            }
        }

       // var_dump($big_uBlock_uDepot_uBlocks);
        
        // read_uBig_uBlock_uDepot
        $pos = 0;
        $index = 0;
        $this->big_uBlock_uChain = array();
        
        for ($i = 0; $i < $this->num_uBig_uBlock_uDepot_uBlocks; $i++) {
            $pos =($big_uBlock_uDepot_uBlocks[$i] + 1) * BIG_BLOCK_SIZE;
            //echo "pos = $pos";	
            for ($j = 0 ; $j < BIG_BLOCK_SIZE / 4; $j++) {
                $this->big_uBlock_uChain[$index] = Get_uInt4d($this->data, $pos);
                $pos += 4 ;
                $index++;
            }
        }

	//var_dump($this->big_uBlock_uChain);
        //echo '=====2';
        // read_uSmall_uBlock_uDepot();
        $pos = 0;
	    $index = 0;
	    $sbd_uBlock = $this->sbd_uStart_uBlock;
	    $this->small_uBlock_uChain = array();
	
	    while ($sbd_uBlock != -2) {
	
	      $pos =($sbd_uBlock + 1) * BIG_BLOCK_SIZE;
	
	      for ($j = 0; $j < BIG_BLOCK_SIZE / 4; $j++) {
	        $this->small_uBlock_uChain[$index] = Get_uInt4d($this->data, $pos);
	        $pos += 4;
	        $index++;
	      }
	
	      $sbd_uBlock = $this->big_uBlock_uChain[$sbd_uBlock];
	    }

        
        // read_uData(root_uStart_uBlock)
        $block = $this->root_uStart_uBlock;
        $pos = 0;
        $this->entry = $this->__read_uData($block);
        
        /*
        while ($block != -2)  {
            $pos =($block + 1) * BIG_BLOCK_SIZE;
            $this->entry = $this->entry.substr($this->data, $pos, BIG_BLOCK_SIZE);
            $block = $this->big_uBlock_uChain[$block];
        }
        */
        //echo '==='.$this->entry."===";
        $this->__read_uProperty_uSets();

    }
    
     function __read_uData ($bl) {
        $block = $bl;
        $pos = 0;
        $data = '';
        
        while ($block != -2)  {
            $pos =($block + 1) * BIG_BLOCK_SIZE;
            $data = $data.substr($this->data, $pos, BIG_BLOCK_SIZE);
            //echo "pos = $pos data=$data\n";	
	    $block = $this->big_uBlock_uChain[$block];
        }
		return $data;
     }
        
    function __read_uProperty_uSets () {
        $offset = 0;
        //var_dump($this->entry);
        while ($offset < strlen($this->entry)) {
              $d = substr($this->entry, $offset, PROPERTY_STORAGE_BLOCK_SIZE);
            
              $name_uSize = ord($d[SIZE_OF_NAME_POS]) |(ord($d[SIZE_OF_NAME_POS+1]) << 8);
              
              $type = ord($d[TYPE_POS]);
              //$max_uBlock = strlen($d) / BIG_BLOCK_SIZE - 1;
        
              $start_uBlock = Get_uInt4d($d, START_BLOCK_POS);
              $size = Get_uInt4d($d, SIZE_POS);
        
            $name = '';
            for ($i = 0; $i < $name_uSize ; $i++) {
              $name .= $d[$i];
            }
            
            $name = str_replace("\x00", "", $name);
            
            $this->props[] = array(
                'name' => $name, 
                'type' => $type , 
                'start_uBlock' => $start_uBlock , 
                'size' => $size);

            if (($name == "Workbook") ||($name == "Book")) {
                $this->wrkbook = count($this->props) - 1;
            }

            if ($name == "Root Entry") {
                $this->rootentry = count($this->props) - 1;
            }
            
            //echo "name ==$name=\n";

            
            $offset += PROPERTY_STORAGE_BLOCK_SIZE;
        }   
        
    }
    
    
    function get_uWork_uBook () {
    	if ($this->props[$this->wrkbook]['size'] < SMALL_BLOCK_THRESHOLD) {
//    	  get_uSmall_uBlock_uStream(Property_uStorage ps)

			$rootdata = $this->__read_uData($this->props[$this->rootentry]['start_uBlock']);
	        
			$stream_uData = '';
	        $block = $this->props[$this->wrkbook]['start_uBlock'];
	        //$count = 0;
	        $pos = 0;
		    while ($block != -2) {
      	          $pos = $block * SMALL_BLOCK_SIZE;
		          $stream_uData .= substr($rootdata, $pos, SMALL_BLOCK_SIZE);

			      $block = $this->small_uBlock_uChain[$block];
		    }
			
		    return $stream_uData;
    		

    	}else{
    	
	        $num_uBlocks = $this->props[$this->wrkbook]['size'] / BIG_BLOCK_SIZE;
	        if ($this->props[$this->wrkbook]['size'] % BIG_BLOCK_SIZE != 0) {
	            $num_uBlocks++;
	        }
	        
	        if ($num_uBlocks == 0) return '';
	        
	        //echo "num_uBlocks = $num_uBlocks\n";
	    //byte[] stream_uData = new byte[num_uBlocks * BIG_BLOCK_SIZE];
	        //print_r($this->wrkbook);
	        $stream_uData = '';
	        $block = $this->props[$this->wrkbook]['start_uBlock'];
	        //$count = 0;
	        $pos = 0;
	        //echo "block = $block";
	        while ($block != -2) {
	          $pos =($block + 1) * BIG_BLOCK_SIZE;
	          $stream_uData .= substr($this->data, $pos, BIG_BLOCK_SIZE);
	          $block = $this->big_uBlock_uChain[$block];
	        }   
	        //echo 'stream'.$stream_uData;
	        return $stream_uData;
    	}
    }
    
}
//require_once './Excel/oleread.inc';
//require_once_once 'OLE.php';

define('SPREADSHEET_EXCEL_READER_BIFF8',             0x600);
define('SPREADSHEET_EXCEL_READER_BIFF7',             0x500);
define('SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS',   0x5);
define('SPREADSHEET_EXCEL_READER_WORKSHEET',         0x10);

define('SPREADSHEET_EXCEL_READER_TYPE_BOF',          0x809);
define('SPREADSHEET_EXCEL_READER_TYPE_EOF',          0x0a);
define('SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET',   0x85);
define('SPREADSHEET_EXCEL_READER_TYPE_DIMENSION',    0x200);
define('SPREADSHEET_EXCEL_READER_TYPE_ROW',          0x208);
define('SPREADSHEET_EXCEL_READER_TYPE_DBCELL',       0xd7);
define('SPREADSHEET_EXCEL_READER_TYPE_FILEPASS',     0x2f);
define('SPREADSHEET_EXCEL_READER_TYPE_NOTE',         0x1c);
define('SPREADSHEET_EXCEL_READER_TYPE_TXO',          0x1b6);
define('SPREADSHEET_EXCEL_READER_TYPE_RK',           0x7e);
define('SPREADSHEET_EXCEL_READER_TYPE_RK2',          0x27e);
define('SPREADSHEET_EXCEL_READER_TYPE_MULRK',        0xbd);
define('SPREADSHEET_EXCEL_READER_TYPE_MULBLANK',     0xbe);
define('SPREADSHEET_EXCEL_READER_TYPE_INDEX',        0x20b);
define('SPREADSHEET_EXCEL_READER_TYPE_SST',          0xfc);
define('SPREADSHEET_EXCEL_READER_TYPE_EXTSST',       0xff);
define('SPREADSHEET_EXCEL_READER_TYPE_CONTINUE',     0x3c);
define('SPREADSHEET_EXCEL_READER_TYPE_LABEL',        0x204);
define('SPREADSHEET_EXCEL_READER_TYPE_LABELSST',     0xfd);
define('SPREADSHEET_EXCEL_READER_TYPE_NUMBER',       0x203);
define('SPREADSHEET_EXCEL_READER_TYPE_NAME',         0x18);
define('SPREADSHEET_EXCEL_READER_TYPE_ARRAY',        0x221);
define('SPREADSHEET_EXCEL_READER_TYPE_STRING',       0x207);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA',      0x406);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA2',     0x6);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMAT',       0x41e);
define('SPREADSHEET_EXCEL_READER_TYPE_XF',           0xe0);
define('SPREADSHEET_EXCEL_READER_TYPE_BOOLERR',      0x205);
define('SPREADSHEET_EXCEL_READER_TYPE_UNKNOWN',      0xffff);
define('SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR', 0x22);
define('SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS',  0x_uE5);

define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS' ,    25569);
define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904', 24107);
define('SPREADSHEET_EXCEL_READER_MSINADAY',          86400);
//define('SPREADSHEET_EXCEL_READER_MSINADAY', 24 * 60 * 60);

//define('SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT', "%.2f");
define('SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT',    "%s");


/*
* Place includes, constant defines and $_GLOBAL settings here.
* Make sure they have appropriate docblocks to avoid php_uDocumentor
* construing they are documented by the page-level docblock.
*/

/**
* A class for reading Microsoft Excel Spreadsheets.
*
* Originally developed by Vadim Tkachenko under the name PHPExcel_uReader.
*(http://sourceforge.net/projects/phpexcelreader)
* Based on the Java version by Andy Khan(http://www.andykhan.com).  Now
* maintained by David Sanders.  Reads only Biff 7 and Biff 8 formats.
*
* @category   Spreadsheet
* @package    Spreadsheet_Excel_Reader
* @author     Vadim Tkachenko <vt@phpapache.com>
* @copyright  1997-2005 The PHP Group
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    Release: @package_version@
* @link       http://pear.php.net/package/Package_uName
* @see        OLE, Spreadsheet_Excel_Writer
*/
class Spreadsheet_Excel_Reader
{
    /**
     * Array of worksheets found
     *
     * @var array
     * @access public
     */
    var $boundsheets = array();

    /**
     * Array of format records found
     * 
     * @var array
     * @access public
     */
    var $format_uRecords = array();

    /**
     * todo
     *
     * @var array
     * @access public
     */
    var $sst = array();

    /**
     * Array of worksheets
     *
     * The data is stored in 'cells' and the meta-data is stored in an array
     * called 'cells_uInfo'
     *
     * Example:
     *
     * $sheets  -->  'cells'  -->  row --> column --> Interpreted value
     *          -->  'cells_uInfo' --> row --> column --> 'type' - Can be 'date', 'number', or 'unknown'
     *                                            --> 'raw' - The raw data that Excel stores for that data cell
     *
     * @var array
     * @access public
     */
    var $sheets = array();

    /**
     * The data returned by OLE
     *
     * @var string
     * @access public
     */
    var $data;

    /**
     * OLE object for reading the file
     *
     * @var OLE object
     * @access private
     */
    var $_ole;

    /**
     * Default encoding
     *
     * @var string
     * @access private
     */
    var $_default_uEncoding;

    /**
     * Default number format
     *
     * @var integer
     * @access private
     */
    var $_default_uFormat = SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT;

    /**
     * todo
     * List of formats to use for each column
     *
     * @var array
     * @access private
     */
    var $_columns_uFormat = array();

    /**
     * todo
     *
     * @var integer
     * @access private
     */
    var $_rowoffset = 1;

    /**
     * todo
     *
     * @var integer
     * @access private
     */
    var $_coloffset = 1;

    /**
     * List of default date formats used by Excel
     *
     * @var array
     * @access public
     */
    var $date_uFormats = array(
        0xe => "d/m/Y" , 
        0xf => "d-M-Y" , 
        0x10 => "d-M" , 
        0x11 => "M-Y" , 
        0x12 => "h:i a" , 
        0x13 => "h:i:s a" , 
        0x14 => "H:i" , 
        0x15 => "H:i:s" , 
        0x16 => "d/m/Y H:i" , 
        0x2d => "i:s" , 
        0x2e => "H:i:s" , 
        0x2f => "i:s.S");

    /**
     * Default number formats used by Excel
     *
     * @var array
     * @access public
     */
    var $number_uFormats = array(
        0x1 => "%1.0f",     // "0"
        0x2 => "%1.2f",     // "0.00" , 
        0x3 => "%1.0f",     //"# , ##0" , 
        0x4 => "%1.2f",     //"# , ##0.00" , 
        0x5 => "%1.0f",     /*"$# , ##0;($# , ##0)" , */
        0x6 => '$%1.0f',    /*"$# , ##0;($# , ##0)" , */
        0x7 => '$%1.2f',    //"$# , ##0.00;($# , ##0.00)" , 
        0x8 => '$%1.2f',    //"$# , ##0.00;($# , ##0.00)" , 
        0x9 => '%1.0f%%',   // "0%"
        0xa => '%1.2f%%',   // "0.00%"
        0xb => '%1.2f',     // 0.00E00" , 
        0x25 => '%1.0f',    // "# , ##0;(# , ##0)" , 
        0x26 => '%1.0f',    //"# , ##0;(# , ##0)" , 
        0x27 => '%1.2f',    //"# , ##0.00;(# , ##0.00)" , 
        0x28 => '%1.2f',    //"# , ##0.00;(# , ##0.00)" , 
        0x29 => '%1.0f',    //"# , ##0;(# , ##0)" , 
        0x2a => '$%1.0f',   //"$# , ##0;($# , ##0)" , 
        0x2b => '%1.2f',    //"# , ##0.00;(# , ##0.00)" , 
        0x2c => '$%1.2f',   //"$# , ##0.00;($# , ##0.00)" , 
        0x30 => '%1.0f');   //"##0.0E0";

    // }}}
    // {{{ Spreadsheet_Excel_Reader()

    /**
     * Constructor
     *
     * Some basic initialisation
     */ 
    function Spreadsheet_Excel_Reader ()
    {
        $this->_ole = new OLERead();
        $this->set_uUTFEncoder('iconv');
    }

    // }}}
    // {{{ set_uOutput_uEncoding()

    /**
     * Set the encoding method
     *
     * @param string Encoding to use
     * @access public
     */
    function set_uOutput_uEncoding ($encoding)
    {
        $this->_default_uEncoding = $encoding;
    }

    // }}}
    // {{{ set_uUTFEncoder()

    /**
     *  $encoder = 'iconv' or 'mb'
     *  set iconv if you would like use 'iconv' for encode UTF-16LE to your encoding
     *  set mb if you would like use 'mb_convert_encoding' for encode UTF-16LE to your encoding
     *
     * @access public
     * @param string Encoding type to use.  Either 'iconv' or 'mb'
     */
    function set_uUTFEncoder ($encoder = 'iconv')
    {
        $this->_encoder_uFunction = '';

        if ($encoder == 'iconv') {
            $this->_encoder_uFunction = function_exists('iconv') ? 'iconv' : '';
        } elseif ($encoder == 'mb') {
            $this->_encoder_uFunction = function_exists('mb_convert_encoding') ?
                                      'mb_convert_encoding' :
                                      '';
        }
    }

    // }}}
    // {{{ set_uRow_uCol_uOffset()

    /**
     * todo
     *
     * @access public
     * @param offset
     */
    function set_uRow_uCol_uOffset ($i_uOffset)
    {
        $this->_rowoffset = $i_uOffset;
        $this->_coloffset = $i_uOffset;
    }

    // }}}
    // {{{ set_uDefault_uFormat()

    /**
     * Set the default number format
     *
     * @access public
     * @param Default format
     */
    function set_uDefault_uFormat ($s_uFormat)
    {
        $this->_default_uFormat = $s_uFormat;
    }

    // }}}
    // {{{ set_uColumn_uFormat()

    /**
     * Force a column to use a certain format
     *
     * @access public
     * @param integer Column number
     * @param string Format
     */
    function set_uColumn_uFormat ($column, $s_uFormat)
    {
        $this->_columns_uFormat[$column] = $s_uFormat;
    }


    // }}}
    // {{{ read()

    /**
     * Read the spreadsheet file using OLE, then parse
     *
     * @access public
     * @param filename
     * @todo return a valid value
     */
    function read ($s_uFile_uName)
    {
    /*
        require_once_once 'OLE.php';
        $ole = new OLE();
        $ole->read($s_uFile_uName);

        foreach ($ole->_list as $i => $pps) {
            if (($pps->Name == 'Workbook' || $pps->Name == 'Book') &&
                $pps->Size >= SMALL_BLOCK_THRESHOLD) {

                $this->data = $ole->get_uData($i, 0, $ole->get_uData_uLength($i));
            } elseif ($pps->Name == 'Root Entry') {
                $this->data = $ole->get_uData($i, 0, $ole->get_uData_uLength($i));
            }
            //var_dump(strlen($ole->get_uData($i, 0, $ole->get_uData_uLength($i))), $pps->Name, md5($this->data), $ole->get_uData_uLength($i));
        }
//exit;
        $this->_parse();

        return sizeof($this->sheets) > 0;
    */

        $res = $this->_ole->read($s_uFile_uName);

        // oops, something goes wrong(Darko Miljanovic)
        if ($res === false) {
            // check error code
            if ($this->_ole->error == 1) {
            // bad file
                die('The filename ' . $s_uFile_uName . ' is not readable');
            }
            // check other error codes here(eg bad fileformat, etc...)
        }

        $this->data = $this->_ole->get_uWork_uBook();


        /*
        $res = $this->_ole->read($s_uFile_uName);

        if ($this->is_uError($res)) {
//        var_dump($res);
            return $this->raise_uError($res);
        }

        $total = $this->_ole->pps_uTotal();
        for ($i = 0; $i < $total; $i++) {
            if ($this->_ole->is_uFile($i)) {
                $type = unpack("v", $this->_ole->get_uData($i, 0, 2));
                if ($type[''] == 0x0809)  { // check if it's a BIFF stream
                    $this->_index = $i;
                    $this->data = $this->_ole->get_uData($i, 0, $this->_ole->get_uData_uLength($i));
                    break;
                }
            }
        }

        if ($this->_index === null) {
            return $this->raise_uError("$file doesn't seem to be an Excel file");
        }

        */

    //echo "data =".$this->data;
        //$this->read_uRecords();
        $this->_parse();
    }


    // }}}
    // {{{ _parse()

    /**
     * Parse a workbook
     *
     * @access private
     * @return bool
     */
    function _parse ()
    {
        $pos = 0;

        $code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
        $length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

        $version = ord($this->data[$pos + 4]) | ord($this->data[$pos + 5])<<8;
        $substream_uType = ord($this->data[$pos + 6]) | ord($this->data[$pos + 7])<<8;
        //echo "Start parse code=".base_convert($code , 10 , 16)." version=".base_convert($version , 10 , 16)." substream_uType=".base_convert($substream_uType , 10 , 16).""."\n";

        if (($version != SPREADSHEET_EXCEL_READER_BIFF8) &&
           ($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
            return false;
        }

        if ($substream_uType != SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS) {
            return false;
        }

        //print_r($rec);
        $pos += $length + 4;

        $code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
        $length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

        while ($code != SPREADSHEET_EXCEL_READER_TYPE_EOF) {
            switch ($code) {
                case SPREADSHEET_EXCEL_READER_TYPE_SST:
                    //echo "Type_SST\n";
                     $spos = $pos + 4;
                     $limitpos = $spos + $length;
                     $unique_uStrings = $this->_Get_uInt4d($this->data, $spos+4);
                                                $spos += 8;
                                       for ($i = 0; $i < $unique_uStrings; $i++) {
        // Read in the number of characters
                                                if ($spos == $limitpos) {
                                                $opcode = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                                                $conlength = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                                                        if ($opcode != 0x3c) {
                                                                return -1;
                                                        }
                                                $spos += 4;
                                                $limitpos = $spos + $conlength;
                                                }
                                                $num_uChars = ord($this->data[$spos]) |(ord($this->data[$spos+1]) << 8);
                                                //echo "i = $i pos = $pos num_uChars = $num_uChars ";
                                                $spos += 2;
                                                $option_uFlags = ord($this->data[$spos]);
                                                $spos++;
                                        $ascii_uEncoding =(($option_uFlags & 0x01) == 0) ;
                                                $extended_uString =(($option_uFlags & 0x04) != 0);

                                                // See if string contains formatting information
                                                $rich_uString =(($option_uFlags & 0x08) != 0);

                                                if ($rich_uString) {
                                        // Read in the crun
                                                        $formatting_uRuns = ord($this->data[$spos]) |(ord($this->data[$spos+1]) << 8);
                                                        $spos += 2;
                                                }

                                                if ($extended_uString) {
                                                  // Read in cch_uExt_uRst
                                                  $extended_uRun_uLength = $this->_Get_uInt4d($this->data, $spos);
                                                  $spos += 4;
                                                }

                                                $len =($ascii_uEncoding)? $num_uChars : $num_uChars*2;
                                                if ($spos + $len < $limitpos) {
                                                                $retstr = substr($this->data, $spos, $len);
                                                                $spos += $len;
                                                }else{
                                                        // found countinue
                                                        $retstr = substr($this->data, $spos, $limitpos - $spos);
                                                        $bytes_uRead = $limitpos - $spos;
                                                        $chars_uLeft = $num_uChars -(($ascii_uEncoding) ? $bytes_uRead :($bytes_uRead / 2));
                                                        $spos = $limitpos;

                                                         while ($chars_uLeft > 0) {
                                                                $opcode = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                                                                $conlength = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                                                                        if ($opcode != 0x3c) {
                                                                                return -1;
                                                                        }
                                                                $spos += 4;
                                                                $limitpos = $spos + $conlength;
                                                                $option = ord($this->data[$spos]);
                                                                $spos += 1;
                                                                  if ($ascii_uEncoding &&($option == 0)) {
                                                                                $len = min($chars_uLeft, $limitpos - $spos); // min($chars_uLeft, $conlength);
                                                                    $retstr .= substr($this->data, $spos, $len);
                                                                    $chars_uLeft -= $len;
                                                                    $ascii_uEncoding = true;
                                                                  }elseif ( ! $ascii_uEncoding &&($option != 0)) {
                                                                                $len = min($chars_uLeft * 2, $limitpos - $spos); // min($chars_uLeft, $conlength);
                                                                    $retstr .= substr($this->data, $spos, $len);
                                                                    $chars_uLeft -= $len/2;
                                                                    $ascii_uEncoding = false;
                                                                  }elseif ( ! $ascii_uEncoding &&($option == 0)) {
                                                                // Bummer - the string starts off as Unicode, but after the
                                                                // continuation it is in straightforward ASCII encoding
                                                                                $len = min($chars_uLeft, $limitpos - $spos); // min($chars_uLeft, $conlength);
                                                                        for ($j = 0; $j < $len; $j++) {
                                                                 $retstr .= $this->data[$spos + $j].chr(0);
                                                                }
                                                            $chars_uLeft -= $len;
                                                                $ascii_uEncoding = false;
                                                                  }else{
                                                            $newstr = '';
                                                                    for ($j = 0; $j < strlen($retstr); $j++) {
                                                                      $newstr = $retstr[$j].chr(0);
                                                                    }
                                                                    $retstr = $newstr;
                                                                                $len = min($chars_uLeft * 2, $limitpos - $spos); // min($chars_uLeft, $conlength);
                                                                    $retstr .= substr($this->data, $spos, $len);
                                                                    $chars_uLeft -= $len/2;
                                                                    $ascii_uEncoding = false;
                                                                        //echo "Izavrat\n";
                                                                  }
                                                          $spos += $len;

                                                         }
                                                }
                                                $retstr =($ascii_uEncoding) ? $retstr : $this->_encode_uUTF16($retstr);
//                                              echo "Str $i = $retstr\n";
                                        if ($rich_uString) {
                                                  $spos += 4 * $formatting_uRuns;
                                                }

                                                // For extended strings, skip over the extended string data
                                                if ($extended_uString) {
                                                  $spos += $extended_uRun_uLength;
                                                }
                                                        //if ($retstr == 'Derby') {
                                                        //      echo "bb\n";
                                                        //}
                                                $this->sst[]=$retstr;
                                       }
                    /*$continue_uRecords = array();
                    while ($this->get_uNext_uCode() == Type_CONTINUE) {
                        $continue_uRecords[] = &$this->next_uRecord();
                    }
                    //echo " 1 Type_SST\n";
                    $this->share_uStrings = new SSTRecord($r, $continue_uRecords);
                    //print_r($this->share_uStrings->strings);
                     */
                     // echo 'SST read: '.($time_end-$time_start)."\n";
                    break;

                case SPREADSHEET_EXCEL_READER_TYPE_FILEPASS:
                    return false;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NAME:
                    //echo "Type_NAME\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMAT:
                        $index_uCode = ord($this->data[$pos+4]) | ord($this->data[$pos+5]) << 8;

                        if ($version == SPREADSHEET_EXCEL_READER_BIFF8) {
                            $numchars = ord($this->data[$pos+6]) | ord($this->data[$pos+7]) << 8;
                            if (ord($this->data[$pos+8]) == 0) {
                                $format_uString = substr($this->data, $pos+9, $numchars);
                            } else {
                                $format_uString = substr($this->data, $pos+9, $numchars*2);
                            }
                        } else {
                            $numchars = ord($this->data[$pos+6]);
                            $format_uString = substr($this->data, $pos+7, $numchars*2);
                        }

                    $this->format_uRecords[$index_uCode] = $format_uString;
                   // echo "Type.FORMAT\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_XF:
                        //global $date_uFormats, $number_uFormats;
                        $index_uCode = ord($this->data[$pos+6]) | ord($this->data[$pos+7]) << 8;
                        //echo "\n_uType.XF ".count($this->format_uRecords['xfrecords'])." $index_uCode ";
                        if (array_key_exists($index_uCode, $this->date_uFormats)) {
                            //echo "isdate ".$date_uFormats[$index_uCode];
                            $this->format_uRecords['xfrecords'][] = array(
                                    'type' => 'date' , 
                                    'format' => $this->date_uFormats[$index_uCode]
                                    );
                        }elseif (array_key_exists($index_uCode, $this->number_uFormats)) {
                        //echo "isnumber ".$this->number_uFormats[$index_uCode];
                            $this->format_uRecords['xfrecords'][] = array(
                                    'type' => 'number' , 
                                    'format' => $this->number_uFormats[$index_uCode]
                                    );
                        }else{
                            $isdate = FALSE;
                            if ($index_uCode > 0) {
                                if (isset($this->format_uRecords[$index_uCode]))
                                    $formatstr = $this->format_uRecords[$index_uCode];
                                //echo '.other.';
                                //echo "\ndate-time=$formatstr=\n";
                                if ($formatstr)
                                if (preg_match("/[^hmsday\/\-:\s]/i", $formatstr) == 0) { // found day and time format
                                    $isdate = TRUE;
                                    $formatstr = str_replace('mm', 'i', $formatstr);
                                    $formatstr = str_replace('h', 'H', $formatstr);
                                    //echo "\ndate-time $formatstr \n";
                                }
                            }

                            if ($isdate) {
                                $this->format_uRecords['xfrecords'][] = array(
                                        'type' => 'date' , 
                                        'format' => $formatstr , 
                                        );
                            }else{
                                $this->format_uRecords['xfrecords'][] = array(
                                        'type' => 'other' , 
                                        'format' => '' , 
                                        'code' => $index_uCode
                                        );
                            }
                        }
                        //echo "\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR:
                    //echo "Type.NINETEENFOUR\n";
                    $this->nineteen_uFour =(ord($this->data[$pos+4]) == 1);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET:
                    //echo "Type.BOUNDSHEET\n";
                        $rec_offset = $this->_Get_uInt4d($this->data, $pos+4);
                        $rec_type_uFlag = ord($this->data[$pos+8]);
                        $rec_visibility_uFlag = ord($this->data[$pos+9]);
                        $rec_length = ord($this->data[$pos+10]);

                        if ($version == SPREADSHEET_EXCEL_READER_BIFF8) {
                            $chartype =  ord($this->data[$pos+11]);
                            if ($chartype == 0) {
                                $rec_name    = substr($this->data, $pos+12, $rec_length);
                            } else {
                                $rec_name    = $this->_encode_uUTF16(substr($this->data, $pos+12, $rec_length*2));
                            }
                        }elseif ($version == SPREADSHEET_EXCEL_READER_BIFF7) {
                                $rec_name    = substr($this->data, $pos+11, $rec_length);
                        }
                    $this->boundsheets[] = array('name'=>$rec_name , 
                                                 'offset'=>$rec_offset);

                    break;

            }

            //echo "Code = ".base_convert($r['code'] , 10 , 16)."\n";
            $pos += $length + 4;
            $code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
            $length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

            //$r = &$this->next_uRecord();
            //echo "1 Code = ".base_convert($r['code'] , 10 , 16)."\n";
        }

        foreach ($this->boundsheets as $key=>$val) {
            $this->sn = $key;
            $this->_parsesheet($val['offset']);
        }
        return true;

    }

    /**
     * Parse a worksheet
     *
     * @access private
     * @param todo
     * @todo fix return codes
     */
    function _parsesheet ($spos)
    {
        $cont = true;
        // read BOF
        $code = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
        $length = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;

        $version = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5])<<8;
        $substream_uType = ord($this->data[$spos + 6]) | ord($this->data[$spos + 7])<<8;

        if (($version != SPREADSHEET_EXCEL_READER_BIFF8) &&($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
            return -1;
        }

        if ($substream_uType != SPREADSHEET_EXCEL_READER_WORKSHEET) {
            return -2;
        }
        //echo "Start parse code=".base_convert($code , 10 , 16)." version=".base_convert($version , 10 , 16)." substream_uType=".base_convert($substream_uType , 10 , 16).""."\n";
        $spos += $length + 4;
        //var_dump($this->format_uRecords);
    //echo "code $code $length";
        while ($cont) {
            //echo "mem= ".memory_get_usage()."\n";
//            $r = &$this->file->next_uRecord();
            $lowcode = ord($this->data[$spos]);
            if ($lowcode == SPREADSHEET_EXCEL_READER_TYPE_EOF) break;
            $code = $lowcode | ord($this->data[$spos+1])<<8;
            $length = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
            $spos += 4;
            $this->sheets[$this->sn]['maxrow'] = $this->_rowoffset - 1;
            $this->sheets[$this->sn]['maxcol'] = $this->_coloffset - 1;
            //echo "Code=".base_convert($code , 10 , 16)." $code\n";
            unset($this->rectype);
            $this->multiplier = 1; // need for format with %
            switch ($code) {
                case SPREADSHEET_EXCEL_READER_TYPE_DIMENSION:
                    //echo 'Type_DIMENSION ';
                    if ( ! isset($this->num_uRows)) {
                        if (($length == 10) || ($version == SPREADSHEET_EXCEL_READER_BIFF7)) {
                            $this->sheets[$this->sn]['num_uRows'] = ord($this->data[$spos+2]) | ord($this->data[$spos+3]) << 8;
                            $this->sheets[$this->sn]['num_uCols'] = ord($this->data[$spos+6]) | ord($this->data[$spos+7]) << 8;
                        } else {
                            $this->sheets[$this->sn]['num_uRows'] = ord($this->data[$spos+4]) | ord($this->data[$spos+5]) << 8;
                            $this->sheets[$this->sn]['num_uCols'] = ord($this->data[$spos+10]) | ord($this->data[$spos+11]) << 8;
                        }
                    }
                    //echo 'num_uRows '.$this->num_uRows.' '.$this->num_uCols."\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS:
                    $cell_uRanges = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    for ($i = 0; $i < $cell_uRanges; $i++) {
                        $fr =  ord($this->data[$spos + 8*$i + 2]) | ord($this->data[$spos + 8*$i + 3])<<8;
                        $lr =  ord($this->data[$spos + 8*$i + 4]) | ord($this->data[$spos + 8*$i + 5])<<8;
                        $fc =  ord($this->data[$spos + 8*$i + 6]) | ord($this->data[$spos + 8*$i + 7])<<8;
                        $lc =  ord($this->data[$spos + 8*$i + 8]) | ord($this->data[$spos + 8*$i + 9])<<8;
                        //$this->sheets[$this->sn]['merged_uCells'][] = array($fr + 1, $fc + 1, $lr + 1, $lc + 1);
                        if ($lr - $fr > 0) {
                            $this->sheets[$this->sn]['cells_uInfo'][$fr+1][$fc+1]['rowspan'] = $lr - $fr + 1;
                        }
                        if ($lc - $fc > 0) {
                            $this->sheets[$this->sn]['cells_uInfo'][$fr+1][$fc+1]['colspan'] = $lc - $fc + 1;
                        }
                    }
                    //echo "Merged Cells $cell_uRanges $lr $fr $lc $fc\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_RK:
                case SPREADSHEET_EXCEL_READER_TYPE_RK2:
                    //echo 'SPREADSHEET_EXCEL_READER_TYPE_RK'."\n";
                    $row = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $rknum = $this->_Get_uInt4d($this->data, $spos + 6);
                    $num_uValue = $this->_Get_uIEEE754($rknum);
                    //echo $num_uValue." ";
                    if ($this->is_uDate($spos)) {
                        list($string, $raw) = $this->create_uDate($num_uValue);
                    }else{
                        $raw = $num_uValue;
                        if (isset($this->_columns_uFormat[$column + 1])) {
                                $this->curformat = $this->_columns_uFormat[$column + 1];
                        }
                        $string = sprintf($this->curformat, $num_uValue * $this->multiplier);
                        //$this->addcell(RKRecord($r));
                    }
                    $this->addcell($row, $column, $string, $raw);
                    //echo "Type_RK $row $column $string $raw {$this->curformat}\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABELSST:
                        $row        = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                        $column     = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                        $xfindex    = ord($this->data[$spos+4]) | ord($this->data[$spos+5])<<8;
                        $index  = $this->_Get_uInt4d($this->data, $spos + 6);
            //var_dump($this->sst);
                        $this->addcell($row, $column, $this->sst[$index]);
                        //echo "Label_uSST $row $column $string\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MULRK:
                    $row        = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $col_uFirst   = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $col_uLast    = ord($this->data[$spos + $length - 2]) | ord($this->data[$spos + $length - 1])<<8;
                    $columns    = $col_uLast - $col_uFirst + 1;
                    $tmppos = $spos+4;
                    for ($i = 0; $i < $columns; $i++) {
                        $num_uValue = $this->_Get_uIEEE754($this->_Get_uInt4d($this->data, $tmppos + 2));
                        if ($this->is_uDate($tmppos-4)) {
                            list($string, $raw) = $this->create_uDate($num_uValue);
                        }else{
                            $raw = $num_uValue;
                            if (isset($this->_columns_uFormat[$col_uFirst + $i + 1])) {
                                        $this->curformat = $this->_columns_uFormat[$col_uFirst + $i + 1];
                                }
                            $string = sprintf($this->curformat, $num_uValue * $this->multiplier);
                        }
                      //$rec['rknumbers'][$i]['xfindex'] = ord($rec['data'][$pos]) | ord($rec['data'][$pos+1]) << 8;
                      $tmppos += 6;
                      $this->addcell($row, $col_uFirst + $i, $string, $raw);
                      //echo "MULRK $row ".($col_uFirst + $i)." $string\n";
                    }
                     //Mul_uRKRecord($r);
                    // Get the individual cell records from the multiple record
                     //$num = ;

                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NUMBER:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $tmp = unpack("ddouble", substr($this->data, $spos + 6, 8)); // It machine machine dependent
                    if ($this->is_uDate($spos)) {
                        list($string, $raw) = $this->create_uDate($tmp['double']);
                     //   $this->addcell(Date_uRecord($r, 1));
                    }else{
                        //$raw = $tmp[''];
                        if (isset($this->_columns_uFormat[$column + 1])) {
                                $this->curformat = $this->_columns_uFormat[$column + 1];
                        }
                        $raw = $this->create_uNumber($spos);
                        $string = sprintf($this->curformat, $raw * $this->multiplier);

                     //   $this->addcell(Number_uRecord($r));
                    }
                    $this->addcell($row, $column, $string, $raw);
                    //echo "Number $row $column $string\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA:
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA2:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    if ((ord($this->data[$spos+6])==0) &&(ord($this->data[$spos+12])==255) &&(ord($this->data[$spos+13])==255)) {
                        //String formula. Result follows in a STRING record
                        //echo "FORMULA $row $column Formula with a string<br>\n";
                    } elseif ((ord($this->data[$spos+6])==1) &&(ord($this->data[$spos+12])==255) &&(ord($this->data[$spos+13])==255)) {
                        //Boolean formula. Result is in +2; 0=false , 1=true
                    } elseif ((ord($this->data[$spos+6])==2) &&(ord($this->data[$spos+12])==255) &&(ord($this->data[$spos+13])==255)) {
                        //Error formula. Error code is in +2;
                    } elseif ((ord($this->data[$spos+6])==3) &&(ord($this->data[$spos+12])==255) &&(ord($this->data[$spos+13])==255)) {
                        //Formula result is a null string.
                    } else {
                        // result is a number, so first 14 bytes are just like a _NUMBER record
                        $tmp = unpack("ddouble", substr($this->data, $spos + 6, 8)); // It machine machine dependent
                        if ($this->is_uDate($spos)) {
                            list($string, $raw) = $this->create_uDate($tmp['double']);
                         //   $this->addcell(Date_uRecord($r, 1));
                        }else{
                            //$raw = $tmp[''];
                            if (isset($this->_columns_uFormat[$column + 1])) {
                                    $this->curformat = $this->_columns_uFormat[$column + 1];
                            }
                            $raw = $this->create_uNumber($spos);
                            $string = sprintf($this->curformat, $raw * $this->multiplier);

                         //   $this->addcell(Number_uRecord($r));
                        }
                        $this->addcell($row, $column, $string, $raw);
                        //echo "Number $row $column $string\n";
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOOLERR:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $string = ord($this->data[$spos+6]);
                    $this->addcell($row, $column, $string);
                    //echo 'Type_BOOLERR '."\n";
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_ROW:
                case SPREADSHEET_EXCEL_READER_TYPE_DBCELL:
                case SPREADSHEET_EXCEL_READER_TYPE_MULBLANK:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABEL:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $this->addcell($row, $column, substr($this->data, $spos + 8, ord($this->data[$spos + 6]) | ord($this->data[$spos + 7])<<8));

                   // $this->addcell(Label_uRecord($r));
                    break;

                case SPREADSHEET_EXCEL_READER_TYPE_EOF:
                    $cont = false;
                    break;
                default:
                    //echo ' unknown :'.base_convert($r['code'] , 10 , 16)."\n";
                    break;

            }
            $spos += $length;
        }

        if ( ! isset($this->sheets[$this->sn]['num_uRows']))
             $this->sheets[$this->sn]['num_uRows'] = $this->sheets[$this->sn]['maxrow'];
        if ( ! isset($this->sheets[$this->sn]['num_uCols']))
             $this->sheets[$this->sn]['num_uCols'] = $this->sheets[$this->sn]['maxcol'];

    }

    /**
     * Check whether the current record read is a date
     *
     * @param todo
     * @return boolean True if date, false otherwise
     */
    function is_uDate ($spos)
    {
        //$xfindex = Get_uInt2d(, 4);
        $xfindex = ord($this->data[$spos+4]) | ord($this->data[$spos+5]) << 8;
        //echo 'check is date '.$xfindex.' '.$this->format_uRecords['xfrecords'][$xfindex]['type']."\n";
        //var_dump($this->format_uRecords['xfrecords'][$xfindex]);
        if ($this->format_uRecords['xfrecords'][$xfindex]['type'] == 'date') {
            $this->curformat = $this->format_uRecords['xfrecords'][$xfindex]['format'];
            $this->rectype = 'date';
            return true;
        } else {
            if ($this->format_uRecords['xfrecords'][$xfindex]['type'] == 'number') {
                $this->curformat = $this->format_uRecords['xfrecords'][$xfindex]['format'];
                $this->rectype = 'number';
                if (($xfindex == 0x9) ||($xfindex == 0xa)) {
                    $this->multiplier = 100;
                }
            }else{
                $this->curformat = $this->_default_uFormat;
                $this->rectype = 'unknown';
            }
            return false;
        }
    }

    //}}}
    //{{{ create_uDate()

    /**
     * Convert the raw Excel date into a human readable format
     *
     * Dates in Excel are stored as number of seconds from an epoch.  On 
     * Windows, the epoch is 30/12/1899 and on Mac it's 01/01/1904
     *
     * @access private
     * @param integer The raw Excel value to convert
     * @return array First element is the converted date, the second element is number a unix timestamp
     */ 
    function create_uDate ($num_uValue)
    {
        if ($num_uValue > 1) {
            $utc_uDays = $num_uValue -($this->nineteen_uFour ? SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904 : SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS);
            $utc_uValue = round(($utc_uDays+1) * SPREADSHEET_EXCEL_READER_MSINADAY);
            $string = date($this->curformat, $utc_uValue);
            $raw = $utc_uValue;
        } else {
            $raw = $num_uValue;
            $hours = floor($num_uValue * 24);
            $mins = floor($num_uValue * 24 * 60) - $hours * 60;
            $secs = floor($num_uValue * SPREADSHEET_EXCEL_READER_MSINADAY) - $hours * 60 * 60 - $mins * 60;
            $string = date($this->curformat, mktime($hours, $mins, $secs));
        }

        return array($string, $raw);
    }

    function create_uNumber ($spos)
    {
        $rknumhigh = $this->_Get_uInt4d($this->data, $spos + 10);
        $rknumlow = $this->_Get_uInt4d($this->data, $spos + 6);
        //for ($i=0; $i<8; $i++) { echo ord($this->data[$i+$spos+6]) . " "; } echo "<br>";
        $sign =($rknumhigh & 0x80000000) >> 31;
        $exp = ($rknumhigh & 0x7ff00000) >> 20;
        $mantissa =(0x100000 |($rknumhigh & 0x000fffff));
        $mantissalow1 =($rknumlow & 0x80000000) >> 31;
        $mantissalow2 =($rknumlow & 0x7fffffff);
        $value = $mantissa / pow(2 ,(20-($exp - 1023)));
        if ($mantissalow1 != 0) $value += 1 / pow(2 ,(21 -($exp - 1023)));
        $value += $mantissalow2 / pow(2 ,(52 -($exp - 1023)));
        //echo "Sign = $sign, Exp = $exp, mantissahighx = $mantissa, mantissalow1 = $mantissalow1, mantissalow2 = $mantissalow2<br>\n";
        if ($sign) {$value = -1 * $value;}
        return  $value;
    }

    function addcell ($row, $col, $string, $raw = '')
    {
        //echo "ADD cel $row-$col $string\n";
        $this->sheets[$this->sn]['maxrow'] = max($this->sheets[$this->sn]['maxrow'], $row + $this->_rowoffset);
        $this->sheets[$this->sn]['maxcol'] = max($this->sheets[$this->sn]['maxcol'], $col + $this->_coloffset);
        $this->sheets[$this->sn]['cells'][$row + $this->_rowoffset][$col + $this->_coloffset] = $string;
        if ($raw)
            $this->sheets[$this->sn]['cells_uInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['raw'] = $raw;
        if (isset($this->rectype))
            $this->sheets[$this->sn]['cells_uInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['type'] = $this->rectype;

    }


    function _Get_uIEEE754 ($rknum)
    {
        if (($rknum & 0x02) != 0) {
                $value = $rknum >> 2;
        } else {
//mmp
// first comment out the previously existing 7 lines of code here
//                $tmp = unpack("d", pack("VV", 0,($rknum & 0xfffffffc)));
//                //$value = $tmp[''];
//                if (array_key_exists(1, $tmp)) {
//                    $value = $tmp[1];
//                } else {
//                    $value = $tmp[''];
//                }
// I got my info on IEEE754 encoding from
// http://research.microsoft.com/~hollasch/cgindex/coding/ieeefloat.html
// The RK format calls for using only the most significant 30 bits of the
// 64 bit floating point value. The other 34 bits are assumed to be 0
// So, we use the upper 30 bits of $rknum as follows...
         $sign =($rknum & 0x80000000) >> 31;
        $exp =($rknum & 0x7ff00000) >> 20;
        $mantissa =(0x100000 |($rknum & 0x000ffffc));
        $value = $mantissa / pow(2 ,(20-($exp - 1023)));
        if ($sign) {$value = -1 * $value;}
//end of changes by mmp

        }

        if (($rknum & 0x01) != 0) {
            $value /= 100;
        }
        return $value;
    }

    function _encode_uUTF16 ($string)
    {
        $result = $string;
        if ($this->_default_uEncoding) {
            switch ($this->_encoder_uFunction) {
                case 'iconv' :     $result = iconv('UTF-16LE', $this->_default_uEncoding, $string);
                                break;
                case 'mb_convert_encoding' :     $result = mb_convert_encoding($string, $this->_default_uEncoding, 'UTF-16LE' );
                                break;
            }
        }
        return $result;
    }

    function _Get_uInt4d ($data, $pos)
    {
        $value = ord($data[$pos]) |(ord($data[$pos+1]) << 8) |(ord($data[$pos+2]) << 16) |(ord($data[$pos+3]) << 24);
        if ($value>=4294967294)
        {
            $value=-2;
        }
        return $value;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
