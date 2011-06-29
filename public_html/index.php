<?php
/*!
 * @file
 * index.php
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @license GPLv3 (see http://www.gnu.org/licenses/gpl-3.0.txt)
 * @created 2011-05-26
 */

require "../header.php";

if ( !($core = Core::initialize()) ) {
  die("Failed to initialize OS.js Core");
}

if ( !ENABLE_CACHE ) {
  header("Expires: Fri, 01 Jan 2010 05:00:00 GMT");
  header("Cache-Control: maxage=1");
  header("Cache-Control: no-cache");
  header("Pragma: no-cache");
}

///////////////////////////////////////////////////////////////////////////////
// FONT STYLESHEET
///////////////////////////////////////////////////////////////////////////////

if ( isset($_GET['font']) && !empty($_GET['font']) ) {

  header("Content-Type: text/css; charset=utf-8");
  $font   = $_GET['font'];
  $italic = $_GET['font'] == "FreeSerif" ? "Italic" : "Oblique";
  $bos    = $_GET['font'] == "Sansation" ? "/*" : "";
  $boe    = $_GET['font'] == "Sansation" ? "*/" : "";

  $template = <<<EOCSS
@charset "UTF-8";
/**
 * Font Stylesheet
 *
 * @package OSjs.Styles
 * @author Anders Evenrud <andersevenrud@gmail.com>
 */

@font-face {
  font-family : CustomFont;
  src: url('/media/System/Fonts/%1\$s.ttf');
}
@font-face {
  font-family : CustomFont;
  font-weight : bold;
  src: url('/media/System/Fonts/%1\$sBold.ttf');
}
@font-face {
  font-family : CustomFont;
  font-style : italic;
  src: url('/media/System/Fonts/%1\$s{$italic}.ttf');
}
{$bos}
@font-face {
  font-family : CustomFont;
  font-weight : bold;
  font-style : italic;
  src: url('/media/System/Fonts/%1\$sBold{$italic}.ttf');
}
{$boe}

body {
  font-family : CustomFont, Arial;
}
EOCSS;

  print sprintf($template, addslashes($font));
  exit;
}

///////////////////////////////////////////////////////////////////////////////
// RESOURCE LOADING
///////////////////////////////////////////////////////////////////////////////

if ( isset($_GET['resource']) && !empty($_GET['resource']) ) {
  $res  = addslashes($_GET['resource']);
  $type = preg_match("/\.js$/", $res) ? "js" : "css";
  $path = sprintf("%s/resources/%s", PATH_RESOURCES, $res); // FIXME

  if ( file_exists($path) ) {
    //header(sprintf("Content-Type: text/%s; charset=utf-8", $type == "js" ? "javascript" : "css"));
    header(sprintf("Content-Type: %s; charset=utf-8", $type == "js" ? "application/x-javascript" : "text/css"));
    print file_get_contents($path);
    exit;
  }

  header("HTTP/1.0 404 Not Found");
  exit;
}

///////////////////////////////////////////////////////////////////////////////
// AJAX
///////////////////////////////////////////////////////////////////////////////

if ( !($json = $core->doGET($_GET)) === false ) {
  header("Content-Type: application/json");
  die($json);
}
if ( !($json = $core->doPOST($_POST)) === false ) {
  header("Content-Type: application/json");
  die($json);
}


///////////////////////////////////////////////////////////////////////////////
// HTML
///////////////////////////////////////////////////////////////////////////////

header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!--
  JavaScript Window Manager

  @package OSjs.Template
  @author  Anders Evenrud <andersevenrud@gmail.com>
  -->
  <title>OS.js</title>

  <!-- Compability -->
<!--
  <meta http-equiv="X-UA-Compatible" content="IE=9" />
  <meta http-equiv="Content-Style-Type" content="text/css; charset=utf-8" />
-->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

  <!-- Vendor libraries -->
  <link rel="stylesheet" type="text/css" href="/css/vendor/ui-lightness/jquery-ui-1.8.13.custom.css" />

  <script type="text/javascript" src="/js/vendor/json2.js"></script>
  <script type="text/javascript" src="/js/vendor/sprintf-0.7-beta1.js"></script>
  <script type="text/javascript" src="/js/vendor/fileuploader.js"></script>
  <script type="text/javascript" src="/js/vendor/jquery-1.5.2.min.js"></script>
  <script type="text/javascript" src="/js/vendor/jquery-ui-1.8.13.custom.min.js"></script>
  <script type="text/javascript" src="/js/vendor/jquery.touch.compact.js"></script>

  <!-- Main resources -->
  <link rel="stylesheet" type="text/css" href="/css/main.css" />
  <link rel="stylesheet" type="text/css" href="/css/glade.css" />
  <link rel="stylesheet" type="text/css" href="/css/pimp.css" />
<!--[if lt IE 9]>
<link rel="stylesheet" type="text/css" href="/css/ie.css" />
<![endif]-->

  <script type="text/javascript" src="/js/utils.js"></script>
  <script type="text/javascript" src="/js/main.js"></script>

  <!-- OS.js defineable -->
  <link rel="stylesheet" type="text/css" href="/?font=Sansation" id="FontFace" />
  <link rel="stylesheet" type="text/css" href="/css/theme.default.css" id="ThemeBase" />
  <link rel="stylesheet" type="text/css" href="/css/theme.dark.css" id="ThemeFace" />
  <link rel="stylesheet" type="text/css" href="/css/cursor.default.css" id="CursorFace" />

  <!-- Preloads -->
  <script type="text/javascript" src="?resource=dialog.panel.js"></script>
  <script type="text/javascript" src="?resource=dialog.launch.js"></script>
  <script type="text/javascript" src="?resource=dialog.file.js"></script>
  <script type="text/javascript" src="?resource=dialog.upload.js"></script>
  <script type="text/javascript" src="?resource=dialog.rename.js"></script>
  <script type="text/javascript" src="?resource=dialog.copy.js"></script>
  <script type="text/javascript" src="?resource=dialog.color.js"></script>
</head>
<body>

<div id="Desktop">
  <!-- Panel -->
  <div class="DesktopPanel AlignTop" id="Panel"><ul></ul></div>

  <div id="ContextMenu">&nbsp;</div>

  <div id="ContextRectangle">&nbsp;</div>

  <div id="Tooltip">&nbsp;</div>

  <!-- Content -->
</div>

<!-- Loading -->
<div id="Loading">
  <div id="LoadingBar"></div>
</div>

<!-- Templates -->
<div id="LoginWindow" class="Window" style="display:none">
  <div class="WindowContent">
    <div class="WindowContentInner">
      <form method="post" action="/">
        <div class="Row">
          <label for="LoginUsername">Username</label>
          <input type="text" id="LoginUsername" value="Administrator" name="username" />
        </div>
        <div class="Row">
          <label for="LoginPassword">Password</label>
          <input type="password" id="LoginPassword" value="Administrator" name="password" />
        </div>
        <div class="Buttons">
          <input type="submit" value="Log in" />
        </div>
      </form>
    </div>
  </div>
</div>

<div id="Window" style="display:none">
  <div class="Window">
    <div class="WindowTop">
      <div class="WindowTopInner">
        <img alt="" src="/img/icons/16x16/emblems/emblem-unreadable.png" />
        <span></span>
      </div>
      <div class="WindowTopControllers">
        <div class="WindowTopController">
          <div class="ActionMinimize">&nbsp;</div>
        </div>
        <div class="WindowTopController">
          <div class="ActionMaximize">&nbsp;</div>
        </div>
        <div class="WindowTopController">
          <div class="ActionClose">&nbsp;</div>
        </div>
      </div>
    </div>
    <div class="WindowContent">
      <div class="WindowContentInner">
      </div>
    </div>
  </div>
</div>

<div id="Dialog" style="display:none">
  <div class="Window Dialog">
    <div class="WindowTop">
      <div class="WindowTopInner">
        <img alt="" src="/img/blank.gif" />
        <span></span>
      </div>
      <div class="WindowTopControllers">
        <div class="WindowTopController ActionClose">
          <span>x</span>
        </div>
      </div>
    </div>
    <div class="WindowContent">
      <div class="WindowContentInner">
        <div class="DialogContent">
          This is a dialog!
        </div>
        <div class="DialogButtons">
          <button class="Choose" style="display:none;">Choose</button>
          <button class="Ok" style="display:none;">Ok</button>
          <button class="Close">Close</button>
          <button class="Cancel" style="display:none;">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="OperationDialogColor" style="display:none">
  <div class="OperationDialog OperationDialogColor">
    <div class="OperationDialogInner">
      <div>
        <div class="Slider SliderR"></div>
      </div>
      <div>
        <div class="Slider SliderG"></div>
      </div>
      <div>
        <div class="Slider SliderB"></div>
      </div>
    </div>
    <div class="CurrentColor">
    </div>
    <div class="CurrentColorDesc">
    </div>
  </div>
</div>

<div id="OperationDialogCopy" style="display:none">
  <div class="OperationDialog OperationDialogCopy">
    <h1>Copy file</h1>
    <div class="OperationDialogInner">
      <p class="Status">0 of 0</p>
      <div class="ProgressBar"></div>
    </div>
  </div>
</div>

<div id="OperationDialogRename" style="display:none">
  <div class="OperationDialog OperationDialogRename">
    <h1>Rename file</h1>
    <div class="OperationDialogInner">
      <input type="text" />
    </div>
  </div>
</div>

<div id="OperationDialogUpload" style="display:none">
  <div class="OperationDialog OperationDialogUpload">
    <h1>Upload file...</h1>
    <div class="OperationDialogInner">
      <p class="Status">No file selected</p>
      <div class="ProgressBar"></div>
    </div>
  </div>
</div>

<div id="OperationDialogFile" style="display:none">
  <div class="OperationDialog OperationDialogFile">
    <div class="FileChooser">
      <ul>
      </ul>
    </div>
    <div class="FileChooserInput">
      <input type="text" />
    </div>
  </div>
</div>

<div id="OperationDialogLaunch" style="display:none">
  <div class="OperationDialog OperationDialogLaunch">
    <div class="OperationDialogInner">
      <ul>
      </ul>
      <div>
        <input type="checkbox" disabled="disabled" /> <label>Use this as default application</label>
      </div>
    </div>
  </div>
</div>

<div id="OperationDialogPanelItem" style="display:none">
  <div class="OperationDialog OperationDialogPanelItem">
    <div class="OperationDialogInner">
    </div>
  </div>
</div>

<div id="OperationDialogPanelItemAdd" style="display:none">
  <div class="OperationDialog OperationDialogPanelItemAdd">
    <div class="OperationDialogInner Wrapper">
      <ul>
      </ul>
    </div>
  </div>
</div>

<!-- Version Stamp -->
<div id="Version">
  OS.js Version <?php print PROJECT_VERSION; ?>
  -
  <?php print PROJECT_AUTHOR; ?>

  &lt;<?php print PROJECT_CONTACT; ?>&gt;

  |

  <a href="/help.php" target="_blank">help</a>
</div>

</body>
</html>
