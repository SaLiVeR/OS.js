<?php
/*!
 * @file
 * OS.js - JavaScript Operating System - Contains Package Class
 *
 * Copyright (c) 2011, Anders Evenrud
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer. 
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution. 
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @licence Simplified BSD License
 * @created 2012-02-18
 */

/**
 * Package -- Package Class
 *
 * @author  Anders Evenrud <andersevenrud@gmail.com>
 * @package OSjs.Sources
 * @class
 */
abstract class Package
  extends CoreObject
{
  const TYPE_APPLICATION  = 1;
  const TYPE_PANELITEM    = 2;

  /////////////////////////////////////////////////////////////////////////////
  // VARIABLES
  /////////////////////////////////////////////////////////////////////////////

  private $_iType = -1;               //!< Package Type Identifier

  /**
   * @var Package Registry
   */
  public static $PackageRegister = Array(
    self::TYPE_APPLICATION  => Array(),
    self::TYPE_PANELITEM    => Array()
  );

  protected static $_LoadedApplications = false;    //!< Loading lock
  protected static $_LoadedPanelItems   = false;    //!< Loading lock

  /////////////////////////////////////////////////////////////////////////////
  // MAGICS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * @constructor
   */
  protected function __construct($type) {
    $this->_iType = (int) $type;
  }

  /////////////////////////////////////////////////////////////////////////////
  // MANAGMENT - STATIC METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Create a new Zipped Package from Project path
   * @param  String     $project      Project absolute path
   * @param  String     $dst_path     Absolute destination path (Default = use internal)
   * @throws ExceptionPackage
   * @return bool
   */
  public static function CreatePackage($project, $dst_path) {
    $name = basename($project);
    $dest = sprintf("%s/%s.zip", $dst_path, $name);

    // Read all files from project directory
    $items = Array();
    if ( is_dir($project) && $handle = opendir($project)) {
      while (false !== ($file = readdir($handle))) {
        if ( substr($file, 0, 1) !== "." ) {
          if ( !is_dir("{$project}/{$file}") ) {
            $items[] = $file;
          }
        }
      }
    }

    if ( in_array("metadata.xml", $items) ) {
      $metadata  = sprintf("%s/%s", $project, "metadata.xml");
      $resources = Array("metadata.xml", "{$name}.class.php");

      if ( file_exists($metadata) && ($xml = file_get_contents($metadata)) ) {
        if ( $xml = new SimpleXmlElement($xml) ) {
          // Parse resources from Metadata
          if ( isset($xml['schema']) ) {
            $resources[] = (string) $xml['schema'];
          }
          if ( isset($xml->resource) ) {
            foreach ( $xml->resource as $r ) {
              $resources[] = (string) $r;
            }
          }

          // Select files to store in package
          $store = Array();
          foreach ( $resources as $r ) {
            if ( !in_array($r, $items) ) {
              throw new ExceptionPackage(ExceptionPackage::MISSING_FILE, Array($name, $r));
            }
            $store[$r] = file_get_contents(sprintf("%s/%s", $project, $r));
          }

          // Clean up
          unset($items);
          unset($resources);

          // Create a package
          $zip = new ZipArchive();
          if ( ($ret = $zip->open($dest, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) === true ) {
            foreach ( $store as $file => $content ) {
              $zip->addFromString($file, $content);
            }
            if  ( $zip->close() ) {
              return true;
            }
          } else {
            throw new ExceptionPackage(ExceptionPackage::FAILED_CREATE, Array($name, $dest, $ret));
          }
        } else {
          throw new ExceptionPackage(ExceptionPackage::INVALID_METADATA, Array($name));
        }
      } else {
        throw new ExceptionPackage(ExceptionPackage::INVALID_METADATA, Array($name));
      }
    } else {
      throw new ExceptionPackage(ExceptionPackage::MISSING_METADATA, Array($name));
    }

    return false;
  }

  /**
   * Extract a Zipped Package to project directory
   * @param  String   $package      Absolute package path (zip-file)
   * @param  String   $dst_path     Absolute destination path
   * @throws ExceptionPackage
   * @return bool
   */
  public static function ExtractPackage($package, $dst_path) {
    $name = str_replace(".zip", "", basename($package));
    $dest = sprintf("%s/%s", $dst_path, $name);

    // Check if source exists
    if ( !file_exists($package) ) {
      throw new ExceptionPackage(ExceptionPackage::PACKAGE_NOT_EXISTS, Array($package));
    }

    // Check if target exists
    if ( !is_dir($dst_path) && !is_dir($dest) ) {
      throw new ExceptionPackage(ExceptionPackage::INVALID_DESTINATION, Array($dest));
    }

    $zip = new ZipArchive();
    if ( ($ret = $zip->open($package)) === true ) {
      $resources  = Array("{$name}.class.php");
      $packaged   = Array();
      $invalid    = false;

      // Read archived file-names
      for ( $i = 0; $i < $zip->numFiles; $i++ ) {
        $packaged[] = $zip->getNameIndex($i);
      }

      // Read metadata resources
      if ( !in_array("metadata.xml", $packaged) ) {
        throw new ExceptionPackage(ExceptionPackage::MISSING_METADATA, Array($package));
      }

      $mread = false;
      if ( $stream = $zip->getStream("metadata.xml") ) {
        $data = "";
        while ( !feof($stream) ) {
          $data .= fread($stream, 2);
        }
        fclose($stream);

        if ( $data && ($xml = new SimpleXmlElement($data)) ) {
          // Parse resources from Metadata
          if ( isset($xml['schema']) ) {
            $resources[] = (string) $xml['schema'];
          }
          if ( isset($xml->resource) ) {
            foreach ( $xml->resource as $r ) {
              $resources[] = (string) $r;
            }
          }

          $mread = true;
        }
      }

      // Make sure metadata was read
      if ( !$mread ) {
        throw new ExceptionPackage(ExceptionPackage::INVALID_METADATA, Array($package));
      }

      // Check that all files are in the archive
      foreach ( $resources as $r ) {
        if ( !in_array($r, $packaged) ) {
          throw new ExceptionPackage(ExceptionPackage::MISSING_FILE, Array($name, $r));
          break;
        }
      }

      unset($resources);
      unset($packaged);

      // Create destination
      if ( !mkdir($dest) ) {
        throw new ExceptionPackage(ExceptionPackage::FAILED_CREATE_DEST, Array($dest));
      }

      // Extract
      $result = false;
      if ( $zip->extractTo($dest) ) {
        $result = true;
      }

      $zip->close();

      return $result;
    } else {
      throw new ExceptionPackage(ExceptionPackage::FAILED_OPEN, Array($name, $package, $ret));
    }

    return false;
  }

  /**
   * Loading Operation for Installing/Uninstalling a package
   * @return Mixed
   */
  protected static function _PackageOperationLoad($mixed, User $user, $system = false) {
    $base   = sprintf("%s/%s", PATH_PACKAGES, $mixed);
    $class  = get_called_class();

    $config = PACKAGE_BUILD;
    $doc    = new DomDocument();
    $cfg    = file_get_contents($config);
    if ( $doc->loadXML($cfg) 
        && ($xml = new SimpleXMLElement(file_get_contents("{$base}/metadata.xml"))) ) {
      return Array($doc, $xml);
    }
    return false;
  }


  /**
   * Uninstall Package
   * @see Package::_PackageOperationLoad()
   * @see Package::_PackageOperationSave()
   * @return Mixed
   */
  public static function Uninstall($package, User $user, $system = true) {
    $buildfile  = PACKAGE_BUILD;
    $class      = get_called_class();
    $base       = sprintf("%s/%s", PATH_PACKAGES, $package);
    $nodeName   = ($class == "Application" ? "application" : "panelitem");

    $met_xml = simplexml_load_file("{$base}/metadata.xml");
    $res_xml = simplexml_load_file($buildfile);

    $removed = false;
    foreach ( $res_xml as $n ) {
      if ( ((string) $n['class']) == $package ) {
        $dom = dom_import_simplexml($n);
        $dom->parentNode->removeChild($dom);
        $removed = true;
        break;
      }
    }

    if ( $removed ) {
      return file_put_contents($buildfile, $res_xml->asXml()) ? true : false;
    }

    return false;
  }

  /**
   * Install Package
   * @see Package::_PackageOperationLoad()
   * @see Package::_PackageOperationSave()
   * @return Mixed
   */
  public static function Install($package, User $user, $system = true) {
    $buildfile  = PACKAGE_BUILD;
    $class    = get_called_class();
    $base     = sprintf("%s/%s", PATH_PACKAGES, $package);
    $nodeName = ($class == "Application" ? "application" : "panelitem");

    $met_xml = simplexml_load_file("{$base}/metadata.xml");
    $res_xml = simplexml_load_file($buildfile);

    foreach ( $res_xml->$nodeName as $n ) {
      if ( $n['class'] == $package ) {
        return false;
      }
    }

    $tmp = new DomDocument("1.0");
    $sxe = $tmp->importNode(dom_import_simplexml($met_xml), true);
    $sxe = $tmp->appendChild($sxe);
    $node = $tmp->documentElement;
    $node->setAttribute("class", $package);

    $dom = new DomDocument("1.0");
    $sxe = $dom->importNode(dom_import_simplexml($res_xml), true);
    $sxe = $dom->appendChild($sxe);
    $dom->documentElement->appendChild($dom->importNode($node, true));

    return file_put_contents($buildfile, $dom->saveXML()) ? true : false;
  }

  /////////////////////////////////////////////////////////////////////////////
  // INSTANCES - STATIC METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Load A Package by name and type
   * @param  String   $name       Package name
   * @param  int      $type       Package type
   * @param  User     $u          User instance
   * @return void
   */
  public static function Load($name, $type = -1, User $u = null) {
    switch ( (int) $type ) {
      case self::TYPE_APPLICATION :
        if ( !isset(self::$PackageRegister[$type][$name]) ) {
          if ( $p = Application::LoadPackage($name) ) {
            self::$PackageRegister[$type][$name] = $p[$name];
          } else {
            throw new Exception("Cannot Load Application '{$name}'!");
          }
        }

        return self::$PackageRegister[$type][$name];
        break;

      case self::TYPE_PANELITEM :
        if ( !isset(self::$PackageRegister[$type][$name]) ) {
          if ( $p = PanelItem::LoadPackage($name) ) {
            self::$PackageRegister[$type][$name] = $p[$name];
          } else {
            throw new Exception("Cannot Load PanelItem '{$name}'!");
          }
        }

        return self::$PackageRegister[$type][$name];
        break;

      default :
        throw new Exception("Cannot Load '{$name}' of type '{$type}'!");
        break;
    }

    return null;
  }

  /**
   * Load All Packages by type
   * @param  int      $type       Package type
   * @param  User     $u          User instance
   * @return void
   */
  public static function LoadAll($type = -1, User $u = null) {
    $loaded = false;

    if ( ($type & self::TYPE_APPLICATION) ) {
      $loaded = true;
      if ( !self::$_LoadedApplications ) {
        if ( $p = Application::LoadPackage() ) {
          foreach ( $p as $k => $v ) {
            self::$PackageRegister[self::TYPE_APPLICATION][$k] = $v;
          }
        }
        ksort(self::$PackageRegister[self::TYPE_APPLICATION]);
        self::$_LoadedApplications = true;
      }
    }
    if ( ($type & self::TYPE_PANELITEM) ) {
      $loaded = true;
      if ( !self::$_LoadedPanelItems ) {
        if ( $p = PanelItem::LoadPackage() ) {
          foreach ( $p as $k => $v ) {
            self::$PackageRegister[self::TYPE_PANELITEM][$k] = $v;
          }
        }
        ksort(self::$PackageRegister[self::TYPE_PANELITEM]);
        self::$_LoadedPanelItems = true;
      }
    }

    if ( !$loaded ) {
      throw new Exception("Cannot LoadAll type '{$type}'");
    }
  }

  /**
   * Load (a) Package(s)
   * @param  String     $name     Package name (if any)
   * @return Mixed
   */
  public static function LoadPackage($name = null) {
    $config = PACKAGE_BUILD;

    if ( $xml = file_get_contents($config) ) {
      if ( $xml = new SimpleXmlElement($xml) ) {
        if ( $name === self::TYPE_APPLICATION ) {
          return $xml->application;
        } else if ( $name == self::TYPE_PANELITEM ) {
          return $xml->panelitem;
        }
        return $xml;
      }
    }

    return false;
  }

  /**
   * Get installed packages
   * @param  User     $user     User Reference
   * @return Array
   */
  public final static function GetInstalledPackages(User $user) {
    Package::LoadAll(Package::TYPE_APPLICATION | Package::TYPE_PANELITEM, $user);

    return Array(
      "Application" => Package::GetPackageMeta(Package::TYPE_APPLICATION),
      "PanelItem"   => Package::GetPackageMeta(Package::TYPE_PANELITEM)
    );
  }

  /**
   * Get Package Metadata
   * @param   int     $type     Package Type
   * @return Array
   */
  public static function GetPackageMeta($type) {
    $result = Array();
    if ( isset(Package::$PackageRegister[$type]) ) {
      $result = Package::$PackageRegister[$type];
    }
    return $result;
  }

  /////////////////////////////////////////////////////////////////////////////
  // EVENTS - STATIC METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Event performed by AJAX
   * @param  String     $action       Package Action
   * @param  Array      $args         Action Arguments
   * @see    Package::Handle
   * @return Mixed
   */
  public static function Event($action, Array $args) {
    return Array();
  }

  /**
   * Handle an Package event
   * @param  String       $action       Package Action
   * @param  Package      $instance     Package Instance
   * @return Mixed
   */
  public static function Handle($action, $instance) {
    return false;
  }

  /////////////////////////////////////////////////////////////////////////////
  // GETTERS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get Package Type
   * @return int
   */
  public final function getPackageType() {
    return $this->_iType;
  }

}

?>
